<?php

namespace QKPHP\Web\Validator;

class Validator {

  protected static function returnFailed($value) {
    return array(false, $value);
  }

  protected static function returnSuccessed($value) {
    return array(true, $value);
  }

}
