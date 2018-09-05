<?php
namespace QKPHP\Web\Dao\Plugins\Mongo;

use QKPHP\Web\Dao\Plugins\iPluginDao;
use MongoDB\Client;

class Mongo implements iPluginDao {

  private $host;
  private $port;
  private $user;
  private $passwd;
  private $isSSL = false;

  private $mongo;

  public function __construct (array $conf) {
    $this->setConfig($conf);
    $this->connect();
  }

  public function setConfig (array $conf) {
    if (empty($conf) || !isset($conf['host']) || !isset($conf['port'])) {
      throw new \Exception('mongo config is not valid: '.print_r($conf));
    }
    $this->host = $conf['host'];
    $this->port = $conf['port'];
    $this->user = $conf['user'];
    $this->passwd = $conf['passwd'];
    $this->SSL = isset($conf['ssl']) ? $conf['ssl'] : false;
  }

  public function connect () {
    $uri = 'mongodb://'. $this->host .':' .$this->port;
    $uriOptions = array(
      //'username' => $this->user,
      //'password' => $this->passwd,
      //'ssl' => $this->isSSL
    );
    $driverOptions = array();
    $this->mongo = new \MongoDB\Client($uri, $uriOptions, $driverOptions);
  }

  public function __call($method, $args) {
    if (!is_array($args)) {
      $args = array($args);
    }
    $argsLen = count($args);
    switch ($argsLen) {
      case 0:
        return $this->mongo->$method();
      case 1:
        return $this->mongo->$method($args[0]);
      case 2:
        return $this->mongo->$method($args[0], $args[1]);
      case 3:
        return $this->mongo->$method($args[0], $args[1], $args[2]);
      case 4:
        return $this->mongo->$method($args[0], $args[1], $args[2], $args[3]);
      case 5:
        return $this->mongo->$method($args[0], $args[1], $args[2], $args[3], $args[4]);
      default:
        return call_user_func_array(array($this->mongo, $method), $args);
    }
  }
}
