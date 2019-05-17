<?php
/**
 * COmanage Registry Placeholder External Auth Logout Handler
 *
 * Portions licensed to the University Corporation for Advanced Internet
 * Development, Inc. ("UCAID") under one or more contributor license agreements.
 * See the NOTICE file distributed with this work for additional information
 * regarding copyright ownership.
 *
 * UCAID licenses this file to you under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with the
 * License. You may obtain a copy of the License at:
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * @link          http://www.internet2.edu/comanage COmanage Project
 * @package       registry
 * @since         COmanage Registry v0.1
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */

// Since this page isn't part of the framework, we need to reconfigure
// to access the Cake session

$sid = "";
foreach ($_COOKIE as $key => $value){
  if(strpos($key, "co_registry_sid") !== false){
    $sid .= $key;
  }
}
session_name($sid);
session_start();

unset($_SESSION['Auth']);

header("Location: " . "/registry/Shibboleth.sso/Logout?return=%2Fregistry%2Fpages%2Fpublic%2Floggedout");
