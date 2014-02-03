<?php
/**
 * COmanage Registry Grouper Rest Client
 *
 * Copyright (C) 2012 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2013 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.8.3
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses('GrouperRestClientException', 'GrouperProvisioner.Lib');
App::uses('HttpSocket', 'Network/Http');

/**
 * An instance is used to query Grouper using
 * the RESTful Grouper endpoints.
 */
class GrouperRestClient extends HttpSocket {

  //private $httpSocket = null;
  private $defaultRequest = null;

  /**
   * Constructor for GrouperRestClient
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.8.3
   * @return instance
   */
  function __construct($serverUrl, $contextPath, $login, $password, $config = array(), $autoConnect = false) {
    parent::__construct($config, $autoConnect);

    // This should be configurable.
    $this->config['ssl_verify_peer'] = false;

    $uri = $this->_parseUri($serverUrl);
    $uri['user'] = $login;
    $uri['pass'] = $password;
    $uri['path'] = $contextPath . '/servicesRest/v2_1_000/';
    
    $this->defaultRequest = array(
      'method' => 'POST',
      'uri' => $uri,
      'header' => array(
        'Content-Type' => 'text/x-json'
        ),
      );
  }

  /**
   * Destructor for GrouperRestClient
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.8.3
   * @return void
   */
  public function __destruct() {
  }

  /**
   * Add many members to a group
   * - precondition: 
   * - postcondition: 
   *
   * @since         COmanage Directory 0.8.3
   * @groupName     full name of group including stem(s)
   * @subjects      array of subjects to add to group
   * @return        void
   * @throws        GrouperRestClientException
   */
  public function addManyMember($groupName, $subjects) {
    $body = array(
      'WsRestAddMemberRequest' => array(
        'actAsSubjectLookup' => array(
          'subjectId' => 'GrouperSystem'
        ),
        'replaceAllExisting' => 'F',
        'subjectLookups' => array()
      )
    );

    foreach ($subjects as $s) {
      $body['WsRestAddMemberRequest']['subjectLookups'][] = array('subjectId' => $s);
    }

    $body = json_encode($body);
    $request = array(
      'method' => 'PUT',
      'uri' => array(
        'path' => 'groups/' . urlencode($groupName) . '/members' 
        ),
      'body' => $body
      );

    $result = $this->grouperRequest($request, 201);

    $success = $result->WsAddMemberResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from add member was not success');
    }

    return ;
  }

  /**
   * Add a member to a group
   * - precondition: 
   * - postcondition: 
   *
   * @since         COmanage Directory 0.8.3
   * @groupName     full name of group including stem(s)
   * @subject       subject to add to group
   * @return        void
   * @throws        GrouperRestClientException
   */
  public function addMember($groupName, $subject) {
    $request = array(
      'method' => 'PUT',
      'uri' => array(
        'path' => 'groups/' . urlencode($groupName) . '/members/' . urlencode($subject)
        )
      );

    $result = $this->grouperRequest($request, 201);

    $success = $result->WsAddMemberLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from add member was not success');
    }

    return ;
  }

  /**
   * Delete many member from a group
   * - precondition: Subject is a member of the Grouper group
   * - postcondition: Subject is no longer a member of the Grouper group
   *
   * @since         COmanage Directory 0.8.3
   * @groupName     full name of group including stem(s)
   * @subjects      array of subject to remove from group
   * @return void
   * @throws        GrouperRestClientException
   */
  public function deleteManyMember($groupName, $subjects) {
    $body = array(
      'WsRestDeleteMemberRequest' => array(
        'actAsSubjectLookup' => array(
          'subjectId' => 'GrouperSystem'
        ),
        'subjectLookups' => array()
      )
    );

    foreach ($subjects as $s) {
      $body['WsRestDeleteMemberRequest']['subjectLookups'][] = array('subjectId' => $s);
    }

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'groups/' . urlencode($groupName) . '/members' 
        ),
      'body' => $body
      );

    $result = $this->grouperRequest($request, 200);

    $success = $result->WsDeleteMemberResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from delete member was not success');
    }

    return ;
  }

  /**
   * Delete a member from a group
   * - precondition: Subject is a member of the Grouper group
   * - postcondition: Subject is no longer a member of the Grouper group
   *
   * @since         COmanage Directory 0.8.3
   * @groupName     full name of group including stem(s)
   * @subject       subject to remove from group
   * @return void
   * @throws        GrouperRestClientException
   */
  public function deleteMember($groupName, $subject) {

    $request = array(
      'method' => 'DELETE',
      'uri' => array(
        'path' => 'groups/' . urlencode($groupName) . '/members/' . urlencode($subject)
        )
      );

    $result = $this->grouperRequest($request, 200);

    $success = $result->WsDeleteMemberLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from delete member was not success');
    }

    return ;
  }

  /**
   * Get members of a Grouper group.
   * - precondition: 
   * - postcondition: 
   *
   * @since               COmanage Directory 0.8.3
   * @names               array of group names
   * @return              array of subject ids
   * @throws              GrouperRestClientException
   */
  public function getMembersManyGroups($names) {
    $body = array(
      'WsRestGetMembersRequest' => array(
        'memberFilter' => 'Immediate',
        'includeGroupDetail' => 'T',
        'includeSubjectDetail' => 'T',
        'wsGroupLookups' => array(
          )
        )
      );

    foreach($names as $n) {
      $body['WsRestGetMembersRequest']['wsGroupLookups'][] = array('groupName' => $n);
    }

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'groups'
        ),
      'body' => $body
      );

    $result = $this->grouperRequest($request, 200);
    $success = $result->WsGetMembersResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from get members was not success');
    }

    $results = $result->WsGetMembersResults->results;

    $members = array();
    foreach($results as $r) {
      $members[$r->wsGroup->name] = array();
      if (property_exists($r,'wsSubjects')){
        foreach($r->wsSubjects as $s){
          $members[$r->wsGroup->name][] = $s->id;
        }
      }
    }

    return $members;
  }

  /**
   * Delete a group
   * - precondition: group exists in Grouper
   * - postcondition: group does not exist in Grouper
   *
   * @since       COmanage Directory 0.8.3
   * @groupName   full name of group including stem(s)
   * @return      void
   * @throws      GrouperRestClientException
   */
  public function groupDelete($groupName) {
    $request = array(
      'method' => 'DELETE',
      'uri' => array(
        'path' => 'groups/' . urlencode($groupName)
        )
      );

    $result = $this->grouperRequest($request, 200);

    $success = $result->WsGroupDeleteLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from group delete was not success');
    }

    return ;
  }

  /**
   * Determine if a group exists in Grouper
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.8
   * @groupName  full name of group
   * @return boolean
   * @throws GrouperRestClientException
   */
  public function groupExists($groupName) {
    $body = array(
      'WsRestFindGroupsLiteRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'queryFilterType' => 'FIND_BY_GROUP_NAME_EXACT',
        'groupName' => $groupName,
        'includeGroupDetail' => 'T'
        )
      );


    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'groups',
        ),
      'body' => $body,
      );

    $result = $this->grouperRequest($request, 200);

    $success = $result->WsFindGroupsResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from stem query was not success');
    }


    $groupExists = ! empty($result->WsFindGroupsResults->groupResults);

    return $groupExists;
  }

  /**
   * Create a new group or role in Grouper
   * - precondition: Group or role does not exist in Grouper
   * - postcondition: Group or role exists in Grouper
   *
   * @since             COmanage Directory 0.8.3
   * @groupName         full name of group including stem(s)
   * @description       description of the group 
   * @displayExtension  displayExtension for the group
   * @type              one of 'group' or 'role'
   * @return void
   * @throws GrouperRestClientException
   */
  public function groupSave($groupName, $description, $displayExtension, $type = 'group') {
    $body = array(
      'WsRestGroupSaveLiteRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'description' => $description,
        'displayExtension' => $displayExtension,
        'groupName' => $groupName,
        'typeOfGroup' => $type
        )
      );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'groups/' . urlencode($groupName)
        ),
      'body' => $body
      );

    $result = $this->grouperRequest($request, 201);

    $success = $result->WsGroupSaveLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from group save was not success');
    }

    $resultCode = $result->WsGroupSaveLiteResult->resultMetadata->resultCode;
    if ($resultCode != 'SUCCESS_INSERTED') {
      throw new GrouperRestClientException('Group already existed');
    }

    return $result->WsGroupSaveLiteResult->wsGroup;
  }

  /**
   * Update an existing group
   * - precondition: group exists in Grouper
   * - postcondition: group exists in Grouper with updated name or description
   *
   * @since  COmanage Directory 0.8.3
   * @oldGroupName  old full name of group including stem(s)
   * @newGroupName  new full name of group including stem(s), may be same as old
   * @description description of the group 
   * @displayExtension display extension for the group 
   * @return void
   * @throws GrouperRestClientException
   */
  public function groupUpdate($oldGroupName, $newGroupName, $description, $displayExtension) {
    $body = array(
      'WsRestGroupSaveLiteRequest' => array(
        'saveMode' => 'UPDATE',
        'actAsSubjectId' => 'GrouperSystem',
        'description' => $description,
        'displayExtension' => $displayExtension,
        'groupName' => $newGroupName
        )
      );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'groups/' . urlencode($oldGroupName)
        ),
      'body' => $body
      );

    $result = $this->grouperRequest($request, 201);

    $success = $result->WsGroupSaveLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from group save/update was not success');
    }

    $resultCode = $result->WsGroupSaveLiteResult->resultMetadata->resultCode;
    if ($resultCode != 'SUCCESS_UPDATED' and $resultCode!= 'SUCCESS_NO_CHANGES_NEEDED') {
        throw new GrouperRestClientException('Error updating group');
    }

    return ;
  }

  /**
   * Merge request arrays for HttpSocket instance
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.8.3
   * @req1   first request array
   * @req2   second and dominant request array
   * @return void
   */
  private function mergeRequests($req1, $req2) {
    foreach ($req2 as $key => $value){
      if(array_key_exists($key, $req1) and is_array($value))
        $req1[$key] = $this->mergeRequests($req1[$key], $req2[$key]);
      else
        $req1[$key] = $value;
    }

    return $req1;
  }

  /**
   * Query Grouper using request array
   * - precondition: 
   * - postcondition: 
   *
   * @since         COmanage Directory 0.8.3
   * @request       request suitable for passing to HttpSocket instance
   * @expectedCode  expected return code from Grouper service
   * @return        decoded JSON response as object 
   * @throws        GrouperRestClientException
   */
  public function grouperRequest(& $request, $expectedCode) {
    if (array_key_exists('uri', $request))
      if (array_key_exists('path', $request['uri']))
        $request['uri']['path'] = $this->defaultRequest['uri']['path'] . $request['uri']['path'];  

    $request = $this->mergeRequests($this->defaultRequest, $request);

    try {
      $response = $this->request($request);

    } catch (SocketException $e) {
      throw new GrouperRestClientException('Error querying Grouper: ' . $e->getMessage(), 1, $e);
    }

    $code = $response->code;
    if ($code != $expectedCode) {
      throw new GrouperRestClientException("Grouper returned code $code, expected was $expectedCode");
    }

    try {
      $result = json_decode($response->body);
    } catch (Exception $e) {
      throw new GrouperRestClientException('Error decoding JSON from Grouper: ' . $e->getMessage(), 2, $e);
    }

    return $result;
  }

  /**
   * Determine if a stem exists in Grouper
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.8.3
   * @stemName  full name of stem
   * @return boolean
   * @throws GrouperRestClientException
   */
  public function stemExists($stemName) {
    $body = array(
      'WsRestFindStemsRequest' => array(
        'actAsSubjectLookup' => array(
          'subjectId' => 'GrouperSystem'
          ),
        'wsStemQueryFilter' => array(
          'stemName' => $stemName,
          'stemQueryFilterType' => 'FIND_BY_STEM_NAME'
          )
        )
      );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'stems',
        ),
      'body' => $body,
      );

    $result = $this->grouperRequest($request, 200);
    $success = $result->WsFindStemsResults->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from stem query was not success');
    }

    $stemExists = ! empty($result->WsFindStemsResults->stemResults);

    return $stemExists;
  }

  /**
   * Save or create a new stem
   * - precondition: 
   * - postcondition: 
   *
   * @since  COmanage Directory 0.8.3
   * @stemName  full name of stem
   * @description description of the stem 
   * @displayExtension displayExtension for the stem
   * @return void
   * @throws GrouperRestClientException
   */
  public function stemSave($stemName, $description, $displayExtension) {
    $body = array(
      'WsRestStemSaveLiteRequest' => array(
        'actAsSubjectId' => 'GrouperSystem',
        'description' => $description,
        'displayExtension' => $displayExtension,
        'stemName' => $stemName
        )
      );

    $body = json_encode($body);

    $request = array(
      'uri' => array(
        'path' => 'stems/' . urlencode($stemName)
        ),
      'body' => $body
      );

    $result = $this->grouperRequest($request, 201);
    $success = $result->WsStemSaveLiteResult->resultMetadata->success;
    if ($success != 'T') {
      throw new GrouperRestClientException('Result from stem save was not success');
    }

    return $result->WsStemSaveLiteResult->wsStem;
  }
}