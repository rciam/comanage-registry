<?php
/**
 * COmanage Registry Certificate Index View
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
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

  // Determine if fields are editable
  $editable = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $editable = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$editable && !$permissions['view'])
    return(false);

  // We can emit all attributes because the ones we don't want should be null
  print $this->Form->hidden('org_identity_id', array('default' => $vv_pid['orgidentityid'])) . "\n";
  print $this->Form->hidden('co_person_id', array('default' => $vv_pid['copersonid'])) . "\n";

  if(isset($cur_co))
    print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";

  if(isset($this->params['named']['co']))
    print $this->Form->hidden('co_id', array('default' => filter_var($this->params['named']['co'],FILTER_SANITIZE_SPECIAL_CHARS))) . "\n";

  // Add breadcrumbs
  print $this->element("coCrumb", array('mvpa' => 'assurances'));
?>
<ul id="<?php print $this->action; ?>_assurance" class="fields form-list">
    <li>
        <span class="field-name">
            <?php print _txt('fd.assurance.value'); ?>
        </span>
        <span class="field-info">
            <?php print ($editable ? $this->Form->input('value', array('class' => 'focusFirst')) : filter_var($assurances[0]['Assurance']['value'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </span>
    </li>
    <li>
        <span class="field-name">
            <?php print _txt('fd.desc'); ?>
        </span>
         <span class="field-info">
            <?php print ($editable ? $this->Form->input('description', array('size' => '512')) : filter_var($assurances[0]['Assurance']['description'],FILTER_SANITIZE_SPECIAL_CHARS)); ?>
        </span>
    </li>
    <li>
        <span class="field-name">
          <?php print _txt('fd.assurance.type'); ?>
        </span>
        <span class="field-info">
        <?php
            global $cm_lang, $cm_texts;

            $attrs = array();
            $attrs['value'] = (isset($assurances) ? $assurances[0]['Assurance']['type'] : "");
            $attrs['empty'] = false;

            if($editable) {
                print $this->Form->select('type',
                                          AssuranceComponentEnum::type,
                                          $attrs);

                if($this->Form->isFieldError('type')) {
                    print $this->Form->error('type');
                }
            } else {
                print filter_var(AssuranceComponentEnum::type[ $assurances[0]['Assurance']['type'] ], FILTER_SANITIZE_SPECIAL_CHARS);
            }
        ?>
        </span>
    </li>
    <?php if($editable): ?>
        <li class="fields-submit">
            <span class="field-name">
                <span class="required"><?php print _txt('fd.req'); ?></span>
            </span>
            <span class="field-info">
                <?php print $this->Form->submit($submit_label); ?>
            </span>
        </li>
    <?php endif; ?>
</ul>
<?php
  print $this->element("changelog");

