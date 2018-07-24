<?php
/**
 * COmanage Registry Email Addresses Controller
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

class VosController extends MVPAController {
  // Class name, used by Cake
  public $name = "Vos";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'id' => 'asc'
    )
  );

  // This controller uses
  public $uses = array('Vo','CoVomsProvisionerTarget');



  /**
   * Callback to set relevant tab to open when redirecting to another page
   * - precondition:
   * - postcondition: Auth component is configured
   * - postcondition:
   *
   * @since  COmanage Registry v0.8
   */

  function beforeFilter() {
  	// the name seems to be irrelevant, is seems that is
	// just an identifier.
    $this->redirectTab = 'vo';

    parent::beforeFilter();
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

    $managed = false;
    $self = false;
    $vo = null;
    
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
        if(!empty($this->request->params['pass'][0])) {
          // look up $this->request->params['pass'][0] and find the appropriate co person id or org identity id
          // then pass that to $this->Role->isXXX
          $args = array();
          $args['conditions']['Vo.id'] = $this->request->params['pass'][0];
          $args['contain'] = false;
          
          $vo = $this->Vo->find('first', $args);
          
          if(!empty($vo['Vo']['co_person_id'])) {
            $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],$vo['Vo']['co_person_id']);
            
            if($vo['Vo']['co_person_id'] == $roles['copersonid']) {
              $self = true;
            }
          } elseif(!empty($vo['Vo']['org_identity_id'])) {
            $managed = $this->Role->isCoOrCouAdminForOrgidentity($roles['copersonid'], $vo['Vo']['org_identity_id']);
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
        $selfperms[$a] = $this->Vo
                              ->CoPerson
                              ->Co
                              ->CoSelfServicePermission
                              ->calculatePermission($this->cur_co['Co']['id'],'Vo',$a,
	                              ($a != 'add' && !empty($vo['Vo']['type']))? $vo['Vo']['type'] : null);
      }
      
      $p['selfsvc'] = $this->Co->CoSelfServicePermission->findPermissions($this->cur_co['Co']['id']);
    } else {
      $p['selfsvc'] = null;
    }
    
    // Add a new Email
    $p['add'] = ($roles['cmadmin']
                 || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                 || $selfperms['add']);
    
    // Delete an existing
    $p['delete'] = ($roles['cmadmin']
                    || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                    || $selfperms['delete']);
    
    // Edit an existing Vo?
    $p['edit'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $selfperms['edit']);
    
    // View all existing
    // Currently only supported via REST since there's no use case for viewing all
    $p['index'] = $this->request->is('restful') && ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing
    $p['view'] = ($roles['cmadmin']
                  || ($managed && ($roles['coadmin'] || $roles['couadmin']))
                  || $selfperms['view']);

	// At this point the user has no permissions over the entries. We only need him to be able to view them
	// We are keeping the rest of the code for later use and for the case of index and view.

	// Overwrite add, delete, edit permissions we false
//	  $p['add'] = false;
//	  $p['edit'] = false;
//	  $p['delete'] = false;

    $this->set('permissions', $p);
    return $p[$this->action];
  }
}

