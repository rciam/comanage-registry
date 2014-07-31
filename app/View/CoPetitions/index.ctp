<!--
/**
 * COmanage Registry CO Petition Index View
 *
 * Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2012-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.5
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */
-->
<script>
  $(function() {
    $( "#statusfilterdialog" ).dialog({
      autoOpen: false,
      height: 300,
      width: 350,
      height: 265,
      modal: true
    });

    $( "#statusfilter" ).click(function() {
      $( "#statusfilterdialog" ).dialog( "open" );
    });
  });
</script>

<style>
  #filters {
    width: 50%;
    margin: 0 0 0 2px;
  }

  #statusfilter {
    overflow: hidden;
  }

  #statusfilter .input>label {
    float: left;
  }
</style>

<?php
// Globals
global $cm_lang, $cm_texts;

  $params = array('title' => $cur_co['Co']['name'] . ' ' . _txt('ct.petitions.pl'));
  print $this->element("pageTitle", $params);

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.petitions.pl'));
  
  if($permissions['add']) {
    print $this->Html->link(_txt('op.enroll'),
                            array('controller' => 'co_enrollment_flows', 'action' => 'select', 'co' => $cur_co['Co']['id']),
                            array('class' => 'addbutton'));    
  }
?>

<button id="statusfilter" class = "searchbutton">
  <?php print _txt('op.filter.status');?>
</button>

<div id="statusfilterdialog" title="<?php print _txt('op.filter.status.by'); ?>">
  <?php
    print $this->Form->create('CoPetition', array('action'=>'search'));
    print $this->Form->hidden('CoPetition.co_id', array('default' => $cur_co['Co']['id'])). "\n";
    
    // Build array of options based on model validation
    $statusOptions = array_keys($cm_texts[ $cm_lang ]['en.status']);

    foreach ($statusOptions as $s) {
      $searchOptions[ $s ] = $cm_texts[ $cm_lang ]['en.status'][ $s ];
    }

    // Build array to check off actively used filters on the page
    $selected = array();
    if(isset($this->passedArgs['search.status'])) {
      foreach($this->passedArgs['search.status'] as $a) {
        $selected[] = $a;
      }
    }
    
    // Collect parameters and print checkboxes
    $formParams = array('options'  => $searchOptions,
                        'multiple' => 'checkbox',
                        'label'    => false,
                        'selected' => $selected);
    print $this->Form->input('search.status', $formParams);
    
    print $this->Form->submit(_txt('op.search')); 
    print $this->Form->end();
  ?>
</div>

<table id="co_people" class="ui-widget">
  <thead>
    <tr class="ui-widget-header">
      <th><?php echo $this->Paginator->sort('EnrolleeCoPerson.Name.family', _txt('fd.enrollee')); ?></th>
      <th><?php echo $this->Paginator->sort('Cou.name', _txt('fd.cou')); ?></th>
      <th><?php echo $this->Paginator->sort('PetitionerCoPerson.Name.family', _txt('fd.petitioner')); ?></th>
      <th><?php echo $this->Paginator->sort('SponsorCoPerson.Name.family', _txt('fd.sponsor')); ?></th>
      <th><?php echo $this->Paginator->sort('ApproverCoPerson.Name.family', _txt('fd.approver')); ?></th>
      <th><?php echo $this->Paginator->sort('status', _txt('fd.status')); ?></th>
      <th><?php echo $this->Paginator->sort('created', _txt('fd.created')); ?></th>
      <th><?php echo $this->Paginator->sort('modified', _txt('fd.modified')); ?></th>
      <th><?php echo _txt('fd.actions'); ?></th>
    </tr>
  </thead>
  
  <tbody>
    <?php $i = 0; ?>
    <?php foreach ($co_petitions as $p): ?>
    <tr class="line<?php print ($i % 2)+1; ?>">
      <td>
        <?php
          print $this->Html->link(generateCn($p['EnrolleeCoPerson']['PrimaryName']),
                                  array(
                                    'controller' => 'co_petitions',
                                    'action' => ($permissions['edit']
                                                 ? 'view'
                                                 : ($permissions['view'] ? 'view' : '')),
                                    $p['CoPetition']['id'])
                                  );
        ?>
      </td>
      <td>
        <?php if(!empty($p['Cou']['name'])) { print $p['Cou']['name']; } ?>
      </td>
      <td>
        <?php
          if(isset($p['PetitionerCoPerson']['id']) && $p['PetitionerCoPerson']['id'] != '') {
            print $this->Html->link(generateCn($p['PetitionerCoPerson']['PrimaryName']),
                                    array(
                                      'controller' => 'co_people',
                                      'action' => 'canvas',
                                      $p['PetitionerCoPerson']['id'])
                                    );
          }
        ?>
      </td>
      <td>
        <?php
          if(isset($p['SponsorCoPerson']['id']) && $p['SponsorCoPerson']['id'] != '') {
            print $this->Html->link(generateCn($p['SponsorCoPerson']['PrimaryName']),
                                    array(
                                      'controller' => 'co_people',
                                      'action' => 'canvas',
                                      $p['SponsorCoPerson']['id'])
                                    );
          }
        ?>
      </td>
      <td>
        <?php
          if(isset($p['ApproverCoPerson']['id']) && $p['ApproverCoPerson']['id'] != '') {
            print $this->Html->link(generateCn($p['ApproverCoPerson']['PrimaryName']),
                                    array(
                                      'controller' => 'co_people',
                                      'action' => 'canvas',
                                      $p['ApproverCoPerson']['id'])
                                    );
          }
        ?>
      </td>
      <td>
        <?php
          global $status_t;
          
          if(!empty($p['CoPetition']['status'])) {
            print _txt('en.status', null, $p['CoPetition']['status']);
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($p['CoPetition']['created'])) {
            print $this->Time->niceShort($p['CoPetition']['created']);
          }
        ?>
      </td>
      <td>
        <?php
          if(!empty($p['CoPetition']['modified'])) {
            print $this->Time->niceShort($p['CoPetition']['modified']);
          }
        ?>
      </td>
      <td>
        <?php
          if($permissions['edit']) {
            print $this->Html->link(_txt('op.view'),
                                    array('controller' => 'co_petitions',
                                          'action' => 'view',
                                          $p['CoPetition']['id']),
                                    array('class' => 'editbutton')) . "\n";
          }
          
          if($permissions['delete'])
            print '<button class="deletebutton" title="' . _txt('op.delete') . '" onclick="javascript:js_confirm_delete(\'' . _jtxt(Sanitize::html($p['CoPetition']['id'])) . '\', \'' . $this->Html->url(array('controller' => 'co_petitions', 'action' => 'delete', $p['CoPetition']['id'], 'co' => $cur_co['Co']['id'])) . '\')";>' . _txt('op.delete') . "</button>\n";
          
          if($permissions['resend'] && $p['CoPetition']['status'] == StatusEnum::PendingConfirmation) {
            $url = array(
              'controller' => 'co_petitions',
              'action' => 'resend',
              $p['CoPetition']['id']
            );
            
            $options = array();
            $options['class'] = 'invitebutton';
            $options['onclick'] = "javascript:js_confirm_generic('" . _jtxt(_txt('op.inv.resend.confirm', array(generateCn($p['EnrolleeCoPerson']['PrimaryName'])))) . "', '"
                                                                 . Router::url($url) . "', '"
                                                                 . _txt('op.inv.resend') . "');return false";
            
            print $this->Html->link(_txt('op.inv.resend'),
                                    $url,
                                    $options) . "\n";
          }
        ?>
        <?php ; ?>
      </td>
    </tr>
    <?php $i++; ?>
    <?php endforeach; // $co_petitions ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="9">
        <?php echo $this->Paginator->numbers(); ?>
      </th>
    </tr>
  </tfoot>
</table>
