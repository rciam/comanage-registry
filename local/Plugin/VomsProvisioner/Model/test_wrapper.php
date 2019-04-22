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
$dn = "CN=Ioannis Igoumenos yCKcijJUgi9e8Y4s 1,O=EGI Foundation,OU=AAI-Pilot,O=EGI";
$ca = "O=EGI, OU=AAI-Pilot, CN=EGI Simple Demo CA";
$cn = "CN=Ioannis Igoumenos yCKcijJUgi9e8Y4s 1";
$email = "ioigoume@gmail.com";
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