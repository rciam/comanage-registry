<?php
/**
 * COmanage Registry CO Voms Provisioner Target Model
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
		$fn = "@provision";
		$dn = $ca = $cn = null;
		// Retrieve vo server data for the case of voms plugin
		$vo_name = trim($coProvisioningTargetData['CoVomsProvisionerTarget']['vo_name']);
		$server_url = trim($coProvisioningTargetData['CoVomsProvisionerTarget']['server_url']);
		$vo_targets_id = $coProvisioningTargetData['CoVomsProvisionerTarget']['id'];

		// Intantiate voms client object
		$vomscli = new VomsClient($server_url, $vo_name);
		$this->log("{$fn}::action => ".$op, LOG_DEBUG);
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
					$vo_entry = $this->provisionExistCheck($co_person_id, $vo_targets_id);
					$this->voProvision($co_person_id, $vomscli, $vo_targets_id, $vo_entry, $op);
					break;
				}
			case ProvisioningActionEnum::CoGroupUpdated:
				break;
			case ProvisioningActionEnum::CoPersonUpdated:
				$this->log("Person Update", LOG_DEBUG);
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
					if($this->provisionExistCheck($co_person_id, $vo_targets_id) != null) {
						// Unsubscribe user and remove reference from his profile
						$this->voUnSubscribe($co_person_id, $vomscli, $vo_targets_id);
					}
				}else{
					$this->log("User belongs in CO:COU:{$vo_name} group.", LOG_DEBUG);
				}
				break;
			case ProvisioningActionEnum::CoPersonReprovisionRequested:
				break;
			case ProvisioningActionEnum::CoPersonDeleted:
				$this->log("{$fn}::action Person Delete/Expunge", LOG_DEBUG);
				// Retrieve co_person_id
				$co_person_id = $provisioningData['CoPerson']['id'];
				// Deprovision co person from VOMS
				$this->log("{$fn}::co person id => ".$co_person_id,LOG_DEBUG);
				// Unsubscribe user and remove reference from his profile
				$this->voUnSubscribe($co_person_id, $vomscli, $vo_targets_id, $op);
				// Soft delete the certificate
				//$this->cert_expunge($co_person_id, $provisioningData['CoOrgIdentityLink']);
				break;
			default:
				// Log noop and fall through.
				$this->log("{$fn}::Provisioning action $op not allowed/implemented", LOG_DEBUG);
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
	private function cert_retrieve($id, $op)
	{
		// The fn variable is used for debugging purposes
		$fn = "@cert_retrieve";

		$this->log("{$fn}::action => ".$op,LOG_DEBUG);
		switch ($op) {
			// PD action - Person Delete action
			case ProvisioningActionEnum::CoPersonDeleted:
				$query_str = "select ce.subject, ce.issuer, em.mail ".
					"from cm_certs as ce, cm_email_addresses as em ".
					"where ce.co_person_id={$id} ".
					"and ce.co_person_id = em.co_person_id ".
					"and ce.modified < (NOW() at time zone ('utc') + '2 minute'::interval) ".
					"and ce.cert_id is null";
				break;
			default:
				$query_str = "select ce.subject, ce.issuer, em.mail ".
					"from cm_certs as ce, cm_email_addresses as em ".
					"where ce.co_person_id={$id} ".
					"and ce.co_person_id = em.co_person_id ".
					"and not ce.deleted ".
					"and ce.cert_id is null ".
					"and ce.deleted is false ".
					"and em.deleted is false";
				break;
		}


		$query_str_cn = "select concat_ws(' ',given,family) as cn from cm_names where co_person_id={$id} and type='official' and deleted is false;";
		// Retrieve Cert model and run the query
		$this->Cert = ClassRegistry::init('Cert');
		$res_cert = $this->Cert->query($query_str);
		$this->log("{$fn}::cert query => ".print_r($query_str,true),LOG_DEBUG);
		$this->log("{$fn}::cert dataset => ".print_r($res_cert,true),LOG_DEBUG);
		// Retrieve Name model and run the query
		$this->Name = ClassRegistry::init('Name');
		$res_name = $this->Name->query($query_str_cn);
		$this->log("{$fn}::name query => ".print_r($query_str_cn,true),LOG_DEBUG);
		$this->log("{$fn}::name dataset => ".print_r($res_name,true),LOG_DEBUG);
		// If multiple certs for a co_person_id are available, we should retrieve data for each one
		// then yield the result
		foreach($res_cert as $cert){
			$cert_attr["dn"] = trim($cert[0]['subject']);
			$cert_attr["ca"] = trim($cert[0]['issuer']);
			$cert_attr["email"] = trim($cert[0]['mail']);
			// Retrieve canonical name
			// if the subject dn is wrong just continue to the next one
			if(isset($res_name[0][0]) && $res_name[0][0]['cn'] != ""){
				$cert_attr["cn"] = $res_name[0][0]['cn'];
			} else {
				$dn_parts = explode(",", $cert_attr["dn"]);
				// Regex
				$regex = '/^CN=(.*?)$/m';
				if (isset($dn_parts) && count($dn_parts) < 2) continue;
				foreach ($dn_parts as $attribute) {
					if (preg_match($regex, $attribute, $match)) {
						$cert_attr["cn"] = trim($attribute);
					}
				}
			}
			$this->log("{$fn}::cn for user($id) => ".$cert_attr["cn"],LOG_DEBUG);
			// Yield the array back to the caller
			yield $cert_attr;
		}
	}


	/**
	 * @param $group_id     the group id for which we are trying to provision
	 * @return Generator    certificate attributes for each co person belonging in the group
	 */
	private function cert_retrieve_GR($group_id, $op){
		// Load the model
		$cert = array();
		$this->Cert = ClassRegistry::init('Cert');
		// At first find the co_person_id from the co_id
		$query_str = "select me.co_person_id ".
					"from cm_co_groups as gr, cm_co_group_members as me ".
					"where me.co_group_id = gr.id and gr.id = ".$group_id;
		$res = $this->Cert->query($query_str);

		foreach ($res as $co_person) {
			foreach ($this->cert_retrieve($co_person[0]['co_person_id'], $op) as $co_cert){
				$cert[0] =  $co_cert;
			}
			yield $cert;
		}
	}


	/**
	 * @param $co_person_id    the co person we want to check
	 * @param $vo_targets_id   the vo we want to check
	 * @return object          if null the user have never been provisioned before. Otherwise,
	 *                         returning the the Vo object
	 */
	private function provisionExistCheck($co_person_id, $vo_targets_id){
		$fn = "provisionExistCheck";
		// retrieve the vo table entry that contains the current co person id
		$args = array();
		$args['conditions']['Vo.vo_id'] = null;
		$args['conditions']['Vo.co_person_id'] = $co_person_id;
		$args['conditions']['Vo.vo_targets_id'] = $vo_targets_id;
		$args['conditions']['Vo.deleted'] = false;
		$args['fields'] = array('Vo.*');
		// Load the model
		//$this->log("provisionExistCheck::query args => ".print_r($args,true),LOG_DEBUG);
		$this->Vo = ClassRegistry::init('Vo');
		$cur_co_person_entry = $this->Vo->find('first',$args);
		//$cur_co_person_entry = $this->Vo->query($query_str);
		// $this->log("provisionExistCheck::cur co person entry => ".print_r($cur_co_person_entry,true),LOG_DEBUG);
		if(isset($cur_co_person_entry) && count($cur_co_person_entry) > 0){
			return $cur_co_person_entry;
		} else {
			return null;
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
		$fn = "VoOnCOURoleAvailable";
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
	private function voProvision($co_person_id, $vomscli, $vo_targets_id, $vo_entry, $op){
		$fn = "voProvision";
		foreach ($this->cert_retrieve($co_person_id, $op) as $co_cert) {
			if (isset($co_cert['dn']) && isset($co_cert['ca'])) {
				$dn = trim($co_cert['dn']);
				$cn = trim($co_cert['cn']);
				$ca = trim($co_cert['ca']);
				$email = trim($co_cert['email']);
				$outmsg = $vomscli->register_user($dn, $ca, $cn, $email);
				$this->log("{$fn}::vomsclient::output => ".$outmsg,LOG_DEBUG);
				$re_fail = '/fail/m';
				preg_match_all($re_fail, $outmsg, $matches_fail, PREG_SET_ORDER, 0);
				$re_exists = '/org.glite.security.voms.admin.persistence.error.UserAlreadyExistsException/m';
				preg_match_all($re_exists, $outmsg, $matches_exists, PREG_SET_ORDER, 0);

				if((!isset($matches_fail[0]) && !isset($matches_exists[0])) || ($vo_entry == null && isset($matches_exists[0]))) {
					$this->log("{$fn}::adding new entry into cm_vos table",LOG_DEBUG);
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
						$this->log("{$fn}::VO data failed to validate", LOG_DEBUG);
						throw new RuntimeException(_txt('er.db.save'));
					}
				} else if($vo_entry != null && isset($matches_exists[0])){
					$this->log("Updating existing entry into cm_vos table",LOG_DEBUG);
					$this->Vo = ClassRegistry::init('Vo');
					// Create new entry with updated provision
					$vo_entry['Vo']['modified'] = date('Y-m-d H:i:s');
					$this->Vo->create(); // Create a new record
					if(!$this->Vo->save($vo_entry)){
						$this->log("{$fn}::VO data(increment revision) failed to validate", LOG_DEBUG);
						throw new RuntimeException(_txt('er.db.save'));
					}
					// Update the old field
					$this->Vo->id = $vo_entry['Vo']['id'];
					$this->Vo->saveField('vo_targets_id', $vo_targets_id);
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
	private function voUnSubscribe($co_person_id, $vomscli, $vo_targets_id, $op){
		$fn = "@voUnSubscribe";

		// Retrieve the certificates related to the co person we are investigating
		foreach ($this->cert_retrieve($co_person_id, $op) as $co_cert) {
			if (isset($co_cert['dn']) && isset($co_cert['ca'])) {
				//$this->log("co_cert => ".print_r($co_cert,true),LOG_DEBUG);
				$dn = $co_cert['dn'];
				$ca = $co_cert['ca'];
				$outmsg = $vomscli->unregister_user($dn, $ca);
				$this->log("{$fn}::voms client raw output => ".$outmsg,LOG_DEBUG);
				$re = '/fail/m';
				preg_match_all($re, $outmsg, $matches, PREG_SET_ORDER, 0);
				if(!isset($matches[0])) {
					$this->log("Co person({$co_person_id}) removed from {$vomscli->getVoName()}", LOG_DEBUG);
					// Soft delete the vo from the user
					$query_str = "update public.cm_vos set deleted='true' " .
						"where vo_targets_id='{$vo_targets_id}' ".
						"and co_person_id='$co_person_id'";
					$this->Vo = ClassRegistry::init('Vo');
					$resQuery = $this->Vo->query($query_str);
					$this->log("Removed VO from profile.",LOG_DEBUG);
				}
			}
		}
	}

	/**
	 * Soft delete all entries in cert table that are associated with the co_person and its org_identity
	 *
	 * @param $co_person_id
	 * @param $org_identity_id
	 * @return mixed							status of query execution
	 */
	private function cert_expunge($co_person_id, $co_org_identity_link)
	{
		// Retrieve the org_identity_ids
		$org_identity_ids = "";
		foreach($co_org_identity_link as $org_identity ){
			$org_identity_ids .= $org_identity['org_identity_id'];
			$org_identity_ids .= ",";
		}
		// Remove the trailing commas
		$org_identity_ids = rtrim($org_identity_ids, ",");
		// Create the query
		$query_str = "update cm_certs ".
			"set deleted='true' ".
			"where co_person_id={$co_person_id} ".
			"or org_identity_id in ({$org_identity_ids});";
		$this->log("cert_expunge:query => ".$query_str, LOG_DEBUG);
		$this->Cert = ClassRegistry::init('Cert');
		// Soft delete all the entries from the Cert table
		$ret = $this->Cert->query($query_str);
		$this->log("cert_expunge:query result = ".print_r($ret,true), LOG_DEBUG);
		return $ret;
	}

}
