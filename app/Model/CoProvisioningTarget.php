<?php
/**
 * COmanage Registry CO Provisioning Target Model
 *
 * Copyright (C) 2012-16 University Corporation for Advanced Internet Development, Inc.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not use this file except in compliance with
 * the License. You may obtain a copy of the License at
 * 
 * http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software distributed under
 * the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright     Copyright (C) 2012-16 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoProvisioningTarget extends AppModel {
  // Define class name for cake
  public $name = "CoProvisioningTarget";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");
  
  public $hasMany = array("CoProvisioningExport" => array('dependent' => true));
  
  // Default display field for cake generated views
  public $displayField = "description";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'message' => 'A CO ID must be provided'
    ),
    'description' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'plugin' => array(
      // XXX This should be a dynamically generated list based on available plugins
      'rule' => 'notBlank',
      'required' => true,
      'message' => 'A plugin must be provided'
    ),
    'status' => array(
      'rule' => array(
        'inList',
        array(
          ProvisionerStatusEnum::AutomaticMode,
          ProvisionerStatusEnum::Disabled,
          ProvisionerStatusEnum::ManualMode
        )
      ),
      'required' => true,
      'message' => 'A valid status must be selected'
    ),
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );
  
  /**
   * Actions to take before a save operation is executed.
   *
   * @since  COmanage Registry v1.0.3
   */
  
  public function beforeSave($options = array()) {
    if(!empty($this->data['CoProvisioningTarget']['co_id'])
       && (empty($this->data['CoProvisioningTarget']['ordr'])
           || $this->data['CoProvisioningTarget']['ordr'] == '')) {
      // In order to deterministically execute provisioners, assign an order.
      // Find the current high value and add one
      $n = 1;
      
      $args = array();
      $args['fields'][] = "MAX(ordr) as m";
      $args['conditions']['CoProvisioningTarget.co_id'] = $this->data['CoProvisioningTarget']['co_id'];
      $args['order'][] = "m";
      
      $o = $this->find('first', $args);
      
      if(!empty($o['m'])) {
        $n = $o['m'] + 1;
      }
      
      $this->data['CoProvisioningTarget']['ordr'] = $n;
    }
    
    return true;
  }
}