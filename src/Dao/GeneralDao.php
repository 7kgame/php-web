<?php

namespace QKPHP\Web\Dao;

use \QKPHP\Web\QKObject;

abstract class GeneralDao extends QKObject {

  private $isMaster = false;

  private $masterConf = array();
  private $slaverConf = array();

  const DB_MYSQL = 'mysql';
  const DB_REDIS = 'redis';
  const DB_MONGO = 'mongo';

  public function __construct ($isMaster=false, array $mysqlConf=null, array $redisConf=null, $mongoConf=null) {
    $this->isMaster  = $isMaster;
    $this->setDBConf($mysqlConf, self::DB_MYSQL);
    $this->setDBConf($redisConf, self::DB_REDIS);
    $this->setDBConf($mongoConf, self::DB_MONGO);
  }

  public function getApplication () {
    global $_QK_APPLICATION_INS;
    return $_QK_APPLICATION_INS;
  }

  public function setDBConf ($conf, $type = self::DB_MYSQL) {
    if (empty($conf)) {
      return;
    }
    if (isset($conf['host'])) {
      if ($this->isMaster) {
        $this->masterConf[$type] = $conf;
      } else {
        $this->slaverConf[$type] = $conf;
      }
    } else {
      if ($this->isMaster && isset($conf['master'])) {
        $this->masterConf[$type] = $conf['master'];
      }
      if (!$this->isMaster && isset($conf['slaver'])) {
        $this->slaverConf[$type] = $conf['slaver'];
      }
    }
  }

  private function registerDb ($type, $classPath) {
    $conf = null;
    if ($this->isMaster) {
      $conf = $this->masterConf[$type];
    } else {
      $conf = empty($this->slaverConf[$type]) ? $this->masterConf[$type]: $this->slaverConf[$type];
    }
    if (empty($conf)) {
      throw new \Exception($type.' conf for '.($this->isMaster ? 'master' : 'slaver').' is not exist.');
    }
    $fieldName = $type.':'.$conf['host'].','.$conf['port'];
    if($type == self::DB_MYSQL) {
      $fieldName = 'mysql:'.$conf['host'].','.$conf['port'].','.$conf['user'];
      if (!empty($conf['dbName'])) {
        $fieldName .= ','.$conf['dbName'];
      }
    }
    $this->registerGlobalObject($fieldName, $classPath, $conf);
    return $fieldName;
  }

  public function getMysql () {
    $classPath = '\QKPHP\Web\Dao\Plugins\Mysql\Mysql';
    $fieldName = $this->registerDb(self::DB_MYSQL, $classPath);
    return $this->$fieldName;
  }

  public function getRedis () {
    $classPath = '\QKPHP\Web\Dao\Plugins\Redis\Redis';
    $fieldName = $this->registerDb(self::DB_REDIS, $classPath);
    return $this->$fieldName;
  }

  public function getMongo () {
    $classPath = '\QKPHP\Web\Dao\Plugins\Mongo\Mongo';
    $fieldName = $this->registerDb(self::DB_MONGO, $classPath);
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

  public function pagination($condition, $fields=array(), $page=1, $limit=20, $withLock = false, $order = array()){
    $count = $this->listCount($condition);
    $pagenum = 1;
    $limit = $limit - 0;
    $datalist = array();
    if($count>0){
      if($count%$limit == 0){
        $pagenum = $count/$limit;
      }else{
        $pagenum = ceil($count/$limit);
      }
      $offset = ($page-1)*$limit;
      $datalist = $this->getList($condition, $fields, $limit, $offset, $withLock, $order);
    }
    $datas = array(
      'count' => $count,
      'page' => $page,
      'pagenum' => $pagenum,
      'pagesize' => $limit,
      'datalist' => $datalist
    );
    return $datas;
  }

  public function listCount($condition){
    list($dbName, $tblName) = $this->getDbNameAndTblName();
    $countSql = "select count(*) as count from $dbName.$tblName";
    $params = array();
    if(!empty($condition)) {
      list($where, $params) = $this->getMysql()->makeCondition($condition);
      if(!empty($where)) {
        $countSql .= " where $where";
      }
    }
    $result = $this->fetch($countSql,$params);
    return $result['count'];
  }

  public function getList($condition, $fields=array(), $limit=20, $offset=0, $withLock = false, $order = array()){
    list($dbName, $tblName) = $this->getDbNameAndTblName();
    $sql = "select ";
    if(empty($fields)) {
      $sql .= " * ";
    } else {
      $sql .= implode(',', $fields);
    }
    $sql .= " from $dbName.$tblName";
    $params = array();
    if(!empty($condition)) {
      list($where, $params) = $this->getMysql()->makeCondition($condition);
      if(!empty($where)) {
        $sql .= " where $where";
      }
    }
    if(!empty($order)){
      foreach($order as $sortField => $sort){
        $sql .= " order by $sortField $sort";
      }
    }
    if ($limit > 0) {
      $sql .= " limit $limit";
      if($offset > 0){
        $sql .= " offset $offset";
      }
    } else {
      if($withLock) {
        $sql .= " for update";
      }
    }
    return $this->fetchAll($sql,$params);
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

  public function replaceEntity (array $data, array $pks) {
    $conditions = array();
    foreach ($pks as $pk) {
      if (!isset($data[$pk])) {
        return false;
      }
      $conditions[$pk] = $data[$pk];
    }
    $doUpdate = false;
    if (!empty($conditions)) {
      $row = $this->getEntity($conditions, $pks);
      $doUpdate = !empty($row);
    }

    if ($doUpdate) {
      $updateData = array();
      foreach ($data as $k => $v) {
        if (!isset($conditions[$k])) {
          $updateData[$k] = $v;
        }
      }
      if (empty($updateData)) {
        return false;
      }
      return $this->updateEntity(array_keys($updateData), array_values($updateData), $conditions);
    } else {
      return $this->insertEntity(array_keys($data), array_values($data));
    }
  }

  protected function getDbNameAndTblName() {
    return array('', '');
  }

  protected function getPrimaryKey() {
    return '';
  }

}
