<?php
namespace QKPHP\Web\Request;

use \QKPHP\Common\Utils\Url;
use \QKPHP\Common\Utils\Utils;

class Request {

  private $ip;
  private $params;
  private $files;

  public function init () {
    $this->ip = Url::getClientIp();
    $this->processRequestParams();
    if (!empty($_FILES)) {
      $this->files = $_FILES;
    }
  }

  private function processRequestParams () {
    $params1 = array();
    if (!empty($_GET)) {
      $params1 = Url::processRequestValue($_GET);
    }
    $params2 = array();
    if (!empty($_POST)) {
      $params2 = Url::processRequestValue($_POST);
    }
    $params3 = array();
    $method = strtoupper($_SERVER['REQUEST_METHOD']);
    if ($method == 'POST' || $method == 'PUT' || $method == 'PATCH') {
      if (empty($_POST)) {
        $input = file_get_contents("php://input");
        if (!empty($input)) {
          if (strpos(strtolower($_SERVER['CONTENT_TYPE']), 'application/xml') !== false) {
            $params3 = Url::processRequestValue(Utils::xmlToArr($input));
          } else {
            $params3 = Url::processRequestValue(json_decode($input, true));
          }
        }
      }
    }
    $this->params = array_merge($params1, $params2, $params3);
  }

  public function getIP () {
    return $this->ip;
  }

  public function getFiles () {
    return $this->files;
  }

  public function getAll () {
    return $this->params;
  }

  public function get($key, $default=null, $type=null) {
    if (empty($this->params) || !isset($this->params[$key])) {
      return $default;
    }
    $value = $this->params[$key];
    if ($type != 'int') {
      return $value;
    } else {
      return Url::processRequestValue($value, true);
    }
  }

  public function getInt($key, $default=0) {
    return $this->get($key, $default, 'int');
  }

  public function getStr($key, $default="") {
    return $this->get($key, $default, 'string');
  }

}
