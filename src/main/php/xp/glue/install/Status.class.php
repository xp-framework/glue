<?php namespace xp\glue\install;

use xp\glue\src\Source;
use xp\glue\task\Task;
use xp\glue\Dependency;
use xp\glue\Project;

/**
 * The installation status interface provides a contract for hooks inside
 * the installation process.
 */
interface Status {

  public function enter(Dependency $dependency);

  public function found(Dependency $dependency, Source $source, Project $project);

  public function error(Dependency $dependency, $error);

  public function start(Dependency $dependency, Task $task);

  public function report(Dependency $dependency, Task $task, $code);

  public function progress(Dependency $dependency, Task $task, $percent);

  public function stop(Dependency $dependency, Task $task);

  public function conflicts($parent, array $conflicts);
}
