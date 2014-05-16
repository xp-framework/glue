<?php namespace xp\glue\task;

use io\Folder;
use xp\glue\Dependency;

/**
 * Download a file
 */
class LinkTo extends Task {
  protected $folder;

  /**
   * Constructor
   *
   * @param  io.Folder $folder
   */
  public function __construct(Folder $folder) {
    $this->folder= $folder;
  }

  /** @return io.Folder */
  public function folder() {
    return $this->folder;
  }

  /**
   * Perform this task and return a URI useable for the class path
   *
   * @param  xp.glue.Dependency $dependency
   * @param  io.Folder $folder
   * @param  var $progress
   * @return string
   */
  public function perform(Dependency $dependency, Folder $folder, callable $progress) {
    return $this->folder()->getURI();
  }
}