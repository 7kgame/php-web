<?php
namespace QKPHP\Web\Script;

use \QKPHP\Common\Utils\Url;
use \QKPHP\Common\Utils\Utils;
use \QKPHP\Common\Utils\Annotation;
use \QKPHP\Common\Loader;
use \QKPHP\Web\Application;

class Router {

  const REQUEST_MAPPING_KEY = 'requestmapping';
  const SUB_REQUEST_MAPPING_KEY = 'subrequestmapping';
  const REQUEST_METHOD_KEY = 'method';

  private static $router = Application::ROUTER_DIR;
  private static $controller = Application::CONTROLLER_DIR;

  public static function gen ($event=null) {
    if (empty($event)) {
      $event = array();
    }
    if (!is_array($event)) {
      $arguments = $event->getArguments();
    } else {
      $arguments = $event;
    }
    if (count($arguments) > 0) {
      self::$controller = $arguments[0];
    }
    if (count($arguments) > 1) {
      self::$router = $arguments[1];
    }
    $cwd = getcwd();
    $controllerDir = $cwd . DIRECTORY_SEPARATOR . self::$controller;
    $routerDir = $cwd . DIRECTORY_SEPARATOR . self::$router;

    self::addAutoLoader($controllerDir);

    if (!file_exists($controllerDir)) {
      die("controller dir \"$controllerDir\" is not exist!\n");
    }

    if (!file_exists($routerDir)) {
      die("router dir \"$routerDir\" is not exist!\n");
    }
    $files = Utils::rdir($controllerDir);
    Utils::delDir($routerDir, false);
    $out = "<?php\nreturn array();\n";
    $defaultRouterFile = $routerDir . DIRECTORY_SEPARATOR . '_.php';
    file_put_contents($defaultRouterFile, $out);
    foreach ($files as $file) {
      if (substr($file, -4) != '.php') {
        continue;
      }
      // $pid = pcntl_fork();
      // if ($pid == -1) {
      //   die("fork child failed\n");
      // } else if ($pid) {
      //   pcntl_wait($status);
      // } else {
        include_once($file);
        $fileParts = explode(DIRECTORY_SEPARATOR, $file);
        $className = array_pop($fileParts);
        if (substr($className, -4) == '.php') {
          $className = substr($className, 0, -4);
        }
        //$className = rtrim(array_pop($fileParts), '.php');
        $annotations = Annotation::parse($className, $file); 
        $parseResult = self::parseOne($annotations);
        if (empty($parseResult)) {
          continue;
          // exit(0);
        }
        self::genRouterFile($parseResult[0], $parseResult[1], $className, $file, $controllerDir, $routerDir);
      //   exit(0);
      // }
    }
  }

  private static function parseOne($annotations) {
    $classAnnos = $annotations['class'];
    $methodAnnos = $annotations['methods'];
    if (empty($methodAnnos) || empty($classAnnos)) {
      return;
    }
    $urlPath0 = '';
    $subUrlPath0 = '';
    $defaultRequestMethod = 'get';
    $classAnnos0 = array();
    foreach ($classAnnos as $cp) {
      $cp[0] = strtolower($cp[0]);
      if ($cp[0] == self::REQUEST_MAPPING_KEY) {
        $urlPath0 = $cp[1];
      } else if ($cp[0] == self::SUB_REQUEST_MAPPING_KEY) {
        $subUrlPath0 = $cp[1];
      } else if ($cp[0] == self::REQUEST_METHOD_KEY) {
        $defaultRequestMethod = $cp[1];
      } else {
        $classAnnos0[] = $cp;
      }
    }
    if (empty($urlPath0)) {
      return;
    }
    $urlPath0 = trim($urlPath0, '/');
    if (empty($urlPath0)) {
      $urlPath0 = '/';
    }
    $methodPaths = array();
    foreach ($methodAnnos as $methodName => $methodInfo) {
      $annos = $methodInfo['anno'];
      $params = $methodInfo['method'];
      $methodAnnos0 = array();
      // no annotation or is not public
      if (empty($annos) || !$methodInfo['method'][0]) {
        continue;
      }
      $urlPath1 = '';
      $requestMethod = '';
      foreach ($annos as $anno) {
        $anno[0] = strtolower($anno[0]);
        if ($anno[0] == self::REQUEST_MAPPING_KEY) {
          $urlPath1 = $anno[1];
        } else if ($anno[0] == self::REQUEST_METHOD_KEY) {
          $requestMethod = $anno[1];
        } else {
          $methodAnnos0[] = $anno;
        }
      }
      if (empty($urlPath1)) {
        continue;
      }
      $urlPath1 = trim($urlPath1, '/');
      if (empty($urlPath1)) {
        $urlPath1 = '/';
      }
      $subUrlPath0 = trim($subUrlPath0, '/');
      if (empty($requestMethod)) {
        $requestMethod = $defaultRequestMethod;
      }
      $urlPathParamSize = strpos($urlPath1, '{') === false ? 0 : 1;
      if ($urlPathParamSize > 0) {
        $urlPathParamSize = count(explode('{', $urlPath1)) - 1;
      }
      $paramsSize = $methodInfo['method'][2];
      if ($urlPathParamSize != $paramsSize) {
        continue;
      }
      if (!empty($subUrlPath0)) {
        $urlPath1 = $subUrlPath0 . '/' . $urlPath1;
      }
      $methodPaths[] = array(
        'method'        => $methodName,
        'requestMethod' => strtoupper($requestMethod),
        'path'          => $urlPath1,
        'paramsSize'    => $paramsSize,
        'annos'         => $methodAnnos0);
    }
    return array($urlPath0, array(
      'class' => $classAnnos0,
      'method' => $methodPaths
    ));
  }

  private static function genRouterFile ($fileName, $annos, $className, $classFile, $controllerDir, $routerDir) {
    if ($fileName == '/') {
      $fileName = '_';
    }
    //$classRelativePath = rtrim(trim(str_replace($controllerDir, '', $classFile), '/'), '.php');
    $classRelativePath = trim(str_replace($controllerDir, '', $classFile), '/');;
    if (substr($classRelativePath, -4) == '.php') {
      $classRelativePath = substr($classRelativePath, 0, -4);
    }
    $routerFile = $routerDir . DIRECTORY_SEPARATOR . $fileName . '.php';
    $routerInfo = array();
    if (file_exists($routerFile)) {
      $routerInfo = require($routerFile);
    }
    $classAnnos = null;
    if (!empty($annos['class'])) {
      $classAnnos = array();
      foreach ($annos['class'] as $anno) {
        $classAnnos[$anno[0]] = $anno[1];
      }
    }
    $routerInfo['class'][$classRelativePath] = array(
      'file'  => $classRelativePath . '.php',
      'class' => $className,
      'annos' => $classAnnos
    );
    if (!empty($annos['method'])) {
      foreach ($annos['method'] as $methodInfo) {
        $methodAnnos = null;
        if (!empty($methodInfo['annos'])) {
          $methodAnnos = array();
          foreach ($methodInfo['annos'] as $anno) {
            $methodAnnos[$anno[0]] = $anno[1];
          }
        }
        $methodItem = array(
          'class' => $classRelativePath,
          'method' => $methodInfo['method'],
          'paramsSize' => $methodInfo['paramsSize'],
          'annos' => $methodAnnos
        );
        foreach(explode('|', $methodInfo['requestMethod']) as $rm) {
          $rm = trim($rm);
          if (empty($rm)) {
            continue;
          }
          if (!isset($routerInfo[$rm])) {
            $routerInfo[$rm] = array();
          }
          $routerInfo[$rm][$methodInfo['path']] = $methodItem;
        }
      }
    }
    $out = "<?php\nreturn " . var_export($routerInfo, true) . ";\n";
    file_put_contents($routerFile, $out);
  }

  private static function addAutoLoader ($path) {
    Loader::setIncludePath(array($path));
    Loader::load();
  }

}
