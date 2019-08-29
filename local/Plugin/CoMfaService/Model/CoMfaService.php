<?php

class CoMfaService extends AppModel
{
  // Define class name for cake
  public $name = "CoMfaService";
  
  // Required by COmanage Plugins
  // To enable this plugin (even though it doesn't do anything), change the type to 'enroller'
  public $cmPluginType = "other";
  
  // Association rules from this model to other models
  public $belongsTo = array(
    "Co",
    "CoPerson",
    "TelephoneNumber",
    "CoMfaServiceSetting"
  );
  
  public $actsAs = array('Containable');
  
  // Document foreign keys
  public $cmPluginHasMany = array();
  
//  // Validation rules for table elements
  public $validate = array(
    'co_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_mfa_service_setting_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'co_person_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'telephone_number_id' => array(
      'rule' => 'numeric',
      'required' => true,
      'allowEmpty' => false
    ),
    'verification_count' => array(
      'rule' => 'numeric',
      'required' => false,
      'allowEmpty' => true
    ),
    'verified' => array(
      'rule' => array('boolean'),
      'required' => false,
      'allowEmpty' => true
    ),
  );

  /**
   * Expose menu items.
   *
   * @return Array with menu location type as key and array of labels, controllers, actions as values.
   * @since  COmanage Registry v2.0.0
   */

  public function cmPluginMenus()
  {
    return array(
      "coconfig" => array(_txt('ct.co_mfa_service_settings.pl') =>
        array('controller' => 'co__mfa_service_settings',
          'action' => 'configure')),
      "copeople" => array(_txt('ct.co_mfa_services.pl') =>
        array('controller' => "co_mfa_services",
          'action' => 'index'))
    );
  }
  
  public function verifyOtp($serverData, $code, $otpId){
    // if one of the two parameters has no data then return null
    if(!isset($serverData) || !isset($code) || !isset($otpId)){
      return null;
    }
  
    // Create the headers
    $headers = $this->otpHeaders($serverData);
    $data = array(
      "code" => $code,
    );
    
    $response = $this->do_curl($serverData['url'] . "/" . $otpId, $data, $headers, $error, $info, "PUT");
    $this->log(__METHOD__ . ":: info => " . print_r($info,true), LOG_INFO);
    $this->log(__METHOD__ . ":: error => " . print_r($error,true), LOG_DEBUG);
    if(isset($info['http_code'])){
      if(isset($response) && $response!= ""){
        if(is_object(json_decode($response))){
          $respTable = json_decode($response, true);
          $info = array_merge($info, $respTable);
        }
      }
      return $info;
    } elseif (isset($error)){
      return $error;
    }
  }

  public function sendOtp($serverData, $phone)
  {
    // if one of the two parameters has no data then return null
    if(!isset($serverData) || !isset($phone)){
      return null;
    }
    // Get the phone from the request data
    $country_code = ($phone['country_code'] != "") ? $phone['country_code'] : "30";
    $data = array(
      'from' => $serverData['from'],
      'to' => isset($phone) ? "+" . $country_code
                                  . $phone['number']
                                  : "",
      'text' => urlencode($serverData['text']),
      'codeLength' => $serverData['code_length'],
      'ttl' => $serverData['ttl'],
      'maxVerificationAttemps' => $serverData['max_verification_attemps'],
      'utf' => $serverData['utf'],
    );
  
    $headers = $this->otpHeaders($serverData);

    $this->log(__METHOD__ . ":: data => " . print_r($data,true), LOG_DEBUG);
    $this->log(__METHOD__ . ":: header => " . print_r($headers,true), LOG_DEBUG);

    $otpId = $this->do_curl($serverData['url'], $data, $headers, $error, $info, "POST");

    $this->log(__METHOD__ . ":: info => " . print_r($info,true), LOG_INFO);
    $this->log(__METHOD__ . ":: error => " . print_r($error,true), LOG_DEBUG);
    if(isset($info['http_code'])){
      if(isset($otpId) && $otpId != ""){
        if(is_object(json_decode($otpId))){
          $otpIdTable = json_decode($otpId, true);
          $info = array_merge($info, $otpIdTable);
        }
      }
      return $info;
    } elseif (isset($error)){
      return $error;
    }
  }

  protected function otpHeaders($serverData){
    // if one of the two parameters has no data then return null
    if(!isset($serverData)){
      return null;
    }
    $authorization =  "Bearer " . $serverData['api_key']
      . "\$_$"
      . $serverData['api_secret'];
  
    $headers = array(
      'Content-Type'  =>  "application/x-www-form-urlencoded",
      'accept'        =>  "application/json",
      'Authorization' =>  $authorization,
    );
  
    $this->log(__METHOD__ . ":: error => " . print_r($headers,true), LOG_DEBUG);
    
    return $headers;
  }

  // Wrapper for curl

  /**
   * @param $url      The URL used to address the request
   * @param $headers  Associative array of http headers. If we use the default headers thn tha variable should be false
   * @param $fields   List of query parameters in a key=>value array format
   * @return array
   */
  protected function do_curl($url, $fields, $headers=false, &$error, &$info, $protocol)  {
    //url-ify the data for the POST
    $fields_string="";
    if(isset($fields)) {
      foreach ($fields as $key => $value) {
        $fields_string .= $key . '=' . $value . '&';
      }
      $fields_string = rtrim($fields_string, '&');
    }

    // Make the headers
    $heads = array();
    if($headers){
      $heads = $headers;
      unset($headers);
      $headers = array();
      foreach($heads as $key=>$value){
        array_push($headers,$key . ': ' . $value);
      }
    }

    // Turning on the PHP output buffer (OB) and string the output stream.
    // This allows us to write verbose information from cURL to the output buffer.
    // We must do this before starting the cURL handler.
    ob_start();
    $out = fopen('php://output', 'w');
    // open connection
    $ch = curl_init();

    // set the url, number of POST vars, POST data
    // Content-type: application/x-www-form-urlencoded => is the default approach for post requests
    curl_setopt($ch,CURLOPT_URL, $url);
    curl_setopt($ch,CURLOPT_CUSTOMREQUEST, $protocol);
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch,CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch,CURLOPT_VERBOSE, true);
    curl_setopt($ch,CURLOPT_STDERR, $out);

    // execute post
    $response = curl_exec($ch);
    fclose($out);
    $error = "";
    if (empty($response)) {
      // probably connection error
      $error = curl_error($ch);
    }

    $info = curl_getinfo($ch);
    $debug = ob_get_clean();
    $this->log(__METHOD__ . "::debug => " . print_r($debug,true), LOG_DEBUG);

    // close connection
    curl_close($ch);
    // return success
    return $response;
  }
}