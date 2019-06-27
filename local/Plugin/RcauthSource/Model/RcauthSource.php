<?php

class RcauthSource extends AppModel {
	// Required by COmanage Plugins
	public $cmPluginType = "orgidsource";

	public $uses = array("Cert");

	// Document foreign keys
	public $cmPluginHasMany = array();

	// Association rules from this model to other models
	public $belongsTo = array("OrgIdentitySource");

	// Default display field for cake generated views
	public $displayField = "description";

	// Validation rules for table elements
	public $validate = array(
		'org_identity_source_id' => array(
			'rule' => 'numeric',
			'required' => true,
			'message' => 'An Org Identity Source ID must be provided'
		),
		'clientid' => array(
			'rule' => 'notBlank',
			'required' => true,
			'allowEmpty' => false
		),
		'client_secret' => array(
			'rule' => 'notBlank',
			'required' => true,
			'allowEmpty' => false
		),
		'access_token' => array(
			'rule' => 'notBlank',
			'required' => false,
			'allowEmpty' => true
		),
		'refresh_token' => array(
			'rule' => 'notBlank',
			'required' => false,
			'allowEmpty' => true
		),
		'id_token' => array(
			'rule' => 'notBlank',
			'required' => false,
			'allowEmpty' => true
		),
		'token_type' => array(
			'rule' => array('inList', array('Bearer$')),
			'required' => false,
			'allowEmpty' => true,
			'message' => 'Currently only Bearer token type is supported.'
		),
		'idphint' => array(
			'rule' => array('url', true),
			'required' => false,
			'allowEmpty' => true
		),
		'mp_oa2_server' => array(
			'rule' => array('url', true),
			'required' => false,
			'allowEmpty' => true
		),
		'issuer' => array(
			'content' => array(
				'rule' => array('maxLength', 400),
				'required' => false,
				'allowEmpty' => false,
				'message' => 'Please enter a valid cert issuer DN',
			),
			'filter' => array(
				'rule' => array('validateInput'),
			),
		)
	);

	/**
	 * Actions to take before a save operation is executed.
	 *
	 * @since  COmanage Registry v2.0.0
	 * @return Boolean
	 */
	public function beforeSave($options = array()) {
		$this->log("@beforeSave: ",LOG_DEBUG);
		// Check if the user provided a value
		if(!empty($this->data['RcauthSource']['issuer'])){
			// TODO maybe copy the issuer in certain certificate rows
		}
		return true;
	}

	/**
	 * Expose menu items.
	 *
	 * @ since COmanage Registry v2.0.0
	 * @ return Array with menu location type as key and array of labels, controllers, actions as values.
	 */
	public function cmPluginMenus() {
		$this->log("@cmPluginMenus:",LOG_DEBUG);
		return array();
	}
}
