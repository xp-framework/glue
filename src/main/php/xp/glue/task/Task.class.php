<?php namespace xp\glue\task;

use io\Folder;
use io\File;
use xp\glue\Dependency;

/**
 * Task base class
 */
abstract class Task extends \lang\Object {

  /**
   * Perform this task and return a URI useable for the class path
   *
   * @param  xp.glue.Dependency $dependency
   * @param  io.Folder $folder
   * @param  var $progress
   * @return string
   */
  abstract public function perform(Dependency $dependency, Folder $folder, callable $progress);
}