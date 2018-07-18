<?php
  require_once (ROOT_PATH.'lib/gcapp.class.php');
  
  function listMapServerRequests() {
    $db = GCApp::getDb();
    $sql = "select project, map, srs, bboxlon, bboxlat, \"user\", ip_address, date_insert, counter from gisclient_3.maprequest order by project, map, srs";
    $stmt = $db->prepare($sql);
    if($stmt->execute())
      return $stmt->fetchAll();
  }

  function storeMapServerRequest($args) {
    if(count($args) == 6) {
      $center = explode(",", $args["center"]);
      $db = GCApp::getDb();
      //verifico se esiste
      $sql = 'select requestid, counter from gisclient_3.maprequest where project = :project and map = :map and srs = :srs '
        .'and bboxlon between :sx and :dx and bboxlat between :up and :down '
        .'and "user" = :usr and ip_address = :ip';
      $stmt = $db->prepare($sql);
      $result = $stmt->execute(array(
        'project'=>$args["project"],
        'map'=>$args["map"],
        'srs'=>$args["projection"],
        'sx'=>($center[0] - 10), 'dx'=>($center[0] + 10),
        'up'=>($center[1] - 10), 'down'=>($center[1] + 10),
        'usr'=>$args["user"],
        'ip'=>$args["ip_address"]));
      //se esiste update
      $res=$stmt->fetchAll();
      if(($result == 1) && (count($res) == 1)) {
        $sql = 'update gisclient_3.maprequest set counter = '.($res[0]["counter"] + 1).' where requestid = '.$res[0]["requestid"];
        $sqlArgs = array();
      } else {
        $sql = 'insert into gisclient_3.maprequest(project, map, srs, bboxlon, bboxlat, "user", ip_address) values(:project, :map, :srs, :bboxlon, :bboxlat, :usr, :ip)';
        $sqlArgs = array(
          'project'=>$args["project"],
          'map'=>$args["map"],
          'srs'=>$args["projection"],
          'bboxlon'=>floatVal($center[0]),
          'bboxlat'=>floatval($center[1]),
          'usr'=>$args["user"],
          'ip'=>$args["ip_address"]);
      }
      $stmt = $db->prepare($sql);
      $stmt->execute($sqlArgs);
    }
  }
?>
