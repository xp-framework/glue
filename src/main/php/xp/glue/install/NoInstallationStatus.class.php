<?php namespace xp\glue\install;

use xp\glue\src\Source;
use xp\glue\task\Task;
use xp\glue\Dependency;
use xp\glue\Project;

class NoInstallationStatus extends \lang\Object implements Status {

  public function enter(Dependency $dependency) { }

  public function found(Dependency $dependency, Source $source, Project $project) { }

  public function error(Dependency $dependency, $code) { }

  public function start(Dependency $dependency, Task $task) { }

  public function report(Dependency $dependency, Task $task, $error) { }

  public function progress(Dependency $dependency, Task $task, $percent) { }

  public function stop(Dependency $dependency, Task $task) { }

  public function conflicts($parent, array $conflicts) { }
}
