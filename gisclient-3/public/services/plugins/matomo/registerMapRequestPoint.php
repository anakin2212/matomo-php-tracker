<?php
  require_once("../../../../config/config.php");
  require_once(ROOT_PATH."lib/gcMapRequestEntity.class.php");

  $args = array();
  if(isset($_GET)) {
    if(isset($_GET["action"]) && (strcmp($_GET["action"], "view") == 0)) {
      $data = listMapServerRequests();
      $entities = gcMapRequestEntityFactory::createEntities($data, $_SERVER["QUERY_STRING"]);
      echo writeHeader();
      echo writeTable($entities);

    } else {
      foreach ($_GET as $key => $value)
        $args[$key] = $value;
      $args["ip_address"] = $_SERVER['REMOTE_ADDR'];
      storeMapServerRequest($args);
    }
  }
  
  function writeHeader() {
    $sql = "<script type=\"text/javascript\" src=\"".PUBLIC_URL."/admin/js/jquery/jquery.js\"></script>
            <script type=\"text/javascript\" src=\"".PUBLIC_URL."/admin/js/jquery/jquery-ui.js\"></script>
            <LINK media=\"screen\" href=\"".PUBLIC_URL."admin/css/styles.css\" type=\"text/css\" rel=\"stylesheet\">
            <link type=\"text/css\" href=\"".PUBLIC_URL."admin/css/plugins/matomo/matomo.css\" rel=\"stylesheet\" />
            <link type=\"text/css\" href=\"".PUBLIC_URL."admin/css/jquery-ui/start/jquery-ui-1.8.16.custom.css\" rel=\"stylesheet\" />";
    $sql .= "<script type=\"text/javascript\">
      $(document).ready(function() {
      $(\"[meta$='DivClass']\").click(function() {
        var selector = $(\"[meta='\"+$(this).attr('meta').replace('Class', 'Desc')+\"']\");
        var old = selector.css('display');
        selector.css('display', old=='none' ? '' : 'none');
        $(\"[meta='\"+$(this).attr('meta').replace('Class', 'Pointer')+\"']\").removeClass(old=='none' ? 'ui-icon-carat-1-e' : 'ui-icon-carat-1-s');
        $(\"[meta='\"+$(this).attr('meta').replace('Class', 'Pointer')+\"']\").addClass(old=='none' ? 'ui-icon-carat-1-s' : 'ui-icon-carat-1-e');
      });
      });
      </script>";
    return $sql;
  }

  function writeTable($entities) {
    if($entities != null && count($entities) > 0) {
      $result ="<div class=\"tableHeader ui-widget ui-widget-header ui-corner-top\">".$entities[array_keys($entities)[0]]->getKey()."</div>";
      foreach($entities as $entity) {
        if($entity->hasChildren())
          $result .="<div class=\"entityLine\" meta=\"".preg_replace('/\s+/', '_', $entity->getValue())."DivClass\">"
            ."<span meta=\"".preg_replace('/\s+/', '_', $entity->getValue())."DivPointer\" style=\"float: left;\" class=\"ui-button-icon-primary ui-icon ui-icon-carat-1-e\"></span></div>";
        $result .="<div class=\"entityLine\" meta=\"".preg_replace('/\s+/', '_', $entity->getValue())."DivClass\">"
          ."<span>".$entity->getValue()."</span></div>";
        $result .="<div class=\"entityLineCounter\" meta=\"".preg_replace('/\s+/', '_', $entity->getValue())."DivClass\"><span>".$entity->getCounter()."</span></div>";
        if($entity->hasChildren()) {
          $result .="<div class=\"entityLineChildren\" style=\"display: none;\" meta=\"".preg_replace('/\s+/', '_', $entity->getValue())."DivDesc\">";
          $result .= writeTable($entity->getChildren());
          $result .="</div>";
        }
      }
      return $result;
    }
  }

?>
