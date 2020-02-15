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

  private $realRouterName;

  private $wildcardPattern = '*';

  public function __construct($routerDir, $routerName) {
    $this->target = $routerName;
    $routerFile = $routerDir . DIRECTORY_SEPARATOR . $routerName . '.php';
    if (is_file($routerFile)) {
      $this->router = require($routerFile);
    } else {
      $this->realRouterName = $routerName;
      $routerName = '_';
      $this->target = $routerName;
      $routerFile = $routerDir . DIRECTORY_SEPARATOR . $routerName . '.php';
      $this->router = require($routerFile);
    }
  }

  public function parse(array $paths, $method='get') {
    if ($this->realRouterName) {
      $paths = array_merge(array($this->realRouterName), $paths);
    }
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
      $patterns = array_keys($this->router[$method]);
      $patternsLen = array();
      foreach ($patterns as $pattern) {
        $patternsLen[] = count(explode('/', $pattern));
      }
      arsort($patternsLen);
      foreach ($patternsLen as $idx => $v) {
        $pattern = $patterns[$idx];
        $params = $this->searchPattern($pattern, $this->router[$method][$pattern]);
        if ($params !== false) {
          $this->meta = $this->router[$method][$pattern];
          $this->pattern = $pattern;
          $this->params = $params;
          break;
        }
      }
    }
    if (empty($this->pattern)) {
      if (isset($this->router[$method][$this->wildcardPattern])) {
        $this->meta = $this->router[$method][$this->wildcardPattern];
        $this->pattern = $this->wildcardPattern;
        $this->params = array();
      } else {
        return false;
      }
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
      return false;
    }
    $patterns = explode('/', $pattern);
    $patternsLen= count($patterns);
    if (strpos($pattern, $this->wildcardPattern) === false && $patternsLen != count($this->paths)) {
      return false;
    }
    $params = array();
    for($i=0; $i<$patternsLen; $i++) {
      if (strpos($patterns[$i], '{') === 0) {
        if (is_numeric($this->paths[$i])) {
          $this->paths[$i] = $this->paths[$i] - 0;
        }
        $params[] = $this->paths[$i];
        continue;
      }
      if ($i == ($patternsLen - 1) && $patterns[$i] == $this->wildcardPattern) {
        return $params;
      }
      if ($patterns[$i] != $this->wildcardPattern && $patterns[$i] != $this->paths[$i]) {
        return false;
      }
    }
    if (count($params) != ($meta['paramsSize'])) {
      return false;
    }
    return $params;
  }
}
