<?php
namespace QKPHP\Web;

use \QKPHP\Web\Application;

abstract class QKObject {

  private $objectContainer = array();

  /**
   * use func_get_args to support php 5.3
   * the param mode is: ($fieldName, $packageName, ...$args)
   */
  protected function registerObject() {
    $args = func_get_args();
    if (count($args) < 2) {
      return;
    }
    $fieldName   = $args[0];
    $packageName = $args[1];
    $args = array_slice($args, 2);
    
    if (empty($fieldName) || empty($packageName) 
      || isset($this->objectContainer[$fieldName])) {
      return;
    }
    if (empty($args)) {
      $args = null;
    }
    $this->objectContainer[$fieldName] = array($packageName, $args);
  }

  public function __get($fieldName) {
    if(isset($this->$fieldName)) {
      return $this->$fieldName;
    }
    if (!isset($this->objectContainer[$fieldName])) {
      return null;
    }
    list($ins, $args) = $this->objectContainer[$fieldName];
    if (is_string($ins)) {
      if (empty($args)) {
        $ins = new $ins;
      } else {
        if (version_compare(PHP_VERSION, '5.6.0') >= 0) {
          $ins = new $ins(...$args);
        } else {
          $cls = new \ReflectionClass($ins);
          $ins = $cls->newInstanceArgs($args);
        }
      }
      $this->objectContainer[$fieldName][0] = $ins;
    }
    return $ins;
  }
}
