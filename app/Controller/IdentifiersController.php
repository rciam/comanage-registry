<?php
/**
 * COmanage Registry Identifiers Controller
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

App::uses("MVPAController", "Controller");

class IdentifiersController extends MVPAController {
  // Class name, used by Cake
  public $name = "Identifiers";
  
  // Establish pagination parameters for HTML views
  public $paginate = array(
    'limit' => 25,
    'order' => array(
      'identifier' => 'asc'
    )
  );
  
  /**
   * Autoassign identifiers for a CO Person.
   * - precondition: $this->request->params holds CO ID and CO Person ID
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   * - postcondition: Redirect issued (HTML)
   *
   * @since  COmanage Registry v0.6
   */  
  
  function assign() {
    if($this->request->is('restful')) {
      $this->Api->parseRestRequestDocument();
      
      $reqdata = $this->Api->getData();
      
      if(!empty($reqdata['co_person_id'])) {
        $copersonid = $reqdata['co_person_id'];
      } else {
        $this->Api->restResultHeader(403, "No Person Specified");
        return;
      }
      
      // Determine the CO ID from the CO Person ID
      
      $coid = $this->Identifier->CoPerson->field('co_id', array('CoPerson.id' => $copersonid));
    } else {
      // While the controller doesn't require_co, this method does.
      
      $coid = $this->parseCOID($this->request->data);
      $copersonid = Sanitize::html($this->request->params['named']['copersonid']);
    }
    
    if($coid != -1) {
      // Assign the identifiers, then walk through the result array and generate a flash message
      $res = $this->Identifier->assign($coid, $copersonid, $this->Session->read('Auth.User.co_person_id'));
      
      if(!empty($res)) {
        // Loop through the results and build result messages
        
        $errs = "";             // Unexpected errors
        $assigned = array();    // Identifiers that were assigned
        $existed = array();     // Identifiers that already existed
        
        foreach(array_keys($res) as $type) {
          if($res[$type] == 2) {
            $existed[] = $type;
          } elseif($res[$type] == 1) {
            $assigned[] = $type;
          } else {
            $errs .= $type . ": " . $res[$type] . "<br />\n";
          }
        }
        
        if($this->request->is('restful')) {
          if($errs != "") {
            $this->Api->restResultHeader(500, $errs);
          } else {
            $this->Api->restResultHeader(200, "OK");
          }
        } else {
          if($errs != "") {
            $this->Flash->set($errs, array('key' => 'error'));
          }
          
          if(!empty($assigned)) {
            $this->Flash->set(_txt('rs.ia.ok') . " (" . implode(',', $assigned) . ")",
                              array('key' => 'success'));
          }
          
          if(!empty($existed)) {
            $this->Flash->set(_txt('er.ia.already') . " (" . implode(',', $existed) . ")",
                              array('key' => 'information'));
          }
        }
      } else {
        if($this->request->is('restful')) {
          $this->Api->restResultHeader(200, "OK");
        } else {
          $this->Flash->set(_txt('er.ia.none'), array('key' => 'information'));
        }
      }
    } else {
      if($this->request->is('restful')) {
        $this->Api->restResultHeader(403, "CO Does Not Exist");
      } else {
        $this->Flash->set(_txt('er.co.unk'), array('key' => 'error'));
      }
    }
    
    if(!$this->request->is('restful')) {
      // Redirect to CO Person view
      $rargs['controller'] = 'co_people';
      $rargs['action'] = 'canvas';
      $rargs[] = $copersonid;
      
      $this->redirect($rargs);
    }
  }
  
  /**
   * Callback before other controller methods are invoked or views are rendered.
   * - postcondition: Redirect may be issued
   *
   * @since  COmanage Registry v0.6
   */
  
  function beforeFilter() {
    parent::beforeFilter();
    
    // Sets tab to open for redirects
    $this->redirectTab = 'id';
  }
  
  /**
   * Determine the CO ID based on some attribute of the request.
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.9
   * @return Integer CO ID, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateImpliedCoId() {
    if(isset($this->viewVars['pool_org_identities'])
       && $this->viewVars['pool_org_identities']
       && isset($this->request->params['named']['copersonid'])) {
      // If org identities are pooled, we need to manually map from copersonid
      // since otherwise AppController won't
      
      $coId = $this->Identifier->CoPerson->field('co_id', array('id' => $this->request->params['named']['copersonid']));
      
      if($coId) {
        return $coId;
      } else {
        throw new InvalidArgumentException(_txt('er.notfound',
                                                array(_txt('ct.co_people.1'),
                                                      Sanitize::html($this->request->params['named']['copersonid']))));
      }
    }
    
    return parent::calculateImpliedCoId();
  }
  
  /**
   * Perform any dependency checks required prior to a write (add/edit) operation.
   * This method is intended to be overridden by model-specific controllers.
   * - postcondition: Session flash message updated (HTML) or HTTP status returned (REST)
   *
   * @since  COmanage Registry v0.6
   * @param  Array Request data
   * @param  Array Current data
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteDependencies($reqdata, $curdata = null) {
    // Check to make sure that a given identifier of a given type is not already
    // in use within a CO. In order to prevent potential conflicts with Identifier
    // Assignment (eg: an admin manually changes an identifier to one that would
    // be next assigned sequentially) this method creates a transaction that
    // checkWriteFollowups commits.
    
    // No need to do this check if we're processing an update and the identifier
    // didn't change.
    
    if(!empty($reqdata['Identifier']['identifier'])
       && !empty($curdata['Identifier']['identifier'])
       && $reqdata['Identifier']['identifier'] == $curdata['Identifier']['identifier']) {
      return true;
    }
    
    if(isset($this->cur_co)) {
      $dbc = $this->Identifier->getDataSource();
      
      if(isset($this->cur_co)) {
        $dbc->begin();
      }
      
      if(!$this->Identifier->checkAvailability($reqdata['Identifier']['identifier'],
                                               $reqdata['Identifier']['type'],
                                               $this->cur_co['Co']['id'])) {
        if($this->request->is('restful'))
          $this->Api->restResultHeader(403, "Identifier In Use");
        else
          $this->Flash->set(_txt('er.ia.exists', array(Sanitize::html($reqdata['Identifier']['identifier']))), array('key' => 'error')); 
        
        $dbc->rollback();
        return false;
      }
    }
    // else don't do this check for org identities
    
    return true;
  }
  
  /**
   * Perform any followups following a write operation.  Note that if this
   * method fails, it must return a warning or REST response, but that the
   * overall transaction is still considered a success (add/edit is not
   * rolled back).
   * This method is intended to be overridden by model-specific controllers.
   *
   * @since  COmanage Registry v0.6
   * @param  Array Request data
   * @param  Array Current data
   * @param  Array Original request data (unmodified by callbacks)
   * @return boolean true if dependency checks succeed, false otherwise.
   */
  
  function checkWriteFollowups($reqdata, $curdata = null, $origdata = null) {
    $dbc = $this->Identifier->getDataSource();
    
    if(isset($this->cur_co)) {
      // Commit under all circumstances
      $dbc->commit();
    }
    
    return true;
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
    
    // In order to manipulate an identifier, the authenticated user must have permission
    // over the associated Org Identity or CO Person. For add action, we accept
    // the identifier passed in the URL, otherwise we lookup based on the record ID.
    
    $managed = false;
    
    if(!empty($roles['copersonid'])) {
      switch($this->action) {
      case 'add':
        if(!empty($pids['copersonid'])) {
          $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                            $pids['copersonid']);
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
          $args['conditions']['Identifier.id'] = $this->request->params['pass'][0];
          $args['contain'] = false;
          
          $identifier = $this->Identifier->find('first', $args);
          
          if(!empty($identifier['Identifier']['co_person_id'])) {
            $managed = $this->Role->isCoOrCouAdminForCoPerson($roles['copersonid'],
                                                              $identifier['Identifier']['co_person_id']);
          } elseif(!empty($identifier['Identifier']['org_identity_id'])) {
            $managed = $this->Role->isCoOrCouAdminForOrgidentity($roles['copersonid'],
                                                                 $identifier['Identifier']['org_identity_id']);
          }
        }
        break;
      }
    }
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Add a new Identifier?
    $p['add'] = ($roles['cmadmin']
                 || $roles['coadmin'] 
                 || ($managed && $roles['couadmin']));
    
    // Assign (autogenerate) Identifiers? (Same logic is in CoPeopleController)
    $p['assign'] = ($roles['cmadmin']
                    || $roles['coadmin'] 
                    || ($managed && $roles['couadmin']));
    
    // Delete an existing Identifier?
    $p['delete'] = ($roles['cmadmin']
                    || $roles['coadmin'] 
                    || ($managed && $roles['couadmin']));
    
    // Edit an existing Identifier?
    $p['edit'] = ($roles['cmadmin']
                  || $roles['coadmin'] 
                  || ($managed && $roles['couadmin']));
    
    // View all existing Identifier?
    // Currently only supported via REST since there's no use case for viewing all
    $p['index'] = $this->request->is('restful') && ($roles['cmadmin'] || $roles['coadmin']);
    
    // View an existing Identifier?
    $p['view'] = ($roles['cmadmin']
                  || $roles['coadmin'] 
                  || ($managed && $roles['couadmin']));
    
    $this->set('permissions', $p);
    return $p[$this->action];
  }
}
