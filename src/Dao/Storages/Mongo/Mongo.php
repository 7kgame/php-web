<?php
namespace QKPHP\Web\Dao\Storages\Mongo;

use QKPHP\Web\Dao\Storages\Storage;

class Mongo extends Storage {

  public function __construct (array $conf, array $options=null) {
    parent::__construct($conf, $options);
    if (!empty($options)) {
      if (isset($options['db']) && isset($options['collection'])) {
        $this->setDBAndTbl($options['db'], $options['collection']);
      }
    }
  }

  public function connect () {
    $uri = 'mongodb://'. $this->host .':' .$this->port;
    $uriOptions = array();
    if (!empty($this->user)) {
      $uriOptions['username'] = $this->user;
      $uriOptions['password'] = $this->passwd;
      $uriOptions['authSource'] = $this->dbName;
    }
    $driverOptions = array();
    $this->ins = new \MongoDB\Client($uri, $uriOptions, $driverOptions);
  }

  public function getCollection() {
    $db = $this->_db;
    $collection = $this->_tbl;
    return $this->ins->$db->$collection;
  }

}
