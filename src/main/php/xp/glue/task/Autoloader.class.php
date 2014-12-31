<?php namespace xp\glue\task;

use xp\glue\Dependency;
use io\Folder;
use io\File;
use io\Path;

        // autoload => [
        //   files => ["src/main/php/autoload.php"]
        // ]

        // autoload => [
        //   psr-0 => [
        //     vendor\\package1 => "src/",
        //     vendor\\package2 => "src/"
        //   ]
        // ]

        // autoload => [
        //   psr-4 => [
        //     vendor\\package1 => "src/"
        //   ]
        //   files => ["src/main/php/lang/functions.php", "src/main/php/streams/functions.php"]
        // ]

class Autoloader extends Task {
  protected $spec, $task;

  /**
   * Constructor
   *
   * @param  [:var] $spec
   * @param  xp.glue.task.Task $task
   */
  public function __construct($spec, Task $task) {
    $this->spec= $spec;
    $this->task= $task;
  }

  /**
   * Perform this task and return a URI useable for the class path
   *
   * @param  xp.glue.Dependency $dependency
   * @param  io.Folder $folder
   * @param  var $status
   * @return string[]
   */
  public function perform(Dependency $dependency, Folder $folder, $status) {
    $uri= $this->task->perform($dependency, $folder, $status);

    $paths= [];
    foreach ($this->spec['files'] as $file) {
      $paths[]= (new Path($uri, $file))->normalize()->toString();
    }
    return $paths;
  }

  /**
   * Returns a status to be used in the installation's output
   *
   * @return string
   */
  public function status() {
    return 'Autoloader: '.$this->task->status();
  }
}