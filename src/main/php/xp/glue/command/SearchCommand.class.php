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
   * @param  string $term
   * @return void
   */
  protected function search($term) {
    $found= 0;
    foreach ($this->sources as $source) {
      foreach ($source->find($term)->counting($found) as $project) {
        Console::writeLinef(
          '* %s/%s@%s @ %s',
          $project->vendor(),
          $project->name(),
          $project->version(),
          str_replace("\n", "\n  ", Objects::stringOf($source))
        );
      }
    }

    Console::writeLine();
    if ($found > 0) {
      Console::writeLine($found, ' result(s) for "', $term, '"');
    } else {
      Console::writeLine('Nothing found for search term "', $term, '"');
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
      $this->search($arg);
    }
    return 0;
  }
}