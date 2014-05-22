<?php namespace xp\glue;

use xp\glue\version\Requirement;

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
   * @param  xp.glue.version.Requirement $required Version requirement
   */
  public function __construct($vendor, $name, Requirement $required) {
    $this->vendor= $vendor;
    $this->name= $name;
    $this->required= $required;
  }

  /** @return string */
  public function vendor() { return $this->vendor; }

  /** @return string */
  public function name() { return $this->name; }

  /** @return xp.glue.version.Requirement */
  public function required() { return $this->required; }

  /**
   * Returns this dependency's vendor and name concatenated together with
   * a forward slash, e.g. `xp-framework/core` or `thekid/dialog`.
   *
   * @return  string
   */
  public function module() {
    return $this->vendor.'/'.$this->name;
  }

  /**
   * Creates a string representation
   *
   * @return string
   */
  public function toString() {
    return $this->getClassName().'<'.$this->vendor.'/'.$this->name.'@'.$this->required->spec().'>';
  }
}