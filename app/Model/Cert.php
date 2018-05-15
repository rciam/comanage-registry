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

class Cert extends AppModel {
    // Define class name for cake
    public $name = "Cert";

    // Current schema version for API
    public $version = "1.0";

    // Add behaviors
    public $actsAs = array('Containable',
        'Normalization' => array('priority' => 4),
        'Provisioner',
        'Changelog' => array('priority' => 5));

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
                'rule' => array('maxLength', 256),
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
                'rule' => array('maxLength', 256),
                'required' => false,
                'allowEmpty' => false,
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
    );
}
