<?php
/**
 * COmanage Registry VO Index View
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

  $params = array('title' => _txt('ct.vos.pl'));
  print $this->element("pageTitle", $params);
?>

<div class="table-container">
  <table id="vos">
    <thead>
      <tr>
        <th><?php print $this->Paginator->sort('vo_name', _txt('fd.vos.vo_name')); ?></th>
        <!-- XXX Following needs to be I18N'd, and also render a full name, if index view sticks around -->
        <th><?php print $this->Paginator->sort('OrgIdentity.PrimaryName.family', 'Org Identity'); ?></th>
        <th><?php print $this->Paginator->sort('CoPerson.PrimaryName.family', 'CO Person'); ?></th>
        <th><?php print _txt('fd.actions'); ?></th>
      </tr>
    </thead>

    <tbody>
    <?php $i = 0; ?>
    <?php foreach ($vos as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link($c['Vo']['vo_targets_id'],
                                 array('controller' => 'vos',
                                       'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $c['Vo']['id']));
        ?>
      </td>
      <td>
        <?php
          if(!empty($c['Vo']['org_identity_id']))
          {
            if($permissions['view'])
              print $this->Html->link(generateCn($c['OrgIdentity']['PrimaryName']),
                                     array('controller' => 'org_identities', 'action' => 'view', $c['OrgIdentity']['id'])) . "\n";
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($c['Vo']['co_person_id']))
          {
            if($permissions['view'])
              print $this->Html->link(generateCn($c['CoPerson']['PrimaryName']),
                                     array('controller' => 'co_people', 'action' => 'view', $c['CoPerson']['id'])) . "\n";
          }
        ?>
      </td>    
      <td>    
        <?php
          if($permissions['edit']) {
            print $this->Html->link('Edit',
                array('controller' => 'vos', 'action' => 'edit', $c['Vo']['id']),
                array('class' => 'editbutton')) . "\n";
          }

          if($permissions['delete']) {
            print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
              . '" onclick="javascript:js_confirm_generic(\''
              . _txt('js.remove') . '\',\''    // dialog body text
              . $this->Html->url(              // dialog confirm URL
                array(
                  'controller' => 'vos',
                  'action' => 'delete',
                  $c['Vo']['id']
                )
              ) . '\',\''
              . _txt('op.remove') . '\',\''    // dialog confirm button
              . _txt('op.cancel') . '\',\''    // dialog cancel button
              . _txt('op.remove') . '\',[\''   // dialog title
              . filter_var(_jtxt($c['Vo']['vo_targets_id']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
              . '\']);">'
              . _txt('op.delete')
              . '</button>';
          }
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>

  <?php
    print $this->element("pagination");
