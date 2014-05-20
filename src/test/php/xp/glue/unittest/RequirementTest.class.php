<?php namespace xp\glue\unittest;

use xp\glue\Requirement;

/**
 * Tests the Requirement classes' basic functionality
 */
class RequirementTest extends \unittest\TestCase {

  /** @return string[] */
  protected function validSpecs() {
    return ['1.0.0', '1.0', '1.0.*', '!=1.0.0', '~1.0.0', '>=1.0.0', '<=1.0.0', '>1.0', '<1.0'];
  }

  #[@test, @values('validSpecs')]
  public function can_create($spec) {
    new Requirement($spec);
  }

  #[@test, @expect(class= 'lang.FormatException', withMessage= '/Invalid specifier/'), @values(
  #  ['a.b.c', '', '-1', '1.a.b', '...']
  #)]
  public function invalid_specifier($spec) {
    new Requirement($spec);
  }

  #[@test, @expect(class= 'lang.FormatException', withMessage= '/Invalid wildcard/'), @values(
  #  ['*', '**', '*.1', '*.*', '*.*.*']
  #)]
  public function invalid_wildcard($spec) {
    new Requirement($spec);
  }

  #[@test, @expect(class= 'lang.FormatException', withMessage= '/Invalid next significant/'), @values(
  #  ['~', '~~', '~1', '~1.']
  #)]
  public function invalid_next_significant($spec) {
    new Requirement($spec);
  }

  #[@test, @values('validSpecs')]
  public function spec_accessor_returns_spec_given_to_constructor($spec) {
    $this->assertEquals($spec, (new Requirement($spec))->spec());
  }


  #[@test, @values('validSpecs')]
  public function equals_other_instance_with_same_spec($spec) {
    $this->assertEquals(new Requirement($spec), new Requirement($spec));
  }

  #[@test, @values('validSpecs')]
  public function not_equals_to_other_spec($spec) {
    $this->assertNotEquals(new Requirement('6.10.0'), new Requirement($spec));
  }
}