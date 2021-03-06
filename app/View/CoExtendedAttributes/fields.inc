<?php
/**
 * COmanage Registry CO Extended Attributes Fields
 *
 * Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-12 University Corporation for Advanced Internet Development, Inc.
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

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_extended_attributes';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('ct.co_extended_attributes.pl'), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_extended_attributes.1')));
  $this->Html->addCrumb($crumbTxt);

  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
?>
<table id="<?php print $this->action; ?>_co_extended_attribute" class="ui-widget">
  <tbody>
    <tr class="line1">
      <td>
        <?php print _txt('fd.name'); ?><span class="required">*</span><br />
        <em><?php print _txt('fd.lan.desc'); ?></em>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('name', array('class' => 'focusFirst')) : Sanitize::html($co_extended_attributes[0]['CoExtendedAttribute']['name'])); ?>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('fd.name.d'); ?><span class="required">*</span>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('display_name') : Sanitize::html($co_extended_attributes[0]['CoExtendedAttribute']['display_name'])); ?>
      </td>
    </tr>
    <tr class="line1">
      <td>
        <?php print _txt('fd.type'); ?><span class="required">*</span>
      </td>
      <td>
        <?php
          global $cm_lang, $cm_texts;
          $attrs['value'] = (isset($co_extended_attributes) ? $co_extended_attributes[0]['CoExtendedAttribute']['type'] : "");
          $attrs['empty'] = false;
          
          if($e && $this->action=='add') {
            print $this->Form->select('type',
                                      $cm_texts[ $cm_lang ]['en.extattr'],
                                      $attrs);
            
            if($this->Form->isFieldError('type')) {
              print $this->Form->error('type');
            }
          } else {
            print _txt('en.extattr', null, $co_extended_attributes[0]['CoExtendedAttribute']['type']);
          }
          
          // Emit data type to make validation work
          if($e && $this->action=='edit')
            print $this->Form->hidden('type');
        ?>
        <p>
          <span class="ui-icon ui-icon-info co-info"></span>
          <em><?php print _txt('fd.type.warn'); ?></em>
        </p>
      </td>
    </tr>
    <tr class="line2">
      <td>
        <?php print _txt('fd.index'); ?>
      </td>
      <td>
        <?php print ($e ? $this->Form->input('indx') : ($co_extended_attributes[0]['CoExtendedAttribute']['type'] ? _txt('fd.yes') : _txt('fd.no'))); ?>
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
