<?php
namespace QKPHP\Web;

use \QKPHP\Web\Application;

abstract class Object {

  protected function registerObject($fieldName, $packageName, array $config=null) {
    Application::getInstance()->registerObject($fieldName, $packageName, $config);
  }

  public function __get($fieldName) {
    if(isset($this->$fieldName)) {
      return $this->$fieldName;
    }
    return Application::getInstance()->getObject($fieldName);
  }
}
