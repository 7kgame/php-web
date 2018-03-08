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

}
