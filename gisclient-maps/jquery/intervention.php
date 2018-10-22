<HTML>
<HEAD>
<link rel="stylesheet" href="../resources/themes/openlayers/style.css" type="text/css"/>
<script src="../resources/jslib/jquery.min.js"></script>
<script src="../resources/jslib/jquery.easyui.min.js"></script>
<script src="../resources/jslib/OpenLayers.js"></script>
<script src="../resources/jslib/proj4js.js"></script>
<!--script src="../config/config.dynamic.js"></script-->
<script src="../config/config.js"></script>
 <TITLE>Punti intervento</TITLE>
 <style>
      html, body {
        height: 100%;
        margin: 0;
        padding: 0;
      }
      
      #chicken {
        border-radius: 5px;
      }
      #chicken_GroupDiv {
        background-color: #C0C0C0;
      }
      #chicken_contentDiv {
        background-color: #C0C0C0;
      }
 </style>
</HEAD>
<BODY>
<div id="map"></div>
<script type="text/javascript">
var strategy, clusters, polygons;
var features = [];
var shape = [];
var popup;


$(document).ready(function() {
  Proj4js.defs["EPSG:3857"] = Proj4js.defs["GOOGLE"];
  if(this.projdefs){
    for (key in this.projdefs)
      if(!Proj4js.defs[key]) Proj4js.defs[key] = this.projdefs[key];
  }
  Proj4js.defs["EPSG:25832"] = "+proj=utm +zone=32 +ellps=GRS80 +units=m +no_defs";
  var lonLat = new OpenLayers.LonLat(542595, 4990102).transform('EPSG:25832', 'EPSG:3857');
  map = new OpenLayers.Map('map', {
    theme: null,
    projection: "EPSG:25832",
    fractionalZoom: true
    });
  var gmap = new OpenLayers.Layer.OSM("Mappa base", null);
  map.isValidZoomLevel = function(zoomLevel) {
    return (zoomLevel != null) && (zoomLevel >= 8);
  };
  map.events.register('zoomend', null, function() {
    clusters.setVisibility(map.getZoom() < 17);
    polygons.setVisibility(map.getZoom() >= 17);
  });
  strategy = new OpenLayers.Strategy.Cluster();
  polygons = new OpenLayers.Layer.Vector("Polygon Layer", {
    styleMap: new OpenLayers.StyleMap({
      "default": new OpenLayers.Style({
        fillColor: "#ffcc66",
        fillOpacity: 0.3,
        strokeColor: "#cc6633",
        strokeOpacity: 0.8
      }),
      "select": new OpenLayers.Style({
        fillColor: "#8aeeef",
        fillOpacity: 0.3,
        strokeColor: "#32a8a9",
        strokeOpacity: 0.8
      })
    })
  });
  clusters = new OpenLayers.Layer.Vector("Clusters", {
    strategies: [strategy],
    styleMap: new OpenLayers.StyleMap({
      "default": new OpenLayers.Style({
        pointRadius: "${radius}",
        fillColor: "${fillColor}",
        fillOpacity: 0.8,
        strokeColor: "#cc6633",
        strokeWidth: "${width}",
        strokeOpacity: 0.8
      },
      {
        context: {
          width: function(feature) {
            return (feature.cluster) ? 2 : 1;
          },
          radius: function(feature) {
            var pix = 10;
            if(feature.cluster)
              pix = (feature.attributes.count > 5) ? 25 : feature.attributes.count * 5;
            return pix;
          },
          fillColor: function(feature) {
            return (feature.cluster && feature.attributes.count > 5) ? "red" : "#ffcc66";
          }
        }
      }),
      "select": {
        fillColor: "#8aeeef",
        strokeColor: "#32a8a9"
      }
    })
  });
  map.addLayers([gmap, clusters, polygons]);
  var select = new OpenLayers.Control.SelectFeature([clusters, polygons], {hover: true});
  map.addControl(select);
  select.activate();
  clusters.events.on({"featureselected": display});
  polygons.events.on({"featureselected": displayPolygon});
  $.ajax({
    url: clientConfig.GISCLIENT_URL + "/services/plugins/matomo/manageMapRequestPoint.php",
    data: {action : "map", srid: "EPSG:3857", query_args: <?php echo "'".$_SERVER["QUERY_STRING"]."'" ?>},
    dataType: "json",
    success: function(data) {
      if(data.length > 0) {
        data.forEach(function(item, index, arr) {
          var currentShape = OpenLayers.Geometry.fromWKT(item.bbox);
          features.push(new OpenLayers.Feature.Vector(currentShape.getCentroid(), {
            project: item.project,
            map: item.map,
            counter: item.counter,
            bbox: currentShape.getCentroid().x + ", " + currentShape.getCentroid().y
          }));
          shape.push(new OpenLayers.Feature.Vector(currentShape, {
            project: item.project,
            map: item.map,
            counter: item.counter,
            bbox: currentShape.getCentroid().x + ", " + currentShape.getCentroid().y
          }));
          if((index+1) == arr.length)
            reset();
        });
      } else {
        var center = map.getPixelFromLonLat(map.getCenter());
        var nwchoords = new OpenLayers.Pixel(center.x - 150, center.y - 80);
        popup = new OpenLayers.Popup("chicken",
          map.getLonLatFromPixel(nwchoords),
          new OpenLayers.Size(300,160),
          "<b>Attenzione!!!</b><hr>Nessun punto di intervento restituito per i parametri di ricerca.",
          true);
        popup.closeOnMove = true;
        map.addPopup(popup);
      }
    }
  });
  map.addControl(new OpenLayers.Control.MousePosition({
    prefix: '<a target="_blank" ' +
        'href="http://spatialreference.org/ref/epsg/3857/">' +
        'EPSG:3857</a> coordinates: '
    }
  ));
  map.addControl(new OpenLayers.Control.Navigation({
    defaultDblClick: function(event) {
      managePopup();
      if(map.getZoom() < 17) {
        var choord = map.getLonLatFromPixel(event.xy);
        for(var i = 0; i < clusters.features.length; i++) {
          var auxLonLat = new OpenLayers.LonLat(clusters.features[i].geometry.x, clusters.features[i].geometry.y);
          var centerPix = map.getPixelFromLonLat(auxLonLat);
          var ray = clusters.features[0].attributes.count * 5;
          var choords = [map.getLonLatFromPixel(centerPix.add(-ray , ray)) , map.getLonLatFromPixel(centerPix.add(ray , ray)),
            map.getLonLatFromPixel(centerPix.add(ray , -ray)), map.getLonLatFromPixel(centerPix.add(-ray , -ray))];
          var arrChoords = [new OpenLayers.Geometry.Point(choords[0].lon, choords[0].lat), new OpenLayers.Geometry.Point(choords[1].lon, choords[1].lat),
            new OpenLayers.Geometry.Point(choords[2].lon, choords[2].lat), new OpenLayers.Geometry.Point(choords[3].lon, choords[3].lat),
            new OpenLayers.Geometry.Point(choords[0].lon, choords[0].lat)];
          var polygon = new OpenLayers.Geometry.Polygon(new OpenLayers.Geometry.LinearRing(arrChoords));
          if(polygon.containsPoint(new OpenLayers.Geometry.Point(choord.lon, choord.lat))) {
            map.setCenter(auxLonLat);
            map.zoomTo(17);
            return;
          }
        }
      }
      OpenLayers.Control.Navigation.prototype.defaultDblClick.apply(this, arguments);
    }
  }));
  map.setCenter(lonLat, 8);
  setInterval(reloadPoint, 60000);
});

function reloadPoint() {
  features = [];
  shape = [];
  reset();
  $.ajax({
    url: clientConfig.GISCLIENT_URL + "/services/plugins/matomo/manageMapRequestPoint.php",
    data: {action : "map", srid: "EPSG:3857", query_args: <?php echo "'".$_SERVER["QUERY_STRING"]."'" ?>},
    dataType: "json",
    success: function(data) {
      if(data.length > 0) {
        data.forEach(function(item, index, arr) {
          var currentShape = OpenLayers.Geometry.fromWKT(item.bbox);
          features.push(new OpenLayers.Feature.Vector(currentShape.getCentroid(), {
            project: item.project,
            map: item.map,
            counter: item.counter,
            bbox: currentShape.getCentroid().x + ", " + currentShape.getCentroid().y
          }));
          shape.push(new OpenLayers.Feature.Vector(currentShape, {
            project: item.project,
            map: item.map,
            counter: item.counter,
            bbox: currentShape.getCentroid().x + ", " + currentShape.getCentroid().y
          }));

          if((index+1) == arr.length)
            reset();
        });
      }
    }
  });
}

function reset() {
  clusters.removeFeatures(clusters.features);
  clusters.addFeatures(features);
  polygons.removeFeatures(polygons.features);
  polygons.addFeatures(shape);
}

function display(event) {
  managePopup();
  var clusters = event.feature.cluster;
  var position = event.feature.geometry;
  var totCounter = 0;
  if(clusters.length> 1) {
    popup = new OpenLayers.Popup("chicken",
      new OpenLayers.LonLat(position.x, position.y),
      new OpenLayers.Size(200,20),
      "Totale punti intervento: " + clusters.length,
      false);
  } else {
    popup = new OpenLayers.Popup("chicken",
      new OpenLayers.LonLat(position.x, position.y),
      new OpenLayers.Size(450,100),
      "<b>Totale visualizzazioni:</b> " + clusters[0].attributes.counter + "<hr>"
      + "<b>Progetto: </b>" + clusters[0].attributes.project + "<br>"
      + "<b>Mappa: </b>" + clusters[0].attributes.map + "<br>"
      + "<b>Coordinate centrali: </b>" + clusters[0].attributes.bbox,
      true);
  }
  map.addPopup(popup);
  setTimeout(  managePopup, 3000);
}

function displayPolygon(event) {
  managePopup();
  var polygon = event.feature;
  var position = polygon.attributes.bbox.split(',');
  popup = new OpenLayers.Popup("chicken",
    new OpenLayers.LonLat($.trim(position[0]), $.trim(position[1])),
    new OpenLayers.Size(450,100),
    "<b>Totale visualizzazioni:</b> " + polygon.attributes.counter + "<hr>"
      + "<b>Progetto: </b>" + polygon.attributes.project + "<br>"
      + "<b>Mappa: </b>" + polygon.attributes.map + "<br>"
      + "<b>Coordinate centrali: </b>" + polygon.attributes.bbox,
      true);
  map.addPopup(popup);
  setTimeout( managePopup, 5000);
}

function managePopup() {
 if(popup != undefined) {
   map.removePopup(popup);
   popup.destroy();
   popup = undefined;
 }
}

</script>
</BODY>
</HTML>
