<?php

namespace QKPHP\Web\Dao;

use \QKPHP\Web\QKObject;

class Dao extends QKObject {

  private $plugins = array();

  public function registerPlugin () {
  }

  public function __get($fieldName) {
    if(isset($this->$fieldName)) {
      return $this->$fieldName;
    }
    if (isset($this->plugins[$fieldName])) {
      return $this->plugins[$fieldName];
    }
    return null;
  }
}
