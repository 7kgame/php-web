<?php
namespace QKPHP\Web\Validator\Rules;

use QKPHP\Web\Validator\Validator;

class Poi extends Validator {

  /**
   * lng,lat
   * 116.408792,40.000788
   */
  public static function validator($poi, array $args=null) {
    $poiInfo = explode(",", $poi);
    if(count($poiInfo) != 2) {
      return self::returnFailed($poi);
    }
    if(is_numeric($poiInfo[0]) && is_numeric($poiInfo[1])) {
      return self::returnSuccessed($poi);
    }
    return self::returnFailed($poi);
  }

}
