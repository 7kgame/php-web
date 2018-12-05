<?php
namespace QKPHP\Web\Dao\Storages;

class Types {

  const MYSQL = 'Mysql';
  const REDIS = 'Redis';
  const MONGO = 'Mongo';

  public static $all = array(
    self::MYSQL, self::REDIS, self::MONGO
  );

}
