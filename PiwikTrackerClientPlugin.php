<?php
  if(defined("TRACK")) {
    array_push($SCRIPT_PLUGINS, "<script type=\"text/javascript\">
      var _paq = _paq || [];
      configLoaded.then(initTrack);
      function initTrack() {
        $(document).ready(function() {
          var u=\"//".TRACK_HOST."/piwik/\";
          _paq.push(['setTrackerUrl', u+'piwik.php']);
          _paq.push(['setSiteId', '".ID_TRACK_SITE."']);
          _paq.push(['setUserId',clientConfig.CLIENT_ID]);
          _paq.push(['trackPageView']);
          _paq.push(['enableLinkTracking']);
          var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
          g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'piwik.js'; s.parentNode.insertBefore(g,s);
        });
      }
      </script>");
  }
?>
