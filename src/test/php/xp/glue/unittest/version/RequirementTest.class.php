<?php namespace xp\glue\unittest\version;

use xp\glue\version\Requirement;
use xp\glue\version\AllOf;
use xp\glue\version\Equals;
use xp\glue\version\Preferred;
use xp\glue\version\Exclude;

/**
 * Tests the Requirement class' basic functionality
 */
class RequirementTest extends \unittest\TestCase {

  #[@test]
  public function can_create() {
    new Requirement(new Equals('1.0.0'));
  }

  #[@test]
  public function spec_accessor_returns_spec_given_to_constructor() {
    $this->assertEquals('1.0.0', (new Requirement(new Equals('1.0.0')))->spec());
  }

  #[@test, @values([
  #  new Equals('1.0.0'),
  #  new Preferred('1.0.0')
  #])]
  public function fixed_accessor_returns_version_for($condition) {
    $this->assertEquals('1.0.0', (new Requirement($condition))->fixed());
  }

  #[@test, @values([
  #  new Exclude('1.0.0'),
  #  new AllOf([new Preferred('1.2.0'), new Exclude('1.3.0')])
  #])]
  public function fixed_accessor_returns_null_for($condition) {
    $this->assertNull((new Requirement($condition))->fixed());
  }

  #[@test]
  public function equals_other_instance_with_same_spec() {
    $this->assertEquals(new Requirement(new Equals('1.0.0')), new Requirement(new Equals('1.0.0')));
  }

  #[@test]
  public function not_equals_to_other_spec() {
    $this->assertNotEquals(new Requirement(new Equals('1.1.0')), new Requirement(new Equals('1.0.0')));
  }
}