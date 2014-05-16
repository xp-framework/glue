<?php namespace xp\glue;

/**
 * Represents a project's dependency
 *
 * @see  xp://xp.glue.Project
 */
class Dependency extends \lang\Object {

  /**
   * Creates a new project instance
   *
   * @param  string $vendor
   * @param  string $name
   * @param  string $required Version requirement
   */
  public function __construct($vendor, $name, $required) {
    $this->vendor= $vendor;
    $this->name= $name;
    $this->required= $required;
  }

  /** @return string */
  public function vendor() {
    return $this->vendor;
  }

  /** @return string */
  public function name() {
    return $this->name;
  }

  /** @return string */
  public function required() {
    return $this->required;
  }

  /**
   * Creates a string representation
   *
   * @return string
   */
  public function toString() {
    return $this->getClassName().'<'.$this->vendor.'/'.$this->name.'@'.$this->required.'>';
  }
}