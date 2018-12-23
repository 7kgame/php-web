<?php
namespace QKPHP\Web\Dao\Storages;

use QKPHP\Web\Dao\Storages\IStorage;

abstract class Storage implements IStorage {

  protected $host;
  protected $port;
  protected $user;
  protected $passwd;
  protected $dbName;

  protected $ins;

  protected $_db;
  protected $_tbl;
  protected $_pk;

  public function __construct (array $conf, array $options=null) {
    $this->setConfig($conf);
    $this->connect();
    if (!empty($options)) {
      if (isset($options['db']) && isset($options['tbl'])) {
        $this->setDBAndTbl($options['db'], $options['tbl']);
      }
      if (isset($options['pk'])) {
        $this->setPrimaryKey($options['pk']);
      }
    }
  }

  public function setConfig (array $conf) {
    if (empty($conf) || !isset($conf['host']) || !isset($conf['port'])) {
      throw new \Exception('config is not valid: '.print_r($conf));
    }
    $this->host = $conf['host'];
    $this->port = $conf['port'];
    $this->dbName = isset($conf['db']) ? $conf['db'] : '';
    $this->user = isset($conf['user']) ? $conf['user'] : '';
    $this->passwd = isset($conf['passwd']) ? $conf['passwd'] : '';
  }

  public function getIns() {
    return $this->ins;
  }

  public function begin() {}

  public function commit() {}

  public function rollBack() {}

  public function setDBAndTbl($db, $tbl) {
    if ($db && empty($this->dbName)) {
      $this->dbName = $db;
    }
    $this->_db = $db;
    $this->_tbl = $tbl;
  }

  public function setPrimaryKey($primaryKey) {
    $this->_pk = $primaryKey;
  }

  public function __call($method, $args) {
    if (!is_array($args)) {
      $args = array($args);
    }
    $argsLen = count($args);
    switch ($argsLen) {
      case 0:
        return $this->ins->$method();
      case 1:
        return $this->ins->$method($args[0]);
      case 2:
        return $this->ins->$method($args[0], $args[1]);
      case 3:
        return $this->ins->$method($args[0], $args[1], $args[2]);
      case 4:
        return $this->ins->$method($args[0], $args[1], $args[2], $args[3]);
      case 5:
        return $this->ins->$method($args[0], $args[1], $args[2], $args[3], $args[4]);
      default:
        return call_user_func_array(array($this->ins, $method), $args);
    }
  }

}
