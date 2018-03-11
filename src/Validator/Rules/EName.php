<?php
namespace QKPHP\Web\Validator\Rules;

use QKPHP\Web\Validator\Validator;

class EName extends Validator {

  /**
   *  args = [exclude]
   */
  public static function validator($name, array $args=null) {
    if (empty($name)) {
      return self::returnFailed($name);
    }
    $exclude = null;
    if ($args && count($args) > 0) {
      $exclude = $args[0];
    }
    $len = mb_strlen($name);
    for ($i=0; $i<$len; $i++) {
      $c = mb_substr($name, $i, 1);
      if ($exclude && mb_strstr($exclude, $c) !== false) {
        return self::returnFailed($name);
      }
      if (ord($c) > 127) {
        return self::returnFailed($name);
      }
    }
    return self::returnSuccessed($name);
  }

}
