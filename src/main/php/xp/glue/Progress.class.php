<?php namespace xp\glue;

use util\cmd\Console;
use io\streams\StringWriter;

class Progress extends \lang\Object {
  protected $width;
  protected $char;
  protected $current;
  protected $out;

  /**
   * Create a new progress bar
   *
   * @param  int $width
   * @param  string $char
   * @param  io.streams.StringWriter $out if omitted, uses Console::$out
   */
  public function __construct($width= 10, $char= '#', StringWriter $out= null) {
    $this->width= $width;
    $this->char= $char;
    $this->current= 0;
    $this->out= $out ?: Console::$out;
  }

  /**
   * Update progress bar
   *
   * @param  double $percent A number between 0 and 100
   */
  public function update($percent) {
    $chars= ceil((min(max($percent, 0), 100) / 100) * $this->width);
    if ($chars > $this->current) {
      $this->out->write(str_repeat($this->char, $chars - $this->current));
      $this->current= $chars;
    }
  }
}