<?php
namespace QKPHP\Web\Dao\Plugins\Mysql;

use QKPHP\Web\Dao\Plugins\iPluginDao;

class Mysql implements iPluginDao {

  private $host;
  private $port;
  private $user;
  private $passwd;
  private $dbName;

  private $mysql;

  public function __construct (array $conf) {
    $this->setConfig($conf);
    $this->connect();
  }

  public function setConfig (array $conf) {
    if (empty($conf) || !isset($conf['host']) || !isset($conf['port']) ||
      !isset($conf['user']) || !isset($conf['passwd'])) {
      throw new \Exception('mysql config is not valid: '.print_r($conf));
    }
    $this->host = $conf['host'];
    $this->port = $conf['port'];
    $this->user = $conf['user'];
    $this->passwd = $conf['passwd'];
    $this->dbName = isset($conf['dbName']) ? $conf['dbName'] : '';
  }

  public function connect () {
    $dsn = 'mysql:host=' . $this->host .
           ';port=' . $this->port .
           ';dbname=' . $this->dbName .
           ';charset=utf8';
    $options = array(
      \PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
    );
    $this->mysql = new \PDO($dsn, $this->user, $this->passwd, $options);
    $this->mysql->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
    $this->mysql->setAttribute(\PDO::ATTR_TIMEOUT, 3);
  }

  public function begin () {
    $this->mysql->beginTransaction();
  }

  public function commit () {
    $this->mysql->commit();
  }

  public function rollBack () {
    $this->mysql->rollBack();
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
    return $this->mysql->prepare($sql);
  }

  public function lastInsertId() {
    $id = $this->mysql->lastInsertId();
    if ($id < 1) {
      $id = 0;
    }
    return $id;
  }

}
