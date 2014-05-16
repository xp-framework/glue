<?php namespace xp\glue;

use io\streams\InputStream;

/**
 * Source base class
 */
class Download extends \lang\Object {

  public function __construct(InputStream $stream, $file, $size, $sha1) {
    $this->stream= $stream;
    $this->file= $file;
    $this->size= $size;
    $this->sha1= $sha1;
  }

  public function stream() {
    return $this->stream;
  }

  public function file() {
    return $this->file;
  }

  public function size() {
    return $this->size;
  }

  public function sha1() {
    return $this->sha1;
  }
}