<?php
namespace QKPHP\Web;

use \QKPHP\Web\Application;

abstract class QKObject {

  private $objectContainer = array();
  private static $globalObjectContainer = array();

  /**
   * use func_get_args to support php 5.3
   * the param mode is: ($fieldName, $packageName, ...$args)
   */
  private function registerObject0(array $args, $isGlobal=false) {
    if (count($args) < 2) {
      return;
    }
    $fieldName   = $args[0];
    $packageName = $args[1];
    $args = array_slice($args, 2);
    
    if (empty($fieldName) || empty($packageName)) { 
      return;
    }
    if (!$isGlobal && isset($this->objectContainer[$fieldName])) {
      return;
    }
    if ($isGlobal && isset(self::$globalObjectContainer[$fieldName])) {
      return;
    }
    if (empty($args)) {
      $args = null;
    }
    if ($isGlobal) {
      self::$globalObjectContainer[$fieldName] = array($packageName, $args);
    } else {
      $this->objectContainer[$fieldName] = array($packageName, $args);
    }
  }

  protected function registerObject () {
    $this->registerObject0(func_get_args());
  }

  protected function registerGlobalObject () {
    $this->registerObject0(func_get_args(), true);
  }

  public function __get($fieldName) {
    if(isset($this->$fieldName)) {
      return $this->$fieldName;
    }
    if (!isset($this->objectContainer[$fieldName]) && !isset(self::$globalObjectContainer[$fieldName])) {
      return null;
    }
    $ins = null;
    $args = null;
    $inGlobal = true;
    if (isset(self::$globalObjectContainer[$fieldName])) {
      list($ins, $args) = self::$globalObjectContainer[$fieldName];
    } else {
      $inGlobal = false;
      list($ins, $args) = $this->objectContainer[$fieldName];
    }
    if (is_string($ins)) {
      $argsLen = empty($args) ? 0 : count($args);
      switch ($argsLen) {
        case 0:
          $ins = new $ins;
          break;
        case 1:
          $ins = new $ins($args[0]);
          break;
        case 2:
          $ins = new $ins($args[0], $args[1]);
          break;
        case 3:
          $ins = new $ins($args[0], $args[1], $args[2]);
          break;
        case 4:
          $ins = new $ins($args[0], $args[1], $args[2], $args[3]);
          break;
        case 5:
          $ins = new $ins($args[0], $args[1], $args[2], $args[3], $args[4]);
          break;
        case 6:
          $ins = new $ins($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
          break;
        case 7:
          $ins = new $ins($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
          break;
        default:
          if (version_compare(PHP_VERSION, '5.6.0') >= 0) {
            $ins = new $ins(...$args);
          } else {
            $cls = new \ReflectionClass($ins);
            $ins = $cls->newInstanceArgs($args);
          }
      }
      if ($inGlobal) {
        self::$globalObjectContainer[$fieldName][0] = $ins;
      } else {
        $this->objectContainer[$fieldName][0] = $ins;
      }
    }
    return $ins;
  }
}
