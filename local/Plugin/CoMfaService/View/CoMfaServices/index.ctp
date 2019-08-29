<?php
/**
 * COmanage Registry CO Service Tokens Index View
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

// Get a pointer to our model
$model = $this->name;
$req = Inflector::singularize($model);

// Include local js files
print $this->Html->script('/' . $req . '/js/comfaservice.js');
// Add breadcrumbs
print $this->element("coCrumb");

$this->Html->addCrumb(_txt('ct.co_mfa_services.pl'));

// Add page title
$params = array();
$params['title'] = _txt('ct.co_mfa_services.pl');

// Add top links
$params['topLinks'] = array();

print $this->element("pageTitleAndButtons", $params);

?>

<table id="co_mfa_services" class="ui-widget">
  <thead>
  <tr class="ui-widget-header">
    <th><?php print _txt('fd.co_mfa_services.mobile'); ?></th>
    <th><?php print _txt('fd.co_mfa_services.status'); ?></th>
    <th><?php print _txt('fd.co_mfa_services.actions'); ?></th>
  </tr>
  </thead>
  
  <tbody>
  <?php $i = 0; ?>
  <?php foreach ($vv_mobiles as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
        print "+" . filter_var($c['TelephoneNumber']['country_code'], FILTER_SANITIZE_SPECIAL_CHARS) . filter_var($c['TelephoneNumber']['number'], FILTER_SANITIZE_SPECIAL_CHARS);
        ?>
      </td>
      <td>
        <?php
        if(isset($c['CoMfaService']['verified']) && $c['CoMfaService']['verified']) {
          print _txt('pl.co_mfa_services.mobile.ok');
        } else {
          print _txt('pl.co_mfa_services.mobile.no');
        }
        ?>
      </td>
      <td>
        <!-- Create the form that we will append in the dialog -->
        <div id="formDialog" method="post" action="" style="display:none;">
          <form >
            <fieldset>
              <legend id="formLegend">Append the code and transmit to verify.</legend>
              <p>Code:&nbsp;<input id="codeText" type="text" name="code" /></p>
            </fieldset>
          </form>
        </div>
        <?php
        $txtkey = "";
        
        if(isset($c['CoMfaService']['verified']) && $c['CoMfaService']['verified']) {
          $txtkey = 'pl.co_mfa_services.confirm.replace';
        } else {
          $txtkey = 'pl.co_mfa_services.confirm';
        }
        
        // Find if the verification period expired
        $expired = true;
        if(isset($c['CoMfaService']) && $c['CoMfaService']['verified']) {
          $modifiedDate = new DateTime($c['CoMfaService']['modified']);
          $currentDate = new DateTime("now");
          $interval = date_diff($currentDate, $modifiedDate);
          $days = $interval->format('%R%a');
          $expired = ($vv_settings['CoMfaServiceSetting']['verify_expiration_period'] - $days <= 0) ? true : false;
        }
        $unconfigured = isset($vv_settings['CoMfaServiceSetting']['id']) ? false : true;

        $sendBtn =  '<button type="button" class="provisionbutton" title="' . _txt('pl.co_mfa_services.fetch')
          . '" onclick="javascript:js_confirm_generic(\''
          . _txt('pl.co_mfa_services.fetch_code') . '\',\''    // dialog body text
          . $this->Html->url(              // dialog confirm URL
            array(
              'plugin'       => 'co_mfa_service',
              'controller'   => 'co_mfa_services',
              'action'       => 'fetchCode',
              'mfasetting'   => isset($vv_settings['CoMfaServiceSetting']['id']) ? $vv_settings['CoMfaServiceSetting']['id'] : "",
              'phoneid'      => $c['TelephoneNumber']['id'],
              'copersonid'   => $vv_co_person_id,
              'coid'         => $this->request->params['named']['co']
            )
          ) . '\',\''
          . _txt('pl.co_mfa_services.fetch') . '\',\''    // dialog confirm button
          . _txt('op.co_mfa_services.cancel') . '\',\''    // dialog cancel button
          . _txt('pl.co_mfa_services.fetch') . '\',[\''   // dialog title
          . ''  // dialog body text replacement strings
          . '\']);"';
        if(!$expired){
          $sendBtn = $sendBtn . ' disabled';
        }
        $sendBtn = $sendBtn . '>' . _txt('pl.co_mfa_services.fetch') . '</button>';
        
        $verifyBtn =  '<button type="button" class="provisionbutton" title="' . _txt('pl.co_mfa_services.verify')
          . '" onclick="javascript:js_form_generic(\''
          . _txt($txtkey) . '\',\''    // dialog body text
          . $this->Html->url(              // dialog confirm URL
            array(
              'plugin'       => 'co_mfa_service',
              'controller'   => 'co_mfa_services',
              'action'       => 'verifyCode',
              'mfasetting'   => isset($vv_settings['CoMfaServiceSetting']['id']) ? $vv_settings['CoMfaServiceSetting']['id'] : "",
              'phoneid'      => $c['TelephoneNumber']['id'],
              'copersonid'   => $vv_co_person_id,
              'coid'         => $this->request->params['named']['co'],
              'otpid'        => $vv_otp_id,
            )
          ) . '\',\''
          . _txt('op.co_mfa_services.verify') . '\',\''    // dialog confirm button
          . _txt('op.co_mfa_services.cancel') . '\',\''    // dialog cancel button
          . _txt('pl.co_mfa_services.verify') . '\',[\''   // dialog title
          . ''  // dialog body text replacement strings
          . '\']);"';
          if(!$expired){
            $verifyBtn = $verifyBtn . ' disabled';
          }
          $verifyBtn = $verifyBtn . '>' . _txt('pl.co_mfa_services.verify') . '</button>';

        print $sendBtn;
        print $verifyBtn;
        ?>
      </td>
    </tr>
    <?php $i++; ?>
  <?php endforeach; ?>
  </tbody>
  
  <tfoot>
  <tr class="ui-widget-header">
    <th colspan="3">
    </th>
  </tr>
  </tfoot>
</table>