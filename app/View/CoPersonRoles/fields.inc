<?php
/**
 * COmanage Registry CO Person Role Fields
 *
 * Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2011-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Globals
  global $cm_lang, $cm_texts;

  // Determine if fields are editable
  $e = false;
  $es = false;

  if(($this->action == "add" && $permissions['add'])
    || ($this->action == "edit" && $permissions['edit']))
    $e = true;

  if($this->action == "edit" && $permissions['editself'])
    $es = true;

  // We shouldn't get here if we don't have at least read permission, but check just in case

  if(!$e && !$permissions['view'])
    return(false);

  if($e)
  {
    // This is for beforeFilter
    print $this->Form->hidden('Co.id', array('default' => $cur_co['Co']['id'])). "\n";
    // And this is to link to the co person
    print $this->Form->hidden('co_person_id', array('default' => $co_people[0]['CoPerson']['id'])). "\n";
    // Make sure ID gets carried over
    if(isset($co_person_roles[0]['CoPersonRole']['id']))
      print $this->Form->hidden('id', array('default' => $co_person_roles[0]['CoPersonRole']['id']));
  }

  // Line number, for rendering
  $l = 1;

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_people';
  $args['action'] = 'index';
  $args['co'] = $cur_co['Co']['id'];
  $this->Html->addCrumb(_txt('me.population'), $args);

  $args = array(
    'controller' => 'co_people',
    'action' => 'canvas',
    $co_people[0]['CoPerson']['id']);
  $this->Html->addCrumb(generateCn($co_people[0]['PrimaryName']), $args);
  $crumbTxt = _txt('op.' . $this->action . '-a', array(_txt('ct.co_person_roles.1')));
  $this->Html->addCrumb($crumbTxt);

  ?>

  <script type="text/javascript">
    $(function() {
      // Explorer menu toggles
      $(".fieldGroupName").click(function(event) {
        event.preventDefault();
        $(this).next(".fields").slideToggle("fast");
        // toggle the +/- icon:
        if ($(this).find(".ui-icon").hasClass("ui-icon-circlesmall-minus")) {
          $(this).find(".ui-icon").removeClass("ui-icon-circlesmall-minus").addClass("ui-icon-circlesmall-plus");
        } else {
          $(this).find(".ui-icon").removeClass("ui-icon-circlesmall-plus").addClass("ui-icon-circlesmall-minus");
        }
      });
    });
  </script>

  <div id="<?php print $this->action; ?>_co_person" class="explorerContainer">
    <div id="petition" class="personExplorer">
      <ul class="topList">

        <li id="fields-role" class="fieldGroup">

          <a href="#tabs-role" class="fieldGroupName">
            <span class="ui-icon ui-icon-circlesmall-minus"></span>
            <?php print _txt('fd.attrs.copr'); ?>
          </a>

          <table id="<?php print $this->action; ?>_co_person_role" class="ui-widget fields">
            <tbody>
              <?php if(!empty($permissions['cous'])): ?>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <td>
                  <?php print Sanitize::html($cur_co['Co']['name']) . " " . _txt('fd.cou'); ?><span class="required">*</span>
                </td>
                <td>
                  <?php
                  $attrs['value'] = (isset($co_person_roles[0]['CoPersonRole']['cou_id'])
                    ? $co_person_roles[0]['CoPersonRole']['cou_id']
                    : 0);
                  $attrs['empty'] = false;

                  if($e && !$es) {
                    print $this->Form->select('cou_id',
                      $permissions['cous'],
                      $attrs);

                    if($this->Form->isFieldError('cou_id')) {
                      print $this->Form->error('cou_id');
                    }
                  } else {
                    print Sanitize::html($co_person_roles[0]['Cou']['name']);
                  }
                  ?>
                </td>
                <?php endif; ?>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <td>
                  <?php print Sanitize::html($cur_co['Co']['name']) . " " . _txt('fd.affiliation'); ?><span class="required">*</span>
                </td>
                <td>
                  <?php
                  global $cm_lang, $cm_texts;
                  $attrs = array();
                  $attrs['value'] = (isset($co_person_roles[0]['CoPersonRole']['affiliation'])
                    ? $co_person_roles[0]['CoPersonRole']['affiliation']
                    : AffiliationEnum::Member);
                  $attrs['empty'] = false;

                  if($e && !$es) {
                    print $this->Form->select('affiliation',
                      $vv_copr_affiliation_types,
                      $attrs);

                    if($this->Form->isFieldError('affiliation')) {
                      print $this->Form->error('affiliation');
                    }
                  } elseif(!empty($co_person_roles[0]['CoPersonRole']['affiliation'])) {
                    print $vv_copr_affiliation_types[ $co_person_roles[0]['CoPersonRole']['affiliation'] ];
                  }
                  ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <td>
                  <?php print _txt('fd.status'); ?><span class="required">*</span>
                </td>
                <td>
                  <?php
                  global $cm_lang, $cm_texts;
                  $attrs = array();
                  $attrs['value'] = (isset($co_person_roles[0]['CoPersonRole']['status'])
                    ? $co_person_roles[0]['CoPersonRole']['status']
                    : StatusEnum::Active);
                  $attrs['empty'] = false;

                  if($e && !$es) {
                    print $this->Form->select('status',
                      $cm_texts[ $cm_lang ]['en.status'],
                      $attrs);

                    if($this->Form->isFieldError('status')) {
                      print $this->Form->error('status');
                    }
                  } else {
                    if(!empty($co_person_roles[0]['CoPersonRole']['status'])) {
                      print _txt('en.status', null, $co_person_roles[0]['CoPersonRole']['status']);
                    }
                  }
                  ?>
                  <?php if($e && !$es
                    && !empty($co_person_roles[0]['CoPersonRole']['status'])
                    && ($co_person_roles[0]['CoPersonRole']['status'] == StatusEnum::PendingApproval
                      || $co_person_roles[0]['CoPersonRole']['status'] == StatusEnum::PendingConfirmation)): ?>
                    <div>
                      <span class="ui-icon ui-icon-info co-info"></span>
                      <em><?php print _txt('fd.status.change'); ?></em>
                    </div>
                  <?php endif; ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <td>
                  <?php print $cur_co['Co']['name'] . " " . _txt('fd.title'); ?>
                </td>
                <td>
                  <?php print (($e && !$es) ? $this->Form->input('title', array('class' => 'focusFirst','default' =>
                    isset($co_person_roles[0]['CoPersonRole']['title'])
                      ? $co_person_roles[0]['CoPersonRole']['title']
                      : ""))
                    : Sanitize::html($co_person_roles[0]['CoPersonRole']['title'])); ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <td>
                  <?php print _txt('fd.o'); ?>
                </td>
                <td>
                  <?php print (($e && !$es) ? $this->Form->input('o',
                    ($this->action == 'add'
                      ? array('default' => $cur_co['Co']['name'])
                      : array()))
                    : Sanitize::html($co_person_roles[0]['CoPersonRole']['o'])); ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <td>
                  <?php print _txt('fd.ou'); ?>
                </td>
                <td>
                  <?php print (($e && !$es) ? $this->Form->input('ou') : Sanitize::html($co_person_roles[0]['CoPersonRole']['ou'])); ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <td>
                  <?php print _txt('fd.sponsor') . " ";
                  print _txt('fd.sponsor.desc');
                  ?>
                </td>
                <td>
                  <?php
                  if(!empty($vv_sponsors)) {
                    if(!empty($co_person_roles[0]['CoPersonRole']['sponsor_co_person_id'])
                      && !isset($vv_sponsors[ $co_person_roles[0]['CoPersonRole']['sponsor_co_person_id'] ])) {
                      if(!empty($co_person_roles[0]['SponsorCoPerson']['PrimaryName'])) {
                        print $this->Html->link(generateCn($co_person_roles[0]['SponsorCoPerson']['PrimaryName']),
                          array('controller' => 'co_people',
                            'action' => 'canvas',
                            $co_person_roles[0]['SponsorCoPerson']['id']));
                      }

                      print '<div>
                                <span class="ui-icon ui-icon-info co-info"></span>
                                <em>' . _txt('fd.sponsor.inel') . '</em>
                              </div>';
                    }

                    if($e && !$es) {
                      print $this->Form->select('sponsor_co_person_id', $vv_sponsors);
                    } elseif(!empty($co_person_roles[0]['CoPersonRole']['sponsor_co_person_id'])) {
                      print Sanitize::html($vv_sponsors[ $co_person_roles[0]['CoPersonRole']['sponsor_co_person_id'] ]);
                    }
                  }
                  ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <td>
                  <?php print _txt('fd.valid_from'); if($e && !$es) print " " . _txt('fd.valid_from.desc'); ?>
                </td>
                <td>
                  <?php print (($e && !$es)
                    ? $this->Form->text('valid_from', array('class' => 'datepicker-f'))
                    : (($co_person_roles[0]['CoPersonRole']['valid_from'] > 0) ? strftime("%F", strtotime($co_person_roles[0]['CoPersonRole']['valid_from'])) : "")); ?>
                </td>
              </tr>
              <tr class="line<?php print ($l % 2); $l++; ?>">
                <td>
                  <?php print _txt('fd.valid_through'); if($e && !$es) print " " . _txt('fd.valid_through.desc'); ?>
                </td>
                <td>
                  <?php print (($e && !$es)
                    ? $this->Form->text('valid_through', array('class' => 'datepicker-u'))
                    : (($co_person_roles[0]['CoPersonRole']['valid_through'] > 0) ? strftime("%F", strtotime($co_person_roles[0]['CoPersonRole']['valid_through'])) : "")); ?>
                </td>
              </tr>
              <?php
              // Check for extended attributes and render

              if(!empty($vv_extended_attributes)) {
                $cl = 'Co' . $cur_co['Co']['id'] . 'PersonExtendedAttribute';

                print $this->Form->hidden($cl . '.id');

                foreach($vv_extended_attributes as $c):
                  ?>
                  <tr class="line<?php print ($l % 2); $l++; ?>">
                    <td>
                      <?php print $c['CoExtendedAttribute']['display_name']; ?>
                    </td>
                    <td>
                      <?php
                      $attr = strtolower($c['CoExtendedAttribute']['name']);

                      if($c['CoExtendedAttribute']['type'] == 'TIMESTAMP') {
                        print ($e && !$es ? $this->Form->text($cl.'.'.$attr, array('class' => 'datepicker'))
                          : (($co_person_roles[0][$cl][$attr] > 0) ? strftime("%F", strtotime($co_person_roles[0][$cl][$attr])) : ""));
                      } else {
                        print ($e && !$es ? $this->Form->input($cl.'.'.$attr,
                          isset($co_person_roles[0][$cl][$attr]) ? array('default' => $co_person_roles[0][$cl][$attr]) : array())
                          : Sanitize::html($co_person_roles[0][$cl][$attr]));
                      }
                      ?>
                    </td>
                  </tr>
                <?php
                endforeach;
              }
              ?>
              <tr>
                <td>
                  <em><span class="required"><?php print _txt('fd.req'); ?></span></em><br />
                </td>
                <td>
                  <?php
                  if($e && !$es) {
                    print $this->Form->submit($submit_label);
                    print $this->Form->button(_txt('op.reset'),
                      array('type'=>'reset'));
                  }
                  ?>
                </td>
              </tr>
            </tbody>
          </table>
        </li>


      <?php if($this->action != "add"): ?>

        <li id="fields-phone" class="fieldGroup">

          <?php if($this->Permission->selfService($permissions, $e, 'TelephoneNumber') == PermissionEnum::ReadWrite): ?>
            <div class="coAddEditButtons">
              <?php
              print $this->Html->link(_txt('op.add'),
                array(
                  'controller' => 'telephone_numbers',
                  'action' => 'add',
                  'copersonroleid' => $co_person_roles[0]['CoPersonRole']['id']
                ),
                array('class' => 'addbutton')
              );
              ?>
            </div>
          <?php endif; // ReadWrite permission, telephone ?>

          <a href="#tabs-phone" class="fieldGroupName">
            <span class="ui-icon ui-icon-circlesmall-minus"></span>
            <?php print _txt('ct.telephone_numbers.pl'); ?>
          </a>

          <ul id="tabs-phone" class="additionalinfo fields">

            <?php if(!empty($co_person_roles[0]['TelephoneNumber'])): ?>
              <?php foreach($co_person_roles[0]['TelephoneNumber'] as $t): ?>
                <?php
                  $perm = $this->Permission->selfService($permissions, $e, 'TelephoneNumber', $t['type']);
                ?>

                <li>

                  <?php if($perm == PermissionEnum::ReadWrite): ?>
                    <div class="actionButtons">
                      <?php
                      print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                        . '" onclick="javascript:js_confirm_generic(\''
                        . _txt('js.remove') . '\',\''    // dialog body text
                        . $this->Html->url(              // dialog confirm URL
                          array(
                            'controller' => 'telephone_numbers',
                            'action' => 'delete',
                            $t['id']
                          )
                        ) . '\',\''
                        . _txt('op.remove') . '\',\''    // dialog confirm button
                        . _txt('op.cancel') . '\',\''    // dialog cancel button
                        . _txt('op.remove') . '\',[\''   // dialog title
                        . filter_var(_jtxt(formatTelephone($t)),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                        . '\']);">'
                        . _txt('op.delete')
                        . '</button>';
                      print $this->Html->link(_txt('op.edit'),
                          array(
                            'controller' => 'telephone_numbers',
                            'action' => 'edit',
                            $t['id']
                          ),
                          array('class' => 'editbutton')) . "\n";
                      ?>
                    </div>
                    <div class="fieldData">
                      <?php
                        print $this->Html->link(formatTelephone($t),
                          array(
                            'controller' => 'telephone_numbers',
                            'action' => 'edit',
                            $t['id']
                          )
                        );
                      ?>
                      <span class="dataDesc">
                        (<?php print $vv_copr_telephonenumber_types[ $t['type'] ]; ?>)
                      </span>
                    </div>

                  <?php elseif($perm == PermissionEnum::ReadOnly): ?>
                    <div class="fieldData">
                      <?php print Sanitize::html(formatTelephone($t)); ?>
                      <span class="dataDesc">
                      (<?php print $vv_copr_telephonenumber_types[ $t['type'] ]; ?>)
                    </span>
                    </div>
                  <?php endif; // ReadWrite permissions ?>

                </li>

              <?php endforeach; // TelephoneNumber ?>
            <?php else: ?>
              <li class="invisible"><span class="dataDesc"><?php print _txt('fd.none'); ?></span></li>
            <?php endif; // TelephoneNumber ?>
          </ul>

        </li>

        <li id="fields-address" class="fieldGroup">

          <?php if($this->Permission->selfService($permissions, $e, 'Address') == PermissionEnum::ReadWrite): ?>
            <div class="coAddEditButtons">
              <?php
              print $this->Html->link(_txt('op.add'),
                array(
                  'controller' => 'addresses',
                  'action' => 'add',
                  'copersonroleid' => $co_person_roles[0]['CoPersonRole']['id']),
                array('class' => 'addbutton'
                )
              );
              ?>
            </div>
          <?php endif; // ReadWrite permission, address ?>

          <a href="#tabs-address" class="fieldGroupName">
            <span class="ui-icon ui-icon-circlesmall-minus"></span>
            <?php print _txt('ct.addresses.pl'); ?>
          </a>


          <ul id="tabs-address" class="additionalinfo fields">

            <?php if(!empty($co_person_roles[0]['Address'])): ?>
              <?php foreach($co_person_roles[0]['Address'] as $addr): ?>
                <?php
                  $perm = $this->Permission->selfService($permissions, $e, 'Address', $addr['type']);
                ?>

                <li>

                  <?php if($perm == PermissionEnum::ReadWrite): ?>
                    <div class="actionButtons">
                      <?php
                      print '<button type="button" class="deletebutton" title="' . _txt('op.delete')
                        . '" onclick="javascript:js_confirm_generic(\''
                        . _txt('js.remove') . '\',\''    // dialog body text
                        . $this->Html->url(              // dialog confirm URL
                          array(
                            'controller' => 'addresses',
                            'action' => 'delete',
                            $addr['id'],
                            'co' => $cur_co['Co']['id']
                          )
                        ) . '\',\''
                        . _txt('op.remove') . '\',\''    // dialog confirm button
                        . _txt('op.cancel') . '\',\''    // dialog cancel button
                        . _txt('op.remove') . '\',[\''   // dialog title
                        . filter_var(_jtxt($addr['street']),FILTER_SANITIZE_STRING)  // dialog body text replacement strings
                        . '\']);">'
                        . _txt('op.delete')
                        . '</button>';
                      print $this->Html->link(_txt('op.edit'),
                          array(
                            'controller' => 'addresses',
                            'action' => 'edit',
                            $addr['id']
                          ),
                          array('class' => 'editbutton')) . "\n";
                      ?>
                    </div>
                    <div class="fieldData">
                      <?php
                        print $this->Html->link($addr['street'],
                          array(
                            'controller' => 'addresses',
                            'action' => 'edit',
                            $addr['id'])
                        );
                      ?>
                      <span class="dataDesc">
                        (<?php print $vv_copr_address_types[ $addr['type'] ]; ?>)
                      </span>
                    </div>
                  <?php elseif($perm == PermissionEnum::ReadOnly): ?>
                    <div class="fieldData">
                      <?php print Sanitize::html($addr['street']); ?>
                      <span class="dataDesc">
                      (<?php $vv_copr_address_types[ $addr['type'] ]; ?>)
                    </span>
                    </div>
                  <?php endif; // ReadWrite permissions ?>
                <?php endforeach; // Address ?>
                <?php else: ?>
                <li class="invisible"><span class="dataDesc"><?php print _txt('fd.none'); ?></span></li>
              <?php endif; // Address ?>
            </ul>
          </li>
        <?php endif; // !add ?>
      </ul>
    </div>
  </div>

<?php
  print $this->element("changelog");
