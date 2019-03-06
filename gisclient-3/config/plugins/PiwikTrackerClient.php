<?php
  require_once("config.piwik.php");
  if(defined("TRACK")) {
    $SCRIPT_PLUGINS[] = "<script type=\"text/javascript\">
      configLoaded.then(initTrack)
      applicationReady.then(registerMapServerRequest);
      function initTrack() {
        $(document).ready(function() {
          $.get(GisClientMap.baseUrl + '".TRACK_CLIENT_REG_LOCATION."',
            {
              url: window.location.pathname + window.location.search,
            },
            function(returnedData){
              if(returnedData != '')
              window.alert(returnedData);
            });
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
