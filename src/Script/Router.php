<?php
namespace QKPHP\Web\Script;

use \QKPHP\Common\Utils\Url;
use \QKPHP\Common\Utils\Utils;
use \QKPHP\Common\Utils\Annotation;
use \QKPHP\Web\Application;

class Router {

  const REQUEST_MAPPING_KEY = 'requestmapping';
  const SUB_REQUEST_MAPPING_KEY = 'subrequestmapping';
  const REQUEST_METHOD_KEY = 'method';

  private static $router = Application::ROUTER_DIR;
  private static $controller = Application::CONTROLLER_DIR;

  public static function gen ($event) {
    $cwd = getcwd();
    $arguments = $event->getArguments();
    if (count($arguments) > 0) {
      self::$controller = $arguments[0];
    }
    if (count($arguments) > 1) {
      self::$router = $arguments[1];
    }
    $controllerDir = $cwd . DIRECTORY_SEPARATOR . self::$controller;
    $routerDir = $cwd . DIRECTORY_SEPARATOR . self::$router;

    if (!file_exists($controllerDir)) {
      die("controller dir \"$controllerDir\" is not exist!\n");
    }

    if (!file_exists($routerDir)) {
      die("router dir \"$routerDir\" is not exist!\n");
    }
    $files = Utils::rdir($controllerDir);
    Utils::delDir($routerDir, false);
    foreach ($files as $file) {
      if (substr($file, -4) != '.php') {
        continue;
      }
      $pid = pcntl_fork();
      if ($pid == -1) {
        die("fork child failed\n");
      } else if ($pid) {
        pcntl_wait($status);
      } else {
        include($file);
        $fileParts = explode(DIRECTORY_SEPARATOR, $file);
        $className = rtrim(array_pop($fileParts), '.php');
        $annotations = Annotation::parse($className, $file); 
        list($path, $annos) = self::parseOne($annotations);
        self::genRouterFile($path, $annos, $className, $file, $controllerDir, $routerDir);
        exit(0);
      }
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
    $classRelativePath = rtrim(trim(str_replace($controllerDir, '', $classFile), '/'), '.php');
    $routerFile = $routerDir . DIRECTORY_SEPARATOR . $fileName . '.php';
    $routerInfo = array();
    if (file_exists($routerFile)) {
      $routerInfo = require($routerFile);
    }
    $routerInfo['class'][$classRelativePath] = array(
      'file'  => $classRelativePath . '.php',
      'class' => $className,
      'annos' => empty($annos['class']) ? null : $annos['class']
    );
    foreach ($annos['method'] as $methodInfo) {
      if (!isset($routerInfo[$methodInfo['requestMethod']])) {
        $routerInfo[$methodInfo['requestMethod']] = array();
      }
      $routerInfo[$methodInfo['requestMethod']][$methodInfo['path']] = array(
        'class' => $classRelativePath,
        'method' => $methodInfo['method'],
        'paramsSize' => $methodInfo['paramsSize'],
        'annos' => empty($methodInfo['annos']) ? null : $methodInfo['annos']
      );
    }
    $out = "<?php\nreturn " . var_export($routerInfo, true) . ";\n";
    file_put_contents($routerFile, $out);
  }

}
