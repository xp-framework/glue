<?php namespace xp\glue\unittest\version;

use xp\glue\version\GlueSpec;
use xp\glue\version\Requirement;
use xp\glue\version\AllOf;
use xp\glue\version\Equals;
use xp\glue\version\Exclude;
use xp\glue\version\StartsWith;
use xp\glue\version\CompareUsing;

/**
 * Tests the glue version requirement specification parser.
 */
class GlueSpecTest extends \unittest\TestCase {
  protected $fixture;

  /**
   * Initializes spec
   */
  public function setUp() {
    $this->fixture= new GlueSpec();
  }

  #[@test, @values(['1.0.0', '1.0.0RC1', '1.0.0alpha1', '1.0.0beta2', '1.0.9pl5', 'dev-master', 'dev-feature/branch'])]
  public function exact_version($version) {
    $this->assertEquals(
      new Requirement(new Equals($version)),
      $this->fixture->parse($version)
    );
  }

  #[@test]
  public function exact_version_using_short_notation() {
    $this->assertEquals(
      new Requirement(new Equals('1.0.0')),
      $this->fixture->parse('1.0')
    );
  }

  #[@test]
  public function exclude_this_version() {
    $this->assertEquals(
      new Requirement(new Exclude('1.0.0')),
      $this->fixture->parse('!=1.0.0')
    );
  }

  #[@test]
  public function wildcard_version() {
    $this->assertEquals(
      new Requirement(new StartsWith('1.0.')),
      $this->fixture->parse('1.0.*')
    );
  }

  #[@test]
  public function greater_than_or_equal_to() {
    $this->assertEquals(
      new Requirement(new CompareUsing('>=', '1.0.0')),
      $this->fixture->parse('>=1.0.0')
    );
  }

  #[@test]
  public function greater_than() {
    $this->assertEquals(
      new Requirement(new CompareUsing('>', '1.0.0')),
      $this->fixture->parse('>1.0.0')
    );
  }

  #[@test]
  public function less_than_or_equal_to() {
    $this->assertEquals(
      new Requirement(new CompareUsing('<=', '1.0.0')),
      $this->fixture->parse('<=1.0.0')
    );
  }

  #[@test]
  public function less_than() {
    $this->assertEquals(
      new Requirement(new CompareUsing('<', '1.0.0')),
      $this->fixture->parse('<1.0.0')
    );
  }

  #[@test]
  public function next_significant() {
    $this->assertEquals(
      new Requirement(new AllOf([
        new CompareUsing('>=', '1.0.0'),
        new CompareUsing('<', '1.1.0')
      ])),
      $this->fixture->parse('~1.0.0')
    );
  }

  #[@test]
  public function comma_separated() {
    $this->assertEquals(
      new Requirement(new AllOf([
        new CompareUsing('>=', '1.0.0'),
        new CompareUsing('<', '2.0.0')
      ])),
      $this->fixture->parse('>=1.0.0,<2.0.0')
    );
  }

  #[@test, @expect(class= 'lang.FormatException', withMessage= '/Invalid specifier/'), @values(
  #  ['a.b.c', '', '-1', '1.a.b', '...']
  #)]
  public function invalid_specifier($spec) {
    $this->fixture->parse($spec);
  }

  #[@test, @expect(class= 'lang.FormatException', withMessage= '/Invalid wildcard/'), @values(
  #  ['*', '**', '*.1', '*.*', '*.*.*']
  #)]
  public function invalid_wildcard($spec) {
    $this->fixture->parse($spec);
  }

  #[@test, @expect(class= 'lang.FormatException', withMessage= '/Invalid next significant/'), @values(
  #  ['~', '~~', '~1', '~1.']
  #)]
  public function invalid_next_significant($spec) {
    $this->fixture->parse($spec);
  }
}