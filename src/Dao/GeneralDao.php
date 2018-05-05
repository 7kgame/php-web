<?php

namespace QKPHP\Web\Dao;

use \QKPHP\Web\QKObject;

abstract class GeneralDao extends QKObject {

  private $isMaster = false;

  private $masterMysqlConf;
  private $slaverMysqlConf;
  private $masterRedisConf;
  private $slaverRedisConf;

  public function __construct ($isMaster=false, array $mysqlConf=null, array $redisConf=null) {
    $this->isMaster  = $isMaster;
    $this->setMysqlConf($mysqlConf);
    $this->setRedisConf($redisConf);
  }

  public function getApplication () {
    global $_QK_APPLICATION_INS;
    return $_QK_APPLICATION_INS;
  }

  public function setMysqlConf (array $mysqlConf=null) {
    if (empty($mysqlConf)) {
      return;
    }
    if (isset($mysqlConf['host'])) {
      if ($this->isMaster) {
        $this->masterMysqlConf = $mysqlConf;
      } else {
        $this->slaverMysqlConf = $mysqlConf;
      }
    } else {
      if ($this->isMaster && isset($mysqlConf['master'])) {
        $this->masterMysqlConf = $mysqlConf['master'];
      }
      if (!$this->isMaster && isset($mysqlConf['slaver'])) {
        $this->slaverMysqlConf = $mysqlConf['slaver'];
      }
    }
  }

  public function setRedisConf (array $redisConf=null) {
    if (empty($redisConf)) {
      return;
    }
    if (isset($redisConf['host'])) {
      if ($this->isMaster) {
        $this->masterRedisConf = $redisConf;
      } else {
        $this->slaverRedisConf = $redisConf;
      }
    } else {
      if ($this->isMaster && isset($redisConf['master'])) {
        $this->masterRedisConf = $redisConf['master'];
      }
      if (!$this->isMaster && isset($redisConf['slaver'])) {
        $this->slaverRedisConf = $redisConf['slaver'];
      }
    }
  }

  private function registerMysql () {
    $conf = null;
    if ($this->isMaster) {
      $conf = $this->masterMysqlConf;
    } else {
      $conf = empty($this->slaverMysqlConf) ? $this->masterMysqlConf : $this->slaverMysqlConf;
    }
    if (empty($conf)) {
      throw new \Exception('mysql conf for '.($this->isMaster ? 'master' : 'slaver').' is not exist.');
    }
    $fieldName = 'mysql:'.$conf['host'].','.$conf['port'].','.$conf['user'];
    $this->registerGlobalObject($fieldName, '\QKPHP\Web\Dao\Plugins\Mysql\Mysql', $conf);
    return $fieldName;
  }

  private function registerRedis () {
    $conf = null;
    if ($this->isMaster) {
      $conf = $this->masterRedisConf;
    } else {
      $conf = empty($this->slaverRedisConf) ? $this->masterRedisConf: $this->slaverRedisConf;
    }
    if (empty($conf)) {
      throw new \Exception('redis conf for '.($this->isMaster ? 'master' : 'slaver').' is not exist.');
    }
    $fieldName = 'redis:'.$conf['host'].','.$conf['port'];
    $this->registerGlobalObject($fieldName, '\QKPHP\Web\Dao\Plugins\Redis\Redis', $conf);
    return $fieldName;
  }

  public function getMysql () {
    $fieldName = $this->registerMysql();
    return $this->$fieldName;
  }

  public function getRedis () {
    $fieldName = $this->registerRedis();
    return $this->$fieldName;
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

  public function insert ($dbName, $tblName, array $fields, array $data, $multi=false) {
    $this->checkWritable();
    if (empty($dbName) || empty($tblName)) {
      throw new \Exception('dbName or tableName can\'t be empty');
    }
    return $this->getMysql()->insert($dbName, $tblName, $fields, $data, $multi);
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

  public function updateByCondition ($dbName, $tblName, array $fields, array $params, array $condition) {
    $this->checkWritable();
    if (empty($dbName) || empty($tblName)) {
      throw new \Exception('dbName or tableName can\'t be empty');
    }
    return $this->getMysql()->updateByCondition($dbName, $tblName, $fields, $params, $condition);
  }

  public function deleteBySql ($sql, array $params) {
    $this->checkWritable();
    return $this->getMysql()->deleteBySql($sql, $params);
  }

  public function insertEntity (array $fields, array $data, $multi=false, $getId=true) {
    list($dbName, $tblName) = $this->getDbNameAndTblName();
    $ret = $this->insert($dbName, $tblName, $fields, $data, $multi);
    if(!$getId || !$ret) {
      return !!$ret;
    }
    return $this->getMysql()->lastInsertId();
  }

  public function set($id, array $fields, array $params) {
    $primaryKey = $this->getPrimaryKey();
    if(empty($primaryKey)) {
      return null;
    }
    return $this->updateEntity($fields, $params, array($primaryKey=>$id));
  }

  public function get($id, array $fields=null, $withLock=false) {
    $primaryKey = $this->getPrimaryKey();
    if(empty($primaryKey)) {
      return null;
    }
    $condition = array($primaryKey=>$id);
    return $this->getEntity($condition, $fields, $withLock);
  }

  public function getEntity(array $condition, array $fields=null, $withLock=false) {
    return $this->getEntities($condition, $fields, 1, $withLock);
  }

  public function getEntities(array $condition, array $fields=null, $limit=-1, $withLock=false) {
    list($dbName, $tblName) = $this->getDbNameAndTblName();
    if (empty($dbName) || empty($tblName)) {
      throw new \Exception('dbName or tableName can\'t be empty');
    }
    $limit = $limit - 0;
    $sql = "select ";
    if(empty($fields)) {
      $sql .= " * ";
    } else {
      $sql .= implode(',', $fields);
    }
    $sql .= " from $dbName.$tblName";
    $data = array();
    if(!empty($condition)) {
      list($where, $data) = $this->getMysql()->makeCondition($condition);
      if(!empty($where)) {
        $sql .= " where $where";
      }
    }
    if ($limit > 1) {
      $sql .= " limit $limit";
    } else {
      if($withLock) {
        $sql .= " for update";
      }
    }
    if ($limit == 1) {
      return $this->fetch($sql, $data);
    } else {
      return $this->fetchAll($sql, $data);
    }
  }

  public function updateEntity (array $fields, array $params, array $condition) {
    $this->checkWritable();
    list($dbName, $tblName) = $this->getDbNameAndTblName();
    return $this->updateByCondition($dbName, $tblName, $fields, $params, $condition);
  }

  public function deleteEntity (array $condition) {
    $this->checkWritable();
    list($dbName, $tblName) = $this->getDbNameAndTblName();
    if (empty($dbName) || empty($tblName)) {
      throw new \Exception('dbName or tableName can\'t be empty');
    }
    return $this->getMysql()->deleteByCondition($dbName, $tblName, $condition);
  } 

  protected function getDbNameAndTblName() {
    return array('', '');
  }

  protected function getPrimaryKey() {
    return '';
  }

}
