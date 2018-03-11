<?php
namespace QKPHP\Web\Validator\Rules;

use QKPHP\Web\Validator\Validator;

class Passwd extends Validator {

  /**
   * $args[0] min length, default 8
   * $args[1] max length, default 20
   */
  public static function validator($passwd, array $args=null) {
    $passwd = trim("$passwd");
    $len = strlen($passwd);
    $minLen = 8;
    $maxLen = 20;
    if (!empty($args)) {
      $minLen = $args[0];
      if (count($args) > 1) {
        $maxLen = $args[1];
      }
    }
    if ($minLen < 1) {
      $minLen = 1;
    }
    if ($len < $minLen || $len > $maxLen) {
      return self::returnFailed($passwd);
    }
    $lowerCase = mb_strtolower($passwd);
    if ($lowerCase == $passwd) {
      // 须包含大写字母
      return self::returnFailed($passwd);
    }
    if(preg_match('/\d/', $passwd)) {
      // 须包含数字
      return self::returnSuccessed($passwd);
    } else {
      return self::returnFailed($passwd);
    }
  }

}
