<?php namespace xp\glue\unittest\version;

use xp\glue\version\GlueSpec;
use xp\glue\version\Requirement;

/**
 * Tests the Requirement::matches() method
 */
class RequirementMatchingTest extends \unittest\TestCase {
  protected $parser;

  /**
   * Initializes parser
   */
  public function setUp() {
    $this->parser= new GlueSpec();
  }

  /** @return xp.glue.version.Requirement */
  protected function newRequirement($spec) {
    return $this->parser->parse($spec);
  }

  #[@test]
  public function exact_version_matched_by() {
    $this->assertTrue(Requirement::equal('1.2.0')->matches('1.2.0'));
  }

  #[@test]
  public function short_exact_version_matched_by() {
    $this->assertTrue($this->newRequirement('1.2')->matches('1.2.0'));
  }

  #[@test, @values(['1.2', '1.2.1', '0.2.0'])]
  public function exact_version_not_matched_by($version) {
    $this->assertFalse(Requirement::equal('1.2.0')->matches($version));
  }

  #[@test]
  public function exclude_version_not_matched_by() {
    $this->assertFalse(Requirement::exclude('1.2.0')->matches('1.2.0'));
  }

  #[@test]
  public function short_exclude_version__not_matched_by() {
    $this->assertFalse($this->newRequirement('!=1.2')->matches('1.2.0'));
  }

  #[@test, @values(['1.1.99', '1.2.1'])]
  public function exclude_version_matched_by($version) {
    $this->assertTrue(Requirement::exclude('1.2.0')->matches($version));
  }

  #[@test, @values(['1.2.0', '1.2.1', '1.2.10'])]
  public function wildcard_matched_by($version) {
    $this->assertTrue($this->newRequirement('1.2.*')->matches($version));
  }

  #[@test, @values(['1.2', '1.1.99', '2.0.0', '1.3.0', '2.2.0', '0.2.0'])]
  public function wildcard_not_matched_by($version) {
    $this->assertFalse($this->newRequirement('1.2.*')->matches($version));
  }

  #[@test, @values(['1.2.0', '1.2.1', '1.2.10', '1.3.0', '1.9.0'])]
  public function next_significant_matched_by($version) {
    $this->assertTrue($this->newRequirement('~1.2')->matches($version));
  }

  #[@test, @values(['1.1.99', '2.0.0'])]
  public function next_significant_not_matched_by($version) {
    $this->assertFalse($this->newRequirement('~1.2')->matches($version));
  }

  #[@test, @values(['1.0.1', '1.1.0', '2.0.0', '99.99.99'])]
  public function greater_than_matched_by($version) {
    $this->assertTrue($this->newRequirement('>1.0')->matches($version));
  }

  #[@test, @values(['0.1.0', '1.0.0', '0.9.99'])]
  public function greater_than_not_matched_by($version) {
    $this->assertFalse($this->newRequirement('>1.0')->matches($version));
  }

  #[@test, @values(['1.0.0', '1.0.1', '1.1.0', '2.0.0', '99.99.99'])]
  public function greater_than_or_equal_to_matched_by($version) {
    $this->assertTrue($this->newRequirement('>=1.0')->matches($version));
  }

  #[@test, @values(['0.1.0', '0.9.99'])]
  public function greater_than_or_equal_to_not_matched_by($version) {
    $this->assertFalse($this->newRequirement('>=1.0')->matches($version));
  }

  #[@test, @values(['0.1.0', '0.9.99'])]
  public function less_than_matched_by($version) {
    $this->assertTrue($this->newRequirement('<1.0')->matches($version));
  }

  #[@test, @values(['1.0.0', '1.0.1', '1.1.0', '2.0.0', '99.99.99'])]
  public function less_than_not_matched_by($version) {
    $this->assertFalse($this->newRequirement('<1.0')->matches($version));
  }

  #[@test, @values(['1.0.0', '0.1.0', '0.9.99'])]
  public function less_than_or_equal_to_matched_by($version) {
    $this->assertTrue($this->newRequirement('<=1.0')->matches($version));
  }

  #[@test, @values(['1.0.1', '1.1.0', '2.0.0', '99.99.99'])]
  public function less_than_or_equal_to_not_matched_by($version) {
    $this->assertFalse($this->newRequirement('<=1.0')->matches($version));
  }
}