<?php
  class gcMapRequestFilter {
  
    //i filtri qui elencati devono fare a pari con quelli elencati nel plugin di matomo
    static private $expectedQueryParams = array('date', 'period');
    
    private $filter = array();

    function __construct($filterString) {
      parse_str($filterString, $reqParams);
      foreach(self::$expectedQueryParams as $key)
        $this->filter[$key] = $reqParams[$key];
    }
    
    function applyTimeFiltersOnDataset($dataset) {
      if(isset($this->filter["period"]) && isset($this->filter["date"])) {
        $result = array();
        foreach($dataset as $singleRow) {
          $insert = false;
          switch($this->filter["period"]) {
            case 'day':
              $insert = (strcmp(date("Ymd", strtotime($singleRow["date_insert"])), date("Ymd", strtotime($this->filter["date"]))) == 0);
              break;
            case 'week':
              $objA = new DateTime(date("Ymd", strtotime($this->filter["date"])));
              $objB = new DateTime(date("Ymd", strtotime($singleRow["date_insert"])));
              $insert = ($objA->format("W") == $objB->format("W"));
              break;
            case 'month':
              $insert = (strcmp(date("m", strtotime($singleRow["date_insert"])), date("m", strtotime($this->filter["date"]))) == 0);
              break;
            case 'year':
              $insert = (strcmp(date("Y", strtotime($singleRow["date_insert"])), date("Y", strtotime($this->filter["date"]))) == 0);
              break;
            case 'range':
              $filterDates = explode(",", $this->filter["date"]);
              error_log(date("Ymd", strtotime($singleRow["date_insert"]))." ".date("Ymd", strtotime($filterDates[0]))." ".date("Ymd", strtotime($filterDates[1])));
              error_log(
              $insert = (strcmp(date("Ymd", strtotime($singleRow["date_insert"])), date("Ymd", strtotime($filterDates[0]))) >= 0) &&
                        (strcmp(date("Ymd", strtotime($singleRow["date_insert"])), date("Ymd", strtotime($filterDates[1]))) <=0));
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
