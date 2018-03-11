<?php
namespace QKPHP\Web\Validator\Rules;

use QKPHP\Web\Validator\Validator;

class Email extends Validator {

  public static function validator($email, array $args=null) {
    $len = strlen($email);
    if ($len < 4 || $len > 50) {
      return self::returnFailed($email);
    }
    if(preg_match('/[\w\._\-]+@[\w\._\-]+\.[\w\._\-]+/', $email)) {
      return self::returnSuccessed($email);
    } else {
      return self::returnFailed($email);
    }
  }

}
