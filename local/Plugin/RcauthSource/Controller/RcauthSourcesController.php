<?php
/**
 * COmanage Registry ORCID Source Controller
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
 * @package       registry
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses("SOISController", "Controller");

class RcauthSourcesController extends SOISController {
	// Class name, used by Cake
	public $name = "RcauthSources";

	public $uses = array("RcauthSource.RcauthSource",
		"RcauthSource.RcauthSourceBackend");

	/**
	 * Update a RcauthSource.
	 *
	 * @since  COmanage Registry v2.0.0
	 * @param  integer $id RcauthSource ID
	 */

	public function edit($id) {
		$this->log("@edit:id:".$id,LOG_DEBUG);
		parent::edit($id);

		// Set the callback URL
		// ioigoume
		// this variable, containing the redirect url is the one shown in comanage web ui screen
		// this is the auto generated redirect url that we register in with the Vo
		$this->set('vv_rcauth_redirect_url', $this->RcauthSourceBackend->callbackUrl());
	}

	function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
		$this->Flash->set(_txt('rs.updated-a3', array(_txt('ct.rcauth_sources.2'))), array('key' => 'success'));
		return true;
	}

	/**
	 * Authorization for this Controller, called by Auth component
	 * - precondition: Session.Auth holds data used for auth decisions
	 * - postcondition: $permissions set with calculated permissions
	 *
	 * @since  COmanage Registry v2.0.0
	 * @return Array Permissions
	 */

	function isAuthorized() {
		$this->log("@isAuthorized",LOG_DEBUG);
		$roles = $this->Role->calculateCMRoles();

		// Construct the permission set for this user, which will also be passed to the view.
		$p = array();

		// Determine what operations this user can perform

		// Delete an existing RcauthSource?
		$p['delete'] = ($roles['cmadmin'] || $roles['coadmin']);

		// Edit an existing RcauthSource?
		$p['edit'] = ($roles['cmadmin'] || $roles['coadmin']);

		// View all existing RcauthSource?
		$p['index'] = ($roles['cmadmin'] || $roles['coadmin']);

		// View an existing RcauthSource?
		$p['view'] = ($roles['cmadmin'] || $roles['coadmin']);

		$this->set('permissions', $p);
		return($p[$this->action]);
	}
}
