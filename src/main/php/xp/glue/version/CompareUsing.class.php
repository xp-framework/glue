<?php namespace xp\glue\version;

/**
 * Compares a version using a given operator
 * 
 * @see  php://version_compare
 */
class CompareUsing extends Condition {
  protected $op;
  protected $value;

  /**
   * Creates a new instance
   *
   * @param  string $op One of `>`, `<`, `<=`, `>=`
   * @param  string $value
   */
  public function __construct($op, $value) {
    $this->op= $op;
    $this->value= $value;
  }

  /** @return string */
  public function spec() { return $this->op.$this->value; }

  /**
   * Returns whether a given input matches this condition
   *
   * @param  string $input
   * @return bool
   */
  public function matches($input) {
    return version_compare($input, $this->value, $this->op);
  }
}