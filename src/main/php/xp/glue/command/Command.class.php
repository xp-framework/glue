<?php namespace xp\glue\command;

use webservices\json\JsonFactory;
use util\Properties;

/**
 * Abstract base class
 */
abstract class Command extends \lang\Object {
  protected static $json;
  protected $conf;

  static function __static() {
    self::$json= JsonFactory::create();
  }

  public function configure(Properties $conf) {
    $this->conf= $conf;
  }

  abstract public function execute(array $args);
}