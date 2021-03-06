<?php
/**
 * COmanage Registry CO Person Model
 *
 * Copyright (C) 2010-16 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2010-16 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

class CoPerson extends AppModel {
  // Define class name for cake
  public $name = "CoPerson";
  
  // Current schema version for API
  public $version = "1.0";
  
  // Add behaviors
  public $actsAs = array('Containable',
                         'Provisioner',
                         'Changelog' => array('priority' => 5));
  
  // Association rules from this model to other models
  public $belongsTo = array("Co");                    // A CO Person Source is attached to one CO
  
  public $hasOne = array(
    "CoNsfDemographic" => array('dependent' => true),
    // A person can have one invite (per CO)
    "CoInvite" => array('dependent' => true),
    // An CO Person has one Primary Name, which is a pointer to a Name
    "PrimaryName" => array(
      'className'  => 'Name',
      'conditions' => array('PrimaryName.primary_name' => true),
      'dependent'  => false,
      'foreignKey' => 'co_person_id'
    )
  );
  
  public $hasMany = array(
    // A person can have one or more groups
    "CoGroupMember" => array('dependent' => true),
    // It's OK to delete notifications where this Person is the subject, but that's it.
    "CoNotificationSubject" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'subject_co_person_id',
      'dependent' => true
    ),
    "CoNotificationActor" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'actor_co_person_id'
    ),
    "CoNotificationRecipient" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'recipient_co_person_id'
    ),
    "CoNotificationResolver" => array(
      'className' => 'CoNotification',
      'foreignKey' => 'resolver_co_person_id'
    ),
    // A person can have more than one org identity
    "CoOrgIdentityLink" => array('dependent' => true),
    // A person can have one or more person roles
    "CoPersonRole" => array('dependent' => true),
    "CoPetitionApprover" => array(
      'className' => 'CoPetition',
      'dependent' => false,
      'foreignKey' => 'approver_co_person_id'
    ),
    "CoPetitionEnrollee" => array(
      'className' => 'CoPetition',
      // The only time we want to delete a petition when deleting a CO Person
      // is if person is the enrollee.
      'dependent' => true,
      'foreignKey' => 'enrollee_co_person_id'
    ),
    "CoPetitionPetitioner" => array(
      'className' => 'CoPetition',
      'dependent' => false,
      'foreignKey' => 'petitioner_co_person_id'
    ),
    "CoPetitionSponsor" => array(
      'className' => 'CoPetition',
      'dependent' => false,
      'foreignKey' => 'sponsor_co_person_id'
    ),
    // A person can be an actor on a petition and generate history
    "CoPetitionHistoryRecord" => array(
      'foreignKey' => 'actor_co_person_id'
    ),
    "CoTAndCAgreement" => array('dependent' => true),
    // A person can have one or more email address
    "EmailAddress" => array('dependent' => true),
    // We allow dependent=true for co_person_id but not for actor_co_person_id (see CO-404).
    "HistoryRecord" => array(
      'dependent' => true,
      'foreignKey' => 'co_person_id'
    ),
    "HistoryRecordActor" => array(
      'className' => 'HistoryRecord',
      'foreignKey' => 'actor_co_person_id'
    ),
    // A person can have many identifiers within a CO
    "Identifier" => array('dependent' => true),
    "Name" => array('dependent' => true),
    // Make this last so it doesn't get recreated by ProvisionerBehavior when
    // deleting a CO person
    "CoProvisioningExport" => array('dependent' => true),
    "SshKey" => array('dependent' => true)
  );

  // Default display field for cake generated views
  public $displayField = "PrimaryName.family";
  
  // Default ordering for find operations
// XXX CO-296 Toss default order?
//  public $order = array("CoPerson.id");
  
  // Validation rules for table elements
  // Validation rules must be named 'content' for petition dynamic rule adjustment
  public $validate = array(
    'co_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => true,
        'message' => 'A CO ID must be provided'
      )
    ),
    'primary_name_id' => array(
      'content' => array(
        'rule' => 'numeric',
        'required' => false,
        'allowEmpty' => true
      )
    ),
    'status' => array(
      'content' => array(
        'rule' => array('inList', array(StatusEnum::Active,
                                        StatusEnum::Approved,
                                        StatusEnum::Confirmed,
                                        StatusEnum::Declined,
                                        StatusEnum::Deleted,
                                        StatusEnum::Denied,
                                        StatusEnum::Duplicate,
                                        StatusEnum::Expired,
                                        StatusEnum::GracePeriod,
                                        StatusEnum::Invited,
                                        StatusEnum::Pending,
                                        StatusEnum::PendingApproval,
                                        StatusEnum::PendingConfirmation,
                                        StatusEnum::Suspended)),
        'required' => true,
        'message' => 'A valid status must be selected'
      )
    ),
    'timezone' => array(
      'content' => array(
        'rule' => array('validateTimeZone'),
        'required' => false,
        'allowEmpty' => true
      )
    )
  );
  
  // Enum type hints
  
  public $cm_enum_txt = array(
    'status' => 'en.status'
  );
  
  public $cm_enum_types = array(
    'status' => 'StatusEnum'
  );
  
  /**
   * Execute logic after a CO Person save operation.
   * For now manage membership of CO Person in members group.
   *
   * @since  COmanage Registry v0.9.3
   * @param  boolean true if a new record was created (rather than update)
   * @param  array, the same passed into Model::save()
   * @return none
   */
  
  public function afterSave($created, $options = array()) {
    // Manage CO person membership in the CO members group.
      
    // Since the Provisioner Behavior will only provision group memberships
    // for CO People with an Active status we do not need to manage 
    // membership in the members group based on status here.  So we only
    // add a CO Person to the members group upon creation and then leave
    // it there. 
    if($created) {
        $coPersonId = $this->data[$this->alias]['id'];
        $coid = $this->data[$this->alias]['co_id'];
        
        // Find the members group for this CO.    
        $args = array();
        $args['conditions']['CoGroup.name'] = 'members';
        $args['conditions']['CoGroup.co_id'] = $coid;
        $args['contain'] = false;
        $membersgroup = $this->CoGroupMember->CoGroup->find('first', $args);
        
        // The members group may not exist if a deployment was upgraded and not
        // reconciled so in that case just silently return.
        if(empty($membersgroup)) {
          return;
        }
            
        // Create the membership in the members group.
        $data = array();
        $data['CoGroupMember']['co_group_id'] = $membersgroup['CoGroup']['id'];
        $data['CoGroupMember']['co_person_id'] = $coPersonId;
        $data['CoGroupMember']['member'] = true;
            
        $this->CoGroupMember->save($data);
        
        // Cut a history record.
        try {
          $msgData = array(
            'members',
            $membersgroup['CoGroup']['id'],
            _txt('fd.yes'),
            _txt('fd.no')
          );                  
          $msg = _txt('rs.grm.added', $msgData);
          $this->HistoryRecord->record($coPersonId, null, null, null, ActionEnum::CoGroupMemberAdded, $msg);
        } catch(Exception $e) {
          $msg = _txt('er.grm.history.members', array($coPersonId));
          $this->log($msg);
        }      
      
    }
  }
  
  /**
   * Completely purge a CO Person. This will cascade deletes past where normal
   * relations would permit, and update history and notifications where the CO Person
   * has a role beyond subject.
   *
   * @since  COmanage Registry v0.8.5
   * @param  integer Identifier of CO Person
   * @param  integer Identifier of CO Person performing expunge
   * @return boolean True on success
   * @throws InvalidArgumentException
   */
  
  public function expunge($coPersonId, $expungerCoPersonId) {
    $coperson = $this->findForExpunge($coPersonId);
    
    if(!$coperson) {
      throw new InvalidArgumentException(_txt('er.cop.unk-a', array($coPersonId)));
    }
    
    // Dynamically bind extended attributes
    
    $c = $this->Co->CoExtendedAttribute->find('count',
                                              array('conditions' =>
                                                    array('co_id' => $coperson['CoPerson']['co_id'])));
    
    if($c > 0) {
      $cl = 'Co' . $coperson['CoPerson']['co_id'] . 'PersonExtendedAttribute';
      
      $this->CoPersonRole->bindModel(array('hasOne' =>
                                           array($cl => array('className' => $cl,
                                                              'dependent' => true))),
                                     false);
    }
    
    // Start a transaction
    $dbc = $this->getDataSource();
    $dbc->begin();
    
    // Set the person to Deleted so they are deprovisioned quickly and to prevent
    // notification errors as the expunge is processed.
    $this->id = $coPersonId;
    $this->saveField('status', StatusEnum::Deleted);
    
    // Rewrite any Notification where this person is an actor, recipient, or resolver
    
    foreach($coperson['CoNotificationActor'] as $n) {
      $this->CoNotificationActor->expungeParticipant($n['id'], 'actor', $expungerCoPersonId);
    }
    
    foreach($coperson['CoNotificationRecipient'] as $n) {
      $this->CoNotificationActor->expungeParticipant($n['id'], 'recipient', $expungerCoPersonId);
    }
    
    foreach($coperson['CoNotificationResolver'] as $n) {
      $this->CoNotificationActor->expungeParticipant($n['id'], 'resolver', $expungerCoPersonId);
    }
    
    // Rewrite any History Records where this person is an actor but not a recipient
    // (since those will be purged shortly anyway)
    
    foreach($coperson['HistoryRecordActor'] as $h) {
      if($h['co_person_id'] != $coPersonId) {
        $this->HistoryRecord->expungeActor($h['id'], $expungerCoPersonId);
      }
    }
    
    // Manually delete org identities since they will not cascade via org identity link.
    // Only do this where there are no other CO People linked to the org identity.
    // Note we're walking two links here... the first is all Org Identities attached
    // to the current CO Person, then the second is all CO People attached to each
    // of those Org Identities.
    
    // We need to do this before deleting the CO Person due to some deep Cake error
    // when selecting the dependency data related to the Org Identity to prepare for
    // deletion generating an invalid SELECT statement and throwing an error.
    
    foreach($coperson['CoOrgIdentityLink'] as $lnk) {
      if(count($lnk['OrgIdentity']['CoOrgIdentityLink']) <= 1) {
        if(!empty($lnk['OrgIdentity']['CoOrgIdentityLink'][0]['id'])) {
          // We need to manually remove this link since it hasn't been removed via
          // the CO Person record yet.
          $this->CoOrgIdentityLink->delete($lnk['OrgIdentity']['CoOrgIdentityLink'][0]['id']);
        }
        
        $this->CoOrgIdentityLink->OrgIdentity->delete($lnk['OrgIdentity']['id']);
      }
    }
    
    // Delete the CO Person. Note that normally (CoPeopleController:checkDeleteDependencies)
    // we verify that each COU the CO Person belongs to can be admin'd by the currently authenticated
    // CO Person. However, at the moment CO People can only be deleted by CO and CMP admins, so there
    // is no need for this check.
    
    // We first delete all dependencies and then delete the CO Person itself (again with cascading
    // to dependencies). The reason for this is that, depending on what order Cake deletes the
    // dependencies in, new history records might be created for the CO Person as a side effect of
    // the delete (typically because provisioning fires off). After _deleteDependent, we should be
    // left with only minimal new residue which the normal delete() will clean up.
    
    $this->_deleteDependent($coPersonId, true);
    $this->delete($coPersonId);
    
    // Need to check if there was an error since we can't see if something failed
    // with provisioners. Note this only catches SQL issues, not general provisioner errors.
    if($dbc->lastError() != null) {
      throw new RuntimeException($dbc->lastError());
    }
    
    $dbc->commit();
    
    return true;
  }
  
  /**
   * Perform a find for a CO Person, but pull exactly the associated data needed
   * for an expunge operation.
   *
   * @since  COmanage Registry v0.8.5
   * @param  Integer CO Person ID
   * @return Array CoPerson information, as returned by find (with some associated data)
   */
  
  public function findForExpunge($coPersonId) {
    $args = array();
    $args['conditions']['CoPerson.id'] = $coPersonId;
    $args['contain'][] = 'PrimaryName';
    $args['contain'][] = 'Co';
    $args['contain'][] = 'CoPersonRole';
    $args['contain']['CoPersonRole'][] = 'Cou';
    $args['contain'][] = 'CoOrgIdentityLink';
    $args['contain']['CoOrgIdentityLink'][] = 'OrgIdentity';
    // This next line pulls all links for the OrgIdentity, not just the one related to this CO Person
    $args['contain']['CoOrgIdentityLink']['OrgIdentity'][] = 'CoOrgIdentityLink';
    $args['contain']['CoOrgIdentityLink']['OrgIdentity'][] = 'Identifier';
    $args['contain']['CoOrgIdentityLink']['OrgIdentity'][] = 'PrimaryName';
    $args['contain'][] = 'CoNotificationActor';
    $args['contain'][] = 'CoNotificationRecipient';
    $args['contain'][] = 'CoNotificationResolver';
    $args['contain'][] = 'HistoryRecordActor';
    
    return $this->find('first', $args);
  }
  
  /**
   * Obtain all people associated with a Group
   *
   * @since  COmanage Registry v0.6
   * @param  Integer CO Group ID
   * @param  Integer Maximium number of results to retrieve (or null)
   * @param  Integer Offset to start retrieving results from (or null)
   * @return Array CoPerson information, as returned by find (with some associated data)
   */
  
  function findForCoGroup($coGroupId, $limit=null, $offset=null) {
    $args = array();
    $args['joins'][0]['table'] = 'co_group_members';
    $args['joins'][0]['alias'] = 'CoGroupMember';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoGroupMember.co_person_id';
    $args['conditions']['CoGroupMember.co_group_id'] = $coGroupId;
    $args['conditions']['OR']['CoGroupMember.member'] = 1;
    $args['conditions']['OR']['CoGroupMember.owner'] = 1;
    // We use contain here to pull data for VootController
    $args['contain'][] = 'PrimaryName';
    $args['contain'][] = 'EmailAddress';
    
    if($limit) {
      $args['limit'] = $limit;
    }
    
    if($offset) {
      $args['offset'] = $offset;
    }
    
    return $this->find('all', $args);
  }
  
  /**
   * Obtain the CO Person ID for an identifier (which must be Active).
   *
   * @since  COmanage Registry v0.6
   * @param  String Identifier
   * @param  String Identifier type (null for any type; not recommended)
   * @param  Boolean Login identifiers only
   * @return Array CO Person IDs
   * @throws InvalidArgumentException
   */
  
  public function idForIdentifier($coId, $identifier, $identifierType=null, $login=false) {
    // Notice confusing change in order of arguments due to which ones default to null/false
    
    try {
      $coPersonIds = $this->idsForIdentifier($identifier, $identifierType, $login, $coId);
    }
    catch(Exception $e) {
      throw new InvalidArgumentException($e->getMessage());
    }
    
    return $coPersonIds[0];
  }
  
  /**
   * Obtain all CO Person IDs for an identifier (which must be Active).
   *
   * @since  COmanage Registry v0.6
   * @param  String Identifier
   * @param  String Identifier type (null for any type; not recommended)
   * @param  Boolean Login identifiers only
   * @param  Integer CO ID (null for all matching COs)
   * @return Array CO Person IDs
   * @throws InvalidArgumentException
   */
  
  function idsForIdentifier($identifier, $identifierType=null, $login=false, $coId=null) {
    $ret = array();
    
    // First pull the identifier record
    
    $args = array();
    $args['conditions']['Identifier.identifier'] = $identifier;
    if($login) {
      $args['conditions']['Identifier.login'] = true;
    }
    $args['conditions']['Identifier.status'] = StatusEnum::Active;
    $args['contain'] = false;
    
    if($coId != null) {
      // Only pull records associated with this CO ID
      
      $args['joins'][0]['table'] = 'co_people';
      $args['joins'][0]['alias'] = 'CoPerson';
      $args['joins'][0]['type'] = 'LEFT';
      $args['joins'][0]['conditions'][0] = 'Identifier.co_person_id=CoPerson.id';
      $args['joins'][1]['table'] = 'org_identities';
      $args['joins'][1]['alias'] = 'OrgIdentity';
      $args['joins'][1]['type'] = 'LEFT';
      $args['joins'][1]['conditions'][0] = 'Identifier.org_identity_id=OrgIdentity.id';
      $args['conditions']['OR']['CoPerson.co_id'] = $coId;
      
      $CmpEnrollmentConfiguration = ClassRegistry::init('CmpEnrollmentConfiguration');
      
      if($CmpEnrollmentConfiguration->orgIdentitiesPooled()) {
        $args['conditions']['OR'][] = 'OrgIdentity.co_id IS NULL';
      } else {
        $args['conditions']['OR']['OrgIdentity.co_id'] = $coId;
      }
    }
    
    if($identifierType) {
      $args['conditions']['Identifier.type'] = $identifierType;
    }
    
    // We might get more than one record, especially if no CO ID and/or type was specified.
    
    $ids = $this->Identifier->find('all', $args);
    
    if(!empty($ids)) {
      foreach($ids as $i) {
        if(isset($i['Identifier']['co_person_id'])) {
          // The identifier is attached to a CO Person, return that ID.
          
          $ret[] = $i['Identifier']['co_person_id'];
        } else {
          // Map the org identity to a CO person. We might pull more than one.
          // In this case, it's OK since they come back to the same org person.
          
          $args = array();
          $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $i['Identifier']['org_identity_id'];
          $args['fields'][] = 'CoOrgIdentityLink.co_person_id';
          $args['contain'] = false;
          
          if($coId != null) {
            $args['joins'][0]['table'] = 'co_people';
            $args['joins'][0]['alias'] = 'CoPerson';
            $args['joins'][0]['type'] = 'INNER';
            $args['joins'][0]['conditions'][0] = 'CoOrgIdentityLink.co_person_id=CoPerson.id';
            $args['conditions']['CoPerson.co_id'] = $coId;
          }
          
          $links = $this->CoOrgIdentityLink->find('list', $args);
          
          if(!empty($links)) {
            foreach(array_values($links) as $v) {
              $ret[] = $v;
            }
          } else {
            throw new InvalidArgumentException(_txt('er.cop.unk'));
          }
        }
      }
    } else {
      throw new InvalidArgumentException(_txt('er.id.unk'));
    }
    
    return $ret;
  }
  
  /**
   * Attempt to match existing records based on the provided criteria.
   *
   * @since  COmanage Registry v0.5
   * @param  integer Identifier of CO
   * @param  Array Hash of field name + search pattern pairs
   * @return Array CO Person records of matching individuals
   */
  
  public function match($coId, $criteria) {
    // XXX For now, we only support Name. That's not the right long term design.
    
    // We need to have at least one non-trivial condition
    if((!isset($criteria['Name.given']) || strlen($criteria['Name.given']) < 3)
       && (!isset($criteria['Name.family']) || strlen($criteria['Name.family']) < 3)) {
      return(array());
    }
    
    // To perform case insensitive searching, we convert everything to lowercase
    if(isset($criteria['Name.given'])) {
      $args['conditions']['LOWER(Name.given) LIKE'] = strtolower($criteria['Name.given']) . '%';
    }
    if(isset($criteria['Name.family'])) {
      $args['conditions']['LOWER(Name.family) LIKE'] = strtolower($criteria['Name.family']) . '%';
    }
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['joins'][0]['table'] = 'names';
    $args['joins'][0]['alias'] = 'Name';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.id=Name.co_person_id';
    $args['contain'][] = 'PrimaryName';
    $args['contain'][] = 'CoPersonRole';
    
    return $this->find('all', $args);
  }
  
  /**
   * Determine if an org identity is already associated with a CO.
   *
   * @since  COmanage Registry v0.3
   * @param  integer Identifier of CO
   * @param  integer Identifier of Org Identity
   * @return boolean true if $orgIdentityId is linked to $coId, false otherwise
   */
  
  public function orgIdIsCoPerson($coId, $orgIdentityId) {
    // Try to retrieve a link for this org identity id where the co person id
    // is a member of this CO
      
    $args['joins'][0]['table'] = 'co_org_identity_links';
    $args['joins'][0]['alias'] = 'CoOrgIdentityLink';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.id=CoOrgIdentityLink.co_person_id';
    $args['conditions']['CoOrgIdentityLink.org_identity_id'] = $orgIdentityId;
    $args['conditions']['CoPerson.co_id'] = $coId;
    $args['contain'] = false;
    
    $link = $this->find('first', $args);
    
    if(!empty($link)) {
      return true;
    }
    
    return false;
  }

  /**
   * Determine the current status of the provisioning targets for this CO Person.
   *
   * @since  COmanage Registry v0.8
   * @param  Integer CO Person ID
   * @return Array Current status of provisioning targets
   * @throws RuntimeException
   */
  
  public function provisioningStatus($coPersonId) {
    // First, obtain the list of active provisioning targets for this person's CO.
    
    $args = array();
    $args['joins'][0]['table'] = 'co_people';
    $args['joins'][0]['alias'] = 'CoPerson';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPerson.co_id=CoProvisioningTarget.co_id';
    $args['conditions']['CoPerson.id'] = $coPersonId;
    $args['conditions']['CoProvisioningTarget.status !='] = ProvisionerStatusEnum::Disabled;
    $args['contain'] = false;
    
    $targets = $this->Co->CoProvisioningTarget->find('all', $args);
    
    if(!empty($targets)) {
      // Next, for each target ask the relevant plugin for the status for this person.
      
      // We may end up querying the same Plugin more than once, so maintain a cache.
      $plugins = array();
      
      for($i = 0;$i < count($targets);$i++) {
        $pluginModelName = $targets[$i]['CoProvisioningTarget']['plugin']
                         . ".Co" . $targets[$i]['CoProvisioningTarget']['plugin'] . "Target";
        
        if(!isset($plugins[ $pluginModelName ])) {
          $plugins[ $pluginModelName ] = ClassRegistry::init($pluginModelName, true);
          
          if(!$plugins[ $pluginModelName ]) {
            throw new RuntimeException(_txt('er.plugin.fail', array($pluginModelName)));
          }
        }
        
        $targets[$i]['status'] = $plugins[ $pluginModelName ]->status($targets[$i]['CoProvisioningTarget']['id'],
                                                                      $coPersonId);
      }
    }
    
    return $targets;
  }
  
  /**
   * Recalculate the status of a CO Person based on the attached CO Person Roles.
   *
   * @since  COmanage Registry v0.9.2
   * @param  Integer $id CO Person ID
   * @param  Boolean $provision Whether to run provisioners
   * @return StatusEnum New status
   * @throws RuntimeException
   */
  
  public function recalculateStatus($id, $provision=true) {
    $newStatus = null;
    
    // We rank status by "preference". More "preferred" statuses rank higher.
    // To faciliate comparison, we'll convert this to an integer value and store
    // it in a hash. Most preferred numbers are larger so we can say things like
    // Active > Expired. Possibly this should go somewhere else, if useful.
    
    $statusRanks = array(
      // Active statuses are most preferred
      StatusEnum::Active                => 14,
      StatusEnum::GracePeriod           => 13,
      
      // Next come invitation statuses
      StatusEnum::Approved              => 12,
      StatusEnum::PendingApproval       => 11,
      StatusEnum::Confirmed             => 10,
      StatusEnum::PendingConfirmation   => 9,
      StatusEnum::Invited               => 8,
      StatusEnum::Pending               => 7,  // It's not clear this is used for anything
      
      // Then expired statuses
      StatusEnum::Suspended             => 6,
      StatusEnum::Expired               => 5,
      
      // Denied and Declined are below expired since other roles are more likely to have been used
      StatusEnum::Denied                => 4,
      StatusEnum::Declined              => 3,
      
      // Finally, we generally don't want Deleted or Duplicate unless all roles are deleted or duplicates
      StatusEnum::Deleted               => 2,
      StatusEnum::Duplicate             => 1
    );
    
    // Start by pulling the roles for this person
    
    $args = array();
    $args['conditions']['CoPersonRole.co_person_id'] = $id;
    $args['contain'] = false;
    
    $roles = $this->CoPersonRole->find('all', $args);
    
    foreach($roles as $role) {
      if(!$newStatus) {
        // This is the first role, just set the new status to it
        
        $newStatus = $role['CoPersonRole']['status'];
      } else {
        // Check if this role's status is more preferable than the current status
        
        if($statusRanks[ $role['CoPersonRole']['status'] ] > $statusRanks[$newStatus]) {
          $newStatus = $role['CoPersonRole']['status'];
        }
      }
    }
    
    if($newStatus) {
      $this->id = $id;
      
      // Pull the current value
      $curStatus = $this->field('status');
      
      if($newStatus != $curStatus) {
        $coId = $this->field('co_id');
        
        // Update the CO Person status
        $this->saveField('status', $newStatus, array('provision' => $provision));
        
        // Record history
        try {
          $ctxt = $this->changesToString(array('CoPerson' => array('status' => $newStatus)),
                                         array('CoPerson' => array('status' => $curStatus)),
                                         $coId);
          
          $this->HistoryRecord->record($role['CoPersonRole']['co_person_id'],
                                       null,
                                       null,
                                       null,
                                       ActionEnum::CoPersonStatusRecalculated,
                                       _txt('rs.cop.recalc', array($ctxt)));
        }
        catch(Exception $e) {
          throw new RuntimeException($e->getMessage());
        }
      }
      // else nothing to do, status is unchanged
    }
    // else no roles, leave status unchanged
    
    return $newStatus;
  }
  
  /**
   * Retrieve list of sponsors for display in dropdown.
   *
   * @since  COmanage Registry v0.3
   * @return Array Array with co_person id as keys and full name as values; array will be empty if sponsoring is disabled
   */
  
  public function sponsorList($coId) {
    $ret = array();
    
    // For eligibility by group(s), the group IDs to check
    $groupIds = array();
    
    // First we need the current setting(s).
    $mode = $this->Co->CoSetting->getSponsorEligibility($coId);
    
    switch($mode) {
      case SponsorEligibilityEnum::CoOrCouAdmin:
        // First pull the list of COUs
        $cous = $this->Co->Cou->allcous($coId, "names");
        
        foreach($cous as $cou) {
          // Find the admin group ID
          $groupIds[] = $this->Co->CoGroup->adminCoGroupId($coId, $cou);
        }
        // Fall through, we want the CO Admin group as well
      case SponsorEligibilityEnum::CoAdmin:
        // Find the admin group ID
        $groupIds[] = $this->Co->CoGroup->adminCoGroupId($coId);
        break;
      case SponsorEligibilityEnum::CoGroupMember:
        // Find the configured group
        $groupId = $this->Co->CoSetting->getSponsorEligibilityCoGroup($coId);
        
        if($groupId) {
          $groupIds[] = $groupId;
        }
        break;
      case SponsorEligibilityEnum::CoPerson:
        // Any Active CO Person may be a sponsor
        $args = array();
        $args['conditions']['CoPerson.co_id'] = $coId;
        $args['conditions']['CoPerson.status'] = StatusEnum::Active;
        $args['contain'][] = 'PrimaryName';
        $args['order'] = array('PrimaryName.family ASC');
        
        $people = $this->find('all', $args);
        
        // Assemble the list, using generateCn
        foreach($people as $p) {
          $ret[ $p['CoPerson']['id'] ] = generateCn($p['PrimaryName']);
        }
        break;
      case SponsorEligibilityEnum::None:
        // Just return an empty array
        break;
      default:
        throw new InvalidArgumentException(_txt('er.unknown', $mode));
        break;
    }
    
    if(!empty($groupIds)) {
      $members = array();
      
      foreach($groupIds as $gid) {
        // Find the Active people in the group
        $args = array();
        $args['conditions']['CoGroupMember.co_group_id'] = $gid;
        $args['conditions']['CoPerson.status'] = StatusEnum::Active;
        $args['contain']['CoPerson'] = 'PrimaryName';
        
        $members = array_merge($members, $this->CoGroupMember->find('all', $args));
      }
      
      // Sort the results by last name
      $sorted = Hash::sort($members, '{n}.CoPerson.PrimaryName.family', 'asc');
      
      // And finally key the results. This will also eliminate dupes (by overwriting the same key).
      foreach($sorted as $s) {
        $ret[ $s['CoPerson']['id'] ] = generateCn($s['CoPerson']['PrimaryName']);
      }
    }
    
    return $ret;
  }
  
  /**
   * Timezone validation.
   *
   * @since  COmanage Registry v1.0.0
   * @return Boolean True if valid timezone provided, false otherwise
   */
  
  public function validateTimeZone($check) {
    return in_array($check['timezone'], array_values(timezone_identifiers_list()));
  }
}
