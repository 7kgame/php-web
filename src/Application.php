<?php
namespace QKPHP\Web;

use \QKPHP\Web\Request\Request;
use \QKPHP\Web\Request\Router;
use \QKPHP\Common\Config\Config;
use \QKPHP\Common\Utils\Url;
use \QKPHP\Common\Utils\Utils;
use \QKPHP\Common\Utils\Annotation;

class Application {

  const ROUTER_DIR = 'router';
  const CONTROLLER_DIR = 'controller';
  const CONFIG_DIR = 'config';

  public  $webroot;

  private $routerDir = self::ROUTER_DIR;
  private $controllerDir = self::CONTROLLER_DIR;
  private $configDir = self::CONFIG_DIR;

  private function __construct() {}

  private function __clone() {}

  private static $ins;

  public static function getInstance () {
    if (empty(self::$ins)) {
      self::$ins = new Application();
    }
    return self::$ins;
  }

  public function init ($webroot, array $options=null) {
    $this->webroot = $webroot;
    if (!empty($options)) {
      isset($options['router']) ? $this->routerDir = $options['router'] : null;
      isset($options['controller']) ? $this->controllerDir = $options['controller'] : null;
      isset($options['config']) ? $this->configDir = $options['config'] : null;
      if ($options['cors']) {
        $this->supportCORS(isset($options['hosts']) ? $options['hosts'] : null);
      }
    }
    Config::setConfigDir($this->getConfigPath());
    return $this;
  }

  public function start () {
    if (empty($this->webroot)) {
      $this->showHttpError('502');
    }

    $paths = explode('/', trim(Url::getRequestPath(), '/'));
    $routerFileName = array_shift($paths);
    if (empty($routerFileName)) {
      $routerFileName = '_';
    }
    $router = new Router($this->getRouterPath(), $routerFileName);
    if(!$router->parse($paths, $_SERVER['REQUEST_METHOD'])) {
      $this->showHttpError('404');
    }

    require($this->getControllerPath() . DIRECTORY_SEPARATOR . $router->file);
    $controller = new $router->class;
    $action = $router->method;

    $params = array();
    $request = new Request();
    $request->init();
    if ($router->paramsSize > 0) {
      if (!empty($router->params)) {
        $params = array_merge($params, $router->params);
      }
    }
    $controller->init($this, $request, $router);
    if (!$controller->beforeCall()) {
    }

    $response = call_user_func_array(array($controller, $router->method), $params);
    $controller->afterCall($response);
  }

  private function showHttpError($code) {
    Url::httpHeader($code);
    die();
  }

  // all instance container, packageName => instance
  private $objectContainer = array();
  // fieldName => packageName
  private $fieldObjectContainer = array();

  public function registerObject($fieldName, $packageName, array $config=null) {
    if (empty($fieldName) || empty($packageName)) {
      return;
    }
    if (isset($this->fieldObjectContainer[$fieldName])) {
      return;
    }
    if (!empty($config)) {
      $packageName = array($packageName, $config);
    }
    $this->fieldObjectContainer[$fieldName] = $packageName;
    $this->objectContainer[$packageName] = $packageName;
  }

  public function getObject($fieldName) {
    if (!isset($this->fieldObjectContainer[$fieldName])) {
      return null;
    }
    $packageName = $this->fieldObjectContainer[$fieldName];
    $config = null;
    if (is_array($packageName)) {
      $config = $packageName[1];
      $packageName = $packageName[0];
    }
    $ins = $this->objectContainer[$packageName];
    if (is_string($ins)) {
      if (empty($config)) {
        $ins = new $ins;
      } else {
        $ins = new $ins($config);
      }
      $this->objectContainer[$packageName] = $ins;
    }
    return $this->objectContainer[$packageName];
  }

  public function getRouterPath () {
    return $this->webroot . DIRECTORY_SEPARATOR . $this->routerDir;
  }

  public function getControllerPath () {
    return $this->webroot . DIRECTORY_SEPARATOR . $this->controllerDir;
  }

  public function getConfigPath () {
    return $this->webroot . DIRECTORY_SEPARATOR . $this->configDir;
  }

  private function supportCORS ($hosts=null) {
    if (empty($_SERVER['HTTP_ORIGIN'])) {
      return;
    }
    $host = $_SERVER['HTTP_ORIGIN'];
    if (!empty($hosts) && $hosts != '*') {
      if (!in_array($host, explode(',', $hosts))) {
        return;
      }
    }
    header("Access-Control-Allow-Credentials: true");
    header('Access-Control-Allow-Origin: ' . $host);
    header("Access-Control-Allow-Headers: *, X-Requested-With, Content-Type");
    header('Access-Control-Allow-Methods:OPTIONS, GET, DELETE, HEAD, OPTIONS, POST, PUT, PATCH');
    header('Access-Control-Max-Age: 3600');
  }

}
