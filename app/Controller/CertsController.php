<?php
/**
 * COmanage Registry Certs Controller
 *
 * Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-14 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses("MVPAController", "Controller");

class CertsController extends MVPAController {
  // Class name, used by Cake
  public $name = "Certs";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'subject' => 'asc'
    )
  );

  /**
   * Callback to set relevant tab to open when redirecting to another page
   * - precondition:
   * - postcondition: Auth component is configured
   * - postcondition:
   *
   * @since  COmanage Registry v0.8
   */

  function beforeFilter() {
    $this->redirectTab = 'cert';

    parent::beforeFilter();
  }


  /**
   * Update ordr column
   * @param $id
   *
   * @since COmanage Registry v3.1.1
   */
  function update_order($id) {
    if(is_null($id)) {
      $this->redirect("/");
    }

    $this->autoRender = false; // We don't render a view in this example
    $this->layout=null;

    $this->Cert->id = $id;
    $this->Cert->saveField('ordr', $this->request->data['Cert']['ordr']);

    $args = array(
      'controller' => 'certs',
      'action'     => 'view',
      filter_var($id,FILTER_SANITIZE_SPECIAL_CHARS)
    );

    $this->redirect($args);
  }

  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v0.1
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    $pids = $this->parsePersonID($this->request->data);

    // Is this a read only record? True if it belongs to an Org Identity that has
    // an OrgIdentity Source Record. As of the initial implementation, not even
    // CMP admins can edit such a record.
    
    if($this->action == 'edit' && !empty($this->request->params['pass'][0])) {
      $orgIdentityId = $this->Cert->field('org_identity_id', array('id' => $this->request->params['pass'][0]));
      
      if($orgIdentityId) {
        $readOnly = $this->Cert->OrgIdentity->readOnly($orgIdentityId);
        
        if($readOnly) {
          // Proactively redirect to view. This will also prevent (eg) the REST API
          // from editing a read only record.
          $args = array(
            'controller' => 'certs',
            'action'     => 'view',
            filter_var($this->request->params['pass'][0],FILTER_SANITIZE_SPECIAL_CHARS)
          );
          
          $this->redirect($args);
        }
      }
    }
    
    // In order to manipulate a certificate, the authenticated user must have permission
    // over the associated Org Identity or CO Person. For add action, we accept
    // the identifier passed in the URL, otherwise we lookup based on the record ID.
    
    $managed = false;
    $self = false;
    $cert = null;
    
    if(!empty($roles['copersonid'])) {
      switch($this->action) {
      case 'add':
        if(!empty($pids['copersonid'])) {
          $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                            $pids['copersonid']);
          
          if($pids['copersonid'] == $roles['copersonid']) {
            $self = true;
          }
        } elseif(!empty($pids['orgidentityid'])) {
          $managed = $this->Role->isCoOrCouAdminForOrgIdentity($roles['copersonid'],
                                                               $pids['orgidentityid']);
        }
        break;
      case 'delete':
      case 'edit':
      case 'view':
      case 'update_order':
        if(!empty($this->request->params['pass'][0])) {
          // look up $this->request->params['pass'][0] and find the appropriate co person id or org identity id
          // then pass that to $this->Role->isXXX
          $args = array();
          $args['conditions']['Cert.id'] = $this->request->params['pass'][0];
          $args['contain'] = false;
          
          $cert = $this->Cert->find('first', $args);
          
          if(!empty($cert['Cert']['co_person_id'])) {
            $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                              $cert['Cert']['co_person_id']);
            
            if($cert['Cert']['co_person_id'] == $roles['copersonid']) {
              $self = true;
            }
          } elseif(!empty($cert['Cert']['org_identity_id'])) {
            $managed = $this->Role->isCoOrCouAdminForOrgidentity($roles['copersonid'],
                                                                 $cert['Cert']['org_identity_id']);
            if(!empty($roles['orgidentities'])) {
              $org_ids = Hash::extract($roles, 'orgidentities.{n}.org_id');
              if(in_array($cert['Cert']['org_identity_id'], $org_ids)) {
                $self = true;
              }
            }
          }
        }
        break;
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Self service is a bit complicated because permission can vary by type.
    // Self service only applies to CO Person-attached attributes.
    
    $selfperms = array(
      'add'    => false,
      'delete' => false,
      'edit'   => false,
      'view'   => false
    );
    
    if($self) {
      foreach(array_keys($selfperms) as $a) {
        $selfperms[$a] = $this->Cert
                              ->CoPerson
                              ->Co
                              ->CoSelfServicePermission
                              ->calculatePermission($this->cur_co['Co']['id'],
                                                    'Cert',
                                                    $a,
                                                    ($a != 'add' && !empty($cert['Cert']['type']))
                                                     ? $cert['Cert']['type'] : null);
      }
      
      $p['selfsvc'] = $this->Co->CoSelfServicePermission->findPermissions($this->cur_co['Co']['id']);
    } else {
      $p['selfsvc'] = null;
    }
    
    // Add a new Email Certificate?
    $p['add'] = ($roles['cmadmin']
                 || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                 || $selfperms['add']);
    
    // Delete an existing Certificate?
    $p['delete'] = ($roles['cmadmin']
                    || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                    || $selfperms['delete']);
    
    // Edit an existing Certificate?
    $p['edit'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $selfperms['edit']);
    
    // View all existing Certificates?
    // Currently only supported via REST since there's no use case for viewing all
    $p['index'] = $this->request->is('restful') && ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Certificate?
    $p['update_order'] = $p['view'] = ($roles['cmadmin']
                                      || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                                      || $selfperms['view']);

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}

