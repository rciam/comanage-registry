<?php
/**
 * COmanage Registry CO Notifications Fields
 *
 * Copyright (C) 2014-15 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014-15 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.4
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Determine if fields are editable
  $e = false;
  
  // XXX drop this if we don't have a manual way to create a notification
  if(($this->action == "add" && $permissions['add']) || ($this->action == "edit" && $permissions['edit']))
    $e = true;
    
  // We shouldn't get here if we don't have at least read permission, but check just in case
  
  if(!$e && !$permissions['view'])
    return(false);

  // Add breadcrumbs
  print $this->element("coCrumb");
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'co_notifications';
  $args['action'] = 'index';
  $args['recipientcopersonid'] = $vv_co_person_id_notifications;
  $args['sort'] = 'created';
  $args['direction'] = 'desc';
  $this->Html->addCrumb(_txt('ct.co_notifications.pl'), $args);
  $this->Html->addCrumb(_txt('ct.co_notifications.1'));
  
  // XXX drop this if we don't have a manual way to create a notification
  print $this->Form->hidden('co_id', array('default' => $cur_co['Co']['id'])) . "\n";
  
  $l = 1;
?>
<table id="<?php print $this->action; ?>_co_localizations" class="ui-widget">
  <tbody>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.comment'); ?></b>
      </td>
      <td>
        <?php print Sanitize::html($co_notifications[0]['CoNotification']['comment']); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.status'); ?></b>
      </td>
      <td>
        <?php
          print '<div class="notificationStatus">' . _txt('en.status.not', null, $co_notifications[0]['CoNotification']['status']) . '</div>';
          
          if($co_notifications[0]['CoNotification']['status'] == NotificationStatusEnum::PendingAcknowledgment
             && $permissions['acknowledge']) {
            print $this->Html->link(
              _txt('op.ack'),
              array(
                'controller' => 'co_notifications',
                'action'     => 'acknowledge',
                $co_notifications[0]['CoNotification']['id']
              ),
              array('class' => 'checkbutton')
            );
          }
          
          // This is not else/if because someone could have permission to either ack or cancel
          if(($co_notifications[0]['CoNotification']['status'] == NotificationStatusEnum::PendingAcknowledgment
              || $co_notifications[0]['CoNotification']['status'] == NotificationStatusEnum::PendingResolution)
             && $permissions['cancel']) {
            print $this->Html->link(
              _txt('op.cancel'),
              array(
                'controller' => 'co_notifications',
                'action'     => 'cancel',
                $co_notifications[0]['CoNotification']['id']
              ),
              array('class' => 'cancelbutton')
            );
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.source'); ?></b>
      </td>
      <td>
        <?php
          if(!empty($co_notifications[0]['CoNotification']['source_url'])) {
            print $this->Html->link(
              $co_notifications[0]['CoNotification']['source_url'],
              $co_notifications[0]['CoNotification']['source_url']
            );
          } elseif(!empty($co_notifications[0]['CoNotification']['source_controller'])) {
            print $this->Html->link(
              $co_notifications[0]['CoNotification']['source_controller'],
              array(
                'controller' => $co_notifications[0]['CoNotification']['source_controller'],
                'action'     => $co_notifications[0]['CoNotification']['source_action'],
                $co_notifications[0]['CoNotification']['source_id'],
                // Inject the CO ID in case it's needed
                'co'         => $cur_co['Co']['id']
              )
            );
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.subject'); ?></b>
      </td>
      <td>
        <?php
          if(!empty($co_notifications[0]['SubjectCoPerson']['PrimaryName'])) {
            print generateCn($co_notifications[0]['SubjectCoPerson']['PrimaryName']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.recipient'); ?></b>
      </td>
      <td>
        <?php
          if(!empty($co_notifications[0]['RecipientCoPerson']['PrimaryName'])) {
            print generateCn($co_notifications[0]['RecipientCoPerson']['PrimaryName']);
          } elseif(!empty($co_notifications[0]['RecipientCoGroup']['name'])) {
            print $co_notifications[0]['RecipientCoGroup']['name'];
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.actor'); ?></b>
      </td>
      <td>
        <?php
          if(!empty($co_notifications[0]['ActorCoPerson']['PrimaryName'])) {
            print generateCn($co_notifications[0]['ActorCoPerson']['PrimaryName']);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.action'); ?></b>
      </td>
      <td>
        <?php print Sanitize::html($co_notifications[0]['CoNotification']['action']); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.created'); ?></b>
      </td>
      <td>
        <?php print $this->Time->format($co_notifications[0]['CoNotification']['created'], "%c $vv_tz", false, $vv_tz); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.not.last'); ?></b>
      </td>
      <td>
        <?php
          if(!empty($co_notifications[0]['CoNotification']['notification_time'])) {
            print $this->Time->format($co_notifications[0]['CoNotification']['notification_time'], "%c $vv_tz", false, $vv_tz);
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.resolved'); ?></b>
      </td>
      <td>
        <?php
          if(!empty($co_notifications[0]['CoNotification']['resolution_time'])) {
            print $this->Time->format($co_notifications[0]['CoNotification']['resolution_time'], "%c $vv_tz", false, $vv_tz);
            
            if(!empty($co_notifications[0]['ResolverCoPerson']['PrimaryName'])) {
              print " (" . generateCn($co_notifications[0]['ResolverCoPerson']['PrimaryName']) . ")";
            }
          }
        ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.not.email.subject'); ?></b>
      </td>
      <td>
        <?php print Sanitize::html($co_notifications[0]['CoNotification']['email_subject']); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.not.email.body'); ?></b>
      </td>
      <td>
        <pre>
          <?php print Sanitize::html($co_notifications[0]['CoNotification']['email_body']); ?>
        </pre>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.not.res.subject'); ?></b>
      </td>
      <td>
        <?php print Sanitize::html($co_notifications[0]['CoNotification']['resolution_subject']); ?>
      </td>
    </tr>
    <tr class="line<?php print ($l % 2); $l++; ?>">
      <td>
        <b><?php print _txt('fd.not.res.body'); ?></b>
      </td>
      <td>
        <pre>
          <?php print Sanitize::html($co_notifications[0]['CoNotification']['resolution_body']); ?>
        </pre>
      </td>
    </tr>
  </tbody>
</table>
