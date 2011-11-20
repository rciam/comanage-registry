<!--
  /*
   * COmanage Gears CO Enrollment Flow Index View
   *
   * Version: $Revision$
   * Date: $Date$
   *
   * Copyright (C) 2011 University Corporation for Advanced Internet Development, Inc.
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
   */
-->
<h1 class="ui-state-default"><?php echo $title_for_layout; ?></h1>

<?php
  if($permissions['add'])
    echo $this->Html->link(_txt('op.add') . ' ' . _txt('ct.co_enrollment_flows.1'),
                           array('controller' => 'co_enrollment_flows', 'action' => 'add', 'co' => $this->params['named']['co']),
                           array('class' => 'addbutton')) . '
    <br />
    <br />
    ';
?>

<table id="cous" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort(_txt('fd.name'), 'name'); ?></th>
      <th><?php echo $this->Paginator->sort(_txt('fd.status'), 'status'); ?></th>
      <th><?php echo _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_enrollment_flows as $c): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          echo $html->link($c['CoEnrollmentFlow']['name'],
                           array('controller' => 'co_enrollment_flows',
                                 'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $c['CoEnrollmentFlow']['id'], 'co' => $this->params['named']['co']));
        ?>
      </td>
      <td><?php echo Sanitize::html($c['CoEnrollmentFlow']['status']); ?></td>
      <td>
        <?php
          if($permissions['edit'])
            echo $html->link(_txt('op.edit'),
                             array('controller' => 'co_enrollment_flows', 'action' => 'edit', $c['CoEnrollmentFlow']['id'], 'co' => $this->params['named']['co']),
                             array('class' => 'editbutton')) . "\n";
            
          if($permissions['delete'])
            echo '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($c['CoEnrollmentFlow']['name'])) . '\', \'' . $html->url(array('controller' => 'co_enrollment_flows', 'action' => 'delete', $c['CoEnrollmentFlow']['id'], 'co' => $this->params['named']['co'])) . '\')";>' . _txt('op.delete') . '</button>';
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="3">
        <?php echo $this->Paginator->numbers(); ?>
      </td>
    </tr>
  </tfoot>
</table>