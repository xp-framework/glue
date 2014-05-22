<?php namespace xp\glue\unittest\install;

use xp\glue\src\Source;
use xp\glue\Dependency;
use xp\glue\Project;

/**
 * Dummy test source used by InstallationTest
 *
 * ## Example usage
 *
 * ```php
 * new TestSource([
 *   'test/test@1.0.0' => [
 *     'depend'  => [new Dependency(...)],
 *     'tasks'   => [new LinkTo(...)]
 *   ]
 * ]);
 * ```
 */
class TestSource extends Source {
  protected $results;

  /**
   * Creates a new source for testing
   *
   * @param  [:var] results
   */
  public function __construct(array $results) {
    foreach ($results as $vmodule => $def) {
      sscanf($vmodule, '%[^/]/%[^@]@%s', $vendor, $name, $version);
      $this->results[$vendor.'/'.$name]= [
        'project' => new Project($vendor, $name, $version, $def['depend']),
        'tasks'   => $def['tasks']
      ];
    }
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