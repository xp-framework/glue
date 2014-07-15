<?php namespace xp\glue\install;

use util\Objects;

class NotFound extends \lang\Object {
  protected $searched;

  public function __construct($searched) {
    $this->searched= $searched;
  }
  
  public function reason() {
    return 'Not found in any of '.Objects::stringOf($this->searched);
  }
}