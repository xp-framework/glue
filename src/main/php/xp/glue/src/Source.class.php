<?php namespace xp\glue\src;

/**
 * Source base class
 */
abstract class Source extends \lang\Object {
  protected $name;

  /**
   * Creates a new source instance with a given name
   *
   * @param  string $name
   */
  public function __construct($name) {
    $this->name= $name;
  }

  /** @return string */
  public function name() { return $this->name; }


  /**
   * Returns this source's compound name, made up of the simple class
   * name of the respective implementation and the name in square
   * brackets, e.g., `Checkout[local]` or `Artifactory[example]`.
   *
   * @return  string
   */
  public function compoundName() {
    return $this->getClass()->getSimpleName().'['.$this->name.']';
  }

  /**
   * Fetches the given dependency. Returns NULL if the dependency cannot be found.
   *
   * @param  xp.glue.Dependency $dependency
   * @param  [:var] $result
   */
  abstract public function fetch(\xp\glue\Dependency $dependency);
}