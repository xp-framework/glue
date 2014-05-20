<?php namespace xp\glue\unittest\version;

use xp\glue\version\MavenVersions;
use xp\glue\version\Requirement;
use xp\glue\version\AllOf;
use xp\glue\version\Equals;
use xp\glue\version\Preferred;
use xp\glue\version\CompareUsing;

/**
 * Tests the Maven dependency version ranges parser.
 */
class MavenVersionsTest extends \unittest\TestCase {
  protected $fixture;

  /**
   * Initializes spec
   */
  public function setUp() {
    $this->fixture= new MavenVersions();
  }

  #[@test]
  public function preferred_version() {
    $this->assertEquals(
      new Requirement(new Preferred('1.0.0')),
      $this->fixture->parse('1.0.0')
    );
  }

  #[@test]
  public function exact_version() {
    $this->assertEquals(
      new Requirement(new Equals('1.0.0')),
      $this->fixture->parse('[1.0.0]')
    );
  }

  #[@test]
  public function example_ge_JUnit38_and_lt_JUnit40() {
    $this->assertEquals(
      new Requirement(new AllOf([
        new CompareUsing('>=', '3.8.0'),
        new CompareUsing('<', '4.0.0'),
      ])),
      $this->fixture->parse('[3.8.0,4.0.0)')
    );
  }

  #[@test]
  public function less_than() {
    $this->assertEquals(
      new Requirement(new CompareUsing('<', '3.8.1')),
      $this->fixture->parse('[,3.8.1)')
    );
  }

  #[@test]
  public function less_than_or_equal_to() {
    $this->assertEquals(
      new Requirement(new CompareUsing('<=', '3.8.1')),
      $this->fixture->parse('[,3.8.1]')
    );
  }

  #[@test]
  public function greater_than() {
    $this->assertEquals(
      new Requirement(new CompareUsing('>', '3.8.1')),
      $this->fixture->parse('(3.8.1,]')
    );
  }

  #[@test]
  public function greater_than_or_equal_to() {
    $this->assertEquals(
      new Requirement(new CompareUsing('>=', '3.8.1')),
      $this->fixture->parse('[3.8.1,]')
    );
  }

  #[@test, @expect(class= 'lang.FormatException', withMessage= '/Invalid dependency version/'), @values(
  #  ['']
  #)]
  public function invalid_specifier($spec) {
    $this->fixture->parse($spec);
  }

  #[@test, @expect(class= 'lang.FormatException', withMessage= '/Invalid dependency version range/'), @values(
  #  ['[1.0', '(1.0']
  #)]
  public function invalid_boundaries($spec) {
    $this->fixture->parse($spec);
  }
}