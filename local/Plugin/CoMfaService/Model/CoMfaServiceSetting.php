<?php

class CoMfaServiceSetting extends AppModel {
  // Define class name for cake
  public $name = "CoMfaServiceSetting";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable');
  
  // Association rules from this model to other models
  public $belongsTo = array(
    // Inverse relation is set in Controller
    "CoMfaService"
  );
  
  // Default display field for cake generated views
  public $displayField = "co_mfa_service_id";
  
  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'from' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'text' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'code_length' => array(
      'numeric' => array(
        'rule' => array('decimal'),
        'message' => 'Please enter only numbers',
        'allowEmpty' => false,
        'required' => true,
      )
    ),
    'ttl' => array(
      'numeric' => array(
        'rule' => array('decimal'),
        'message' => 'Please enter only numbers',
        'allowEmpty' => false,
        'required' => true,
      )
    ),
    'max_verification_attemps' => array(
      'numeric' => array(
        'rule' => array('decimal'),
        'message' => 'Please enter only number',
        'allowEmpty' => false,
        'required' => true,
      )
    ),
    'verify_expiration_period' => array(
      'numeric' => array(
        'rule' => array('decimal'),
        'message' => 'Please enter number of days to expiration',
        'allowEmpty' => true,
        'required' => false,
      )
    ),
    'utf' => array(
      'rule' => 'notBlank',
      'required' => false,
      'allowEmpty' => true
    ),
    'api_key' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'api_secret' => array(
      'rule' => 'notBlank',
      'required' => true,
      'allowEmpty' => false
    ),
    'url' => array(
      'rule' => 'url',
      'required' => true,
      'allowEmpty' => false
    ),
  );
}