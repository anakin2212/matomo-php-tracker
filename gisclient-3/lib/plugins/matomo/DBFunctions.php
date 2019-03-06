<?php
  require_once (ROOT_PATH.'lib/gcapp.class.php');

  function listMapServerRequests($srid = null, $centralBox = FALSE) {
    $db = GCApp::getDB();
    $point = (($srid == null) ? "bbox" : "st_transform(bbox, '".str_replace("EPSG:","", $srid)."')");
    $point = $centralBox ? "ST_CENTROID(".$point.")" : $point;
    $point = "ST_AsText(".$point.")";
    $sql = "select project, map || ' (".TRACK_SRID.")' as map, $point as bbox, \"user\", ip_address, date_insert, counter from public.maprequest order by project, map";
    $stmt = $db->prepare($sql);
    if($stmt->execute())
      return $stmt->fetchAll();
  }
  
  function storeMapServerRequest($args) {
    if(count($args) == 6) {
      $extent = explode(",", $args["extent"]);
      $workedCenter = createGeoJSONPolygonGeometry($extent, $args["project"], $args['projection']);
      $db = GCApp::getDb();
      //verifico se esiste
      $sql = 'select requestid, counter, ST_CONTAINS('.$workedCenter.', bbox) as contained  from public.maprequest where project = :project and map = :map '//and srs = :srs '
        .'and (ST_CONTAINS('.$workedCenter.', bbox) or ST_CONTAINS(bbox, '.$workedCenter.')) '
        .'and "user" = :usr and ip_address = :ip';
      $prot = array(
        'project'=>$args["project"],
        'map'=>$args["map"],
        'usr'=>$args["user"],
        'ip'=>$args["ip_address"]);
      $stmt = $db->prepare($sql);
      $result = $stmt->execute($prot);
      //se esiste update
      $res=$stmt->fetchAll();
      if(($result == 1) && (count($res) == 1)) {
        $sql = 'update public.maprequest set counter = '.($res[0]["counter"] + 1)
               .($res[0]['contained']== true ? ', bbox='.parseGeoJSONPolygonGeometry($extent, $args["project"], $args["projection"]) : '')
               .' where requestid = '.$res[0]["requestid"];
        $sqlArgs = array();
      } else {
        $sql = 'insert into public.maprequest(project, map, bbox, "user", ip_address) values(:project, :map, '.parseGeoJSONPolygonGeometry($extent, $args["project"], $args["projection"]).', :usr, :ip)';
        $sqlArgs = array(
          'project'=>$args["project"],
          'map'=>$args["map"],
          'usr'=>$args["user"],
          'ip'=>$args["ip_address"]);
      }
      $stmt = $db->prepare($sql);
      $stmt->execute($sqlArgs);
    }
  }
  
  function createGeoJSONPolygonGeometry($geom, $project, $mapSRID) {
    $headlessDefaultSRID = str_replace("EPSG:","", TRACK_SRID);
    $headlessMapSRID = str_replace("EPSG:","", $mapSRID);
    $SRS_params = getMatomoProjParams($project, [$headlessDefaultSRID, $headlessMapSRID]);
    $stt = $geom[1]." ".$geom[3].", ".$geom[2]." ".$geom[3].", ".$geom[2]." ".$geom[0].", ".$geom[1]." ".$geom[0].", ".$geom[1]." ".$geom[3];
    $wkt = "st_geomfromtext('POLYGON((".$stt."))', $headlessMapSRID)";
	if($headlessMapSRID != $headlessDefaultSRID) {
	  if(empty($SRS_params[$headlessDefaultSRID]) || empty($SRS_params[$headlessMapSRID]))
        $wkt = "st_transform($wkt, '".$headlessDefaultSRID."')";
	  else {
        $paramFrom = $SRS_params[$headlessMapSRID];
		$paramTo = $SRS_params[$headlessDefaultSRID];
		$wkt = POSTGIS_TRANSFORM_GEOMETRY."($wkt,'".$paramFrom."','".$paramTo."','".$headlessDefaultSRID."')";
	  }
	}
	return $wkt;//"ST_Polygon($wkt, $headlessDefaultSRID)";
  }
  
  function parseGeoJSONPolygonGeometry($geom, $project, $mapSRID) {
    $headlessDefaultSRID = str_replace("EPSG:","", TRACK_SRID);
    $headlessMapSRID = str_replace("EPSG:","", $mapSRID);
    $SRS_params = getMatomoProjParams($project, [$headlessDefaultSRID, $headlessMapSRID]);
    $stt = $geom[1]." ".$geom[3].", ".$geom[2]." ".$geom[3].", ".$geom[2]." ".$geom[0].", ".$geom[1]." ".$geom[0].", ".$geom[1]." ".$geom[3];
    $wkt = "st_geomfromtext('POLYGON((".$stt."))', $headlessMapSRID)";
	if($mapSRID != $headlessDefaultSRID) {
	  if(empty($SRS_params[$headlessDefaultSRID]) || empty($SRS_params[$headlessMapSRID]))
        $wkt = "st_transform($wkt, '".$headlessDefaultSRID."')";
	  else {
        $paramFrom = $SRS_params[$headlessMapSRID];
		$paramTo = $SRS_params[$headlessDefaultSRID];
		$wkt = POSTGIS_TRANSFORM_GEOMETRY."($wkt,'".$paramFrom."','".$paramTo."','".$headlessDefaultSRID."')";
	  }
	}
	return $wkt;
  }

  function getMatomoProjParams($project, $sridArr){
    $result = array();
    $db = GCApp::getDB();
	$sridStr = "";
	foreach($sridArr as $srid)
      $sridStr .= "'$srid',";
	$sql="SELECT srid, proj4text,projparam FROM spatial_ref_sys LEFT JOIN ".DB_SCHEMA.".project_srs using(srid)
            WHERE srid in (".substr($sridStr, 0 , strlen($sridStr)-1).") AND (project_name IS NULL OR project_name = ?);";
	$stmt = $db->prepare($sql);
	$stmt->execute(array($project));
	while($projparams = $stmt->fetch(PDO::FETCH_ASSOC)) {
	  $projString = $projparams["proj4text"];
	  if(strpos($projString,"towgs84") === false && !empty($projparams["projparam"]))
        $projString .="+towgs84=".$projparams["projparam"];
	  $result[$projparams["srid"]] = $projString;
    }
	return $result;
  }

?>
