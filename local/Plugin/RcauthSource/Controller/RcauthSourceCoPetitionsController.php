<?php
/**
 * COmanage Registry ORCID Source Co Petitions Controller
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
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('CoPetitionsController', 'Controller');

class RcauthSourceCoPetitionsController extends CoPetitionsController {
	// Class name, used by Cake
	public $name = "RcauthSourceCoPetitions";

	public $uses = array("CoPetition",
		"OrgIdentitySource",
		"RcauthSource",
		"OrgIdentitySourceRecord",
		"RcauthSource.RcauthSourceBackend",
		"Cert",
		"Identifier");

	/**
	 * Enrollment Flow selectOrgIdentity (authenticate mode)
	 *
	 * @since  COmanage Registry v2.0.0
	 * @param  Integer $id CO Petition ID
	 * @param  Array $oiscfg Array of configuration data for this plugin
	 * @param  Array $onFinish URL, in Cake format
	 * @param  Integer $actorCoPersonId CO Person ID of actor
	 */
	protected function execute_plugin_selectOrgIdentityAuthenticate($id, $oiscfg, $onFinish, $actorCoPersonId) {
		// First pull our RCAUTH configuration
		$this->log('@execute_plugin_selectOrgIdentityAuthenticate', LOG_DEBUG);
		$args = array();
		$args['conditions']['RcauthSource.id'] = $oiscfg['OrgIdentitySource']['id'];
		$args['contain'] = false;

		$cfg = $this->RcauthSource->find('first', array('Rcauth'));

		if(empty($cfg)) {
			throw new InvalidArgumentException(_txt('er.notfound',
				array(_txt('ct.rcauth_sources.1'),
					$oiscfg['OrgIdentitySource']['id'])));
		}
		// Construct the callback URL, needed for both the initial query and
		// exchanging the code for a response

		$callback = $this->RcauthSourceBackend->callbackUrl();
		$redirectUri = Router::url($callback, array('full' => true));

		if(empty($this->request->query['code'])) {
			// Retrieve all available scope from the official API web page
			$scope = "";
			foreach ($this->RcauthSourceBackend->rcauthUrl->getScopesSupported() as $key => $value) {
				$scope .= $value." ";
			}
			$scope = trim($scope);

			// First time through, redirect to the authorize URL
			// After we retrieve the code we are served by another function
			$url = $this->RcauthSourceBackend->rcauthUrl->getAuthorizationEndpoint()."?";
			$url .= "response_type=code";
			$url .= "&scope=".urlencode($scope);
			$url .= "&client_id=" . $cfg['RcauthSource']['clientid'];
			$url .= "&state=".base64_encode("{\"pass\" : {$id}, \"named\" : {$oiscfg['OrgIdentitySource']['id']} }");
			$url .= "&redirect_uri=" . urlencode($redirectUri);

			$this->redirect($url);
		}

		// Else we're back from an OAuth request, exchange the code for an access token
		try {

			// Data we retrieved
			//{
			//   "access_token":"https://masterportal-pilot.aai.example.org/mp-oa2-server/accessToken/720a43b1d5b408f7fdfc6345faa2eca6/1525496398788",
			//   "refresh_token":"https://masterportal-pilot.aai.example.org/mp-oa2-server/refreshToken/41c249bef6ce0b184855585c1b592bb7/1525496398788",
			//   "id_token":"eyJ0eXAiOiJKV1QiLCJraWQiOiI5Mzk2ODI3QjExMDI0QzlBNDI1MDM2MDk3REU0MTJGMiIsImFsZyI6IlJTMjU2In0.eyJpc3MiOiJodHRwczovL21hc3RlcnBvcnRhbC1waWxvdC5hYWkuZWdpLmV1L21wLW9hMi1zZXJ2ZXIiLCJzdWIiOiIwMjU2NTliNDAxYjQ1NzkzMjUzZGJlNTI1MTExYzhlNTQxYzU1MTlkZTg1ZTU0NGRkNTU3ZWY2ODI1ZDM4MTRjQGVnaS5ldSIsImV4cCI6MTUyNTQ5NzI5OCwiYXVkIjoibXlwcm94eTpvYTRtcCwyMDEyOi9jbGllbnRfaWQvMWY2ZjY1NDk3ZmU3M2NlYjdiYzJjNDlmYTE2ZWNhMGIiLCJpYXQiOjE1MjU0OTYzOTgsImF1dGhfdGltZSI6IjE1MjU0OTYzOTYiLCJpZHAiOiJodHRwczovL2FhaS1kZXYuZWdpLmV1L3Byb3h5L3NhbWwyL2lkcC9tZXRhZGF0YS5waHAiLCJlZHVQZXJzb25UYXJnZXRlZElEIjoiaHR0cHM6Ly9hYWktZGV2LmVnaS5ldS9wcm94eS9zYW1sMi9pZHAvbWV0YWRhdGEucGhwITdjMDY2ZjQ4ZDRiMTk2MjFhM2M1YmQ0YzdhZmQ1ODgyYjIwYWIzMGQiLCJjZXJ0X3N1YmplY3RfZG4iOiJDTj1Jb2FubmlzIElnb3VtZW5vcyB5Q0tjaWpKVWdpOWU4WTRzLE89RUdJIEZvdW5kYXRpb24sT1U9QUFJLVBpbG90LE89RUdJIiwiaWRwX2Rpc3BsYXlfbmFtZSI6IkVHSSBGb3VuZGF0aW9uIiwibmFtZSI6IklvYW5uaXMgSWdvdW1lbm9zIiwiZWR1UGVyc29uVW5pcXVlSWQiOiIwMjU2NTliNDAxYjQ1NzkzMjUzZGJlNTI1MTExYzhlNTQxYzU1MTlkZTg1ZTU0NGRkNTU3ZWY2ODI1ZDM4MTRjQGVnaS5ldSIsImdpdmVuX25hbWUiOiJFZnRoaW1pb3MiLCJmYW1pbHlfbmFtZSI6Iklnb3VtZW5vcyIsImVtYWlsIjoiaW9pZ291bWVAZ21haWwuY29tIn0.FeyiV_czMqT9LlbHBC6qhUC8c6THW8DYQ8dupG5no9yGdBoquOq4YMdEtzX5BPH_ku9SJhGcrbfLo3HLKFrhaHeE7YSlnfjAC6-218kHd1Tcgm0qaR3ukvjGiB6uNczli5rPkJlCikt_zi3f6SRoXlnmm67MIx7hlUDzXV0invEr0SPMnaTw1Lb_0jNDgqjNndsSZG_loS7XXDJjFQNGtfzLYfPiAsFXCjMbuHN7Z-cKvzSthQF4KYANXSob2PeMFdJjAl2GAbyl-NJpB-9hAkz7jNoqvYvp3oXdU-KR_5yRSVY0v1yrgFvBJNPN9sAWY1VX_C0IgEyGjWpnC9WmWA",
			//   "token_type":"Bearer",
			//   "expires_in":1296000
			//}
			// Exchange the code for an access token
			$response = $this->RcauthSourceBackend->exchangeCode($redirectUri,
																$cfg['RcauthSource']['clientid'],
																$cfg['RcauthSource']['client_secret'],
																$this->request->query['code']);

			// Data in the response object
			// $response->access_token
			// $response->refresh_token
			// $response->token_type
			// $response->expires_in
			// It looks like the access_token could be stored and used to refresh the user's data,
			// though we just do that with our RcauthSource level access token.

			// Save the data we retrieved above for the current client id
			// Create th data structure
			$save_data = array(
				'RcauthSource' => array(
					'access_token'  => $response->access_token,
					'refresh_token' => $response->refresh_token,
					'id_token'      => $response->id_token,
					'token_type'    => $response->token_type
				)
			);
			// Find the entry in the table we are interested in
			// For our case we want to update the existing entry of the current petition
			// We reach the table through our instance of the AppModel and the description
			// of its schema
			$this->RcauthSource->id = $cfg['RcauthSource']['id'];
			// Save the eduPersonUniqueId
			$this->RcauthSource->set($save_data);
			if($this->RcauthSource->validates()){
				$this->RcauthSource->save($save_data);
			} else {
				$this->log("rcauthsource data failed to validate", LOG_DEBUG);
				throw new RuntimeException(_txt('er.db.save'));
			}

			// Now that we saved the access token, we need to obtain the record
			// We return an array with two entries:
			//  [0] => raw_json_result
			//  [1] => orgIdentityObj Array
			$user_data_array = $this->RcauthSourceBackend->retrieve($response->access_token);
			$user_data_obj = json_decode($user_data_array['raw']);
			$ePUID = $user_data_obj->eduPersonUniqueId;

			// Find the co_person_id of the user enrolling and correlate the data with the user
			$query_str = "select co_person_id ".
						 "from public.cm_identifiers ".
						 "where identifier='{$ePUID}' ".
						 "and co_person_id is not null ".
						 "and not deleted;";
			$resQuery = $this->Identifier->query($query_str);
			$cur_co_person_id = $resQuery[0][0]['co_person_id'];

			try {
				/*
				 * Rules/ Constraints followed by RCAuth plugin
				 * - Since the user is not present create a new org identity
				 * - Now that we have the RCAUTH, create an Org Identity to store it
				 * - if the user runs the enrollment for the second+ time we should check the if the certificate
				 * i still up to date or we should update it
				 */
				// selectEnrollee hasn't run yet so we can't pull the target CO Person from the
				// petition, but for OISAuthenticate, it's the current user (ie: $actorCoPersonId)
				// that we always want to link to.
				$OrgId = $this->OrgIdentitySource->createOrgIdentity($oiscfg['OrgIdentitySource']['id'],
					$ePUID,
					$actorCoPersonId,
					$this->cur_co['Co']['id'],
					$cur_co_person_id,
					true,
					null,
					false,
					$user_data_array);

				// Record the RCAUTH into History and Petition History
				$this->CoPetition->EnrolleeOrgIdentity->HistoryRecord->record($cur_co_person_id,
					null,
					$OrgId,
					$actorCoPersonId,
					ActionEnum::CoPersonOrgIdLinked,
					_txt('pl.rcauthsource.linked', array($ePUID)));

				// Save the certificate in the database(cm_certs table)
				$this->certificateSave($user_data_obj->cert_subject_dn,
					$user_data_obj->eduPersonUniqueId,
					$cfg['RcauthSource']['issuer'],
					$cur_co_person_id,
					$OrgId);

			} catch(OverflowException $e) {
				// RCAuth plugin runs and an existing org identity had been found. Check if
				// the certificate data should be updated before exit
				// Retrieve the ORG IDENTITY ID
				$args = array();
				$args['conditions']['OrgIdentitySourceRecord.org_identity_source_id'] = $oiscfg['OrgIdentitySource']['id'];
				$args['conditions']['OrgIdentitySourceRecord.sorid'] = $ePUID;
				// Finding via a join to OrgIdentity bypasses ChangelogBehavior, so we need to
				// manually exclude those records.
				$args['conditions']['OrgIdentitySourceRecord.deleted'] = false;
				$args['conditions'][] = 'OrgIdentitySourceRecord.org_identity_source_record_id IS NULL';
				$record = $this->OrgIdentitySourceRecord->OrgIdentity->find('first', $args);
				$org_identity_id = $record['OrgIdentity']['id'];
				$this->log("org identity id(catch) => ".$org_identity_id,LOG_DEBUG);
				unset($args);
				// retrieve org identity entry from CERTS table
				$args = array();
				$args['conditions']['Cert.org_identity_id'] = $org_identity_id;
				$args['conditions']['Cert.deleted'] = false;
				$args['fields'] = array('Cert.*');
				$org_identity_entry = $this->Cert->find('first',$args);
				unset($args);
				// retrieve the cert table entry that contains the current co person id and we want to update
				$args = array();
				$args['conditions']['Cert.issuer'] = $org_identity_entry['Cert']['issuer'];
				$args['conditions']['Cert.subject'] = $org_identity_entry['Cert']['subject'];
				$args['conditions']['Cert.cert_id'] = null;
				$args['conditions']['Cert.co_person_id'] = $cur_co_person_id;
				$args['conditions']['Cert.deleted'] = false;
				$args['fields'] = array('Cert.*');
				$cur_co_person_entry = $this->Cert->find('first',$args);
				$this->log("cur co person entry => ".print_r($cur_co_person_entry,true),LOG_DEBUG);

				// Update the certificate entry for the org_identity retrieved and for the current co person
				$this->certEntryUpdate($org_identity_entry, $cur_co_person_entry, $user_data_obj->cert_subject_dn, $cfg['RcauthSource']['issuer']);
			}

			// At the end, add a record to the petition history table
			$this->CoPetition->CoPetitionHistoryRecord->record($id,
				$actorCoPersonId,
				PetitionActionEnum::IdentityLinked,
				_txt('pl.rcauthsource.linked', array($ePUID)));
		}
		catch(Exception $e) {
			// This might happen if (eg) the Rcauth is already in use
			throw new RuntimeException($e->getMessage());
		}
		// The step is done
		$this->redirect($onFinish);
	}

	/**
	 * @param $org_id_res
	 * @param $co_person_res
	 * @param $new_subject
	 * @param $new_issuer
	 */
	private function certEntryUpdate($org_id_res, $co_person_res, $new_subject, $new_issuer){
		$this->log("res org_id => ".print_r($org_id_res, true), LOG_DEBUG);
		$this->log("res co person => ".print_r($co_person_res, true), LOG_DEBUG);

		$issuer = $org_id_res['Cert']['issuer'];
		$subject = $org_id_res['Cert']['subject'];
		$coperson = $co_person_res['Cert']['co_person_id'];
		$org_identity_id = $org_id_res['Cert']['org_identity_id'];
		$actor_identifier = $org_id_res['Cert']['actor_identifier'];

		// if both subject dn and issuer are different we should create a new certificate entry
		// A certificate is uniquely identified by those two fields. As a result, if both are different
		// the we should create a new entry and not update the old ones
		if (trim($issuer) != trim($new_issuer) && trim($subject) != trim($new_subject)) {
			// Create the new certificate
			$this->certificateSave($new_subject, $actor_identifier, $issuer, $coperson, $org_identity_id);
		} else {
			// This is the case where one of the two gets update
			// Update the modify field
			$org_id_res['Cert']['modified'] = date('Y-m-d H:i:s');
			$co_person_res['Cert']['modified'] = date('Y-m-d H:i:s');
			// org_identity_id case
			$this->Cert->create(); // Create a new record
			$this->Cert->save($org_id_res); // And save it
			// co_person_id case
			$this->Cert->create(); // Create a new record
			$this->Cert->save($co_person_res); // And save it

			if(trim($new_subject) != trim($subject)) {
				// Update the old entry for org identity case
				$this->Cert->id = $org_id_res['Cert']['id'];
				$this->Cert->saveField('subject', $new_subject);

				// Update the old entry for co person case
				$this->Cert->id = $co_person_res['Cert']['id'];
				$this->Cert->saveField('subject', $new_subject);
			} elseif(trim($new_issuer) != trim($issuer)){
				// Update the old entry for org identity case
				$this->Cert->id = $org_id_res['Cert']['id'];
				$this->Cert->saveField('issuer', $new_issuer);

				// Update the old entry for co person case
				$this->Cert->id = $co_person_res['Cert']['id'];
				$this->Cert->saveField('issuer', $new_issuer);
			}
		}
	}


	/**
	 * @param $subject                  subject DN
	 * @param $actor_identifier         actor identifier
	 * @param $issuer                   CA issuer
	 * @param $co_person_id             co person id
	 * @param $org_identity_id          org identity id
	 */
	private function certificateSave($subject, $actor_identifier, $issuer, $co_person_id, $org_identity_id){
		// Save the subject DN
		// Save the data to the certificates table
		// Create the data structure
		$data_certificate = array(
			array(
					'subject' => $subject,
					'actor_identifier' => $actor_identifier,
					'issuer' => $issuer,
					'co_person_id' => $co_person_id
			),
			array(
					'subject' => $subject,
					'org_identity_id' => $org_identity_id,
					'actor_identifier' => $actor_identifier,
					'issuer' => $issuer,
				)
		);

		try {
			$this->Cert->set($data_certificate);
			if($this->Cert->Validates()) {
				$this->Cert->saveAll($data_certificate);
			} else {
				$this->log("Cert data failed to validate", LOG_DEBUG);
				throw new RuntimeException(_txt('er.db.save'));
			}
		} catch (Exception $ex) {
			$this->log("message: " . $ex, LOG_DEBUG);
		}
	}

}
