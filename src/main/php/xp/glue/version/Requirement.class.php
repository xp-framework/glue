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
  protected $condition;

  /**
   * Creates a new project instance
   *
   * @param  xp.glue.Condition $condition
   */
  public function __construct(Condition $condition) {
    $this->condition= $condition;
  }

  /** @return string */
  public function spec() { return $this->condition->spec(); }

  /** @return bool */
  public function fixed() { return $this->condition->fixed(); }

  /**
   * Compares this requirement against a given version
   *
   * @param  string $version
   * @return bool
   */
  public function matches($version) {
    return $this->condition->matches($version);
  }

  /** @return self */
  public static function equal($version) { return new self(new Equals($version)); }

  /** @return self */
  public static function preferred($version) { return new self(new Preferred($version)); }

  /** @return self */
  public static function exclude($version) { return new self(new Exclude($version)); }

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