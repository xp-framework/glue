<?php namespace xp\glue\unittest;

class TestSource extends \xp\glue\src\Source {
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
  public function fetch(\xp\glue\Dependency $d) {
    return isset($this->results[$d->compoundName()])
      ? $this->results[$d->compoundName()]
      : null
    ;
  }
}