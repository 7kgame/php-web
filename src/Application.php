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

  private $instanceConfig = array();

  public  $webroot;

  private $routerDir = self::ROUTER_DIR;
  private $controllerDir = self::CONTROLLER_DIR;
  private $configDir = self::CONFIG_DIR;

  private $_supportCORS = false;

  private function __construct() {}

  private function __clone() {}

  private static $ins;

  public static function getInstance () {
    if (empty(self::$ins)) {
      self::$ins = new Application();
      global $_QK_APPLICATION_INS;
      $_QK_APPLICATION_INS = self::$ins;
    }
    return self::$ins;
  }

  public function init ($webroot, array $options=null) {
    $this->webroot = $webroot;
    if (!empty($options)) {
      isset($options['router']) ? $this->routerDir = $options['router'] : null;
      isset($options['controller']) ? $this->controllerDir = $options['controller'] : null;
      isset($options['configDir']) ? $this->configDir = $options['configDir'] : null;
      isset($options['configs']) && is_array($options['configs']) ? $this->instanceConfig = $options['configs'] : null;
      if (isset($options['cors']) && is_array($options['cors'])) {
        $this->_supportCORS = true;
        $this->supportCORS(isset($options['cors']['hosts']) ? $options['cors']['hosts'] : null);
      }
    }
    Config::setConfigDir($this->getConfigPath());
    return $this;
  }

  public function start () {
    if (empty($this->webroot)) {
      $this->showHttpError('502');
    }

    if ($this->_supportCORS && strtoupper($_SERVER['REQUEST_METHOD']) === 'OPTIONS') {
      die('');
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

    require($this->getControllerPath() . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, explode('/', $router->file)));
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
    if ($result = $controller->beforeCall()) {
      header('Content-Type: application/json');
      $result = json_encode($result);
      die($result);
    }

    $response = call_user_func_array(array($controller, $router->method), $params);
    $controller->afterCall($response);
  }

  private function showHttpError($code) {
    Url::httpHeader($code);
    die();
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

  public function getAppConfig ($name, $key=null) {
    return Config::getAppConf($name, $key);
  }

  public function getDBConfig ($name, $key=null) {
    return Config::getDBConf($name, $key);
  }

  public function getServiceConfig ($name, $key=null) {
    return Config::getServiceConf($name, $key);
  }

  public function getConfig ($name, $key=null, $type=null) {
    return Config::getConf($name, $key, $type);
  }

  public function getInstanceConfig ($key) {
    if (isset($this->instanceConfig[$key])) {
      return $this->instanceConfig[$key];
    } else {
      return null;
    }
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
