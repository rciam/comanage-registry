<?php
/**
 * Created by PhpStorm.
 * User: root
 * Date: 4/6/2018
 * Time: 9:42 μμ
 */

include_once("VomsClient.php");

// Create vomsclient for specific vo server
$vo_chkInt = new vomsClient("voms2.hellasgrid.gr");

// List members
//var_dump($vo_chkInt->vo_memberships());
//print("\n");
//
//// Find user
//$dn = "/C=GR/O=HellasGrid/OU=grnet.gr/CN=Ioannis Igoumenos";
//print("{$dn} ".($vo_chkInt->user_membership($dn) ? "user exists" : "user not exists")."\n");
//$dn = "/C=GR/O=HellasGrid/OU=ht.grnet.gr/CN=Ioannis Igoumenos";
//print("{$dn} ".($vo_chkInt->user_membership($dn) ? "user exists" : "user not exists")."\n");
//print("\n");
//
// Create user
//$dn = "/O=EGI/OU=AAI-Pilot/O=EGI Foundation/CN=Nicolas Liampotis 7ewsApjEZsBcCLz-";
$dn = "/DC=eu/DC=rcauth/DC=rcauth-clients/O=EGI Foundation/CN=Nicolas Liampotis 7ewsApjEZsBcCLz-";
$ca = "/O=EGI/OU=AAI-Pilot/CN=EGI Simple Demo CA";
//$ca = "O=EGI, OU=AAI-Pilot, CN=EGI Simple Demo CA"; // Doesn't work!
//$ca = "DC=eu, DC=rcauth, O=Certification Authorities, CN=Research and Collaboration Authentication Pilot G1 CA";
$cn = "Nicolas Liampotis";
$email = "nliam@grnet.gr";
$output = $vo_chkInt->register_user($dn,$ca,$cn,$email);
print($output."\n");


//// Create user
//$dn = "/C=IT/O=INFN/OU=Personal Certificate/L=Catania/CN=Giuseppe La Rocca";
//$ca = "/C=IT/O=INFN/CN=INFN Certification Authority";
//$cn = "CN=Giuseppe La Rocca";
//$email = "dummy@mail.com";
//$output = $vo_chkInt->register_user($dn,$ca,$cn,$email);
//print($output."\n");
//
//// List all members
//var_dump($vo_chkInt->vo_memberships());
//print("\n");
//print("\n");
//// Find user
//print("{$dn} ".($vo_chkInt->user_membership($dn) ? "user exists" : "user not exists")."\n");
//print("\n");
//
//// Delete user
//$output = $vo_chkInt->unregister_user($dn,$ca);
//print($output."\n");
