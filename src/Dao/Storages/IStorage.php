<?php
namespace QKPHP\Web\Dao\Storages;

interface IStorage {

  public function setConfig (array $conf);
  public function connect();
  public function getIns();

  public function begin();
  public function commit();
  public function rollBack();

  public function setDBAndTbl($db, $tbl);
  public function setPrimaryKey($primaryKey);

}
