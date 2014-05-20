<?php namespace xp\glue\version;

class AllOf extends Condition {
  protected $conditions;

  /**
   * Creates a new instance
   *
   * @param  xp.glue.Condition[] $conditions
   */
  public function __construct(array $conditions) {
    $this->conditions= $conditions;
  }

  /**
   * Creates a string representation
   *
   * @return string
   */
  public function spec() {
    $s= '';
    foreach ($this->conditions as $condition) {
      $s.= ','.$condition->spec();
    }
    return (string)substr($s, 1);
  }

  /**
   * Returns whether this condition evaluates to a fixed version
   *
   * @return bool
   */
  public function fixed() {
    foreach ($this->conditions as $condition) {
      if (!$condition->fixed()) return false;
    }
    return true;
  }

  /**
   * Returns whether a given input matches this condition
   *
   * @param  string $input
   * @return bool
   */
  public function matches($input) {
    foreach ($this->conditions as $condition) {
      if (!$condition->matches($input)) return false;
    }
    return true;
  }
}