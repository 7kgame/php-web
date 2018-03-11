<?php
namespace QKPHP\Web\Validator\Rules;

use QKPHP\Web\Validator\Validator;

class Length extends Validator {

  /**
   * $args = array(2, 10); // 2 <= length <= 10
   */
  public static function validator($value, array $args) {
    if(count($args) != 2) {
      return self::returnFailed($value);
    }
    $minLen = $args[0] - 0;
    $maxLen = $args[1] - 0;
    $len = mb_strlen($value);
    if($len > $maxLen || $len < $minLen) {
      return self::returnFailed($value);
    } else {
      return self::returnSuccessed($value);
    }
  }

}
