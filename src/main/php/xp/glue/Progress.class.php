<?php namespace xp\glue;

use util\cmd\Console;
use io\streams\StringWriter;

class Progress extends \lang\Object {
  const STATUS_WIDTH= 5;

  protected $width;
  protected $char;
  protected $current;
  protected $cursor;
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

    $this->out->write('[>>> ', str_repeat('.', $this->width), ']');
    $this->cursor= $this->width + self::STATUS_WIDTH + 1;
  }

  public function status($code) {
    $this->out->writef("%s[\033[43;1;37m%03d\033[0m ", str_repeat("\x08", $this->cursor), $code);
    $this->cursor= self::STATUS_WIDTH;
  }

  /**
   * Update progress bar
   *
   * @param  double $percent A number between 0 and 100
   */
  public function update($percent) {
    $chars= ceil((min(max($percent, 0), 100) / 100) * $this->width);

    // TODO: Optimize
    $this->out->write(
      str_repeat("\x08", $this->cursor - self::STATUS_WIDTH).
      str_repeat($this->char, $chars).
      str_repeat('.', $this->width - $chars)
    );

    $this->cursor= $this->width + self::STATUS_WIDTH;
  }
}