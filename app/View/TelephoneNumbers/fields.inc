<?php
/**
 * COmanage Registry Telephone Number Fields
 *
 * Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Determine if fields are editable
  $e = false;
  
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  $idForBreadcrumbs = '';

  // Migrate the target person from a named argument to a form field
  if(isset($this->request->params['named']['copersonroleid'])) {
    print $this->Form->hidden('co_person_role_id', array('default' => Sanitize::html($this->request->params['named']['copersonroleid']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->params['named']['copersonroleid']);
  } elseif(isset($this->request->data['TelephoneNumber']['co_person_role_id'])) {
    print $this->Form->hidden('co_person_role_id', array('default' => Sanitize::html($this->request->data['TelephoneNumber']['co_person_role_id']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->data['TelephoneNumber']['co_person_role_id']);
  } elseif(isset($this->request->params['named']['orgidentityid'])) {
    print $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->params['named']['orgidentityid']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->params['named']['orgidentityid']);
  } elseif(isset($this->request->data['TelephoneNumber']['org_identity_id'])) {
    print $this->Form->hidden('org_identity_id', array('default' => Sanitize::html($this->request->data['TelephoneNumber']['org_identity_id']))) . "\n";
    $idForBreadcrumbs = Sanitize::html($this->request->data['TelephoneNumber']['org_identity_id']);
  }

  if(isset($this->params['named']['co']))
    print $this->Form->hidden('co_id', array('default' => Sanitize::html($this->params['named']['co']))) . "\n";

  // Add breadcrumbs
  print $this->element("coCrumb");
  if(isset($this->request->params['named']['copersonroleid']) || isset($this->request->data['TelephoneNumber']['co_person_role_id'])) {
    // CO Person History
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'co_people';
    $args['action'] = 'index';
    $args['co'] = $cur_co['Co']['id'];
    $this->Html->addCrumb(_txt('me.population'), $args);
    
    $args = array(
      'controller' => 'co_person_roles',
      'action' => 'edit',
      $idForBreadcrumbs);
    $this->Html->addCrumb(_txt('ct.co_person_roles.1'), $args);
  } elseif(isset($this->request->params['named']['orgidentityid']) || isset($this->request->data['TelephoneNumber']['org_identity_id'])) {
    // Org ID History
    $args = array();
    $args['plugin'] = null;
    $args['controller'] = 'org_identities';
    $args['action'] = 'index';
    if(!$pool_org_identities) {
      $args['co'] = $cur_co['Co']['id'];
    }
    $this->Html->addCrumb(_txt('ct.org_identities.pl'), $args);
    
    $args = array(
      'controller' => 'orgIdentities',
      'action' => 'edit',
      $idForBreadcrumbs);
    $this->Html->addCrumb(_txt('ct.org_identities.1'), $args);
  }
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.telephone_numbers.1')));
  $this->Html->addCrumb($crumbTxt);

  $l = 1;
?>
<table id="<?php print $this->action; ?>_telephone_number" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.telephone_number.country_code'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('country_code') : Sanitize::html($telephone_numbers[0]['TelephoneNumber']['country_code'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.telephone_number.area_code'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('area_code') : Sanitize::html($telephone_numbers[0]['TelephoneNumber']['area_code'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.telephone_number.number'); ?><span class="required">*</span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('number', array('class' => 'focusFirst')) : Sanitize::html($telephone_numbers[0]['TelephoneNumber']['number'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.telephone_number.extension'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('extension') : Sanitize::html($telephone_numbers[0]['TelephoneNumber']['extension'])); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <?php print _txt('fd.type'); ?>
      </td>
      <td>
        <?php
          $attrs = array();
          $attrs['value'] = (isset($telephone_numbers) ? $telephone_numbers[0]['TelephoneNumber']['type'] : "");
          $attrs['empty'] = false;
          
          if($e) {
            print $this->Form->select('type',
                                      $vv_available_types,
                                      $attrs);
            
            if($this->Form->isFieldError('type')) {
              print $this->Form->error('type');
            }
          } else {
            print _txt('en.contact', null, $telephone_numbers[0]['TelephoneNumber']['type']);
          }
        ?>
      </td>
    </tr>
    <tr>
      <td>
        <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
      </td>
      <td>
        <?php
          if($e) {
            print $this->Form->submit($submit_label);
            print $this->Form->button(_txt('op.reset'), 
                                      array('type'=>'reset'));
          }
        ?>
      </td>
    </tr>
  </tbody>
</table>
<?php
  print $this->element("changelog");
