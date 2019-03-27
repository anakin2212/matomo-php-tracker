<?php



final class PiwikWatchDog {

    /**
     * Call this method to get singleton
     *
     * @return UserFactory
     */
  public static function Instance() {
    static $inst = null;
    if ($inst === null)
      $inst = new PiwikWatchDog();
    return $inst;
  }

  /**
   * Private constructor so nobody else can instantiate it
   *
  */
  private function __construct(){}
                   //investigare qui se ci vuole true o false - piwiktracker

  public function greenSemaphore() {
    $result = $this->doFileExist();
    if($result)
      unlink(TRACK_SEMAPHORE_FILEPATH);
    return $result;
  }

  public function isRedSemaphore() {
    if($this->doFileExist()) {
      $fileContent = file_get_contents(TRACK_SEMAPHORE_FILEPATH);
      return time() < ($fileContent + (TRACK_RETRY_TIME * 60));
    }
    return false;
  }
  
  public function doRetryMatomoSending() {
    return $this->doFileExist();
  }
  
  public function writeSemaphore($timestamp){
    if(defined("TRACK_SEMAPHORE_FILEPATH")) {
      //file_put_contents esegue un rewrite del file, se presente
      $result = $this->doFileExist();
      file_put_contents(TRACK_SEMAPHORE_FILEPATH, $timestamp);
      return $result;
    }
    return true;
  }
  
  private function doFileExist() {
    return defined("TRACK_SEMAPHORE_FILEPATH") && file_exists(TRACK_SEMAPHORE_FILEPATH);
  }
}
?>
