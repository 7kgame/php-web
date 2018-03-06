<?php

namespace QKPHP\Web\Cron;

use \QKPHP\Web\QKObject;

abstract class Task extends QKObject {

  abstract public function process($params=array());

}
