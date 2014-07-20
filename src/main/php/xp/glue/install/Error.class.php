<?php namespace xp\glue\install;

abstract class Error extends \lang\Object {
  
  /**
   * Returns a status to be used in the installation's output
   *
   * @return string
   */
  public abstract function reason();

  /**
   * Creates a string representation
   *
   * @return string
   */
  public function toString() {
    return $this->getClassName().'('.$this->status().')';
  }
}