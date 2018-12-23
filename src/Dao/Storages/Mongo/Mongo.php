<?php
namespace QKPHP\Web\Dao\Storages\Mongo;

use QKPHP\Web\Dao\Storages\Storage;

class Mongo extends Storage {

  private static $conn;

  public function __construct (array $conf, array $options=null) {
    parent::__construct($conf, $options);
    if (!empty($options)) {
      if (isset($options['db']) && isset($options['tbl'])) {
        $this->setDBAndTbl($options['db'], $options['tbl']);
      }
    }
  }

  public function connect () {
    if (!self::$conn) {
      $uri = 'mongodb://'. $this->host .':' .$this->port;
      $uriOptions = array();
      if (!empty($this->user)) {
        $uriOptions['username'] = $this->user;
        $uriOptions['password'] = $this->passwd;
        $uriOptions['authSource'] = $this->dbName;
      }
      $driverOptions = array();
      self::$conn = new \MongoDB\Client($uri, $uriOptions, $driverOptions);
    }
    $this->ins = self::$conn;
  }

  public function getCollection() {
    $db = $this->_db;
    $collection = $this->_tbl;
    return $this->ins->$db->$collection;
  }

}
