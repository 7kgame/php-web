<?php

namespace QKPHP\Web\Dao;

use \QKPHP\Web\QKObject;

abstract class GeneralDao extends QKObject {

  private $isMaster = false;

  private $mysqlConf;
  private $redisConf;
  private $mysqlFieldName;
  private $redisFieldName;

  public function __construct ($isMaster=false, array $mysqlConf=null, array $redisConf=null) {
    $this->isMaster  = $isMaster;
    $this->mysqlConf = $mysqlConf;
    $this->redisConf = $redisConf;
  }

  public function setMysqlConf (array $mysqlConf=null) {
    $this->mysqlConf = $mysqlConf;
  }

  public function setRedisConf (array $redisConf=null) {
    $this->redisConf = $redisConf;
  }

  private function registerMysql () {
    if (!empty($this->mysqlConf)) {
      $this->mysqlFieldName = 'mysql:'.$this->mysqlConf['host'].','.$this->mysqlConf['port'].','.$this->mysqlConf['user'];
    }
    if (!empty($this->mysqlFieldName)) {
      $this->registerGlobalObject($this->mysqlFieldName,
        '\QKPHP\Web\Dao\Plugins\Mysql\Mysql',
        $this->mysqlConf);
    }
  }

  private function registerRedis () {
    if (!empty($this->redisConf)) {
      $this->redisConf = $this->redisConf;
      $this->redisFieldName = 'redis:'.$this->redisConf['host'].','.$this->redisConf['port'];
    }
    if (!empty($this->redisFieldName)) {
      $this->registerGlobalObject($this->redisFieldName,
        '\QKPHP\Web\Dao\Plugins\Redis\Redis',
        $this->redisConf);
    }
  }

  public function getMysql () {
    $this->registerMysql();
    $mysqlFieldName = $this->mysqlFieldName;
    return $this->$mysqlFieldName;
  }

  public function getReids () {
    $this->registerRedis();
    $redisFieldName = $this->redisFieldName;
    return $this->$redisFieldName;
  }

  public function checkWritable () {
    if(!$this->isMaster) {
      throw new \Exception("Write to slave error.");
    }
  }

  public function begin () {
    return $this->getMysql()->begin();
  }

  public function commit () {
    return $this->getMysql()->commit();
  }

  public function rollBack () {
    return $this->getMysql()->rollBack();
  }

  public function create (array $fields, array $data, $multi=false) {
    $this->checkWritable();
    list($dbName, $tblName) = $this->getDbNameAndTblName();
    return $this->getMysql()->create($dbName, $tblName, $fields, $data, $multi);
  }

  public function fetch ($sql, array $params=null) {
    return $this->getMysql()->fetch($sql, $params);
  }

  public function fetchAll ($sql, array $params=null) {
    return $this->getMysql()->fetchAll($sql, $params);
  }

  public function updateBySql ($sql, array $params=null) {
    $this->checkWritable();
    return $this->getMysql()->updateBySql($sql, $params);
  }

  public function updateByCondition (array $fields, array $params, array $condition) {
    $this->checkWritable();
    list($dbName, $tblName) = $this->getDbNameAndTblName();
    return $this->getMysql()->updateByCondition($dbName, $tblName, $fields, $params, $condition);
  }

  public function deleteBySql ($sql, array $params) {
    $this->checkWritable();
    return $this->getMysql()->deleteBySql($sql, $params);
  }

  public function deleteEntity (array $condition) {
    $this->checkWritable();
    list($dbName, $tblName) = $this->getDbNameAndTblName();
    return $this->getMysql()->deleteByCondition($dbName, $tblName, $condition);
  } 

  abstract protected function getDbNameAndTblName();
  abstract protected function getPrimaryKey();

}
