<?php
namespace QKPHP\Web\Dao\Storages\Redis;

use QKPHP\Web\Dao\Storages\Storage;

class Redis extends Storage {

  private $timeout;

  public function __construct (array $conf, array $options=null) {
    parent::__construct($conf, $options);
    $this->timeout = isset($conf['timeout']) ? $conf['timeout'] : 0;
  }

  public function connect () {
    $redis = new \Redis();
    if(!$redis->connect($this->host, $this->port, $this->timeout)) {
      throw new \Exception("redis pconnect error: host is " . $this->host . ":" . $this->port);
    }
    $this->ins = $redis;
  }

}
