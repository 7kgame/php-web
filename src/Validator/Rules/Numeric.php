<?php
namespace QKPHP\Web\Validator\Rules;

use QKPHP\Web\Validator\Validator;

class Numeric extends Validator {

  public static function validator($num, array $args=null) {
    if(!is_numeric($num)) {
      return self::returnFailed($num);
    }

    $min = 'inf';
    $max = 'inf';
    if($args && isset($args[0])) {
      $min = $args[0];
    }
    if($args && isset($args[1])) {
      $max = $args[1];
    }

    if(($min != 'inf' && $num < $min) || ($max!='inf' && $num>$max)) {
      return self::returnFailed($num);
    }
    return self::returnSuccessed($num);
  }

}
