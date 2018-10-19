<?php

namespace Piwik\Plugins\GisClientPlugin\Widgets;

use Piwik\Site;
use Piwik\Widget\Widget;

class GetIntervento extends Widget {

  static protected $expectedQueryParams = array('date', 'period', 'segment');
  
  static protected $idSiteQueryParam = "idSite";

  protected function getGisClientUrlInfo() {
    $result = array();
    parse_str($_SERVER["QUERY_STRING"], $reqParams);
    $passingParams = array();
    foreach(self::$expectedQueryParams as $queryParam) {
      if(array_key_exists($queryParam, $reqParams))
        $passingParams[$queryParam] = $reqParams[$queryParam];
    }
    $result["query"] = $passingParams;
    $site = new Site($reqParams[self::$idSiteQueryParam]);
    $result["host"] = $site->getMainUrl();
    return $result;
  }
}
?>
