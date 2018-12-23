<?php

namespace QKPHP\Web\MVC;

use \QKPHP\Web\QKObject;

abstract class Controller extends QKObject {

  /**
   * $fieldFilter: 0 返回action定义的fields
   *               1 返回entity定义的fields
   *               2 返回action定义的fields与post fields的交集
   */
  const VALIDATOR_ACTION_FIELDS = 0;
  const VALIDATOR_ENTITY_FIELDS = 1;
  const VALIDATOR_ACTION_ENTITY_FIELDS = 2;

  //Section init
  protected $application;
  protected $request;
  protected $router;

  public function init ($application, $request, $router) {
    $this->application = $application;
    $this->request = $request;
    $this->router = $router;
    $this->postInit();
  }

  protected function postInit() {
  }

  //Section lifecycle
  public function beforeCall() {
    //return true;
  }

  public function afterCall($response) {
    if (empty($response)) {
      return;
    }
    $annos = $this->router->annos;
    $responseType = 'json';
    if (!empty($annos) && isset($annos['reponsetype'])) {
      $responseType = strtolower($annos['reponsetype']);
    }
    if (is_array($response) || $responseType == 'json') {
      header('Content-Type: application/json');
      $response = json_encode($response);
      $callback = $this->request->get('callback');
      if (!empty($callback)) {
        $response = $callback . "($response)";
      }
    }
    echo $response;
  }

  //Section view
  private $template;
  private $view;

  private function initTemplate() {
    if(empty($this->template)) {
      $this->template = new namespace\Template();
      $this->view = $this->template->view;
    }
  }

  /**
   * Assign template value with a template file
   */
  protected function template($var, $templateFile) {
    $this->initTemplate();
    $this->template->assign($var, $templateFile);
  }

  /**
   * Assign template value pair
   */
  protected function templateValue($var, $value) {
    $this->initTemplate();
    $this->template->assignValue($var, $value);
  }

  /**
   * Assign multiple template values
   */
  protected function templateValues($pairs = array()) {
    $this->initTemplate();
    if (is_array($pairs)) {
      foreach($pairs AS $var => $value){
        $this->templateValue($var, $value);
      }
    }
  }

  /**
   * Render the template
   */
  protected function show($templateFile) {
    $this->initTemplate();
    $this->template->show($templateFile);
  }

  //Section transacation
  private $transacationServiceName;

  protected function addTransacationService($serviceName) {
    $this->transacationServiceName = $serviceName;
  }

  private function getTransactionService() {
    $transacationServiceName = $this->transacationServiceName;
    $type = getType($this->$transacationServiceName);
    if (empty($transacationServiceName) || $type != "object") {
      return null;
    }
    return $this->$transacationServiceName;
  }

  public function begin() {
    $transacationService = $this->getTransactionService();
    if (empty($transacationService)) {
      return;
    }
    $transacationService->beginGlobal();
  }

  public function commit() {
    $transacationService = $this->getTransactionService();
    if (empty($transacationService)) {
      return;
    }
    $transacationService->commitGlobal();
  }

  public function rollback() {
    $transacationService = $this->getTransactionService();
    if (empty($transacationService)) {
      return;
    }
    $transacationService->rollBackGlobal();
  }

  /**
   * $fieldFilter: 0 返回action定义的fields
   *               1 返回entity定义的fields
   *               2 返回action定义的fields与post fields的交集
   */
  protected function validate($entity, $action = "default", $fieldFilter = self::VALIDATOR_ACTION_ENTITY_FIELDS, $options=null) {
    if (is_string($entity)) {
      $entity = new $entity;
    }
    $entity->initValidator($options);
    if ($fieldFilter == self::VALIDATOR_ENTITY_FIELDS) {
      $v = $entity->toArray(false);
      $fields = array_keys($v);
    } else {
      $fields = $entity->getValidatorField($action);
    }
    if (empty($fields)) {
      return array(false, null, null, null);
    }
    $emptySet = true;
    foreach ($fields as $field) {
      $value = $this->request->getStr($field, null);
      if ($value === null && $fieldFilter == self::VALIDATOR_ACTION_ENTITY_FIELDS) {
        continue;
      }
      $emptySet = false;
      $entity->set($field, $value);
    }
    if ($emptySet) {
      return array(false, array(-1), array('参数为空'), $entity);
    }
    list($status, $errorCodes, $errorMsgs) = $entity->validate($action);
    return array($status, $errorCodes, $errorMsgs, $entity);
  }

  protected function multiValidate($entity, $action = "default", $fieldFilter = self::VALIDATOR_ACTION_ENTITY_FIELDS, $options=null) {
    if (is_string($entity)) {
      $entity = new $entity;
    }
    $entity->initValidator($options);
    if ($fieldFilter == self::VALIDATOR_ENTITY_FIELDS) {
      $v = $entity->toArray(false);
      $fields = array_keys($v);
    } else {
      $fields = $entity->getValidatorField($action);
    }
    if (empty($fields)) {
      return array(false, null, null, null);
    }
    $entitys = array();
    foreach ($fields as $field) {
      $values = $this->request->get($field);
      if ($values === null && $fieldFilter == self::VALIDATOR_ACTION_ENTITY_FIELDS) {
        continue;
      }
      for ($i = 0; $i < count($values); $i++) {
        if (!isset($entitys[$i])) {
          $entitys[$i] = clone $entity;
        }
        $entitys[$i]->set($field, $values[$i]);
      }
    }
    $errorCodes = array();
    $errorMsgs = array();
    $status = true;

    foreach ($entitys as $e) {
      list($s, $c, $m) = $e->validate($action);
      if (!$s) {
        $status = false;
      }
      $errorCodes[] = $c;
      $errorMsgs[] = $m;
    }
    if (empty($entitys)) {
      return array(false, null, null, null);
    }
    return array($status, $errorCodes, $errorMsgs, $entitys);
  }

}
