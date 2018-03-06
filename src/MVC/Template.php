<?php

namespace QKPHP\Web\MVC;

class Template {

  private $templateVars = array ();
  private $view = null;

  public function __construct() {
    $this->view = new namespace\View;
  }

  public function getView() {
    return $this->view;
  }

  public function assign($name, $templateFile) {
    $this->templateVars[$name] = $this->view->render($templateFile);
    $this->view->$name = $this->templateVars[$name];
  }

  public function assignValue($name, $value) {
    $this->view->$name = $value;
    $this->templateVars[$name] = $value;
  }

  public function __get($name){
    return isset($this->templateVars[$name]) ? $this->templateVars[$name] : '';
  }

  public function __isset($name){
    return isset($this->templateVars[$name]);
  }

  public function show($template) {
    header("Content-Type:text/html;charset=utf-8");
    foreach($this->templateVars as $key => $value) {
      $this->$key = $value;
    }
    require($template);
  }

}
