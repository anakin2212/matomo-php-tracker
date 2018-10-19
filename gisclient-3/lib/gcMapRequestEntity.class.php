<?php

  include_once("gcMapRequestFilter.class.php");

  class gcMapRequestEntityFactory {
    static private $countLabel = "counter";

    /* Costruisce un oggetto gcMapRequest a partire da un dataset recuperato da tabella
    gisclient_3.maprequest*/
    public static function createEntities($orderedDataset = array(), $matomoFilters) {
      return self::createEntitiesFromIndexField(self::createEntitiesForMap($orderedDataset, $matomoFilters), 0);
    }
    
    public static function createEntitiesForMap($orderedDataset = array(), $matomoFilters) {
      error_log($matomoFilters);
      $filteredDataset = array();
      if(!empty($matomoFilters)) {
        $filterManager = new gcMapRequestFilter($matomoFilters);
        $filteredDataset = $filterManager->applyTimeFiltersOnDataset($orderedDataset);
        $filteredDataset = $filterManager->applyUserAndIpFilters($filteredDataset);
      } else
        $filteredDataset = $orderedDataset;
      return $filteredDataset;
    }
    
    private function createEntitiesFromIndexField($orderedDataset, $indexLabel) {
      $entities = array();
        foreach ($orderedDataset as $singleDataset) {
          $ent = null;
          $previous = null;
          $choords = null;
          $userInfo = null;
          $counter = $singleDataset[self::$countLabel];
          foreach($singleDataset as $key=>$value) {
            switch($key) {
              case 'project':
                if(array_key_exists($value,$entities)) {
                  $entities[$value]->addToCounter($counter);
                  $ent = $entities[$value];
                } else {
                  $ent = new gcMapRequestEntity($key, $value, $counter);
                  $entities[$value] = $ent;
                }
                $previous = [$key, $value];
                break;
              case 'bbox':
                $choords = $value;
                $ent->addChild($previous, new gcMapRequestEntity("choords", $choords, $counter));
                $previous = ["choords", $choords];
                break;
              case 'counter':
              case 'date_insert':
                break;
              case 'user':
                $userInfo = $value;
                break;
              case 'ip_address':
                $userInfo .= " (indirizzo ip: $value)";
                $ent->addChild($previous, new gcMapRequestEntity("user", $userInfo, $counter));
                break;
              default:
                $ent->addChild($previous, new gcMapRequestEntity($key, $value, $counter));
                $previous = [$key, $value];
                break;
            }

          }
        }
        return $entities;
    }
  }
  
  class gcMapRequestEntity {
    private $key;
    private $value;
    private $counter;
    private $children;

    function __construct($key, $value, $counter) {
      $this->value = $value;
      $this->key = $key;
      $this->counter = $counter;
      $this->children = array();
    }

    function getValue() {
      return $this->value;
    }
    
    function getTableKey() {
      switch($this->key) {
        case 'project':
          return "Nome progetto";
        case 'map':
          return "Mappa";
        case 'choords':
          return "Punto centrale di view";
        case 'user':
          return "Utente (indirizzo IP)";
        default:
          return $this->key;
      }
    }

    function getKey() {
      return $this->key;
    }

    function getCounter() {
      return $this->counter;
    }
    
    function getChildren() {
      return $this->children;
    }

    function addToCounter($num) {
      $this->counter += $num;
    }
    
    function hasChildren() {
      return !empty($this->children);
    }

    function addChild($parent, $child) {
      if(strcmp($parent[0], $this->getKey()) == 0 && strcmp($parent[1], $this->getValue()) == 0){
        //sono io il padre
        foreach($this->children as $curr) {
          //devo aggiungerlo se non presente oppure incrementare il counter
          if(strcmp($child->getValue(), $curr->getValue()) == 0 && strcmp($child->getKey(), $curr->getKey()) == 0) {
            $curr->addToCounter($child->getCounter());
            return 1;
          }
        }
        $this->children[] = $child;
        return 1;
      } else {
        foreach($this->children as $curr) {
          if($curr->addChild($parent, $child) == 1)
            return 1;
        }
      }
      return 0;
    }
    
    public function toString() {
      $obj = "Label: ".$this->key." Key: ".$this->value." Counter: ".$this->counter." <br/>";
      if(strcmp("choords", $this->key) !=0) {
        $obj .= "Children for $this->value: <br/>";
        foreach($this->children as $child)
          $obj .= $child->toString();
      }
      return $obj;
    }
  }
  
?>
