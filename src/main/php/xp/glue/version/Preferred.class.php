<?php namespace xp\glue\version;

/**
 * Indicates a preferred version. Matches any input.
 */
class Preferred extends Condition {
  protected $value;

  /** @param  string $value */
  public function __construct($value) { $this->value= $value; }

  /** @return string */
  public function spec() { return '?'.$this->value; }

  /**
   * Returns whether a given input matches this condition
   *
   * @param  string $input
   * @return bool
   */
  public function matches($input) {
    return true;
  }
}