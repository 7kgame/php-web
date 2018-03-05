<?php
namespace QKPHP\Web\Request;

class Router {

  public $target;
  public $paths;
  public $router;

  public $class;
  public $file;
  public $annos;
  public $meta;
  public $pattern;
  public $paramsSize = 0;
  public $params;
  public $method;

  public function __construct(array $router) {
    $this->router = $router;
  }

  public function parse($paths, $method='get') {
    if (!is_array($paths)) {
      $paths = explode('/', trim($paths, '/'));
    }
    if (empty($paths)) {
      $paths = array();
    }
    $target = array_slice($paths, 0, 1);
    $paths = array_slice($paths, 1);
    if (empty($target)) {
      $target = '/';
    } else {
      $target = $target[0];
    }
    $method = strtoupper($method);
    if (!isset($this->router[$target]) || !isset($this->router[$target][$method])) {
      return null;
    }
    $this->target = $target;
    $this->paths = $paths;
    $this->class = $this->router[$target]['_class'];
    $this->file = $this->router[$target]['_file'];
    $router = $this->router[$target][$method];
    $paths0 = implode('/', $paths);
    if (isset($router[$paths0])) {
      $this->meta = $router[$paths0];
      $this->pattern = $paths0;
      $this->params = null;
    } else {
      $this->pattern = null;
      foreach ($router as $k=>$v) {
        $params = $this->searchPattern($k, $v);
        if (!empty($params)) {
          $this->meta = $v;
          $this->pattern = $k;
          $this->params = $params;
          break;
        }
      }
    }
    if (empty($this->pattern)) {
      return false;
    }
    $this->method = $this->meta['method'];
    $annos = $this->router[$target]['_annos'];
    if (empty($annos) || !is_array($annos) || !isset($annos[$this->method])) {
      $this->annos = null;
    } else {
      $this->annos = $annos[$this->method];
    }
    $this->paramsSize = isset($this->meta['paramsSize']) ? $this->meta['paramsSize'] - 0 : 0;
    return true;
  }

  private function searchPattern($pattern, $meta) {
    if (empty($meta) || !is_array($meta)) {
      return;
    }
    $paths = explode('/', $pattern);
    $pathsLen = count($paths);
    if ($pathsLen != count($this->paths)) {
      return;
    }
    $params = array();
    for($i=0; $i<$pathsLen; $i++) {
      if (strpos($paths[$i], '{') === 0) {
        if (is_numeric($this->paths[$i])) {
          $this->paths[$i] = $this->paths[$i] - 0;
        }
        $params[] = $this->paths[$i];
        continue;
      }
      if ($paths[$i] != $this->paths[$i]) {
        return;
      }
    }
    if (isset($meta['paramsSize']) && count($params) != ($meta['paramsSize'])-1) {
      return;
    }
    return $params;
  }
}
