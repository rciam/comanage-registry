<?php
/**
 * COmanage Registry CO Homedir Provisioner Target Model
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry-plugin
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("CoProvisionerPluginTarget", "Model");
include_once("VomsClient.php");

/**
 * Class CoVomsProvisionerTarget
 */
class CoVomsProvisionerTarget extends CoProvisionerPluginTarget {
	// Define class name for cake
	public $name = "CoVomsProvisionerTarget";

	// Add behaviors
	public $actsAs = array('Containable');

	// Association rules from this model to other models
	public $belongsTo = array('CoProvisioningTarget');

	// Default display field for cake generated views
	public $displayField = "co_provisioning_target_id";

	// Validation rules for table elements
	public $validate = array(
		'server_url' => array(
			'rule' => array('custom', '/^(\w+).(\w+).(\w+)*/'),
			'required' => true,
			'allowEmpty' => false,
			'message' => 'Please enter a valid vo URL'
		),
		'co_provisioning_target_id' => array(
			'rule' => 'numeric',
			'required' => true,
			'message' => 'A CO Provisioning Target ID must be provided'
		),
		'entity_type' => array(
			'rule' => array('inList', array(VomsProvUnconfAttrEnum::co,
											VomsProvUnconfAttrEnum::cou,
											VomsProvUnconfAttrEnum::Group)),
			'required' => true,
			'message' => 'Default values is CO'
		)
	);

	/**
	* Provision for the specified CO Person.
	*
	* @since  COmanage Registry v0.8
	* @param  Array CO Provisioning Target data
	* @param  ProvisioningActionEnum Registry transaction type triggering provisioning
	* @param  Array Provisioning data, populated with ['CoPerson'] or ['CoGroup']
	* @return Boolean True on success
	* @throws RuntimeException
	*/

	public function provision($coProvisioningTargetData, $op, $provisioningData)
	{
		$this->log("provision op => ".$op, LOG_DEBUG);
		$dn = $ca = $cn = null;
		// Retrieve vo server data for the case of voms plugin
		$vo_name = trim($coProvisioningTargetData['CoVomsProvisionerTarget']['vo_name']);
		$server_url = trim($coProvisioningTargetData['CoVomsProvisionerTarget']['server_url']);
		$vo_targets_id = $coProvisioningTargetData['CoVomsProvisionerTarget']['id'];

		// Intantiate voms client object
		$vomscli = new VomsClient($server_url, $vo_name);
		switch ($op) {
			// the whole provisioning happens during provisioning, either we are talking
			// about CO or COU. At the end of petition, provisioning process takes place and
			// the user is part of
			case ProvisioningActionEnum::CoPersonPetitionProvisioned:
//				$this->log("target data => ".print_r($coProvisioningTargetData,true),LOG_DEBUG);
//				$this->log("provision data => ".print_r($provisioningData,true),LOG_DEBUG);
				// Provision for the case of any Petition finalization
				// The person  will be provisioned if s/he is part of vo group or
				// COU member
				if( $this->VoOnCOURoleAvailable($provisioningData, $vo_name) != -1
				|| $this->VoOnGroupMemberAvailable($provisioningData, $vo_name) != -1){
					// Check if the user is already provisioned, check VO array
				$co_person_id = $provisioningData['CoPerson']['id'];
					if($this->provisionExistCheck($co_person_id, $vo_targets_id)){
						$this->log("User already provisioned", LOG_DEBUG);
					} else {
						$this->log("time to subscribe!!!", LOG_DEBUG);
						$this->voProvision($co_person_id, $vomscli, $vo_targets_id);
					}
					break;
				}
			case ProvisioningActionEnum::CoGroupUpdated:
				break;
			case ProvisioningActionEnum::CoPersonUpdated :
//				$this->log("target data person => ".print_r($coProvisioningTargetData,true),LOG_DEBUG);
//				$this->log("provision data person => ".print_r($provisioningData,true),LOG_DEBUG);
				// Check if the user belongs to any group containing the VO of interest
				// If we retrieve no group then we should unsubscribe all user's instances in the VO
				// Each user instance is represented by different certificates
				$couVoMember = ( ($co_person_id =$this->VoOnCOURoleAvailable($provisioningData, $vo_name)) != -1 )  ? true : false;
				$groupVoMember = ( ($co_person_id =$this->VoOnGroupMemberAvailable($provisioningData, $vo_name)) != -1 ) ? true : false;
				if(!$couVoMember && !$groupVoMember){
					// Retrieve co_person_id
					$co_person_id = $provisioningData['CoPerson']['id'];
					if($this->provisionExistCheck($co_person_id, $vo_targets_id)) {
						// Unsubscribe user and remove reference from his profile
						$this->log("time to un subscribe!!!");
						$this->voUnSubscribe($co_person_id, $vomscli, $vo_targets_id);
					}
				}else{
					$this->log("User belongs in CO:COU:{$vo_name} group.");
				}
				break;
			case ProvisioningActionEnum::CoPersonReprovisionRequested:
				$this->log("Reprovision", LOG_DEBUG);
				break;
			default:
				// Log noop and fall through.
				$this->log("Voms provisioning action $op not allowed/implemented");
			}

		return true;
	}


	/**
	 * @param $id               co_person_id to search for certificate in cm_certs
	 * @return Generator        certificate attributes for a co person
	 */
	// Cert retrieve function will actually find out if we ran RCAuth. Since this is
	// the plugin that will fetch the subject DN we need
	/**
	 * @param co_person_id $id
	 * @return Generator
	 */
	private function cert_retrieve($id)
	{
		$query_str = "select ce.subject, ce.issuer, em.mail ".
			"from cm_certs as ce, cm_email_addresses as em ".
			"where ce.co_person_id={$id} and ce.co_person_id = em.co_person_id";
		// Regex
		$regex = '/^CN=(.*?)$/m';
		// Retrieve the model and run the query
		$this->Cert = ClassRegistry::init('Cert');
		$res = $this->Cert->query($query_str);
		// If multiple certs for a co_person_id are available, we should retrieve data for each one
		// then yield the result
		foreach($res as $cert){
			$cert_attr["dn"] = trim($cert[0]['subject']);
			$cert_attr["ca"] = trim($cert[0]['issuer']);
			$cert_attr["email"] = trim($cert[0]['mail']);
			$this->log("Provisioning user for dn: {$cert_attr["dn"]}.",LOG_DEBUG);
			$dn_parts = explode(",", $cert[0]['subject']);
			// if the subject dn is wrong just continue to the next one
			if(isset($dn_parts) && count($dn_parts) < 2) continue;
			foreach($dn_parts as $attribute){
				if (preg_match($regex, $attribute, $match)) {
					$cert_attr["cn"] = trim($attribute);
				}
			}
			// Yield the array back to the caller
			$this->log("array => ".print_r($cert_attr,true),LOG_DEBUG);

			yield $cert_attr;
		}
	}

	/**
	 * @param $group_id     the group id for which we are trying to provision
	 * @return Generator    certificate attributes for each co person belonging in the group
	 */
	private function cert_retrieve_GR($group_id){
		// Load the model
		$cert = array();
		$this->Cert = ClassRegistry::init('Cert');
		// At first find the co_person_id from the co_id
		$query_str = "select me.co_person_id ".
					"from cm_co_groups as gr, cm_co_group_members as me ".
					"where me.co_group_id = gr.id and gr.id = ".$group_id;
		$res = $this->Cert->query($query_str);

		foreach ($res as $co_person) {
			foreach ($this->cert_retrieve($co_person[0]['co_person_id']) as $co_cert){
				$cert[0] =  $co_cert;
			}
			yield $cert;
		}
	}


	/**
	 * @param $co_person_id    the co person we want to check
	 * @param $vo_targets_id   the vo we want to check
	 * @return bool            if true we must abort. co person has already been provisioned
	 */
	private function provisionExistCheck($co_person_id, $vo_targets_id){
		$query_str = "select * ".
					"from cm_vos ".
					"where co_person_id={$co_person_id} ".
					"and vo_targets_id={$vo_targets_id}";
		$this->log("query => ".$query_str,LOG_DEBUG);
		$this->Vo = ClassRegistry::init('Vo');
		$res = $this->Vo->query($query_str);
		$this->log("res => ".print_r($res,true),LOG_DEBUG);
		$this->log("isset => ".isset($res),LOG_DEBUG);
		$this->log("count => ".count($res),LOG_DEBUG);
		if(isset($res) && count($res) > 0){
			$this->log("res => ".print_r($res,true),LOG_DEBUG);
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param $vo_name      the vo_name we are getting provisioned for. Case of Group provisioning
	 * @return bool         if true we can continue with provisioning
	 */
	private function validateGroupVoNameProvisioning($vo_name){
		$query_str = "select name ".
			"from cm_co_groups ".
			"where name='{$vo_name}'";
		$this->CoGroup = ClassRegistry::init('CoGroup');
		$res = $this->CoGroup->query($query_str);
		if(isset($res[0])){
			$name = $res[0][0]['name'];
			$value = (strcmp($name, $vo_name) == 0) ? true : false;
			return $value;
		} else {
			return false;
		}
	}


	/**
	 * @param $vo_name      the vo_name we are getting provisioned for. Case of COU provisioning
	 * @return bool         if true we can continue with provisioning
	 */
	private function validateCOUVoNameProvisioning($vo_name, $cou_id){
		$query_str = "select name ".
			"from cm_cous ".
			"where name='{$vo_name}' and id={$cou_id}";
			//"where name='{$vo_name}' and id={$cou_id}";
		$this->Cou = ClassRegistry::init('Cou');
		$res = $this->Cou->query($query_str);
		if(isset($res[0])){
			$name = $res[0][0]['name'];
			$value = (strcmp($name, $vo_name) == 0) ? true : false;
			return $value;
		} else {
			return false;
		}
	}

	/**
	 * @param $data             the provisioned array data provided by comanage
	 * @param $vo_name          vo_name to check against the enrollment cou name
	 * @return array            on success return array with cou_id and co_person_id,
	 *                          on failure return null
	 */
	private function retrieveAndValidateCodata($data, $vo_name)
	{
		// Find cou_id through co_persons enrollments
		// if the person is not enrolled in the desired cou group then
		// we should probably NOT provision
		if(isset($data['CoPersonRole'])){
			$enrollments = $data['CoPersonRole'];
			foreach($enrollments as $roles){
				if(strcmp($roles['Cou']['name'],$vo_name) == 0){
					return array ( 'cou_id' => $roles['cou_id'], 'co_person_id' => $roles['co_person_id']);
				}
			}
		} else if(isset($data['CoGroup'])){ // The case of identifying if the co_group that we are removing is the one we want. co_group and cou actually is the same entity
			$name = $data['CoGroup']['name'];
			if(strpos($name, $vo_name) !== false){
				return array('cou_id' => $data['CoGroup']['cou_id'], 'co_person_id' => $data['CoGroup']['CoPerson']['id']);
			}
		} else return null;
		return null;
	}

	/**
	 * @param $provisioningData       Data structure, multidimentional arrays, with person's profile
	 * @param $vo_name                the name of the Virtual Organization
	 * @return int                    co_person_id, on failure or no result -1
	 */
	private function VoOnCOURoleAvailable($provisioningData, $vo_name){
		$re = "/^C?O?:?C?O?U?:?{$vo_name}/m";
		if(!isset($provisioningData['CoPersonRole'])) return -1;

		// I need to search all CoGroupMember in the array and find if there is
		// a member with the description i am looking
		$copersonroles = $provisioningData['CoPersonRole'];
		foreach($copersonroles as $role){
			foreach($role as $cou){
				if(isset($cou['name'])){
					preg_match_all($re, $cou['name'], $matches, PREG_SET_ORDER, 0);
					if(isset($matches[0])){
						return $role['co_person_id'];
					}
				}
			}
		}
		return -1;
	}



	/**
	 * @param $provisioningData       Data structure, multidimentional arrays, with person's profile
	 * @param $vo_name                the name of the Virtual Organization
	 * @return int                    co_person_id, on failure or no result -1
	 */
	// Check if the person belongs to the VO group we want to provision to
	private function VoOnGroupMemberAvailable($provisioningData, $vo_name){
		$re = "/^C?O?:?C?O?U?:?{$vo_name}/m";
		if(!isset($provisioningData['CoGroupMember'])) return -1;
		// I need to search all CoGroupMember in the array and find if there is
		// a member with the description i am looking
		$cogroupmember = $provisioningData['CoGroupMember'];
		foreach($cogroupmember as $group){
			foreach($group as $member){
				if(isset($member['description'])){
					preg_match_all($re, $member['description'], $matches, PREG_SET_ORDER, 0);
					if(isset($matches[0])){
						return $group['co_person_id'];
					}
				}
			}
		}
		return -1;
	}

	/**
	 * @param $co_person_id         the co_person_id to subscribe
	 * @param $vomscli              the object of voms client
	 * @param $vo_targets_id        the id field of cm_co_voms_provisioner_targets for the specified vo, entry
	 *                              this field is part of $coProvisioningTargetData array
	 */
	// Create the vo client object and register the co person
	private function voProvision($co_person_id, $vomscli, $vo_targets_id){
		foreach ($this->cert_retrieve($co_person_id) as $co_cert) {
			if (isset($co_cert['dn']) && isset($co_cert['ca'])) {
				$this->log("co_cert => ".print_r($co_cert,true),LOG_DEBUG);
				$dn = trim($co_cert['dn']);
				$cn = trim($co_cert['cn']);
				$ca = trim($co_cert['ca']);
				$email = trim($co_cert['email']);
				$outmsg = $vomscli->register_user($dn, $ca, $cn, $email);
				$this->log($outmsg, LOG_DEBUG);
				$re = '/fail/m';
				preg_match_all($re, $outmsg, $matches, PREG_SET_ORDER, 0);
				if(!isset($matches[0])) {
					$this->log("Adding to cm_vos table",LOG_DEBUG);
					$save_data = array(
						'Vo' => array(
							'co_person_id'    => $co_person_id,
							'vo_targets_id'   => $vo_targets_id,
							'status'          => StatusEnum::Active,
							'type'            => 'VO'
						)
					);
					$this->Vo = ClassRegistry::init('Vo');
					if(!$this->Vo->save($save_data)){
						$this->log("VO data failed to validate", LOG_DEBUG);
						throw new RuntimeException(_txt('er.db.save'));
					}
				}
			}
		}
	}

	/**
	 * @param $co_person_id         the co_person_id to unsubscribe
	 * @param $vomscli              the object of voms client
	 * @param $vo_targets_id        the id field of cm_co_voms_provisioner_targets for the specified vo, entry
	 *                              this field is part of $coProvisioningTargetData array
	 */
	private function voUnSubscribe($co_person_id, $vomscli, $vo_targets_id){
		foreach ($this->cert_retrieve($co_person_id) as $co_cert) {
			if (isset($co_cert['dn']) && isset($co_cert['ca'])) {
				//$this->log("co_cert => ".print_r($co_cert,true),LOG_DEBUG);
				$dn = $co_cert['dn'];
				$ca = $co_cert['ca'];
				$outmsg = $vomscli->unregister_user($dn, $ca);
				$this->log($outmsg, LOG_DEBUG);
				$re = '/fail/m';
				preg_match_all($re, $outmsg, $matches, PREG_SET_ORDER, 0);
				if(!isset($matches[0])) {
					$this->log("Co person({$co_person_id}) removed from {$vomscli->getVoName()}", LOG_DEBUG);

					$query_str = "delete " .
						"from public.cm_vos ".
						"where vo_targets_id='{$vo_targets_id}' ".
						"and co_person_id='$co_person_id'";
					$this->Vo = ClassRegistry::init('Vo');
					$resQuery = $this->Vo->query($query_str);
					$this->log("res query => ".print_r($resQuery,true),LOG_DEBUG);
					$this->log("Removed VO from profile.",LOG_DEBUG);
				}
			}
		}
	}
}
