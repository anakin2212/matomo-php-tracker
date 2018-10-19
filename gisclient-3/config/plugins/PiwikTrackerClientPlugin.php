<?php
  require_once("config.piwik.php");
  if(defined("TRACK")) {
    $SCRIPT_PLUGINS[] = "<script type=\"text/javascript\">
      var _paq = _paq || [];
      configLoaded.then(initTrack)
      applicationReady.then(registerMapServerRequest);
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
      function registerMapServerRequest() {
        $(document).ready(function() {
          GisClientMap.map.events.register('moveend', GisClientMap.map, function() {
            if(GisClientMap.map.zoom >= (GisClientMap.map.getNumZoomLevels() - ".TRACK_ZOOM_OFFSET.")) {
              var extent = GisClientMap.map.getExtent();
              $.get(GisClientMap.baseUrl + '".TRACK_SERVICE_LOCATION."',
                {
                  projection : GisClientMap.map.projection,
                  extent: extent.bottom + ',' + extent.left + ',' + extent.right + ',' + extent.top,
                  map: GisClientMap.map.config.mapsetTitle,
                  project: GisClientMap.map.config.projectTitle,
                  user: clientConfig.CLIENT_ID,
                },
                function(returnedData){
                  if(returnedData != '')
                    window.alert(returnedData);
              });
            }
          });
        });
      }
      </script>";
  }
?>
