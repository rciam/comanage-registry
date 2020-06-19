<?php
/**
 * COmanage Registry Remote User Authentication Login
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

// So we don't have to put the entire app under .htaccess auth, we grab REMOTE_USER
// and stuff it into the session so the auth component knows who we authenticated.

// Since this page isn't part of the framework, we need to reconfigure
// to access the Cake session.

session_name("CAKEPHP");
session_start();

// Set the user

if(empty($_SERVER['REMOTE_USER'])) {
  print	"ERROR: REMOTE_USER is empty. Please check your configuration.";
  exit;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
  <script type="text/javascript" src="../../js/comanage.js"></script>
  <script type="text/javascript" src="../../js/jquery/spin.min.js"></script>
</head>
<body onload="actionsLoad()" style="margin:0;">
<div id="coSpinner"></div>
<?php

$_SESSION['Auth']['external']['user'] = $_SERVER['REMOTE_USER'];
$path = str_replace('auth/login/', '', $_SERVER["REQUEST_URI"]);
$redirect_url = $_SERVER["REQUEST_SCHEME"] . '://' . $_SERVER["SERVER_NAME"] . $path . 'users/login';
?>


<script>
    function actionsLoad() {
        let coSpinnerTarget = document.getElementById('coSpinner');
        // coSpinnerOpts are set in js/comanage.js
        let coSpinner = new Spinner(coSpinnerOpts).spin(coSpinnerTarget);
        // Show spinner and Redirect to COmanage
        let redirect_url = "<?php print $redirect_url; ?>";
        window.location.assign(redirect_url);
    }
</script>
</body>
</html>
