<?php

require_once('config.piwik.php');
require_once('PiwikTracker.php');
require_once('DBFunctions.php');

if(defined("TRACK")) {
  PiwikTracker::$URL = 'https://'.TRACK_HOST.'/piwik/';

  $piwikTracker = new PiwikTracker(ID_TRACK_SITE);
  $emptySession = !isset($_COOKIE[GC_SESSION_NAME]);
  $user = new GCUser();
  // Specify an API token with at least Admin permission, so the Visitor IP address can be recorded
  // Learn more about token_auth: https://matomo.org/faq/general/faq_114/
  $piwikTracker->setTokenAuth(TRACK);

  $piwikTracker->setUserId($user->isAuthenticated() ? $user->getUsername() : "-anonymous_".session_id()."-");
  if($emptySession) {
    $piwikTracker->setCustomTrackingParameter("dimension1", $_SERVER['PHP_SELF']);
    $piwikTracker->setCustomTrackingParameter("dimension2", $_SERVER['REMOTE_ADDR']);
  }

  $piwikTracker->doTrackPageView($_SERVER['PHP_SELF']);

  if(!empty($_POST['username']) && !empty($_POST['password']))
    $piwikTracker->doTrackEvent("Profilazione", "Operazione Login utente:".$_POST['username']);
  if((substr($_SERVER['PHP_SELF'], strlen($_SERVER['PHP_SELF']) - strlen("logout.php") ) == "logout.php") || !empty($_REQUEST["logout"]))
    $piwikTracker->doTrackEvent("Profilazione", "Operazione Logout utente:".$user->getUsername());
  if(substr($_SERVER['PHP_SELF'], strlen($_SERVER['PHP_SELF']) - strlen("session.php") ) == "session.php")
    $piwikTracker->doTrackEvent("Profilazione", "Disattivazione sessione utente precedente");
}
//se php_self contiene login -> registro la login
//se php_self contiene session.php  -> registro il logout (dovuto a vero logout o a fine sessione)
?>