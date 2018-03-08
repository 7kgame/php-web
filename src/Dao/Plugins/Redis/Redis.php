<?php
namespace QKPHP\Web\Dao\Plugins\Redis;

use QKPHP\Web\Dao\Plugins\iPluginDao;

class Redis implements iPluginDao {

  private $host;
  private $port;
  private $timeout;

  private $redis;

  public function __construct (array $conf) {
    $this->setConfig($conf);
    $this->connect();
  }

  public function setConfig (array $conf) {
    if (empty($conf) || !isset($conf['host']) || !isset($conf['port'])) {
      throw new \Exception('redis config is not valid: '.print_r($conf));
    }

    $this->host = $conf['host'];
    $this->port = $conf['port'];
    $this->timeout = isset($conf['timeout']) ? $conf['timeout'] : 0;
  }

  public function connect () {
    $redis = new \Redis();
    if(!$redis->connect($this->host, $this->port, $this->timeout)) {
      throw new \Exception("redis pconnect error: host is " . $this->host . ":" . $this->port);
    }
    $this->redis = $redis;
  }

  public function __call($method, $args) {
    if (!is_array($args)) {
      $args = array($args);
    }
    $argsLen = count($args);
    $redis = $this->redis;
    switch ($argsLen) {
      case 0:
        return $redis->$method();
      case 1:
        return $redis->$method($args[0]);
      case 2:
        return $redis->$method($args[0], $args[1]);
      case 3:
        return $redis->$method($args[0], $args[1], $args[2]);
      case 4:
        return $redis->$method($args[0], $args[1], $args[2], $args[3]);
      case 5:
        return $redis->$method($args[0], $args[1], $args[2], $args[3], $args[4]);
      default:
        return call_user_func_array(array($redis, $method), $args);
    }
  }

}
