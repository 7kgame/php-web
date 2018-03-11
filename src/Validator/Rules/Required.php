<?php
namespace QKPHP\Web\Validator\Rules;

use QKPHP\Web\Validator\Validator;

class Required extends Validator {

  /**
   *  args = false for reserving the field
   */
  public static function validator($value, array $args=null) {
    $required = true;
    if($args && isset($args[0])) {
      $required = $args[0];
    }
    $isOk = true;
    if(is_array($value)) {
      if(empty($value)) {
        $isOk = !$required;
      }
    } else {
      if($value === null) {
        $isOk = !$required;
      } else {
        $value = trim($value);
        if(strlen($value) < 1) {
          $isOk = !$required;
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
