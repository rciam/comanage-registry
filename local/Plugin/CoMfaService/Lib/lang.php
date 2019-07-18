<?php

global $cm_lang, $cm_texts;

// When localizing, the number in format specifications (eg: %1$s) indicates the argument
// position as passed to _txt.  This can be used to process the arguments in
// a different order than they were passed.

$cm_co_mfa_service_texts['en_US'] = array(
  // Titles, per-controller
  'ct.co_mfa_service_setting.1'  => 'SMS MFA Settings',
  'ct.co_mfa_service_settings.1'  => 'SMS MFA Settings',
  'ct.co_mfa_service_settings.pl' => 'SMS MFA Settings',
  
  'ct.co_mfa_service.1'  => 'SMS MFA service',
  'ct.co_mfa_services.1'  => 'SMS MFA service',
  'ct.co_mfa_services.pl' => 'SMS MFA service',
  
  // Error messages
  'er.co_mfa_service_settings.fetchCode.400'       => 'Some of the provided parameters are not valid',
  'er.co_mfa_service_settings.verifyCode.400'      => 'The code was not verified',
  'er.co_mfa_service_settings.code.401'            => 'The provided apiKey is not valid',
  'er.co_mfa_service_settings.code.403'            => 'You are not authorized to access the OTP API',
  'er.co_mfa_service_settings.code.404'            => 'Not Found',
  'er.co_mfa_service_settings.code.405'            => 'Method Not Allowed',
  'er.co_mfa_service_settings.code.500'            => 'The OTP API is temporarily unavailable',
  'er.co_mfa_service_settings.code.default'        => 'Undefined error and outcome',
  
  // Success messages
  'ct.co_mfa_service_settings.fetchCode.200'            => 'OTP generated and sent successfully',
  'ct.co_mfa_service_settings.verifyCode.200'           => 'The code was verified successfully',
  
  // Plugin texts
  'pl.co_mfa_service_settings.from'                         => 'From(string)',
  'pl.co_mfa_service_settings.from.desc'                    => 'The sender id of the SMS that will be sent to the end user',
  'pl.co_mfa_service_settings.text'                         => 'Text(string)',
  'pl.co_mfa_service_settings.text.desc'                    => 'The body of the SMS that contains the generated code. The text must contain the code placeholder: ${code}. It may also optionally contain the placeholder ${refId} which will be replaced with the auto-generated reference ID of the OTP. It can further be refined by specifying the number of characters to use from the start or end of the ID. For example ${refId:5} will show the first 5 characters while ${refId:-5} will show the last 5 characters',
  'pl.co_mfa_service_settings.codeLength'                   => 'Code Length',
  'pl.co_mfa_service_settings.codeLength.desc'              => 'The length of the generated code',
  'pl.co_mfa_service_settings.ttl'                          => 'TTL',
  'pl.co_mfa_service_settings.ttl.desc'                     => 'The time-to-live of the generated code in seconds',
  'pl.co_mfa_service_settings.maxVerificationAttemps'       => 'Max Verification Attemps',
  'pl.co_mfa_service_settings.maxVerificationAttemps.desc'  => 'The maximum number of allowed verification attempts',
  'pl.co_mfa_service_settings.utf'                          => 'UTF',
  'pl.co_mfa_service_settings.utf.desc'                     => 'Whether the message must be submitted with unicode character support, at the cost of reduced maximum length',
  'pl.co_mfa_service_settings.api_key'                      => 'API Key',
  'pl.co_mfa_service_settings.api_key.desc'                 => 'Api Key security scheme. Requires to input your API Key in an Authorization header\',',
  'pl.co_mfa_service_settings.api_secret'                   => 'API Secret',
  'pl.co_mfa_service_settings.api_secret.desc'              => 'API Secret',
  'pl.co_mfa_service_settings.url'                          => 'URL',
  'pl.co_mfa_service_settings.url.desc'                     => 'URL of the service providing the OTP API',
  'pl.co_mfa_service_settings.expire'                       => 'Expiration Days',
  'pl.co_mfa_service_settings.expire.desc'                  => 'Number of days to expiration. The count down begins immediately after the verification.',
  
  
  // CoMfaService text
  'pl.co_mfa_services.fetch'                 => 'Send Code',
  'pl.co_mfa_services.verify'                => 'Verify Code',
  'pl.co_mfa_services.confirm.replace'       => 'Do you want to redo the verification process?',
  'pl.co_mfa_services.confirm'               => 'Please use the code you received via sms to verify you mobile!',
  'pl.co_mfa_services.fetch_code'            => 'Do you want to proceed and fetch a verification code?',
  'pl.co_mfa_services.mobile.ok'             => 'Mobile Verified',
  'pl.co_mfa_services.mobile.no'             => 'Mobile NOT Verified',
  
  // Actions
  'op.co_mfa_service_settings.save'           => 'Save',
  'op.co_mfa_services.cancel'                 => 'Cancel',
  'op.co_mfa_services.verify'                 => 'Verify',
  
  //
  'fd.co_mfa_service_settings.config'        => 'Config Option',
  'fd.co_mfa_service_settings.value'         => 'Value',
  'fd.co_mfa_services.mobile'                => 'Mobile number',
  'fd.co_mfa_services.status'                => 'Status',
  'fd.co_mfa_services.actions'               => 'Actions',
  
  // database
  'rs.saved'                                  => 'Saved',
  'rs.error'                                  => 'Error',
  
  
  
);
