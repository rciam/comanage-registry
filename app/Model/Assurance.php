<?php
/**
 * COmanage Registry Assurance Model
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
 * @since         COmanage Registry v3.1.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

class Assurance extends AppModel {
  // Define class name for cake
  public $name = "Assurance";

  // Current schema version for API
  public $version = "1.0";

  // Add behaviors
  public $actsAs = array(
    'Containable',
    'Normalization' => array('priority' => 4),
    'Provisioner',
    'Changelog' => array('priority' => 5),
  );

  // Association rules from this model to other models
  public $belongsTo = array(
    "OrgIdentity",
    "CoPerson",
  );

  // Default display field for cake generated views
  public $displayField = "Assurance.value";

  // Default ordering for find operations
  public $order = array("Assurance.type");

  // Validation rules for table elements
  public $validate = array(
    'co_person_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
      ),
    ),
    'org_identity_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true,
      ),
    ),
    'value' => array(
      'content' => array(
        'rule' => array('validateInput'),
        'required' => true,
        'allowEmpty' => false,
      ),
    ),
    'description' => array(
      'content' => array(
        'rule' => array('maxLength', 512),
        'required' => false,
        'allowEmpty' => true
      ),
      'filter' => array(
        'rule' => array('validateInput')
      ),
    ),
    'type' => array(
      'content' => array(
        'rule' => array('inList', array(AssuranceComponentEnum::IdentityAssurance,
                                      AssuranceComponentEnum::AttributeAssurance,
                                      AssuranceComponentEnum::AssuranceProfile,
                                      AssuranceComponentEnum::IdentifierUniqueness)),
        'required' => true,
        'allowEmpty' => false,
      ),
    ),
  );

  /**
   * Find OrgIdentities by Identifier
   *
   * @param string $identifier
   * @return array|int|null
   */
  public function getOrgIdentityByIdentifier($identifier) {
    // Get the Certificates associated with the provided identifier
    $args = array();
    $args['joins'][0]['table'] = 'cm_identifiers';
    $args['joins'][0]['alias'] = 'Identifier';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Identifier.org_identity_id=OrgIdentity.id';
    $args['conditions']['Identifier.identifier'] = $identifier;
    $args['conditions']['Identifier.login'] = true;   // Make this login to avoid any conflict with RCAuth
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions'][] = 'Identifier.org_identity_id IS NOT NULL';
    $args['contain'] = false;

    $this->OrgIdentity = ClassRegistry::init('OrgIdentity');
    $certs = $this->OrgIdentity->find('all', $args);

    return $certs;
  }

  /**
   * Find Assurance Entries linked to Organizational Identities
   *
   * @param string $identifier
   * @return array|int|null
   */
  public function getAssurancesByOrgIdentityIdentifier($identifier) {
    // Get the Certificates associated with the provided identifier
    $args = array();
    $args['joins'][0]['table'] = 'cm_identifiers';
    $args['joins'][0]['alias'] = 'Identifier';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Identifier.org_identity_id=Assurance.org_identity_id';
    $args['joins'][1]['table'] = 'cm_org_identities';
    $args['joins'][1]['alias'] = 'OrgIdentity';
    $args['joins'][1]['type'] = 'INNER';
    $args['joins'][1]['conditions'][0] = 'OrgIdentity.id=Assurance.org_identity_id';
    $args['conditions']['Identifier.identifier'] = $identifier;
    $args['conditions']['Identifier.login'] = true;   // Make this login to avoid any conflict with RCAuth
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions'][] = 'Identifier.org_identity_id IS NOT NULL';
    $args['conditions'][] = 'Assurance.org_identity_id IS NOT NULL';
    $args['conditions'][] = 'NOT Assurance.deleted';
    $args['contain'] = false;

    $certs = $this->find('all', $args);

    return $certs;
  }

  /**
   * Get Assurance attribute map
   *
   * @return string
   */
  public function getEnvAssuranceMapping() {
    $assurance_env_map = "";
    $this->CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
    $env_attributes = $this->CmpEnrollmentConfiguration->enrollmentAttributesFromEnv();
    if(!empty($env_attributes)) {
      // Get issuer env attribute
      $assurance_found = array_filter(
        $env_attributes,
        static function($value) {
          return ($value['attribute'] === 'assurances:value');
        }
      );
      if (!empty($assurance_found)) {
        $assurance_found = reset($assurance_found);
        $assurance_env_map = !empty($assurance_found['env_name']) ? $assurance_found['env_name'] : "";
      }
    }

    return $assurance_env_map;
  }

  /**
   * Check if the environnmental attribute has value. If this is the case check if it is single valued or multi valued
   *
   * @param string $env_value
   * @return bool|null  true for MULTI valued | false for SINGLE valued | null for NO value
   *
   * @depends Shibboleth SP configuration. Currently we assume that the delimiter is the default one `;`. The semicolon.
   */
  public function isEnvMultiVal($env_value) {
    if(!empty($env_value)) {
      $env_value_vals = explode(";", $env_value);
      return (count($env_value_vals) > 1) ? true : false;
    }
    return null;
  }

  /**
   * Get Assurance Environment Value
   *
   * @return string
   */
  public function getEnvValues() {
    // Get Assurance mappings
    $assurance_value = "";
    $assurance_map = $this->getEnvAssuranceMapping();
    // Get Subject DN env value if available
    if(!empty($assurance_map)) {
      $assurance_value = getenv($assurance_map);
      $assurance_value = !empty($assurance_value) ? $assurance_value : "";
    }

    return $assurance_value;
  }

  /**
   * @param string $identifier      OrgIdentity Identifier constructed from the IdP
   */
  public function syncByIdentifier($identifier) {
    $current_assurances = $this->getAssurancesByOrgIdentityIdentifier($identifier);
    $active_login_orgs = $this->getOrgIdentityByIdentifier($identifier);
    $assurance_env_value = $this->getEnvValues();
    // XXX Is the assurance attribute multi valued?
    $is_assurance_val_multi_val = $this->isEnvMultiVal($assurance_env_value);

    // XXX New Assurance list is empty
    if(is_null($is_assurance_val_multi_val)) {
      foreach($current_assurances as $assurance) {
        // Soft Delete everything
        // Since i am using delete function, Changelog behaviour should work
        $this->delete($assurance['Assurance']['id']);
      }
      // Cleared everything, return
      return;
    }

    // XXX The Assurance is MULTI valued
    $new_assur_values = array();
    if($is_assurance_val_multi_val) {
      $new_assur_values = explode(';', $assurance_env_value);
    } else {
      $new_assur_values[] = $assurance_env_value;
    }


    // XXX Filter and REMOVE obsolete assurance entries
    // (The ones that are no longer present in OrgIdentity retrieved attributes)
    $assurance_to_delete = array_filter(
      $current_assurances,
      function($assurance, $mdl) use($new_assur_values) {
        return !in_array($assurance['Assurance']['value'], $new_assur_values);
      },
      ARRAY_FILTER_USE_BOTH
    );
    // Delete the certificates
    foreach($assurance_to_delete as $assurance) {
      // Soft Delete everything
      // Since i am using delete function, Changelog behaviour should work
      $this->delete($assurance['Assurance']['id']);
    }

    // XXX Import the non existing ones
    $current_assurances_list = Hash::combine($current_assurances,'{n}.Assurance.id', '{n}.Assurance.value', '{n}.Assurance.org_identity_id');
    $this->importAssurancesLoginOrgIdentity($active_login_orgs, $current_assurances_list, $new_assur_values);
  }

  /**
   * Import NEW Assurance values
   *
   * @param [string] $active_login_orgs        List of linked OrgIdentities
   * @param [string] $current_assurance_list   List of available Assurance values in the Registry
   * @param [string] $new_assurance_list       List of new Assurance values in the Environment
   */
  public function importAssurancesLoginOrgIdentity(
    $active_login_orgs,
    $current_assurance_list,
    $new_assurance_list
  ) {
    // List of OrgIdentity IDs with login identifier enabled
    $current_active_linked = Hash::extract($active_login_orgs, '{n}.OrgIdentity.id');
    // The list of OrgIdentities with at least one assurance
    $orgs_have_assurance = array_keys($current_assurance_list);

    foreach($current_active_linked as $org_id) {
      if(!in_array($org_id, $orgs_have_assurance)) {
        $current_assurance_list[$org_id] = array();
      }
    }

    // Extract the NEW Assurance values to import
    $assurances_to_import = array();
    foreach($current_assurance_list as $org_id => $curr_assurance_list) {
      foreach($new_assurance_list as $new_assurance_component) {
        if(!in_array($new_assurance_component, $curr_assurance_list)) {
          $assurances_to_import[$org_id][] = $new_assurance_component;
        }
      }
    }

    $this->log(__METHOD__ . "::Assurance values to Import => " . print_r($assurances_to_import, true), LOG_DEBUG);
    foreach($assurances_to_import as $org_id => $assurrance_values) {
      $data = array();
      foreach($assurrance_values as $v) {
        $data[] = array(
          'value' => $v,
          'type' => $this->defAssuranceType($v),
          'org_identity_id' => $org_id
        );
      }
      $this->saveMany($data);
      $this->clear();
    }
  }

  /**
   * @param  string $ass_value Assurance value
   * @return string
   */
  public function defAssuranceType($ass_value) {
    if(empty($ass_value)) {
      return '';
    }
   $type = AssuranceComponentEnum::AssuranceProfile;
    foreach(AssuranceComponentEnum::type as $t => $fname) {
      if(strpos($ass_value, "/" . $t . "/") !== false) {
       $type = $t;
      }
    }

    return $type;
  }

  /**
   * Check the value passed and assign
   *
   * @param array $options
   * @return bool
   */
  public function beforeSave($options = array())
  {
    if(!empty($this->data['Assurance']['value'])) {
      $this->data['Assurance']['value'] = trim($this->data['Assurance']['value']);
      // Check if this value is already in the database before saving
      $args = array();
      $args['conditions']['Assurance.value'] = trim($this->data['Assurance']['value']);
      $args['conditions']['Assurance.org_identity_id'] = $this->data["Assurance"]["org_identity_id"];
      $args['conditions'][] = 'Assurance.org_identity_id IS NOT NULL';
      $args['conditions'][] = 'NOT Assurance.deleted';
      $args['contain'] = false;

      $assurance_count = $this->find('count', $args);
      if($assurance_count > 0) {
        return false;
      }

      $this->data['Assurance']['type'] = $this->defAssuranceType($this->data['Assurance']['value']);
    }

    return true;
  }

}