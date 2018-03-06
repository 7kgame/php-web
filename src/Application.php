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

    $paths = explode('/', trim(Url::getRequestPath(), '/'));
    $routerFileName = array_shift($paths);
    if (empty($routerFileName)) {
      $routerFileName = '_';
    }
    $router = new Router($this->getRouterPath(), $routerFileName);
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

  public static function updateRouter ($event) {
    $cwd = getcwd();
    $controller = 'controller';
    $router = 'router';
    $arguments = $event->getArguments();
    if (count($arguments) > 0) {
      $controller = $arguments[0];
    }
    if (count($arguments) > 1) {
      $router = $arguments[1];
    }
    $controllerDir = $cwd . DIRECTORY_SEPARATOR . $controller;
    $routerDir = $cwd . DIRECTORY_SEPARATOR . $router;

    if (!file_exists($controllerDir)) {
      die("controller dir \"$controllerDir\" is not exist!\n");
    }

    if (!file_exists($routerDir)) {
      die("router dir \"$routerDir\" is not exist!\n");
    }
    $files = Utils::rdir($controllerDir);
    foreach ($files as $file) {
      if (substr($file, -4) != '.php') {
        continue;
      }
      $pid = pcntl_fork();
      //父进程和子进程都会执行下面代码
      if ($pid == -1) {
        //错误处理：创建子进程失败时返回-1.
        die('could not fork');
      } else if ($pid) {
        //父进程会得到子进程号，所以这里是父进程执行的逻辑
        pcntl_waitpid($pid, $status); //等待子进程中断，防止子进程成为僵尸进程。
        echo "parent ===\n";
      } else {
        //子进程得到的$pid为0, 所以这里是子进程执行的逻辑。
        echo "child ===\n";

        include($file);
        $fileParts = explode(DIRECTORY_SEPARATOR, $file);
        $className = rtrim(array_pop($fileParts), '.php');
        $classInfo = Annotation::parse($className, $file); 
        $classAnnos = $classInfo['class'];
        $methodAnnos = $classInfo['methods'];
        if (empty($methodAnnos)) {
          //continue;
        }
        var_dump($classAnnos);
        exit(0);
      }
      echo $pid."\n";
    }

  }

}
