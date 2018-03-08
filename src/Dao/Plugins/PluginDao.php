<?php
namespace QKPHP\Web\Dao\Plugins;

interface iPluginDao {

  public function setConfig (array $conf);
  public function connect();

}
