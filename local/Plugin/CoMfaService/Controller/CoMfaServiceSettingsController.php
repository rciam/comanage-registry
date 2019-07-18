<?php
/**
 * COmanage Registry CO Services Token Setting Controller
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

App::uses("StandardController", "Controller");

class CoMfaServiceSettingsController extends StandardController {
  // Class name, used by Cake
  public $name = "CoMfaServiceSettings";
  
  // This controller needs a CO to be set
  public $requires_co = true;
  
  
  public function configure() {
    if($this->request->is('post')) {
      // We're processing an update
      
      $coId = $this->request->data['CoMfaServiceSetting']['co_id'];
      $args = array();
      $args['conditions']['CoMfaServiceSetting.co_id'] = $this->cur_co['Co']['id'];
      $args['contain'] = false;
      $data = $this->CoMfaServiceSetting->find('all', $args);
      
      $id = isset($data[0]['CoMfaServiceSetting']) ? $data[0]['CoMfaServiceSetting']['id'] : -1;
      // if i had already set configuration before, now retrieve the entry and update
      if($id > 0){
        $this->CoMfaServiceSetting->id = $id;
      }
      
      try {
        if($this->CoMfaServiceSetting->save($this->request->data['CoMfaServiceSetting'])){
          $this->Flash->set(_txt('rs.saved'), array('key' => 'success'));
        } else {
          $params = array();
          $invalidFields = $this->CoMfaServiceSetting->invalidFields();
          $this->log(__METHOD__ . "::exception error => ".print_r($invalidFields, true), LOG_DEBUG);
          if(isset($invalidFields)){
            foreach($invalidFields as $key => $value){
              $params[$key] = reset($value);
            }
          }
          $this->log(__METHOD__ . "::params => ".print_r($params, true), LOG_DEBUG);
          $this->Flash->set(_txt('rs.error'), array('key' => 'error'));
        }
      }
      catch(Exception $e) {
        $this->log(__METHOD__ . "::exception error => ".$e, LOG_DEBUG);
        $this->Flash->set($e->getMessage(), array('key' => 'error'));
      }
      // Redirect back to a GET
      $this->redirect(array('action' => 'configure', 'co' => $coId));
    } else {
      // Get (if) available settings
      $args = array();
      $args['conditions']['CoMfaServiceSetting.co_id'] = $this->cur_co['Co']['id'];
      $args['contain'] = false;
      $data = $this->CoMfaServiceSetting->find('all', $args);
      $this->set('co_mfa_service_settings', $data);
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v2.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Configure CO Service Token Settings
    $p['configure'] = ($roles['cmadmin'] || $roles['coadmin']);
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
  
  /**
   * For Models that accept a CO ID, find the provided CO ID.
   * - precondition: A coid must be provided in $this->request (params or data)
   *
   * @since  COmanage Registry v2.0.0
   * @return Integer The CO ID if found, or -1 if not
   */
  
  public function parseCOID($data = null) {
    if($this->action == 'configure') {
      if(isset($this->request->params['named']['co'])) {
        return $this->request->params['named']['co'];
      } elseif(isset($this->request->data['CoMfaServiceSetting']['co_id'])) {
        return $this->request->data['CoMfaServiceSetting']['co_id'];
      }
    }
    
    return parent::parseCOID();
  }
}