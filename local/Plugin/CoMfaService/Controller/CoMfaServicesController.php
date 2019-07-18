<?php


App::uses("StandardController", "Controller");

class CoMfaServicesController extends StandardController
{
  // Class name, used by Cake
  public $name = "CoMfaServices";
  
  // This controller needs a CO Person to be set
  public $requires_person = true;
  public $requires_co = true;
  
  
  /**
   * @return Integer co_person_id, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  
  protected function calculateCoPersonId($co_id) {
    $co_person_id = isset($_SESSION['Auth']['co_person_id']) ? $_SESSION['Auth']['co_person_id'] : null;
    if($co_person_id == ""){
      foreach($_SESSION['Auth']['User']['cos'] as $cos){
        if($cos['co_id'] == $co_id){
          // Found the co_person_id. Break and continue
          $co_person_id = $cos['co_person_id'];
          break;
        }
      }
    }
    return $co_person_id;
  }
  
  
  /**
   * @return Integer co_id, or null if not implemented or not applicable.
   * @throws InvalidArgumentException
   */
  protected function calculateImpliedCoId($data = null)
  {
    $coid = isset($this->request->params['named']['coid']) ? $this->request->params['named']['coid'] : null;
    if(isset($coid)){
      return $coid;
    } else {
      return parent::calculateImpliedCoId($data);
    }
  }
  
  
  public function index() {
    parent::index();
    
    // Get the otp_id(if exists) from the request
    $otp_id = isset($this->request->params['named']['otp_id']) ? $this->request->params['named']['otp_id'] : "" ;
    $this->set('vv_otp_id', $otp_id);
    
    // Get the co_person_id and co_id from the request
    $co_id = $this->request->params['named']['co'];
    $co_person_id = $this->calculateCoPersonId($co_id);

    $this->set('vv_co_person_id', $co_person_id);
    
    // Get the mobile phone numbers
    $args['joins'][0]['table'] = 'co_person_roles';
    $args['joins'][0]['alias'] = 'CoPersonRole';
    $args['joins'][0]['type'] = 'INNER';
    $args['joins'][0]['conditions'][0] = 'CoPersonRole.id=TelephoneNumber.co_person_role_id';
    $args['joins'][1]['table'] = 'co_people';
    $args['joins'][1]['alias'] = 'CoPerson';
    $args['joins'][1]['type'] = 'INNER';
    $args['joins'][1]['conditions'][0] = 'CoPersonRole.co_person_id=CoPerson.id';
    $args['joins'][2]['table'] = 'cos';
    $args['joins'][2]['alias'] = 'Co';
    $args['joins'][2]['type'] = 'INNER';
    $args['joins'][2]['conditions'][0] = 'CoPerson.co_id=Co.id';
    $args['conditions']['CoPerson.id'] = $co_person_id;
    $args['conditions']['TelephoneNumber.type'] = ContactEnum::Mobile;
    $args['contain'] = false;
    
    $phonesList = $this->CoMfaService->TelephoneNumber->find('all', $args);
    $phones = array();
    // Find if the phone has an entry in the cm_co_mfa_services table. The one that holds the verification info
    foreach($phonesList as $phone){
      $args = array();
      $args['conditions']['CoMfaService.telephone_number_id'] = $phone['TelephoneNumber']['id'];
      $args['conditions']['CoMfaService.deleted'] = false;
      $args['contain'] = false;
  
      $entry = $this->CoMfaService->find('first',$args);
      if(isset($entry['CoMfaService'])){
        $phone = array_merge($phone, $entry);
      }
      array_push($phones, $phone);
    }
  
    $this->set('vv_mobiles', $phones);
    // Get the settings
    $args = array();
    $args['conditions']['CoMfaServiceSetting.co_id'] = $co_id;
    $args['conditions']['CoMfaServiceSetting.deleted'] = false;
    $args['contain'] = false;
    
    $settings = $this->CoMfaService->CoMfaServiceSetting->find('first',$args);
    $this->set('vv_settings', $settings);
  }
  
  public function fetchCode(){
    // This condition and response is for testing purposes
    // This is dummy code
    if($this->request->is('restful')) {
      $this->Api->restResultHeader(200, "OK");
    }
    
    // Retrieve data from the request
    $mfa_settings_id = isset($this->request->params['named']['mfasetting']) ? $this->request->params['named']['mfasetting'] : "";
    $phone_id = isset($this->request->params['named']['phoneid']) ? $this->request->params['named']['phoneid'] : "";
    
    
    // Fetch the settings
    $args = array();
    $args['conditions']['CoMfaServiceSetting.id'] = $mfa_settings_id;
    $args['conditions']['CoMfaServiceSetting.deleted'] = false;
    $args['contain'] = false;
  
    $settings = $this->CoMfaService->CoMfaServiceSetting->find('first',$args);
    unset($args);
  
    // Fetch the phone number
    $args = array();
    $args['conditions']['TelephoneNumber.id'] = $phone_id;
    $args['conditions']['TelephoneNumber.deleted'] = false;
    $args['fields'] = array('country_code', 'number');
    $args['contain'] = false;
  
    $phone = $this->CoMfaService->TelephoneNumber->find('first',$args);
    $response = $this->CoMfaService->sendOtp($settings['CoMfaServiceSetting'], $phone['TelephoneNumber']);
    $otp_id = isset($response['otp_id']) ? $response['otp_id'] : "";
    $this->triggerMessage($response, __FUNCTION__);
    $this->performRedirect($otp_id);
  }
  
  public function verifyCode(){
    // Retrieve data from the request
    $mfa_settings_id = isset($this->request->params['named']['mfasetting']) ? $this->request->params['named']['mfasetting'] : "";
    $phone_id = isset($this->request->params['named']['phoneid']) ? $this->request->params['named']['phoneid'] : "";
    $code = isset($this->request->query['code']) ? $this->request->query['code'] : "";
    $otpId = isset($this->request->params['named']['otpid']) ? $this->request->params['named']['otpid'] : "";
  
    // Fetch the settings
    $args = array();
    $args['conditions']['CoMfaServiceSetting.id'] = $mfa_settings_id;
    $args['conditions']['CoMfaServiceSetting.deleted'] = false;
    $args['contain'] = false;
  
    $settings = $this->CoMfaService->CoMfaServiceSetting->find('first',$args);
    
    // Request verification from OTP service
    $response = $this->CoMfaService->verifyOtp($settings['CoMfaServiceSetting'], $code, $otpId);
    
    // Let's save or update the data if we have success and the data does not exist
    if(isset($response['valid']) && $response['valid']){
      $data = array(
        'co_id'                     => $this->request->params['named']['coid'],
        'co_person_id'              => $this->request->params['named']['copersonid'],
        'telephone_number_id'       => $phone_id,
        'co_mfa_service_setting_id' => $mfa_settings_id,
        'verified'                  => $response['valid'],
      );
      
      // Check if there is already an entry in the table
      // Fetch the settings
      $args = array();
      $args['conditions']['CoMfaService.telephone_number_id'] = $phone_id;
      $args['conditions']['CoMfaService.co_person_id'] = $this->request->params['named']['copersonid'];
      $args['conditions']['CoMfaService.deleted'] = false;
      $args['contain'] = false;
  
      $serviceEntry = $this->CoMfaService->find('first',$args);
      if(isset($serviceEntry['CoMfaService'])){
        $this->CoMfaService->id = $serviceEntry['CoMfaService']['id'];
        $verification_count = $serviceEntry['CoMfaService']['verification_count'] + 1;
        $data = array_merge($data, array('verification_count' => $verification_count));
      } else {
        $data = array_merge($data, array('verification_count' => 1));
        $this->CoMfaService->create();
      }
      if($this->CoMfaService->save($data)){
        $this->log(__METHOD__ . "::Co Mfa Service saved successfully.",LOG_DEBUG);
      } else {
        $invalidFields = $this->CoMfaService->invalidFields();
        $this->log(__METHOD__ . "::Co Mfa Service save failed => ". print_r($invalidFields, true),LOG_DEBUG);
      }
    }
    
    // Inform the user about the outcome of the verification
    $this->triggerMessage($response, __FUNCTION__);
    
    // Redirect to the index view
    $this->performRedirect();
  }
  /**
   * Perform a redirect back to the controller's default view.
   * - postcondition: Redirect generated
   *
   */
  
  public function performRedirect($otp_id = "") {
    if(!empty($this->request->params['named']['coid'])) {
      $data = array(
        'plugin'     => 'co_mfa_service',
        'controller' => 'co_mfa_services',
        'action'     => 'index',
        'co' => filter_var($this->request->params['named']['coid'], FILTER_SANITIZE_SPECIAL_CHARS)
      );
      if($otp_id != ""){
        $data = array_merge($data, array('otp_id' => $otp_id));
      }
      $this->redirect($data);
    } else {
      $this->redirect('/');
    }
  }
  
  /**
   * Authorization for this Controller, called by Auth component
   * - precondition: Session.Auth holds data used for authz decisions
   * - postcondition: $permissions set with calculated permissions
   *
   * @since  COmanage Registry v2.0.0
   * @return Array Permissions
   */
  
  function isAuthorized() {
    $roles = $this->Role->calculateCMRoles();
    
    $self = (!empty($roles['copersonid'])
      && !empty($_SESSION['Auth']['User']['co_person_id'])
      && ($roles['copersonid'] == $_SESSION['Auth']['User']['co_person_id']));
    
    // Construct the permission set for this user, which will also be passed to the view.
    $p = array();
    
    // Determine what operations this user can perform
    
    // Fetch or verify code (for this CO Person)?
    $p['fetchCode'] = ($roles['cmadmin'] || $roles['coadmin']) || $self;
    $p['verifyCode'] = ($roles['cmadmin'] || $roles['coadmin']) || $self;
    
    // View all existing CO Service Tokens (for this CO Person)?
    $p['index'] = ($roles['cmadmin'] || $roles['coadmin']) || $self;
    
    $this->set('permissions', $p);
    return($p[$this->action]);
  }
  
  
  protected function triggerMessage($response, $function){
    if($response){
      if( isset($response['http_code']) ){
        switch ( $response['http_code'] ) {
          case 200:
            $this->Flash->set(_txt("ct.co_mfa_service_settings.{$function}.200"), array('key' => 'success'));
            break;
          case 400:
            $this->Flash->set(_txt("er.co_mfa_service_settings.{$function}.400"), array('key' => 'error'));
            break;
          case 401:
            $this->Flash->set(_txt("er.co_mfa_service_settings.code.401"), array('key' => 'error'));
            break;
          case 403:
            $this->Flash->set(_txt("er.co_mfa_service_settings.code.403"), array('key' => 'error'));
            break;
          case 404:
            $this->Flash->set(_txt("er.co_mfa_service_settings.code.404"), array('key' => 'error'));
            break;
          case 405:
            $this->Flash->set(_txt("er.co_mfa_service_settings.code.405"), array('key' => 'error'));
            break;
          case 500:
            $this->Flash->set(_txt("er.co_mfa_service_settings.code.500"), array('key' => 'error'));
            break;
          default:
            $this->Flash->set(_txt("er.co_mfa_service_settings.code.default"), array('key' => 'error'));
            break;
        }
      } else {
        $this->Flash->set(_txt('er.co_mfa_service_settings.code.default'), array('key' => 'error'));
      }
    } else {
      $this->Flash->set(_txt('er.co_mfa_service_settings.code.default'), array('key' => 'error'));
    }
  }
}