<?php

namespace QKPHP\Web\Dao;

use \QKPHP\Web\QKObject;

class Common extends QKObject {

  private $config;
  private $isMaster = false;

  private $mysqlConfig;
  private $redisConfig;
  private $mysqlFieldName;
  private $redisFieldName;

  public function __construct (array $config=null) {
    if (empty($config)) {
      $config = array();
    }
    $this->config = $config;
    isset($config['master']) ? ($this->isMaster = $config['master']) : null;
    isset($config['mysql']) ? ($this->mysqlConfig = $config['mysql']) : null;
    isset($config['redis']) ? ($this->redisConfig = $config['redis']) : null;
    if (!empty($this->mysqlConfig)) {
      $this->mysqlFieldName = 'mysql:'.$this->mysqlConfig['host'].','.$this->mysqlConfig['port'];
    }
    if (!empty($this->redisConfig)) {
      $this->redisFieldName = 'redis:'.$this->redisConfig['host'].','.$this->redisConfig['port'];
    }
    if (!empty($this->mysqlFieldName)) {
      $this->registerObject($this->mysqlFieldName,
        '\QKPHP\Web\Dao\Plugins\Mysql\Mysql',
        array_merge($config['mysql'], array('master'=>$this->isMaster)));
    }
    if (!empty($this->redisFieldName)) {
      $this->registerObject($this->redisFieldName,
        '\QKPHP\Web\Dao\Plugins\Redis\Redis',
        array_merge($config['redis'], array('master'=>$this->isMaster)));
    }
  }

  public function getMysql () {
    return $this->$mysqlFieldName;
  }

  public function getReids () {
    return $this->$redisFieldName;
  }

}
