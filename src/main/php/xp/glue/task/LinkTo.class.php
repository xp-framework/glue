<?php namespace xp\glue\task;

use io\Folder;
use xp\glue\Dependency;

/**
 * The "LinkTo" task creates a classpath entry pointing to an existing
 * folder on the local hard drive.
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
  public function folder() { return $this->folder; }

  /**
   * Perform this task and return a URI useable for the class path
   *
   * @param  xp.glue.Dependency $dependency
   * @param  io.Folder $folder
   * @param  var $status
   * @return string
   */
  public function perform(Dependency $dependency, Folder $folder, $status) {
    $status->report($dependency, $this, 302);
    return $this->folder()->getURI();
  }

  /**
   * Returns a status to be used in the installation's output
   *
   * @return string
   */
  public function status() {
    return '->'.$this->folder->getURI();
  }
}