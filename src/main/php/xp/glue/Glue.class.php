<?php namespace xp\glue;

use util\cmd\Console;
use util\Properties;
use lang\reflect\Package;

class Glue extends \lang\Object {

  /**
   * Parse command line
   *
   * @param  string[] $args
   * @return var
   */
  protected static function parse($args) {
    $config= 'glue.ini';
    $method= 'help';
    for ($i= 0; $i < sizeof($args); $i++) {
      if ('-c' === $args[$i]) {
        $config= $args[++$i];
      } else if ('-d' === $args[$i]) {
        chdir($args[++$i]);
      } else {
        $method= $args[$i];
        break;
      }
    }
    return ['config' => $config, 'method' => ucfirst($method)];
  }

  /**
   * Entry point
   *
   * @param  string[] $args
   * @return int
   */
  public static function main(array $args) {
    $parsed= self::parse($args);
    try {
      $command= Package::forName('xp.glue.command')->loadClass($parsed['method'])->newInstance();
      $command->configure(new Properties($parsed['config']));
      $command->execute($args);
      return 0;
    } catch (\lang\Throwable $e) {
      Console::$err->writeLine('*** ', ucfirst($method), ': ', $e);
      return 1;
    }
  }
}