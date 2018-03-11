<?php
namespace QKPHP\Web\Validator\Rules;

use QKPHP\Web\Validator\Validator;

class Mobile extends Validator {

  const MOBILE_LEN = 11;

  /**
   * 138********, 138 *** *****, 138-***-*****
   * 86138********, 86 138********
   * +86
   * (86)
   */
  public static function validator($mobile, array $args=null) {
    $len = strlen($mobile);
    if($len < self::MOBILE_LEN || $len > (self::MOBILE_LEN*2)) {
      return self::returnFailed($mobile);
    }
    $mobile = preg_replace("/[() \-+]/", "", $mobile);
    if("86" == $mobile[0].$mobile[1]) {
      $mobile = substr($mobile, 2);
    }
    if(strlen($mobile) != self::MOBILE_LEN) {
      return self::returnFailed($mobile);
    }
    return self::returnSuccessed($mobile);
  } 

}
