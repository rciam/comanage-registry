<?php
/**
 * COmanage Registry CO Service Token Setting Index View
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

$e = false;

if($this->action == "configure" && $permissions['configure'])
  $e = true;

// Add breadcrumbs
print $this->element("coCrumb");

$this->Html->addCrumb(_txt('ct.co_mfa_service_settings.pl'));

// Add page title
$params = array();
$params['title'] = _txt('ct.co_mfa_service_settings.pl');

// Add top links
$params['topLinks'] = array();

print $this->element("pageTitleAndButtons", $params);

print $this->Form->create('CoMfaServiceSetting',
    array('url' => array('action' => 'configure', 'co' => $cur_co['Co']['id']),
          'inputDefaults' => array('label' => false, 'div' => false))) . "\n";
print $this->Form->hidden('CoMfaServiceSetting.co_id', array('default' => $cur_co['Co']['id'])) . "\n";

?>

<div class="co-info-topbox">
  <i class="material-icons">info</i>
  <?php print _txt('ct.co_mfa_service_setting.1'); ?>
</div>
<ul id="<?php print $this->action; ?>_co_mfa_service_setting" class="fields form-list">
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.co_mfa_service_settings.from'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.co_mfa_service_settings.from.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      $value = isset($co_mfa_service_settings[0]['CoMfaServiceSetting']) ? $co_mfa_service_settings[0]['CoMfaServiceSetting']['from'] : "";
      print $this->Form->input('from', array('size' => 50,
                                             'value' => $value));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.co_mfa_service_settings.text'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.co_mfa_service_settings.text.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      $value = isset($co_mfa_service_settings[0]['CoMfaServiceSetting']) ? $co_mfa_service_settings[0]['CoMfaServiceSetting']['text'] : "";
      print $this->Form->input('text', array('size' => 50,
                                             'value' => $value));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.co_mfa_service_settings.codeLength'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.co_mfa_service_settings.codeLength.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      $value = isset($co_mfa_service_settings[0]['CoMfaServiceSetting']) ? $co_mfa_service_settings[0]['CoMfaServiceSetting']['code_length'] : "";
      print $this->Form->input('code_length', array('size' => 50,
                                                    'value' => $value));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.co_mfa_service_settings.ttl'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.co_mfa_service_settings.ttl.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      $value = isset($co_mfa_service_settings[0]['CoMfaServiceSetting']) ? $co_mfa_service_settings[0]['CoMfaServiceSetting']['ttl'] : "";
      print $this->Form->input('ttl', array('size' => 50,
                                            'value' => $value));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.co_mfa_service_settings.maxVerificationAttemps'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.co_mfa_service_settings.maxVerificationAttemps.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      $value = isset($co_mfa_service_settings[0]['CoMfaServiceSetting']) ? $co_mfa_service_settings[0]['CoMfaServiceSetting']['max_verification_attemps'] : "";
      print $this->Form->input('max_verification_attemps', array('size' => 50,
                                                                 'value' => $value));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.co_mfa_service_settings.utf'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.co_mfa_service_settings.utf.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      print ( $this->Form->select('utf',
        CoMfaServiceSettingsAttrEnum::utf,
        array(
          'empty' => '--',
          'value' => isset($co_mfa_service_settings[0]['CoMfaServiceSetting']['utf']) ? $co_mfa_service_settings[0]['CoMfaServiceSetting']['utf']
                                                                                      : ""
        )
      )
      );
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.co_mfa_service_settings.url'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.co_mfa_service_settings.url.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      $value = isset($co_mfa_service_settings[0]['CoMfaServiceSetting']) ? $co_mfa_service_settings[0]['CoMfaServiceSetting']['url'] : "";
      print $this->Form->input('url', array('size' => 50,
                                            'value' => $value));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.co_mfa_service_settings.api_key'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.co_mfa_service_settings.api_key.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      $value = isset($co_mfa_service_settings[0]['CoMfaServiceSetting']) ? $co_mfa_service_settings[0]['CoMfaServiceSetting']['api_key'] : "";
      print $this->Form->input('api_key', array('size' => 50,
                                                'value' => $value));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.co_mfa_service_settings.api_secret'); ?>
        <span class="required">*</span>
      </div>
      <div class="field-desc"><?php print _txt('pl.co_mfa_service_settings.api_secret.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      $value = isset($co_mfa_service_settings[0]['CoMfaServiceSetting']) ? $co_mfa_service_settings[0]['CoMfaServiceSetting']['api_secret'] : "";
      print $this->Form->input('api_secret', array('size' => 50,
                                                   'value' => $value));
      ?>
    </div>
  </li>
  <li>
    <div class="field-name">
      <div class="field-title">
        <?php print _txt('pl.co_mfa_service_settings.expire'); ?>
      </div>
      <div class="field-desc"><?php print _txt('pl.co_mfa_service_settings.expire.desc'); ?></div>
    </div>
    <div class="field-info">
      <?php
      $value = isset($co_mfa_service_settings[0]['CoMfaServiceSetting']) ? $co_mfa_service_settings[0]['CoMfaServiceSetting']['verify_expiration_period'] : "";
      print $this->Form->input('verify_expiration_period', array('size' => 50,'value' => $value));
      ?>
    </div>
  </li>
  <?php if($e): ?>
    <li class="fields-submit">
      <div class="field-name">
        <span class="required"><?php print _txt('fd.req'); ?></span>
      </div>
      <div class="field-info">
        <?php
          $options = array('style' => 'float:left;');
          $submit_label = _txt('op.save');
          print $this->Form->submit($submit_label, $options);
          print $this->Form->end();
        ?>
      </div>
    </li>
  <?php endif; ?>
</ul>
