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
 * @since         COmanage Registry v3.1.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class Cert extends AppModel {
  // Define class name for cake
  public $name = "Cert";

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
      // A Certificate may be attached to a CO Person Role
      "CoPerson",
      // A Certificate may be attached to an Org Identity
      "OrgIdentity",
  );

  // Default display field for cake generated views
  public $displayField = "Cert.subject";

  // Default ordering for find operations
  public $order = array("Cert.subject");

  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'subject' => array(
      'content' => array(
        'rule' => array('maxLength', 512),
        'required' => false,
        'allowEmpty' => false,
        'message' => 'Please enter a valid cert subject DN',
      ),
      'filter' => array(
        'rule' => array('validateInput'),
      ),
    ),
    'issuer' => array(
      'content' => array(
        'rule' => array('maxLength', 512),
        'required' => false,
        'allowEmpty' => true,
        'message' => 'Please enter a valid cert issuer DN',
      ),
      'filter' => array(
        'rule' => array('validateInput'),
      ),
    ),
    'type' => array(
      'content' => array(
        'rule' => array('validateExtendedType',
          array('attribute' => 'Cert.type',
            'default' => array(CertEnum::X509))),
        'required' => false,
        'allowEmpty' => false,
      ),
    ),
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
    'ordr' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    )
  );

  /**
   * Get Certificate Subject DN attribute map
   * Get Certificate Issuer DN attribute map
   *
   * @return string[]
   * @example ['distinguishedName', 'voPersonCertificateIssuerDn']
   */
  public function getEnvCertMapping() {
    $issuer_env_dn = "";
    $subject_env_dn = "";
    $this->CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
    $env_attributes = $this->CmpEnrollmentConfiguration->enrollmentAttributesFromEnv();
    if(!empty($env_attributes)) {
      // Get issuer env attribute
      $issuer_found = array_filter(
        $env_attributes,
        static function($value) {
          return ($value['attribute'] === 'certs:issuer');
        }
      );
      if (!empty($issuer_found)) {
        $issuer_found = reset($issuer_found);
        $issuer_env_dn = !empty($issuer_found['env_name']) ? $issuer_found['env_name'] : "";
      }
      // Get subject env attribute
      $subject_found = array_filter(
        $env_attributes,
        static function($value) {
          return ($value['attribute'] === 'certs:subject');
        }
      );
      if (!empty($subject_found)) {
        $subject_found = reset($subject_found);
        $subject_env_dn = !empty($subject_found['env_name']) ? $subject_found['env_name'] : "";
      }
    }

    return array($subject_env_dn,$issuer_env_dn);
  }


  /**
   * Get Certificate Subject DN Environment Value
   * Get Certificate Issuer DN Environment Value
   *
   * @return string[]
   * @example ['/CN /O ...', '/CN /O ...']
   */
  public function getEnvValues() {
    // Get Certificate SDN and IDN mappings
    $subject_dn_value = "";
    $issuer_dn_value = "";
    list($subject_dn_map, $issuer_dn_map) = $this->getEnvCertMapping();
    // Get Subject DN env value if available
    if(!empty($subject_dn_map)) {
      $subject_dn_value = getenv($subject_dn_map);
      $subject_dn_value = !empty($subject_dn_value) ? $subject_dn_value : "";
    }
    // Get Issuer DN env value if available
    if(!empty($issuer_dn_map)) {
      $issuer_dn_value = getenv($issuer_dn_map);
      $issuer_dn_value = !empty($issuer_dn_value) ? $issuer_dn_value : "";
    }

    return array($subject_dn_value, $issuer_dn_value);
  }

  /**
   * Check if the envarinmental attribute has value. If this is the case check if it is single valued or multi valued
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
   * Decide whether we will consume the available Certificate Subject and Issuer DN attributes
   * As a general rulle, if Certificate Subject Dn is MULTI valued ignore Subject Issuer Dn
   *
   * @ticket RCIAM-492
   * @return bool
   */
  public function consumeDecideVoPersonCertAttr() {
    // continue = true, means that:
    // 1. we will skip ISSUER handling during Enrollment Flow
    // 2. we will skip ISSUER updated value during login
    $skip_issuer_dn_import = false;
    $issuer_is_multi = false;
    $subject_is_multi = false;

    // Get Certificate SDN and IDN values
    list($subject_value, $issuer_value) = $this->getEnvValues();
    // Subject DN value type
    $subject_is_multi = $this->isEnvMultiVal($subject_value);
    // Issuer DN value type
    $issuer_is_multi = $this->isEnvMultiVal($issuer_value);

    if((!is_null($subject_is_multi) && $subject_is_multi)
       || (!is_null($issuer_is_multi) && $issuer_is_multi)) {
      $skip_issuer_dn_import = true;
    }

    return $skip_issuer_dn_import;
  }

  /**
   * Find Certificates linked to Organizational Identities
   *
   * @param string $identifier
   * @return array|int|null
   */
  public function getCertsByOrgIdentityIdentifier($identifier) {
    // Get the Certificates associated with the provided identifier
    $args = array();
    $args['joins'][0]['table'] = 'cm_identifiers';
    $args['joins'][0]['alias'] = 'Identifier';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'Identifier.org_identity_id=Cert.org_identity_id';
    $args['joins'][1]['table'] = 'cm_org_identities';
    $args['joins'][1]['alias'] = 'OrgIdentity';
    $args['joins'][1]['type'] = 'INNER';
    $args['joins'][1]['conditions'][0] = 'OrgIdentity.id=Cert.org_identity_id';
    $args['conditions']['Identifier.identifier'] = $identifier;
    $args['conditions']['Identifier.login'] = true;   // Make this login to avoid any conflict with RCAuth
    $args['conditions']['Identifier.status'] = SuspendableStatusEnum::Active;
    $args['conditions'][] = 'Identifier.org_identity_id IS NOT NULL';
    $args['conditions'][] = 'Cert.org_identity_id IS NOT NULL';
    $args['conditions'][] = 'NOT Cert.deleted';
    $args['contain'] = false;

    $certs = $this->find('all', $args);

    return $certs;
  }

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
   * @param string $identifier      OrgIdentity Identifier constructed from the IdP
   */
  public function syncByIdentifier($identifier) {
    $current_certs = $this->getCertsByOrgIdentityIdentifier($identifier);
    $active_login_orgs = $this->getOrgIdentityByIdentifier($identifier);
    list($subject_dn_env, $issuer_dn_env) = $this->getEnvValues();
    // XXX Is the subject DN multi valued?
    $is_sdn_multi_val = $this->isEnvMultiVal($subject_dn_env);

    // REMOVE CERTIFICATES

    // XXX New Certificate list is empty
    if(is_null($is_sdn_multi_val)) {
      foreach($current_certs as $cert) {
        // Soft Delete everything
        // Since i am using delete function, Changelog behaviour should work
        $this->delete($cert['Cert']['id']);
      }
      // Cleared everything, return
      return;
    }

    // XXX The Subject DN is MULTI valued
    $new_sdns = array();
    if($is_sdn_multi_val) {
      $new_sdns = explode(';', $subject_dn_env);
    } else {
      $new_sdns[] = $subject_dn_env;
    }

    // XXX Filter and REMOVE obsolete certificates
    // (The ones that are no longer present in OrgIdentity retrieved attributes)
    $certs_to_delete = array_filter(
      $current_certs,
      function($cert, $mdl) use($new_sdns) {
        return !in_array($cert['Cert']['subject'], $new_sdns);
      },
      ARRAY_FILTER_USE_BOTH
    );
    // Delete the certificates
    foreach($certs_to_delete as $cert) {
      // Soft Delete everything
      // Since i am using delete function, Changelog behaviour should work
      $this->delete($cert['Cert']['id']);
    }

    // ADD/UPDATE CERTIFICATES

    // Current Subject DNs
    $current_sdn_list = Hash::extract($current_certs, '{n}.Cert.subject');

    // XXX The Subject DN is MULTI valued
    if($is_sdn_multi_val) {
      $this->log(__METHOD__ . "::Multi valued Subject DN", LOG_DEBUG);
      $this->importCertsLoginOrgIdentity($active_login_orgs, $current_sdn_list, $new_sdns);
    } else {
      // XXX Currently, we can not handle MULTI value Issuer DN.
      // Return if we have no Issuer or it is MULTI valued
      if(is_null($this->isEnvMultiVal($issuer_dn_env))
         || $this->isEnvMultiVal($issuer_dn_env)) {
        $this->log(__METHOD__ . "::Multi valued Issuer DN", LOG_DEBUG);
        return;
      }
      // XXX The subject DN is single valued
      // Are there existing Certs to update?
      $certs_to_update = array_filter(
        $current_certs,
        function($cert, $mdl) use($new_sdns) {
          return in_array($cert['Cert']['subject'], $new_sdns);
        },
        ARRAY_FILTER_USE_BOTH
      );
      foreach($certs_to_update as $exist_cert) {
        $cert_data = array(
          'id' => $exist_cert['Cert']['id'],
          'issuer' => $issuer_dn_env,
          'subject' => $subject_dn_env,
        );
        $this->save($cert_data);
        $this->clear();
      }

      // Import Non Existing Certificates
      $this->importCertsLoginOrgIdentity($active_login_orgs, $current_sdn_list, $new_sdns, $issuer_dn_env);
    } // Single valued Subject DN
  }


  /**
   * Import NEW Certificates
   *
   * @param array         $active_login_orgs  List of linked OrgIdentities
   * @param array         $current_sdn_list   List of available Certificate Subject DNs in the Registry
   * @param array         $new_sdn_list       List of available Certificate Subject DNs in the Environment
   * @param null|string   $issuer_dn_env
   */
  public function importCertsLoginOrgIdentity(
    $active_login_orgs,
    $current_sdn_list,
    $new_sdn_list,
    $issuer_dn_env = null
  ) {
    // List of OrgIdentity IDs with login identifier enabled
    $current_active_linked = Hash::extract($active_login_orgs, '{n}.OrgIdentity.id');

    // Extract the NEW Certificates to import
    $certs_to_import = array_filter(
      $new_sdn_list,
      function($new_subject_dn) use($current_sdn_list) {
        return !in_array($new_subject_dn, $current_sdn_list);
      }
    );
    // Add the new Certificate Subject DNs under every Organizational Identity
    $this->log(__METHOD__ . "::Certificates to Import => " . print_r($certs_to_import, true), LOG_DEBUG);
    foreach($certs_to_import as $sdn) {
      $data = array();
      foreach($current_active_linked as $org_ident) {
        $data[] = array(
          'subject' => $sdn,
          'org_identity_id' => $org_ident
        );
        if(!is_null($issuer_dn_env)) {
          $key = key($data);
          $data[$key]['issuer'] = $issuer_dn_env;
        }
      }
      $this->saveMany($data);
      $this->clear();
    }
  }

  /**
   * Check if the field passed is empty and fill in with the default value
   *
   * @param array $options
   * @return bool
   */
  public function beforeSave($options = array())
  {
    if(count($options["fieldList"]) === 1
       && $options["fieldList"][0] === 'ordr') {
      return true;
    }

    if(empty($this->data['Cert']['type'])) {
      // Assign the default value
      $this->data['Cert']['type'] = CertEnum::X509;
    }
    if(!empty($this->data['Cert']['issuer'])) {
      $this->data['Cert']['issuer'] = trim($this->data['Cert']['issuer']);
    }
    $this->data['Cert']['subject'] = trim($this->data['Cert']['subject']);

    if(empty($this->data['Cert']['ordr'])
       || $this->data['Cert']['ordr'] == '') {
      // Find the current high value and add one
      $n = 1;

      // I know the CO Person username
      $u = !empty($_SESSION["Auth"]["User"]["username"]) ? $_SESSION["Auth"]["User"]["username"] : null;
      if(!empty($u)) {
        $oargs = array();
        $oargs['joins'][0]['table'] = 'identifiers';
        $oargs['joins'][0]['alias'] = 'Identifier';
        $oargs['joins'][0]['type'] = 'INNER';
        $oargs['joins'][0]['conditions'][0] = 'OrgIdentity.id=Identifier.org_identity_id';
        $oargs['conditions']['Identifier.identifier'] = $u;
        $oargs['conditions']['Identifier.login'] = true;
        // Join on identifiers that aren't deleted (including if they have no status)
        $oargs['conditions']['OR'][] = 'Identifier.status IS NULL';
        $oargs['conditions']['OR'][]['Identifier.status <>'] = SuspendableStatusEnum::Suspended;
        // As of v2.0.0, OrgIdentities have validity dates, so only accept valid dates (if specified)
        // Through the magic of containable behaviors, we can get all the associated
        $oargs['conditions']['AND'][] = array(
          'OR' => array(
            'OrgIdentity.valid_from IS NULL',
            'OrgIdentity.valid_from < ' => date('Y-m-d H:i:s', time())
          )
        );
        $oargs['conditions']['AND'][] = array(
          'OR' => array(
            'OrgIdentity.valid_through IS NULL',
            'OrgIdentity.valid_through > ' => date('Y-m-d H:i:s', time())
          )
        );
        // data we need in one clever find
        $oargs['contain'][] = 'Cert';

        $this->OrgIdentity = ClassRegistry::init('OrgIdentity');
        $orgIdentities = $this->OrgIdentity->find('all', $oargs);

        $cert_ordering = Hash::extract($orgIdentities, '{n}.Cert.{n}.ordr');
        if(!empty($cert_ordering)) {
          rsort($cert_ordering);
          $n = (int)current($cert_ordering) + 1;
        }
      } else {
        $args = array();
        $args['fields'][] = "MAX(ordr) as m";
        $args['conditions']['Cert.org_identity_id'] = $this->data['Cert']['org_identity_id'];
        $args['order'][] = "m";

        $o = $this->find('first', $args);

        if(!empty($o[0]['m'])) {
          $n = $o[0]['m'] + 1;
        }
      }

      $this->data['Cert']['ordr'] = $n;
    }

    return true;
  }

}
