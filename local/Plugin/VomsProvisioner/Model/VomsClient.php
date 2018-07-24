<?php
class VomsClient
{
	// server url of the vo of interest
	private $host;
	private $vo_name;

	public function __construct($host, $vo_name)
	{
		$this->host = $host;
		$this->vo_name = $vo_name;
	}


	/**
	 * @param $dn           user we are filtering subject dn
	 * @return boolean      true if user exists false otherwise
	 */
	public function user_membership($dn)
	{
		foreach($this->vo_memberships($this->host) as $userdata)
		{
			if($userdata["dn"] === $dn ){
				return true;
			}
		}
		return false;
	}

	/**
	 * @return    array of users subscribed in vo. Each User is defined by an array of dn, ca and email values
	 * Structure of returned array
	 *  array(2) {
	 *  	[0]=> array(3) {
	 *  		["dn"]      =>  "dn value"
	 *  		["ca"]      =>  "ca value"
	 *  		["email"]   =>  "email value"
	 *      }
	 *  	[1]=> array(3) {
	 *  		["dn"]      =>  "dn value"
	 *  		["ca"]      =>  "ca value"
	 *  		["email"]   =>  "email value"
	 *  	}
	 *  }
	 */
	public function vo_memberships()
	{
		// Return array with all memberships of the specified Vo
		$cmd = "voms-admin --vo {$this->vo_name} --host {$this->host} list-users 2>&1";
		exec($cmd, $subscriptions);
		$members = array();
		foreach ($subscriptions as $memberData)
		{
			$memberDataAr = explode(",",$memberData);
			$members[] = array(
				"dn"   => $memberDataAr[0],
				"ca" => $memberDataAr[1],
				"email" => $memberDataAr[2]
			);
		}
		return $members;
	}

	/**
	 * @param $dn       user to register subject dn
	 * @param $ca       issuer of the certificate
	 * @param $cn       user to register canonical name
	 * @param $email    user to register email
	 * @return string   return success or failure message
	 */
	public function register_user($dn, $ca, $cn, $email)
	{
		$cmd = "voms-admin --nousercert --vo {$this->vo_name} --host {$this->host} create-user '{$dn}' '{$ca}' '{$cn}' '{$email}' 2>&1";
		//CakeLog::write('debug', "cmd: ".$cmd);
		exec($cmd, $data, $var);
		if(is_array($data)){
			$data = implode($data," ");
		}
		//CakeLog::write('debug', "voms client ret(var): ".print_r($var,true));
		if(trim($data) != ""){
			return "User {$cn} creation in {$this->host} failed. [MSG:output => {$data}";
		} else {
			return "User {$cn} created succesfully in {$this->host}";
		}
	}

	/**
	 * @param $dn           user to remove from vo subject dn
	 * @return string       success or failure of removal
	 */
	public function unregister_user($dn, $ca)
	{
		$cmd = "voms-admin --nousercert --vo {$this->vo_name} --host {$this->host} delete-user '{$dn}' '{$ca}' 2>&1";
		exec($cmd, $data);
		if(is_array($data)){
			$data = implode($data," ");
		}

		if(trim($data) != ""){
			return "User {$dn} deletion from {$this->host} failed. [MSG:output => {$data}";
		} else {
			return "User {$dn} deleted succesfully from {$this->host}";
		}
	}

	// voms-admin --verbose  --nousercert
	// --vo checkin-integration
	// --host voms2.hellasgrid.gr
	// --name Giuseppe
	// --surname "La Rocca"
	// --institution INFN
	// --address somewhere
	// --phoneNumber 555555
	// create-user '/C=IT/O=INFN/OU=Personal Certificate/L=Catania/CN=Giuseppe La Rocca' '/C=IT/O=INFN/CN=INFN Certification Authority' 'dummy@mail.com'
	/**
	 * @param $dn           user to register subject dn
	 * @param $ca           issuer of the certificate
	 * @param $cn           user to register canonical name
	 * @param $name         user name
	 * @param $surname      user surname
	 * @param $institution  user institution
	 * @param $email        user email
	 * @return string       success or faillure message
	 */
	public function register_user_complete($dn, $ca, $cn, $name, $surname, $institution, $email)
	{
		$cmd = "voms-admin --nousercert --vo {$this->vo_name} --host {$this->host}".
			"--name {$name} --surname {$surname} --institution {$institution} --address unknown --phonenumber 5555555".
			"create-user '{$dn}' '{$ca}' '{$cn}' '{$email}' 2>&1";
		exec($cmd, $data);
		if(is_array($data)){
			$data = implode($data," ");
		}
		print($data);
		if(trim($data) != ""){
			return "User {$cn} creation in {$this->host} failed. [MSG:output => {$data}";
		} else {
			return "User {$cn} created succesfully in {$this->host}";
		}
	}

	/**
	 * @return mixed
	 */
	public function getHost()
	{
		return $this->host;
	}

	/**
	 * @return mixed
	 */
	public function getVoName()
	{
		return $this->vo_name;
	}
}