<?php namespace xp\glue\unittest\install;

use xp\glue\src\Source;
use xp\glue\Dependency;

/**
 * Dummy test source used by InstallationTest
 */
class TestSource extends Source {
  protected $results;

  /**
   * Creates a new source for testing
   *
   * @param  [:var] results
   */
  public function __construct(array $results) {
    $this->results= $results;
  }

  /**
   * Fetch
   *
   * @param  xp.glue.Dependency $d
   * @return var
   */
  public function fetch(Dependency $d) {
    return isset($this->results[$d->module()]) ? $this->results[$d->module()] : null;
  }
}