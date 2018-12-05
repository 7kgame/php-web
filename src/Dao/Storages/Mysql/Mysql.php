<?php
namespace QKPHP\Web\Dao\Storages\Mysql;

use QKPHP\Web\Dao\Storages\Storage;

class Mysql extends Storage {

  public function __construct (array $conf, array $options=null) {
    parent::__construct($conf, $options);
  }

  public function connect () {
    $dsn = 'mysql:host=' . $this->host .
           ';port=' . $this->port .
           ';dbname=' . $this->dbName .
           ';charset=utf8';
    $options = array(
      \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
    );
    $this->ins = new \PDO($dsn, $this->user, $this->passwd, $options);
    $this->ins->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $this->ins->setAttribute(\PDO::ATTR_TIMEOUT, 3);
  }

  public function begin () {
    $this->ins->beginTransaction();
  }

  public function commit () {
    $this->ins->commit();
  }

  public function rollBack () {
    $this->ins->rollBack();
  }

  public function insert ($db, $tbl, array $fields, array $data, $multi=false) {
    if(!$multi) {
      $data = array($data);
    }
    $sqlPlaceHolder = array();
    $values = array();
    $sql = "insert into $db.$tbl (".implode(", ", $fields).") values ";
    foreach($data as $d) {
      $sqlPlaceHolder[] = "(".implode(", ", array_pad(array(), count($fields), '?')).")";
      $d = array_values($d);
      $values = array_merge($values, $d);
    }
    $sql .= implode(", ", $sqlPlaceHolder);
    $sth = $this->prepare($sql, false);
    if(!$sth->execute($values)) {
      return false;
    } else {
      return $sth;
    }
  }

  public function fetch ($sql, array $params=null) {
    $sth = $this->prepare($sql);
    if($params === null) {
      $params = array();
    }
    if(!$sth->execute($params)) {
      return null;
    }
    return $sth->fetch(\PDO::FETCH_ASSOC);
  }

  public function fetchAll ($sql, array $params=null) {
    $sth = $this->prepare($sql);
    if($params === null) {
      $params = array();
    }
    if(!$sth->execute($params)) {
      return null;
    }
    return $sth->fetchAll(\PDO::FETCH_ASSOC);
  }

  public function updateBySql ($sql, array $params=null) {
    $sth = $this->prepare($sql);
    if(!$sth->execute($params)) {
      return false;
    }
    $rowCount = $sth->rowCount();
    return $rowCount > 0;
  }

  public function updateByCondition ($db, $tbl, array $fields, array $params, array $condition) {
    $sql = "update $db.$tbl set ";
    foreach($fields as $field) {
      // eg: 'balance=balance+1'
      if(preg_match('/=/', $field)) {
        $sql .= $field.",";
      } else {
        $sql .= $field."=?,";
      }
    }
    $sql = trim($sql, ",");
    list($where, $data) = $this->makeCondition($condition);
    if(empty($where)) {
      return false;
    } else {
      $sql .= " where ".$where;
    }
    $params = array_merge($params, $data);
    return $this->updateBySql($sql, $params);
  }

  public function deleteBySql ($sql, array $params) {
    return $this->updateBySql($sql, $params);
  }

  public function deleteByCondition ($db, $tbl, array $condition) {
    $sql = "delete from $db.$tbl where ";
    list($where, $data) = $this->makeCondition($condition);
    if(empty($where)) {
      return false;
    } else {
      $sql .= $where;
    }
    return $this->deleteBySql($sql, $data);
  }

  /**
   * support mode:
   *  array(
   *    field      => value,     // field=value
   *    uselessKey => array(sql) // sql: "id in (1,2,3)" or "time >= '2016-08-15'"
   *    uselessKey => array(sql, value) // sql: "field3 >= ?", value3
   *  );
   */
  public function makeCondition (array $condition, $dbAlias="") {
    $where = "";
    $data = array();
    if(empty($condition)) {
      $condition = array();
    }
    if (!empty($dbAlias)) {
      $dbAlias = rtrim($dbAlias, '.').".";
    }
    $conditionSql = array();
    foreach($condition as $k=>$v) {
      if(!is_array($v)) {
        $conditionSql[] = $dbAlias.trim($k)."=?";
        $data[] = $v;
        continue;
      }
      $vcount = count($v);
      if($vcount == 1) {
        $conditionSql[] = $dbAlias.trim($v[0]);
      } else if($vcount == 2){
        $conditionSql[] = $dbAlias.trim($v[0]);
        $data[] = $v[1];
      } else {
        return array('', array());
      }
    }
    if(!empty($conditionSql)) {
      $where = implode(' and ', $conditionSql);
    }
    return array($where, $data);
  }

  public function prepare ($sql, $checkInsert=true) {
    if($checkInsert && preg_match('/^insert\s/i', $sql)) {
      throw new \Exception('use create method instead of');
    }
    return $this->ins->prepare($sql);
  }

  public function lastInsertId() {
    $id = $this->ins->lastInsertId();
    if ($id < 1) {
      $id = 0;
    }
    return $id;
  }

}
