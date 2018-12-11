<?php

namespace QKPHP\Web\Dao;

use \QKPHP\Web\QKObject;
use \QKPHP\Web\Dao\Storages\Types;

abstract class BaseDao extends QKObject {

  private $isMaster = false;
  private $options;

  private $masterConf = array();
  private $slaverConf = array();

  private $defaultStorage = null;

  public function __construct ($isMaster=false, array $options=null, $defaultStorage=Types::MYSQL, array $defaultStorageConf=null) {
    if (empty($defaultStorage)) {
      throw new \Exception("default storage type can't be empty.");
    }
    $this->isMaster = $isMaster;
    $defaultStorage = explode('\\', $defaultStorage);
    $defaultStorage = array_pop($defaultStorage);
    $this->defaultStorage = $defaultStorage;
    if (!empty($defaultStorageConf)) {
      $this->setStorageConf($defaultStorageConf, $defaultStorage);
    }
    $this->options = $options;
  }

  public function getApplication () {
    global $_QK_APPLICATION_INS;
    return $_QK_APPLICATION_INS;
  }

  public function checkWritable () {
    if(!$this->isMaster) {
      throw new \Exception("Write to slave error.");
    }
  }

  public function setStorageConf ($conf, $storageType= Types::MYSQL) {
    if (empty($conf) || empty($storageType)) {
      return;
    }
    if (isset($conf['host'])) {
      if ($this->isMaster) {
        $this->masterConf[$storageType] = $conf;
      } else {
        $this->slaverConf[$storageType] = $conf;
      }
    } else {
      if ($this->isMaster && isset($conf['master'])) {
        $this->masterConf[$storageType] = $conf['master'];
      }
      if (!$this->isMaster && isset($conf['slaver'])) {
        $this->slaverConf[$storageType] = $conf['slaver'];
      }
    }
  }

  private function registerDb ($storageType, $classPath) {
    $conf = null;
    if ($this->isMaster) {
      $conf = $this->masterConf[$storageType];
    } else {
      $conf = empty($this->slaverConf[$storageType]) ? $this->masterConf[$storageType]: $this->slaverConf[$storageType];
    }
    if (empty($conf)) {
      throw new \Exception($storageType.' conf for '.($this->isMaster ? 'master' : 'slaver').' is not exist.');
    }
    $fieldName = $storageType.':'.$conf['host'].','.$conf['port'];
    if (isset($conf['user'])) {
      $fieldName .= ',' . $conf['user'];
    }
    $this->registerObject($fieldName, $classPath, $conf, $this->options);
    return $fieldName;
  }

  public function getStorage ($storageType) {
    if (empty ($storageType)) {
      throw new \Exception("storage type can't be empty.");
    }
    $class = $storageType;
    if (strpos($storageType, '\\') === false) {
      $class = '\QKPHP\Web\Dao\Storages\\' . $storageType . '\\' . $storageType;
    }
    $storageType = explode('\\', $class);
    $storageType = array_pop($storageType);
    $fieldName = $this->registerDb($storageType, $class);
    return $this->$fieldName;
  }

  public function getStorageIns ($storageType) {
    return $this->getStorage($storageType)->getIns();
  }

  public function getMysql () {
    return $this->getStorage(Types::MYSQL);
  }

  public function getMysqlIns () {
    return $this->getMysql()->getIns();
  }

  public function getRedis () {
    return $this->getStorage(Types::REDIS);
  }

  public function getRedisIns () {
    return $this->getRedis()->getIns();
  }

  public function getMongo () {
    return $this->getStorage(Types::MONGO);
  }

  public function getMongoIns () {
    return $this->getMongo()->getIns();
  }

  public function begin ($storageType=Types::MYSQL) {
    return $this->getStorage($storageType)->begin();
  }

  public function commit ($storageType=Types::MYSQL) {
    return $this->getStorage($storageType)->commit();
  }

  public function rollBack ($storageType=Types::MYSQL) {
    return $this->getStorage($storageType)->rollBack();
  }

}
