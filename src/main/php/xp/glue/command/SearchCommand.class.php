<?php namespace xp\glue\command;

use util\cmd\Console;
use util\data\Sequence;

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

    $found= Sequence::of($this->sources)
      ->flatten(function($source) use($term) { return $source->find($term); })
      ->distinct()
      ->each(function($module) { Console::writeLine($module); })
    ;

    Console::writeLine();
    if ($found > 0) {
      Console::writeLine($found, ' result(s) for "', $term, '"');
    } else {
      Console::writeLine('Nothing found for search term "', $term, '"');
    }
  }
}