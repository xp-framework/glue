<?php namespace xp\glue\task;

use peer\http\HttpConnection;
use io\Folder;
use io\File;
use xp\glue\Dependency;

/**
 * The "Download" task fetches a file from a given stream and stores
 * it locally.
 */
class Download extends Task {
  protected $conn;
  protected $file;
  protected $size;
  protected $sha1;

  /**
   * Constructor
   *
   * @param  peer.http.HttpConnection $conn
   * @param  string $file
   * @param  int $size
   * @param  string $sha1
   */
  public function __construct(HttpConnection $conn, $file, $size, $sha1) {
    $this->conn= $conn;
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
   * @param  var $status
   * @return string
   */
  public function perform(Dependency $dependency, Folder $folder, $status) {
    $folder->exists() || $folder->create(0755);

    $target= new File($folder, $this->file);
    $headers= [];
    if ($target->exists()) {
      $headers['If-Modified-Since']= gmdate('D, d M Y H:i:s \G\M\T', $target->lastModified());
    }
    $response= $this->conn->request('GET', [], $headers);

    if (304 === $response->statusCode()) {
      $status->report($dependency, $this, 304);
    } else {
      $status->report($dependency, $this, $response->statusCode());
      with ($in= $response->getInputStream(), $out= $target->getOutputStream()); {
        $done= 0;
        while ($in->available()) {
          $chunk= $in->read();
          $done+= strlen($chunk);
          $out->write($chunk);
          $status->progress($dependency, $this, $done / $this->size * 100);
        }
        $in->close();
        $out->close();
      }
    }
    return $target->getURI();
  }

  /**
   * Returns a status to be used in the installation's output
   *
   * @return string
   */
  public function status() {
    return '@//'.$this->conn->getURL()->getHost().'/.../'.$this->file;
  }
}