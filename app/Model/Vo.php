<?php
/**
 * COmanage Registry Certificate Model
 *
 * Copyright (C) 2010-17 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-16 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class Vo extends AppModel
{
	// Define class name for cake
	public $name = "Vo";

	// Current schema version for API
	public $version = "1.0";

	// Add behaviors
	public $actsAs = array('Containable',
		'Normalization' => array('priority' => 4),
		'Changelog' => array('priority' => 5));

	// Association rules from this model to other models
	public $belongsTo = array(
		// A Vo may be attached to a CO Person Role
		'CoPerson',
		// A Vo may be attached to an Org Identity
		'OrgIdentity',
		// A Vo will be attached to a CO Voms Provisioner Targets
		'CoVomsProvisionerTarget' => array(
			'foreignKey' => 'vo_targets_id',
			'conditions' => 'Vo.vo_targets_id = CoVomsProvisionerTarget.id',
			'joinType'   => 'INNER'
		),
	);

	// Default display field for cake generated views
	public $displayField = "Vo.id";

	// Default ordering for find operations
	public $order = array("Vo.id");

	// Validation rules for table elements
	// Validation rules must be named 'content' for petition dynamic rule adjustment
	public $validate = array(
		'vo_targets_id' => array(
			'content' => array(
				'rule' => 'numeric',
				'required' => true,
				'allowEmpty' => false
			),
		),
		'co_person_id' => array(
			'content' => array(
				'rule' => 'numeric',
				'required' => false,
				'allowEmpty' => true,
			),
		),
		'type' => array(
			'content' => array(
				'rule' => array('validateExtendedType',
					array('attribute' => 'Vo.type',
						'default' => array(VOsEnum::VO))),
				'required' => false,
				'allowEmpty' => false,
			),
		),
		'status' => array(
			'content' => array(
				'rule' => array('inList',
					array(StatusEnum::Active,
						StatusEnum::Suspended)),
				'required' => false,
				'allowEmpty' => true
			),
		),
		'org_identity_id' => array(
			'content' => array(
				'rule' => 'numeric',
				'required' => false,
				'allowEmpty' => true,
				),
			),
		);

	// Check if the field passed is empty and fill in with the default value
	public function beforeSave($options = array())
	{
		$this->log("@beforeSave Vo", LOG_DEBUG);
		// TODO: IMPORTANT TIP FOR FUTURE USE
		// This kind of schemas should have a certain architecture. This is why
		// cert table has the fields it has. So, the i am not getting wrong data back
		// I am getting the ones the framework is programmed to create!!!

		//$this->log("data => ".print_r($this->data,true),LOG_DEBUG);
		if(!(isset($this->data['Vo']['co_person_id']) && isset($this->data['Vo']['vo_targets_id']))){
			return false;
		}else {
			return true;
		}
	}
}