<?php namespace xp\glue\command;

use util\Objects;
use util\cmd\Console;
use xp\glue\Dependency;
use xp\glue\version\GlueSpec;
use xp\glue\version\Requirement;

/**
 * Search: Searches for a given package
 */
class SearchCommand extends Command {
  protected static $ANY_VERSION;

  static function __static() {
    self::$ANY_VERSION= new Requirement(newinstance('xp.glue.version.Condition', [], '{
      public function matches($input) { return true; }
      public function spec() { return "*"; }
    }'));
  }

  /**
   * Locate a dependency
   *
   * @param  xp.glue.Dependency $dependency 
   * @return void
   */
  protected function locate($dependency) {
    $found= false;
    foreach ($this->sources as $source) {
      if (null === ($resolved= $source->fetch($dependency))) continue;

      if (!$found) {
        Console::writeLine($dependency, ' found');
        $found= true;
      }
      Console::writeLinef(
        '- %s @ %s',
        $resolved['project']->version(),
        str_replace("\n", "\n  ", Objects::stringOf($source))
      );
    }

    if (!$found) {
      Console::writeLine($dependency, ' not found');
    }
  }

  /**
   * Execute this action
   *
   * @param  string[] $args
   */
  public function execute(array $args) {
    $spec= new GlueSpec();
    foreach ($args as $arg) {
      $version= null;
      if (sscanf($arg, '%[^/]/%[^@]@%s', $vendor, $module, $version) < 2) {
        Console::$err->writeLine('*** Unparseable argument "'.$arg.'"');
        return 127;
      }

      $this->locate(new Dependency(
        $vendor,
        $module,
        $version ? $spec->parse($version) : self::$ANY_VERSION
      ));
    }
    return 0;
  }
}