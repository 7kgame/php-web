<?php
namespace QKPHP\Web;

abstract class Object {

  private $objectContainer = array();

  protected function registerObject($fieldName, $package) {
    $this->objectContainer[$fieldName] = $package;
  }

  public function __get($fieldName) {
    if(isset($this->$fieldName)) {
      return $this->$fieldName;
    }
    if(!isset($this->objectContainer[$fieldName]) || empty($this->objectContainer[$fieldName])) {
      return null;
    }

    $type = gettype($this->objectContainer[$fieldName]);
    if($type == "object") {
      return $this->objectContainer[$fieldName];
    } else if($type == "string") {
      $class = $this->objectContainer[$fieldName];
      $this->objectContainer[$fieldName] = new $class;
      return $this->objectContainer[$fieldName];
    } else {
      return null;
    }
  }
}
