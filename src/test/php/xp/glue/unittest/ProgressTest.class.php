<?php namespace xp\glue\unittest;

use xp\glue\Progress;
use io\streams\StringWriter;

class ProgressTest extends \unittest\TestCase {
  protected $out;

  /**
   * Initializes output stream
   */
  public function setUp() {
    $this->out= newinstance('io.streams.OutputStream', [], [
      'bytes'     => [],
      'pos'       => 0,
      'displayed' => function() { 
        return implode('', $this->bytes);
      },
      'write'     => function($bytes) {
        for ($i= 0, $s= strlen($bytes); $i < $s; $i++) {
          if ("\x08" === $bytes{$i}) {
            $this->pos--;
          } else if ("\033" === $bytes{$i} && '[' === $bytes{$i + 1}) {
            if (false === ($p= strpos($bytes, "m", $i))) {
              throw new \lang\IllegalStateException('Illegal escape sequence');
            } else {
              $i= $p;
            }
          } else {
            $this->bytes[++$this->pos]= $bytes{$i};
          }
        }
      },
      'flush'     => function() { },
      'close'     => function() { }
    ]);
  }

  /**
   * Creates a new fixture using the output stream
   */
  protected function newFixture() {
    return new Progress(10, '#', new StringWriter($this->out));
  }

  #[@test]
  public function can_create() {
    $this->newFixture();
  }

  #[@test]
  public function initial_representation() {
    $fixture= $this->newFixture();
    $this->assertEquals('[>>> ..........]', $this->out->displayed());
  }

  #[@test, @values([[0, '[000 ..........]'], [200, '[200 ..........]']])]
  public function with_status($code, $result) {
    $fixture= $this->newFixture();
    $fixture->status($code);
    $this->assertEquals($result, $this->out->displayed());
  }

  #[@test]
  public function call_before_after_updating() {
    $fixture= $this->newFixture();
    $fixture->status(999);
    $fixture->update(50);
    $this->assertEquals('[999 #####.....]', $this->out->displayed());
  }

  #[@test]
  public function call_status_after_updating() {
    $fixture= $this->newFixture();
    $fixture->update(50);
    $fixture->status(999);
    $this->assertEquals('[999 #####.....]', $this->out->displayed());
  }

  #[@test, @values([0, 10, 20, 30, 40, 50, 60, 70, 80, 90, 100])]
  public function update_to_given_percentage($p) {
    $fixture= $this->newFixture();
    $fixture->update($p);
    $this->assertEquals('[>>> '.str_repeat('#', $p / 10).str_repeat('.', (100 - $p) / 10).']', $this->out->displayed());
  }

  #[@test]
  public function update_called_multiple_times_each_with_incrementing_value() {
    $fixture= $this->newFixture();
    for ($p= 0; $p <= 100; $p+= 10) {
      $fixture->update($p);
      $this->assertEquals('[>>> '.str_repeat('#', $p / 10).str_repeat('.', (100 - $p) / 10).']', $this->out->displayed(), $p.'%');
    }
  }

  #[@test]
  public function update_called_multiple_times_each_with_decrementing_value() {
    $fixture= $this->newFixture();
    for ($p= 100; $p >= 0; $p-= 10) {
      $fixture->update($p);
      $this->assertEquals('[>>> '.str_repeat('#', $p / 10).str_repeat('.', (100 - $p) / 10).']', $this->out->displayed(), $p.'%');
    }
  }
}