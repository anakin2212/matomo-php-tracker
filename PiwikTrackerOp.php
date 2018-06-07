<?php
require_once "PiwikTracker.php";

if(defined(TRACK)) {
  $user = new GCUser();
  PiwikTracker::$URL = 'https://geoweb-dev.master.local/piwik/';

  $piwikTracker = new PiwikTracker("1");

  // Specify an API token with at least Admin permission, so the Visitor IP address can be recorded
  // Learn more about token_auth: https://matomo.org/faq/general/faq_114/
  $piwikTracker->setTokenAuth(TRACK);

  $piwikTracker->setUserId($user->isAuthenticated() ? $user->getUsername() : "<anonymous_".session_id().">");

  // Sends Tracker request via http
  $piwikTracker->doTrackPageView($_SERVER['PHP_SELF']);

  if(substr($_SERVER['PHP_SELF'], strlen($_SERVER['PHP_SELF']) - strlen("login.php") ) == "login.php")
    $piwikTracker->doTrackEvent("Profilazione", "Operazione Login utente:".$_POST['username']);
  if(substr($_SERVER['PHP_SELF'], strlen($_SERVER['PHP_SELF']) - strlen("logout.php") ) == "logout.php")
    $piwikTracker->doTrackEvent("Profilazione", "Operazione Logout utente:".$user->getUsername());
  if(substr($_SERVER['PHP_SELF'], strlen($_SERVER['PHP_SELF']) - strlen("session.php") ) == "session.php")
    $piwikTracker->doTrackEvent("Profilazione", "Disattivazione sessione utente precedente");
}
//se php_self contiene login -> registro la login
//se php_self contiene session.php  -> registro il logout (dovuto a vero logout o a fine sessione)
?>
