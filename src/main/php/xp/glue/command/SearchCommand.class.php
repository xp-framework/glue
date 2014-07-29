<?php namespace xp\glue\command;

use util\Objects;
use util\cmd\Console;

/**
 * Search: Searches for a given package
 */
class SearchCommand extends Command {

  /**
   * Execute this action
   *
   * @param  string[] $args
   */
  public function execute(array $args) {
    $term= implode(' ', $args);
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
}