<?php
/**
 * COmanage Registry Navigation Links Order View
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
 * @since         COmanage Registry v0.8.2
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

  // Add breadcrumbs
  $this->Html->addCrumb(_txt('ct.navigation_links.pl'), array('controller' => 'navigation_links', 'action' => 'index'));
  $crumbTxt = _txt('op.reorder-a', array(_txt('ct.navigation_links.pl')));
  $this->Html->addCrumb($crumbTxt);

  // Add page title
  $params = array();
  $params['title'] = $title_for_layout;

  // Add top links
  $params['topLinks'] = array();

  if($permissions['add']) {
    $params['topLinks'][] = $this->Html->link(
      _txt('op.add-a', array(_txt('ct.navigation_links.1'))),
      array(
        'controller' => 'navigation_links',
        'action' => 'add'
      ),
      array('class' => 'addbutton')
    );
  }

  print $this->element("pageTitleAndButtons", $params);

?>
<script type="text/javascript">
  $(function() {
    // Define sortable
    $("#sortable").sortable({
      update: function( event, ui ) {
        // POST to /reorder with the new order serialized
        var jqxhr = $.post("<?php print $this->Html->url(array('controller' => 'navigation_links',
                                                               'action'     => 'reorder',
                                                               'ext'        => 'json')); ?>", $("#sortable").sortable("serialize"));
        
        jqxhr.done(function(data, textStatus, jqXHR) {
        });
        
        jqxhr.fail(function(jqXHR, textStatus, errorThrown) {
          // Note we're getting 200 here but it's actually a success (perhaps because no body returned; CO-984)
          if(jqXHR.status != "200") {
            $("#result-dialog").html("<p><?php print _txt('er.reorder'); ?>" + errorThrown + " (" +  jqXHR.status + ")</p>");
            $("#result-dialog").dialog("open");
          }
        });
      }
    });
    
    // Result dialog
    $("#result-dialog").dialog({
      autoOpen: false,
      buttons: {
        "<?php print _txt('op.ok'); ?>": function() {
          $(this).dialog("close");
        },
      },
      modal: true,
      show: {
        effect: "fade"
      },
      hide: {
        effect: "fade"
      }
    });
  });
</script>

<table id="navigation_links" class="ui-widget">  
  <thead>
    <tr class="ui-widget-header">
      <th><?php print _txt('fd.ea.order'); ?></th>
      <th><?php print _txt('fd.link.title'); ?></th>
      <th><?php print _txt('fd.link.url'); ?></th>
      <th><?php print _txt('fd.desc'); ?></th>
    </tr>
  </thead>
  
  <tbody id="sortable">
    <?php $i = 0; ?>
    <?php foreach ($navigation_links as $c): ?>
      <tr id = "NavigationLinkId_<?php print $c['NavigationLink']['id']?>" class="line1">
        <td class = "order">
          <span class="ui-icon ui-icon-arrow-4"></span>
        </td>
        <td>
          <?php
            print $this->Html->link($c['NavigationLink']['title'],
                                    array('controller' => 'navigation_links',
                                          'action' => ($permissions['edit'] ? 'edit' : ($permissions['view'] ? 'view' : '')), $c['NavigationLink']['id']));
          ?>
        </td>
        <td><?php print Sanitize::html($c['NavigationLink']['url']); ?></td>
        <td><?php print Sanitize::html($c['NavigationLink']['description']); ?></td>
      </tr>
    <?php $i++; ?>
    <?php endforeach; ?>
  </tbody>
  
  <tfoot>
    <tr class="ui-widget-header">
      <th colspan="4">
        <?php print $this->element("pagination"); ?>
      </th>
    </tr>
  </tfoot>
</table>

<?php
  $args = array();
  $args['plugin'] = null;
  $args['controller'] = 'navigation_links';
  $args['action'] = 'index';
  
  print $this->Html->link(_txt('op.done'),
                          $args,
                          array('class'  => 'backbutton'));
?>

<div id="result-dialog" title="<?php print _txt('op.reorder'); ?>">
  <p></p>
</div>
