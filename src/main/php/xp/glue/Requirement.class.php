<?php namespace xp\glue;

/**
 * Represents a version requirement
 *
 * @see  https://getcomposer.org/doc/01-basic-usage.md#package-versions
 * @see  xp://xp.glue.Dependency
 */
class Requirement extends \lang\Object {

  /**
   * Creates a new project instance
   *
   * @param  string $spec
   */
  public function __construct($spec) {
    $this->spec= $spec;
  }

  /** @return string */
  public function spec() {
    return $this->spec;
  }

  /**
   * Creates a string representation
   *
   * @return string
   */
  public function toString() {
    return $this->getClassName().'<'.$this->spec.'>';
  }
}