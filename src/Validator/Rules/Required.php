<?php
namespace QKPHP\Web\Validator\Rules;

use QKPHP\Web\Validator\Validator;

class Required extends Validator {

  public static function validator($value, $args=null) {
    $isOk = true;
    if(is_array($value)) {
      if(empty($value)) {
        $isOk = false;
      }
    } else {
      if($value === null) {
        $isOk = false;
      } else {
        $value = trim($value);
        if(strlen($value) < 1) {
          $isOk = false;
        }
      }
    }
    if($isOk) {
      return self::returnSuccessed($value);
    } else {
      return self::returnFailed($value);
    }
  }

}
