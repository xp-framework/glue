<?php namespace xp\glue\command;

use webservices\json\JsonFactory;
use util\Properties;
use lang\reflect\Package;

/**
 * Abstract base class
 */
abstract class Command extends \lang\Object {
  protected static $json;
  protected $conf;
  protected $sources= [];

  static function __static() {
    self::$json= JsonFactory::create();
  }

  /**
   * Configure this command
   *
   * @param  util.Properties $conf
   * @return void
   */
  public function configure(Properties $conf) {
    $this->conf= $conf;
    $this->sources= [];
    foreach ($this->conf->readSection('sources') as $name => $url) {
      sscanf($name, '%[^@]@%s', $impl, $spec);
      $class= implode('', array_map('ucfirst', explode('-', $impl)));
      $this->sources[]= Package::forName('xp.glue.src')->loadClass($class)->newInstance($spec, $url);
    }
  }

  abstract public function execute(array $args);
}