<?php
/**
 * COmanage Registry ORCID Source Plugin Language File
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
 * @package       registry-plugin
 * @since         COmanage Registry v2.0.0
 * @license       Apache License, Version 2.0 (http://www.apache.org/licenses/LICENSE-2.0)
 */
 
global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_rcauth_source_texts['en_US'] = array(
	// Titles, per-controller
	'ct.rcauth_sources.1'  => 'RCAUTH Organizational Identity Source',
	'ct.rcauth_sources.2'  => 'RCAUTH Source',
	'ct.rcauth_sources.pl' => 'RCAUTH Organizational Identity Sources',

	// Error messages
	'er.rcauthsource.code'       => 'Error exchanging code for RCAUTH and access token: %1$s',
	'er.rcauthsource.search'     => 'Search request returned %1$s',
	'er.rcauthsource.token.api'  => 'Access token not found in API response',
	'er.rcauthsource.token.none' => 'Access token not configured (try resaving configuration)',
	'er.rcauthsource.mp_oa2_server.none' => 'No or invalid MasterPortal OA2 Url. Please check RCAuth OIS configuration',

	// Plugin texts
	'pl.rcauthsource.clientid'       => 'Client ID',
	'pl.rcauthsource.clientid.desc'  => 'Client ID obtained from registering with the RCAUTH Public ID',
	'pl.rcauthsource.linked'         => 'Obtained DN "%1$s" via authenticated OAuth flow',
	'pl.rcauthsource.redirect_url'   => 'RCAUTH Redirect URI',
	'pl.rcauthsource.secret'         => 'Client Secret',
	'pl.rcauthsource.secret.desc'    => 'Client Secret obtained from registering with the RCAUTH Public ID',
	'pl.rcauthsource.issuer'         => 'DN Issuer',
	'pl.rcauthsource.issuer.desc'    => 'The DN of the certificate issuer',
	'pl.rcauthsource.idphint'        => 'IdpHint parameter',
	'pl.rcauthsource.idphint.desc'   => 'Optionally the VO portal can redirect the user to a specific IdP by also sending an idphint parameter',
	'pl.rcauthsource.mp_oa2_server'        => 'MasterPortal OP configuration URL',
	'pl.rcauthsource.mp_oa2_server.desc'   => 'URL of the well known configuration of MasterPortal\'s OP',
);
