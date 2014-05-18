<?php namespace xp\glue\task;

use io\Folder;
use xp\glue\Dependency;

/**
 * Task base class
 *
 * @see  xp://xp.glue.task.LinkTo Links to existing installations
 * @see  xp://xp.glue.task.Download Downloads dependencies
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