<?php

namespace QKPHP\Web\Cron;

abstract class Task extends \QKPHP\Web\Object {

  abstract public function process($params=array());

}
