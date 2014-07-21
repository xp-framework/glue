<?php namespace xp\glue\command;

use xp\glue\install\Status;
use xp\glue\Dependency;
use xp\glue\Project;
use xp\glue\Progress;
use xp\glue\src\Source;
use xp\glue\task\Task;
use io\streams\StringWriter;

/**
 * Installation status which prints out to the console 
 */
class InstallationStatus extends \lang\Object implements Status {
  const PW = 10;

  protected $offset;
  protected $progress;
  protected $out;

  /**
   * Creates a new installation status instance, writing to $out
   *
   * @param  io.streams.StringWriter $out
   */
  public function __construct(StringWriter $out) {
    $this->out= $out;
  }

  public function enter(Dependency $dependency) {
    $this->out->writef('%s @ %s: ', $dependency->module(), $dependency->required()->spec());
  }

  public function found(Dependency $dependency, Source $source, Project $project) {
    $this->out->writeLinef('%s %s', $source->compoundName(), $project->version());
  }

  public function start(Dependency $dependency, Task $task) {
    $str= $task->status();
    $w= self::PW + 6;
    $this->out->writef('%s %s%s', str_repeat(' ', $w), $str, str_repeat("\x08", strlen($str) + $w + 1));
    $this->progress= new Progress(self::PW, '#');
    $this->progress->update(0);
  }

  public function report(Dependency $dependency, Task $task, $code) {
    $this->progress->status($code);
  }

  public function progress(Dependency $dependency, Task $task, $percent) {
    $this->progress->update($percent);
  }

  public function stop(Dependency $dependency, Task $task) {
    $this->progress->update(100);
    $this->out->writeLine();
  }

  public function error(Dependency $dependency, $error) {
    $this->out->writeLine(str_replace("\n", "\n  ", $error->reason()));
  }

  public function conflicts($parent, array $conflicts) {
    foreach ($conflicts as $conflict) {
      $this->out->writeLinef(
        'Conflict: %s requires %s @ %s, but %s in use by %s',
        $parent,
        $conflict['module'],
        $conflict['required'],
        $conflict['used'],
        $conflict['by'] ?: 'project'
      );
    }
  }
}
