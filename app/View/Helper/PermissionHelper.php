<?php
/**
 * COmanage Registry Permission Helper
 *
 * Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
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
 * @copyright     Copyright (C) 2014 University Corporation for Advanced Internet Development, Inc.
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.9
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 * @version       $Id$
 */

App::uses('AppHelper', 'View/Helper');

class PermissionHelper extends AppHelper {
  /**
   * Calculate a self service permission. Because this is likely to be called in the
   * context of a function that can be run by an admin, this will also return suitable
   * values when invoked by an admin.
   *
   * @since  COmanage Registry v0.9
   * @param  Array   $permissions Array of permissions as set by the Controller
   * @param  Boolean $e           Whether fields are editable, as generally calculated by most views
   * @param  String  $model       Model name to calculate permission for
   * @param  String  $type        Type to calculate permission for
   * @return PermissionEnum
   */
  
  public function selfService($permissions, $e, $model, $type=null) {
    // This logic is similar to, but not the same as, that in Model/CoSelfServicePermission.php
    
    if(!$permissions['selfsvc']) {
      // The requester isn't self
      
      return ($e ? PermissionEnum::ReadWrite : PermissionEnum::ReadOnly);
    }
    
    if($type
       && isset($permissions['selfsvc'][$model][$type])) {
      return $permissions['selfsvc'][$model][$type];
    }
    
    if(isset($permissions['selfsvc'][$model]['*'])) {
      // Use default value
      return $permissions['selfsvc'][$model]['*'];
    }
    
    return PermissionEnum::None;
  }
}