<?php namespace xp\glue\unittest\version;

use xp\glue\version\CompareUsing;

/**
 * Tests the CompareUsing::matches() method
 */
class CompareUsingTest extends \unittest\TestCase {

  /** @return var[][] */
  protected function newerAndOlder() {
    return [
      ['1.2.1', '1.2.0'],
      ['1.3.0', '1.2.0'],
      ['2.0.0', '1.2.0'],
      ['2.0.0', '2.0.0alpha1'],
      ['2.0.0', '2.0.0beta2'],
      ['2.0.0', '2.0.0RC3'],
      ['2.0.0', '2.0.0RC4'],
      ['2.0.0alpha2', '2.0.0alpha1'],
      ['2.0.0beta1', '2.0.0alpha9'],
      ['2.0.0RC1', '2.0.0beta9'],
      ['2.0.0RC3', '2.0.0RC2'],
      ['2.0.0pl1', '2.0.0'],
      ['2.0.0pl2', '2.0.0pl1']
    ];
  }

  #[@test, @values('newerAndOlder')]
  public function less_than($newer, $older) {
    $this->assertTrue((new CompareUsing('<', $newer))->matches($older));
  }

  #[@test, @values('newerAndOlder')]
  public function less_than_or_equal_to($newer, $older) {
    $this->assertTrue((new CompareUsing('<=', $newer))->matches($older));
  }

  #[@test, @values('newerAndOlder')]
  public function less_than_or_equal_to_itself($newer, $older= null) {
    $this->assertTrue((new CompareUsing('<=', $newer))->matches($newer));
  }

  #[@test, @values('newerAndOlder')]
  public function greater_than($newer, $older) {
    $this->assertTrue((new CompareUsing('>', $older))->matches($newer));
  }

  #[@test, @values('newerAndOlder')]
  public function greater_than_or_equal_to($newer, $older) {
    $this->assertTrue((new CompareUsing('>=', $older))->matches($newer));
  }

  #[@test, @values('newerAndOlder')]
  public function greater_than_or_equal_to_itself($newer, $older= null) {
    $this->assertTrue((new CompareUsing('>=', $newer))->matches($newer));
  }
}