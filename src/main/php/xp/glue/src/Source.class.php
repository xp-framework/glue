<?php namespace xp\glue\src;

/**
 * Source base class
 */
abstract class Source extends \lang\Object {

  /**
   * Fetches the given dependency. Returns NULL if the dependency cannot be found.
   *
   * @param  xp.glue.Dependency $dependency
   * @param  [:var] $result
   */
  abstract public function fetch(\xp\glue\Dependency $dependency);
}