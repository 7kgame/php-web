<?php
namespace QKPHP\Web\Request;

class Router {

  public $target;
  public $paths;
  public $router;

  public $class;
  public $file;
  public $filePath;
  public $classAnnos;
  public $annos;
  public $meta;
  public $pattern;
  public $paramsSize = 0;
  public $params;
  public $method;

  public function __construct($routerDir, $routerName) {
    $this->target = $routerName;
    $this->router = require($routerDir . DIRECTORY_SEPARATOR . $routerName . '.php');
  }

  public function parse(array $paths, $method='get') {
    if (empty($paths)) {
      $paths = array('/');
    }
    $this->paths = $paths;
    $method = strtoupper($method);
    if (!isset($this->router[$method])) {
      return null;
    }

    $pathStr = implode('/', $paths);
    if (isset($this->router[$method][$pathStr])) {
      $this->meta = $this->router[$method][$pathStr];
      $this->pattern = $pathStr;
      $this->params = null;
    } else {
      $this->pattern = null;
      foreach ($this->router[$method] as $path => $v) {
        $params = $this->searchPattern($path, $v);
        if (!empty($params)) {
          $this->meta = $v;
          $this->pattern = $path;
          $this->params = $params;
          break;
        }
      }
    }
    if (empty($this->pattern)) {
      return false;
    }

    $this->method = $this->meta['method'];
    $classInfo = $this->router['class'][$this->meta['class']];
    $this->file = $classInfo['file'];
    $this->filePath = $this->meta['class'];
    $this->class = $classInfo['class'];
    $this->classAnnos = $classInfo['annos'];
    $this->paramsSize = $this->meta['paramsSize'];
    $this->annos = $this->meta['annos'];

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
    if (count($params) != ($meta['paramsSize'])) {
      return;
    }
    return $params;
  }
}
