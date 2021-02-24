<?php
/**
 * COmanage Registry Assurance Helper
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
 * @since         COmanage Registry v3.3.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

App::uses('AppHelper', 'View/Helper');

class AssuranceHelper extends AppHelper {

  /**
   * @param $assurance_payload
   */
  public function assuranceRulesEvaluate($assurance_payload) {

    // Evaluate IAP components
    $assurance_iap = Hash::extract($assurance_payload, '{n}[default=/\/IAP\//].default');
    $this->Assurance = ClassRegistry::init("Assurance");
    // Evaluate IAP components
    $assurance_payload = $this->Assurance->componentRefedsIAPEvaluate($assurance_iap, $assurance_payload);
    // Evaluate ATP components
    $assurance_atp = Hash::extract($assurance_payload, '{n}[default=/\/ATP\//].default');
    $assurance_payload = $this->Assurance->componentRefedsATPEvaluate($assurance_atp, $assurance_payload);

    return $assurance_payload;
  }
}