<?php
namespace QKPHP\Web\Validator\Rules;

use QKPHP\Web\Validator\Validator;
use QKPHP\Common\Utils\IDNo as IDNoUtil;

class IdNo extends Validator {

  public static function validator($value) {
    if (IDNoUtil::check($value)) {
      return self::returnSuccessed($value);
    } else {
      return self::returnFailed($value);
    }
  }

}
