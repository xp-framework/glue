<?php namespace xp\glue\version;

/**
 * Represents a version requirement
 *
 * @see  https://getcomposer.org/doc/01-basic-usage.md#package-versions
 * @see  xp://xp.glue.Dependency
 * @see  php://version_compare
 * @test xp://xp.glue.unittest.version.RequirementTest
 * @test xp://xp.glue.unittest.version.RequirementMatchingTest
 */
class Requirement extends \lang\Object {
  protected $spec;
  protected $compare;
  protected $condition;

  /**
   * Creates a new project instance
   *
   * @param  xp.glue.Condition $condition
   * @param  bool $fixed
   */
  public function __construct(Condition $condition, $fixed= false) {
    $this->fixed= $fixed;
    $this->condition= $condition;
  }

  /** @return string */
  public function spec() { return $this->condition->spec(); }

  /** @return xp.glue.Condition */
  public function condition() { return $this->condition; }

  /** @return bool */
  public function fixed() { return $this->fixed; }

  /**
   * Compares this requirement against a given version
   *
   * @param  string $version
   * @return bool
   */
  public function matches($version) {
    return $this->condition->matches($version);
  }

  /**
   * Creates a string representation
   *
   * @return string
   */
  public function toString() {
    return $this->getClassName().'('.$this->condition->spec().')';
  }

  /**
   * Returns whether another requirement is equal to this requirement
   *
   * @return bool
   */
  public function equals($cmp) {
    return $cmp instanceof self && $this->condition->equals($cmp->condition);
  }
}