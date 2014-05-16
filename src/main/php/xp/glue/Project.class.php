<?php namespace xp\glue;

/**
 * Represents a project
 */
class Project extends \lang\Object {

  /**
   * Creates a new project instance
   *
   * @param  string $vendor
   * @param  string $name
   * @param  string $version
   * @param  xp.glue.Dependency[] $dependencies
   */
  public function __construct($vendor, $name, $version, array $dependencies= array()) {
    $this->vendor= $vendor;
    $this->name= $name;
    $this->version= $version;
    $this->dependencies= $dependencies;
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
  public function version() {
    return $this->version;
  }

  /** @return xp.glue.Dependency[] */
  public function dependencies() {
    return $this->dependencies;
  }

  /**
   * Creates a string representation
   *
   * @return string
   */
  public function toString() {
    $dep= '';
    foreach ($this->dependencies as $dependency) {
      $dep.= '  '.$dependency->toString()."\n";
    }
    return sprintf(
      "%s<%s/%s@%s>[\n%s]",
      $this->getClassName(),
      $this->vendor,
      $this->name,
      $this->version,
      $dep
    );
  }
}