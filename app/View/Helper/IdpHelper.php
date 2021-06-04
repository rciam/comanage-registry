<?php
App::uses('AppHelper', 'View/Helper');
App::uses('Cache', 'Cache');

class IdpHelper extends AppHelper
{

  public function getIdpInfo($entityId)
  {
    $authnAuthFN = null;
    $logoUrl = null;

    if (empty($entityId) || !filter_var($entityId, FILTER_VALIDATE_URL)) {
      return null;
    }

    $data = Cache::read($entityId, '_cake_core_');
    if ($data !== false)
      return $data;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_URL, $entityId);    // get the url contents
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);

    $xmlData = curl_exec($ch); // execute curl request
    $http_response = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_response !== 200) {
      $response = json_decode($xmlData, true);
      $this->log(__METHOD__ . "::HTTP response code: " . $http_response . ", error message: '" . $response['Error']['Message'], LOG_DEBUG);
      curl_close($ch);
      return null;
    }
    curl_close($ch);

    $sxe = new SimpleXMLElement($xmlData);
    $namespaces = $sxe->getNamespaces(true);
    // TODO: Make namespaces a configuration in a future version
    if (isset($namespaces['mdui']) && isset($namespaces['xml'])) {
      $sxe->registerXPathNamespace('mdui', $namespaces['mdui']);
      $sxe->registerXPathNamespace('xml', $namespaces['xml']);
      $displayNames = $sxe->xpath('//mdui:DisplayName');
      $logoUrlObj = $sxe->xpath('//mdui:Logo');

      $displayNamesLangs = $sxe->xpath('//mdui:DisplayName/@xml:lang');
      foreach ($displayNamesLangs as $key => $lang) {
        $strLang = (string)$lang['lang'];
        // Here i can customize the language of choice
        if ($strLang === "en") {
          $authnAuthFN = (string)$displayNames[$key];
          $logoUrl = (string)$logoUrlObj[$key];
          $data = array();
          $data['authnAuthFN'] = $authnAuthFN;
          $data['logoUrl'] = $logoUrl;
          Cache::write($entityId, $data, '_cake_core_');
          return $data;
        }
      }
    }
  }
}

?>