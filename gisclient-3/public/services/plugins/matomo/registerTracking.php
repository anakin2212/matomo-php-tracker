<?php
require_once("../../../../config/config.php");
if(isset($_GET["url"])) {
  PiwikTracker::$URL = 'https://'.TRACK_HOST.'/piwik/';
  $piwikTracker = new PiwikTracker(ID_TRACK_SITE);
  $emptySession = !isset($_COOKIE[GC_SESSION_NAME]);
  $user = new GCUser();
  // Specify an API token with at least Admin permission, so the Visitor IP address can be recorded
  // Learn more about token_auth: https://matomo.org/faq/general/faq_114/
  $piwikTracker->setTokenAuth(TRACK);
  $piwikTracker->setRequestTimeout(defined("TRACK_TIMEOUT") ? TRACK_TIMEOUT : 10);
  $piwikTracker->setUserId($user->isAuthenticated() ? $user->getUsername() : "-anonymous_".session_id()."-");
  if($emptySession) {
    $piwikTracker->setCustomTrackingParameter("dimension1", $_GET["url"]);
    $piwikTracker->setCustomTrackingParameter("dimension2", $_SERVER['REMOTE_ADDR']);
  }
  $piwikTracker->doTrackPageView($_GET["url"]);
  $mapsetAssignmentStr = substr($_GET["url"], strpos($_GET["url"], "mapset"));
  $piwikTracker->doTrackEvent("Invocazione Mapset", "Richiamata pagina di visualizzazione mappa :".substr($mapsetAssignmentStr, strpos($mapsetAssignmentStr, "=")+1));
}
?>
