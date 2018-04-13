<?php

namespace QKPHP\Web\Service;

use \QKPHP\Web\QKObject;
use \QKPHP\Web\Dao\Common;

abstract class Service extends QKObject {

  private $daoPackage;
  private $masterDaoFieldName = 'mdao';
  private $slaverDaoFieldName = 'sdao';
  private $masterDaoUsed = false;

  public function __construct($daoPackage=null) {
    $this->setDaoPackage($daoPackage);
  }

  public function setDaoPackage ($daoPackage) {
    $this->daoPackage = $daoPackage;
    if (!empty($daoPackage)) {
      $this->registerObject($this->masterDaoFieldName, $daoPackage, true);
      $this->registerObject($this->slaverDaoFieldName, $daoPackage, false);
    }
  }

  public function getDao($isMaster=false, $force2UseSlaver=false) {
    $fieldName = $this->masterDaoFieldName;
    if (!$isMaster && (!$this->masterDaoUsed || $force2UseSlaver)) {
      $fieldName = $this->slaverDaoFieldName;
    }
    $dao = $this->$fieldName;
    if ($isMaster) {
      $this->masterDaoUsed = true;
    }
    return $dao;
  }

  private $otherDaoMasterUsed = array();

  public function registerOtherDao ($fieldName, $daoPackage) {
    if (empty($fieldName) || empty($daoPackage)) {
      return;
    }
    if (!isset($this->otherDaoMasterUsed[$fieldName])) {
      $this->otherDaoMasterUsed[$fieldName] = false;
      $this->registerObject("other.mdao.$fieldName", $daoPackage, true);
      $this->registerObject("other.sdao.$fieldName", $daoPackage, true);
    }
  }

  public function getOtherDao ($fieldName, $isMaster=false, $force2UseSlaver=false) {
    $fieldName0 = "other.mdao.$fieldName";
    if (!$isMaster && (!$this->otherDaoMasterUsed[$fieldName] || $force2UseSlaver)) {
      $fieldName0 = "other.sdao.$fieldName";
    }
    $dao = $this->$fieldName0;
    if ($isMaster) {
      $this->otherDaoMasterUsed[$fieldName] = true;
    }
    return $dao;
  }

  private function _getDao ($fieldName, $isMaster=false) {
    if (empty($fieldName)) {
      return $this->getDao($isMaster);
    } else {
      return $this->getOtherDao($fieldName, $isMaster);
    }
  }

  public function insertEntity(array $fields, array $data, $multi=false, $getId=true, $fieldName=null) {
    return $this->_getDao($fieldName, true)->insertEntity($fields, $data, $multi, $getId);
  }

  public function set ($id, $fields, $params, $fieldName=null) {
    if (!is_array($fields)) {
      $fields = array($fields);
      $params = array($params);
    }
    return $this->_getDao($fieldName, true)->set($id, $fields, $params);
  }

  public function get ($id, $fields=null, $withLock=false, $fieldName=null) {
    if (!empty($fields) && !is_array($fields)) {
      $fields = array($fields);
    }
    return $this->_getDao($fieldName)->get($id, $fields, $withLock);
  }

  public function getEntity (array $condition, array $fields=null, $withLock=false, $fieldName=null) {
    return $this->_getDao($fieldName)->getEntity($condition, $fields, $withLock);
  }

  public function getEntities (array $condition, array $fields=null, $limit=-1, $withLock=false, $fieldName=null) {
    return $this->_getDao($fieldName)->getEntities($condition, $fields, $limit, $withLock);
  }

  public function pagination (array $condition, array $fields=null, $page=1, $limit=20, $withLock=false, array $order=array(), $fieldName=null) {
    return $this->_getDao($fieldName)->pagination($condition, $fields, $page, $limit, $withLock, $order);
  }
  
  public function getList (array $condition, array $fields=null, $limit=20, $offset=0, $withLock=false, array $order=array(), $fieldName=null) {
    return $this->_getDao($fieldName)->getList($condition, $fields, $limit, $offset, $withLock, $order);
  }

  public function updateEntity (array $fields, array $params, array $condition, $fieldName=null) {
    return $this->_getDao($fieldName, true)->updateEntity($fields, $params, $condition);
  }

  public function deleteEntity (array $condition, $fieldName=null) {
    return $this->_getDao($fieldName, true)->deleteEntity($condition);
  }

}
