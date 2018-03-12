<?php

namespace QKPHP\Web\Entity;

abstract class Entity {

  protected $fields = array();
  private $kvmap = array();

  private static $defaultActionName = "default";
  private $rules = array();

  protected $validateFields = array();

  public function __construct() {
    foreach($this->fields as $k=>$v) {
      if(is_numeric($k)) {
        $this->kvmap[$v] = '';
      } else {
        $this->kvmap[$k] = $v;
      }
    }
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

  public function toArray($useValidateField=false) {
    if($useValidateField) {
      $data = array();
      foreach($this->kvmap as $k=>$v) {
        if(isset($this->validateFields[$k])) {
          $data[$k] = $v;
        }
      }
      return $data;
    } else {
      return $this->kvmap;
    }
  }

  public function toEntity($data) {
    foreach($data as $k=>$v) {
      if(isset($this->kvmap[$k])) {
        $this->kvmap[$k] = $v;
      }
    }
    return $this;
  }

  abstract public function initValidator($options);
  abstract public function getStaticMessageMap();

  public function getMessage($code) {
    $code = "$code";
    $messageMap = $this->getStaticMessageMap();
    if(!empty($messageMap) && isset($messageMap[$code])) {
      return $messageMap[$code];
    } else {
      return '';
    }
  }

  public function getValidatorField($action="") {
    if(empty($action)) {
      $action = self::$defaultActionName;
    }
    if (!isset($this->rules[$action])) {
      return null;
    }
    return array_keys($this->rules[$action]);
  }

  /**
   * $action: the name of one validator group
   * $field: entity field name
   * $validator: validator package or name, use '_' to auto set field value without validate. eg: \QKPHP\Web\Validator\Rules\Length
   * $args: the arguments of this validator
   * $errorMsg
   * $errorCodes
   * TODO field 支持多validator
   */
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

  /**
   *  return array(status, errorCodes, errorMsgs)
   */
  public function validate($action="") {
    $status = true;
    $errorCodes = array();
    $errorMsgs = array();
    if(empty($this->validateFields)) {
      return array(false, null, null);
    }

    if(empty($action)) {
      $action = self::$defaultActionName;
    }
    if (!isset($this->rules[$action])) {
      return array(false, null, null);
    }

    foreach($this->rules[$action] as $field => $validatorInfo) {
      if(!isset($this->validateFields[$field])) {
        continue;
      }
      foreach($validatorInfo as $validator => $params) {
        if ($validator === '_') {
          $st = true;
          $val = $this->$field;
        } else {
          if (substr($validator, 0, 1) != '\\') {
            $validator = "\\QKPHP\Web\\Validator\\Rules\\".$validator;
          }
          list($st, $val) = $validator::validator($this->$field, $params["args"]);
        }
        if(!$st) {
          $status = false;
          $errorCodes[] = empty($params['code']) ? $field : $params['code'];
          $errorMsgs[] = $params['msg'];
          break;
        } else {
          $this->$field = $val;
        }
      }
    }
    if ($status) {
      return array(true, null, null);
    } else {
      return array(false, $errorCodes, $errorMsgs);
    }
  }

}
