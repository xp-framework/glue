<?php namespace xp\glue\unittest\version;

use xp\glue\version\Requirement;
use xp\glue\version\Equals;

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

  #[@test]
  public function equals_other_instance_with_same_spec() {
    $this->assertEquals(new Requirement(new Equals('1.0.0')), new Requirement(new Equals('1.0.0')));
  }

  #[@test]
  public function not_equals_to_other_spec() {
    $this->assertNotEquals(new Requirement(new Equals('1.1.0')), new Requirement(new Equals('1.0.0')));
  }
}