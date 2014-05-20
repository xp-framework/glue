<?php namespace xp\glue\version;

abstract class Condition extends \lang\Object {

  /**
   * Returns whether a given input matches this condition
   *
   * @param  string $input
   * @return bool
   */
  public abstract function matches($input);

  /**
   * Returns a string specification
   *
   * @return string
   */
  public abstract function spec();

  /**
   * Returns whether this condition evaluates to a fixed version
   *
   * @return bool
   */
  public function fixed() {
    return false;
  }

  /**
   * Returns whether another value is equal to this condition 
   *
   * @param  var $cmp
   * @return bool
   */
  public function equals($cmp) {
    return $cmp instanceof self && $cmp->spec() === $this->spec();
  }
}