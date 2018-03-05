<?php
namespace QKPHP\Web;

use \QKPHP\Web\Request\Request;
use \QKPHP\Web\Request\Router;
use \QKPHP\Common\Utils\Url;
use \QKPHP\Common\Config\Config;

class Application {

  public  $webroot;
  public  $bizPrefix;

  private $routerDir = 'router';
  private $controllerDir = 'controller';
  private $configDir = 'config';

  public function __construct ($webroot, array $options=null) {
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
  }

  public function init () {
  }

  public function start () {
    if (empty($this->webroot)) {
      $this->showHttpError('502');
    }

    $path = Url::getRequestPath();
    if (!empty($this->bizPrefix) && strpos($path, $this->bizPrefix) !== false) {
      $path = substr($path, strlen($this->bizPrefix));
    }
    $paths = explode('/', trim($path, '/'));
    $routerConf = require($this->getRouterPath() .DIRECTORY_SEPARATOR .
      $this->bizPrefix . 'router.php');

    $router = new Router($routerConf);
    if(!$router->parse($paths, $_SERVER['REQUEST_METHOD'])) {
      $this->showHttpError('404');
    }

    include($this->getControllerPath() . DIRECTORY_SEPARATOR . $router->file);
    $controller = new $router->class;
    $action = $router->method;

    $params = array();
    $request = new Request();
    $request->init();
    if ($router->paramsSize > 0) {
      $params[] = $request;
      if (!empty($router->params)) {
        $params = array_merge($params, $router->params);
      }
    }
    $controller->init($this, $request, $router->annos);
    if (!$controller->beforeCall($request)) {
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
