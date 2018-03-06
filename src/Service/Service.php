<?php

namespace QKPHP\Web;

use \QKPHP\Web\QKObject;
use \QKPHP\Web\Dao\Common;

abstract class Service extends QKObject {

  private $daoPackage;

  public function __construct($daoPackage) {
    $this->daoPackage = $daoPackage;
  }

}
