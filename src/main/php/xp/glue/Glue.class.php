<?php namespace xp\glue;

use util\cmd\Console;
use util\Properties;
use io\File;
use lang\reflect\Package;

class Glue extends \lang\Object {

  static function __static() {
    \lang\XPClass::forName('lang.ResourceProvider');
  }

  public static function main(array $args) {
    $method= ucfirst(array_shift($args));
    try {
      $command= Package::forName('xp.glue.command')->loadClass($method)->newInstance();
      $command->configure(Properties::fromFile(new File('res://glue.ini')));
      $command->execute($args);
      return 0;
    } catch (\lang\Throwable $e) {
      Console::$err->writeLine('*** ', ucfirst($method), ': ', $e);
      return 1;
    }
  }
}