<?php

namespace QKPHP\Web\Entity;

abstract class Entity {

  protected $fields = array();
  private $kvmap = array();

  private static $defaultActionName = "default";
  private $rules = array();
  private $baseRules = array();

  protected $validateFields = array();

  public function __construct() {
    foreach($this->fields as $k=>$v) {
      if(is_numeric($k)) {
        $this->kvmap[$v] = '';
      } else {
        $this->kvmap[$k] = $v;
      }
    }
    $this->initValidator();
  }

  public function get($field) {
    if(!isset($this->kvmap[$field])) {
      return null;
    }
    return $this->kvmap[$field];
  }

  public function set($field, $value, $ignoreNull=true) {
    if(isset($this->kvmap[$field])) {
      $this->validateFields[$field] = true;
      if(!$ignoreNull || $value !== null) {
        $this->kvmap[$field] = $value;
      }
    }
  }

  public function __get($field) {
    return $this->get($field);
  }

  public function __set($field, $value) {
    $this->set($field, $value);
  }

  public function toArray($withValidate=false) {
    if($withValidate) {
      $data = array();
      foreach($this->kvmap as $k=>$v) {
        //if(isset($this->validateFields[$k])) {
          $data[$k] = $v;
        //}
      }
      return $data;
    } else {
      return $this->kvmap;
    }
  }

  public function toModel($data) {
    foreach($data as $k=>$v) {
      if(isset($this->kvmap[$k])) {
        $this->kvmap[$k] = $v;
      }
    }
    return $this;
  }

  abstract protected function initValidator($extends);
  abstract protected function getStaticMessageMap();

  public function getMessage($code) {
    $code = "$code";
    $messageMap = $this->getStaticMessageMap();
    if(isset($messageMap[$code])) {
      return $messageMap[$code];
    } else {
      return '';
    }
  }

  protected function addValidator($action, $field, $validator='_', $args=null, $errorMsg=null, $errorCode=null) {
    if ($errorCode === null) {
      $errorCode = $field;
    }
    if(empty($action)) {
      $action = self::$defaultActionName;
    }
    if($errorMsg === null) {
      $error = $this->getStaticMessageMap();
      if(isset($error[$errorCode])) {
        $errorMsg = $error[$errorCode];
      }
    }

    $this->rules[$action][$field][$validator] = array(
      "args" => $args,
      "code" => $errorCode,
      "msg"  => $errorMsg
    );
  }

  protected function addValidators($action, array $validators) {
    if (empty($validators)) {
      return;
    }
    foreach ($validators as $validator) {
      $len = count($validator);
      switch ($len) {
        case 1:
          $this->addValidator($action, $validator[0]);
          break;
        case 2:
          $this->addValidator($action, $validator[0], $validator[1]);
          break;
        case 3:
          $this->addValidator($action, $validator[0], $validator[1], $validator[2]);
          break;
        case 4:
          $this->addValidator($action, $validator[0], $validator[1], $validator[2], $validator[3]);
          break;
        case 5:
          $this->addValidator($action, $validator[0], $validator[1], $validator[2], $validator[3], $validator[4]);
          break;
      }
    }
  }
}
