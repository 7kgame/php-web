<?php

namespace QKPHP\Web\MVC;

class View {

  private $viewvars;

  public function __set($name, $value) {
    $this->viewvars[$name] = $value;
  }

  public function __get($name) {
    return isset($this->viewvars[$name]) ? $this->viewvars[$name] : null;
  }

  public function __isset($name) {
    return isset($this->viewvars[$name]);
  }

  public function render($script) {
    ob_start();
    require($script);
    $html = ob_get_clean();
    return $html;
  }
}
