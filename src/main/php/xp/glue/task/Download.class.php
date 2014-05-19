<?php namespace xp\glue\task;

use io\streams\InputStream;
use io\Folder;
use io\File;
use xp\glue\Dependency;

/**
 * The "Download" task fetches a file from a given stream and stores
 * it locally.
 */
class Download extends Task {
  protected $stream;
  protected $file;
  protected $size;
  protected $sha1;

  /**
   * Constructor
   *
   * @param  io.streams.InputStream $stream
   * @param  string $file
   * @param  int $size
   * @param  string $sha1
   */
  public function __construct(InputStream $stream, $file, $size, $sha1) {
    $this->stream= $stream;
    $this->file= $file;
    $this->size= $size;
    $this->sha1= $sha1;
  }

  /** @return io.streams.InputStream */
  public function stream() { return $this->stream; }

  /** @return string */
  public function file() { return $this->file; }

  /** @return int */
  public function size() { return $this->size; }

  /** @return string */
  public function sha1() { return $this->sha1; }

  /**
   * Perform this task and return a URI useable for the class path
   *
   * @param  xp.glue.Dependency $dependency
   * @param  io.Folder $folder
   * @param  var $progress
   * @return string
   */
  public function perform(Dependency $dependency, Folder $folder, callable $progress) {
    $folder->exists() || $folder->create(0755);

    $target= new File($folder, $this->file);
    with ($out= $target->getOutputStream()); {
      $done= 0;
      while ($this->stream->available()) {
        $chunk= $this->stream->read();
        $done+= strlen($chunk);
        $out->write($chunk);
        $progress($done / $this->size * 100);
      }
    
      $this->stream->close();
      $out->close();
    }
    return $target->getURI();
  }

  /**
   * Creates a string representation
   *
   * @return string
   */
  public function toString() {
    return $this->getClassName().'<'.$this->file.' ('.$this->sha1.')>';
  }
}