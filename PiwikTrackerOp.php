<?php

require_once('config.piwik.php');
require_once('PiwikTracker.php');

if(defined("TRACK")) {
  $user = new GCUser();
  PiwikTracker::$URL = 'https://'.TRACK_HOST.'/piwik/';

  $piwikTracker = new PiwikTracker(ID_TRACK_SITE);

  // Specify an API token with at least Admin permission, so the Visitor IP address can be recorded
  // Learn more about token_auth: https://matomo.org/faq/general/faq_114/
  $piwikTracker->setTokenAuth(TRACK);

  $piwikTracker->setUserId($user->isAuthenticated() ? $user->getUsername() : "-anonymous_".session_id()."-");

  // Sends Tracker request via http
  //error_log(json_encode($_POST));
  if(!empty($_GET))
    $piwikTracker->setCustomTrackingParameter("dimension1", json_encode($_GET));
  if(isset($_POST["parametri"]))
    $piwikTracker->setCustomTrackingParameter("dimension2", json_encode($_POST["parametri"]));
  if(isset($_POST["mode"]) || isset($_POST["modo"]))
    $piwikTracker->setCustomTrackingParameter("dimension3", $_POST["mode"].$_POST["modo"]);
  if(isset($_POST["azione"]))
    $piwikTracker->setCustomTrackingParameter("dimension4", $_POST["azione"]);

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
