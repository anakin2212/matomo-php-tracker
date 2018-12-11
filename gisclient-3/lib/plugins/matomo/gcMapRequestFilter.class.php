<?php
  class gcMapRequestFilter {
  
    //i filtri qui elencati devono fare a pari con quelli elencati nel plugin di matomo
    static private $expectedQueryParams = array('date', 'period', 'segment');
    static private $expectedSegmentFilters = array(
      'visitIp' => ['==' , '!='],
      //uguale,diverso,contiene,non contiene,inizia con, finisce con
      'userId' => ['==' , '!=', '=@', '!@', '=^', '=$']
    );

    private $notProcessedArgs = array();
    private $filter = array();

    function __construct($filterString) {
      parse_str($filterString, $reqParams);
      foreach(self::$expectedQueryParams as $key) {
        if(array_key_exists($key, $reqParams)) {
          if(strcmp($key, "segment") == 0)
            $this->manageSegmentFilters($reqParams[$key]);
          else
            $this->filter[$key] = $reqParams[$key];
        }
      }
    }
    
    private function manageSegmentFilters($complexFilter) {
      $segmentArray = explode("," , $complexFilter);
      //adesso ho i singoli pezzi... tengo solo quelli che iniziano come le chiavi di expectedSegmentFilter
      foreach(self::$expectedSegmentFilters as $expectedSegmentElement=>$expectedSegmentOperators) {
        foreach($segmentArray as $singleSegment) {
          $length = strlen($expectedSegmentElement);
          //se il singolo segmento inizia con una delle chiavi
          if(substr($singleSegment, 0, strlen($expectedSegmentElement)) === $expectedSegmentElement) {
            $key = str_replace($expectedSegmentElement, "" , $singleSegment);
            $nextSegmentFilter = FALSE;
            foreach($expectedSegmentOperators as $expectedSegmentSingleOperator) {
              if(substr($key, 0, strlen($expectedSegmentSingleOperator)) === $expectedSegmentSingleOperator) {
                $this->filter[$expectedSegmentElement] = ["operator" => $expectedSegmentSingleOperator, "value" => str_replace($expectedSegmentSingleOperator, "" , $key)];
                $nextSegmentFilter = 1;
                break;
              }
            }
            if($nextSegmentFilter)
              break;
            else
              error_log("Impossibile gestire filtro di segmento:".$singleSegment);
          }
        }
      }
      //error_log(json_encode($this->filter));
    }
    
    function applyUserAndIpFilters($dataset) {
      $result = $dataset;
      //if(isset($this->filter["visitIp"]))
        $result  = $this->applyEqualNotEqualFilter($result, "ip_address" ,"visitIp");
      //if(isset($this->filter["userId"]))
        $result = $this->applyComparableStringFilter($result, "user", "userId");
      return $result;
    }
    
    private function applyEqualNotEqualFilter($dataset, $dbFieldName, $filterName) {
      if(isset($this->filter[$filterName])) {
        $result = array();
        foreach($dataset as $singleRow) {
          $insert = false;
          switch($this->filter[$filterName]["operator"]) {
            case '==':
              $insert = (strcmp($singleRow[$dbFieldName], $this->filter[$filterName]["value"]) == 0);
              break;
            case '!=':
              $insert = (strcmp($singleRow[$dbFieldName], $this->filter[$filterName]["value"]) != 0);
              break;
            default:
              break;
          }
          if($insert)
            $result[] = $singleRow;
        }
        return $result;
      }
      return $dataset;
    }

    private function applyComparableStringFilter($dataset, $dbFieldName, $filterName) {
      if(isset($this->filter[$filterName])) {
        $result = array();
        foreach($dataset as $singleRow) {
          $insert = false;
          switch($this->filter[$filterName]["operator"]) {
            case '==':
              $insert = (strcmp($singleRow[$dbFieldName], $this->filter[$filterName]["value"]) == 0);
              break;
            case '!=':
              $insert = (strcmp($singleRow[$dbFieldName], $this->filter[$filterName]["value"]) != 0);
              break;
            case '=@':
              $insert = (strpos($singleRow[$dbFieldName], $this->filter[$filterName]["value"]) !== false);
              break;
            //non contiene
            case '!@':
              $insert = (strpos($singleRow[$dbFieldName], $this->filter[$filterName]["value"]) === false);
              break;
            case '=^':
              $insert = (substr($singleRow[$dbFieldName], 0, strlen($this->filter[$filterName]["value"])) === $this->filter[$filterName]["value"]);
              break;
            case '=$':
              $insert = (substr($singleRow[$dbFieldName], -strlen($this->filter[$filterName]["value"])) === $this->filter[$filterName]["value"]);
              break;
            default:
              break;
          }
          if($insert)
            $result[] = $singleRow;
        }
        return $result;
      }
      return $dataset;
    }
    
    
    function applyTimeFiltersOnDataset($dataset) {
      if(isset($this->filter["period"]) && isset($this->filter["date"])) {
        $result = array();
        foreach($dataset as $singleRow) {
          $insert = false;
          switch($this->filter["period"]) {
            case 'day':
              $auxToday = strcmp($this->filter["date"], "today") == 0 ? date("Y-m-d") : $this->filter["date"];
              $dateInsert = DateTime::createFromFormat("d/m/Y H:i:s.u", $singleRow["date_insert"]);
              $insert = strcmp($dateInsert->format("Y-m-d"), $auxToday) == 0;
              break;
            case 'week':
              $objA = DateTime::createFromFormat("Y-m-d", $this->filter["date"]);
              $objB = DateTime::createFromFormat("d/m/Y H:i:s.u", $singleRow["date_insert"]);
              $insert = ($objA->format("W") == $objB->format("W"));
              break;
            case 'month':
              $objA = DateTime::createFromFormat("Y-m-d", $this->filter["date"]);
              $objB = DateTime::createFromFormat("d/m/Y H:i:s.u", $singleRow["date_insert"]);
              $insert = (strcmp($objA->format("m"), $objB->format("m")) == 0);
              break;
            case 'year':
              $objA = DateTime::createFromFormat("Y-m-d", $this->filter["date"]);
              $objB = DateTime::createFromFormat("d/m/Y H:i:s.u", $singleRow["date_insert"]);
              $insert = (strcmp($objA->format("Y"), $objB->format("Y")) == 0);
              break;
            case 'range':
              $filterDates = explode(",", $this->filter["date"]);
              $dateInsert = DateTime::createFromFormat("d/m/Y H:i:s.u", $singleRow["date_insert"]);
              $insert = (strcmp($dateInsert->format("Y-m-d"), $filterDates[0]) >= 0) &&
                        (strcmp($dateInsert->format("Y-m-d"), $filterDates[1]) <=0);
              break;
            default:
              break;
          }
          if($insert)
            $result[] = $singleRow;
        }
        return $result;
      }
      return $dataset;
    }
    
  }
?>
