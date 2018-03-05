<?php

namespace QKPHP\Web;

abstract class Service {

  private $daoPackage;
  private $dao = array();

  private $objectContainer = array();

  public function __construct($daoPackage) {
    $this->daoPackage = $daoPackage;
  }

  protected function getDao($isMaster=false, $package=null) {
    if (empty($package)) {
      $package = $this->daoPackage;
    }
    if (!isset($this->dao[$package])) {
      $this->dao[$package] = array();
    }
    $key = "k0";
    if($isMaster) {
      $key = "k1";
    } else {
      if(isset($this->dao[$package]['k1'])) {
        $key = 'k1';
      }
    }
    if(!isset($this->dao[$package][$key])) {
      $this->dao[$package][$key] = new $package($isMaster);
    }
    return $this->dao[$package][$key];
  }

  protected function getOtherDao($package, $isMaster=false) {
    return $this->getDao($isMaster, $package);
  }

  public function ping($isMaster=false) {
    $this->getDao($isMaster)->mysqlPing();
  }

  public function beginGlobal() {
    $this->getDao(true)->beginGlobal();
  }

  public function commitGlobal() {
    $this->getDao(true)->commitGlobal();
  }

  public function rollBackGlobal() {
    $this->getDao(true)->rollBackGlobal();
  }

  protected function registerObject($fieldName, $package) {
    $this->objectContainer[$fieldName] = $package;
  }

  public function __get($fieldName) {
    if(isset($this->$fieldName)) {
      return $this->$fieldName;
    }
    if(!isset($this->objectContainer[$fieldName]) || empty($this->objectContainer[$fieldName])) {
      return null;
    }

    $type = gettype($this->objectContainer[$fieldName]);
    if($type == "object") {
      return $this->objectContainer[$fieldName];
    } else if($type == "string") {
      $class = $this->objectContainer[$fieldName];
      $this->objectContainer[$fieldName] = new $class;
      return $this->objectContainer[$fieldName];
    } else {
      return null;
    }
  }

  public function insertEntity(array $fields, array $data, $multi=false, $getId=true) {
    return $this->getDao(true)->insertEntity($fields, $data, $multi, $getId);
  }

  public function updateEntity(array $fields, array $params, array $conditions) {
    return $this->getDao(true)->updateEntity($fields, $params, $conditions);
  }

  public function set($id, array $fields, array $params) {
    return $this->getDao(true)->set($id, $fields, $params);
  }

  public function get($id, array $fields=null, $withLock=false) {
    return $this->getDao($withLock)->get($id, $fields, $withLock);
  }

  public function getEntity(array $conditions, array $fields=null, $isMaster=false) {
    return $this->getDao($isMaster)->getEntity($conditions, $fields);
  }

  public function getEntities(array $conditions, array $fields=null) {
    return $this->getDao()->getEntities($conditions, $fields);
  }

  public function getEntityWithLock(array $conditions) {
    return $this->getDao(true)->getEntity($conditions, null, true);
  }

  public function getEntitiesWithLock(array $conditions, array $fields=null) {
    return $this->getDao(true)->getEntities($conditions, $fields, false, true);
  }

  public function search(array $conditions, $page, $pageSize, $orderBy="", array $field=null) {
    return $this->getDao()->entityLists($conditions, $page, $pageSize, $orderBy, $field);
  }

}
