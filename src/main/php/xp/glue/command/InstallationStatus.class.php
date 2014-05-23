<?php namespace xp\glue\command;

use xp\glue\install\Status;
use xp\glue\Dependency;
use xp\glue\Project;
use xp\glue\Progress;
use xp\glue\src\Source;
use io\streams\StringWriter;

/**
 * Installation status which prints out to the console 
 */
class InstallationStatus extends \lang\Object implements Status {
  const PW = 16;

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
    $l= sprintf(
      '[>>> %s] %s @ %s',
      str_repeat('.', self::PW),
      $dependency->module(),
      $dependency->required()->spec()
    );
    $this->offset= strlen($l);
    $this->out->write($l);
  }

  public function found(Dependency $dependency, Source $source, Project $project) {
    $name= $source->compoundName();
    $this->out->writef(
      ": %s %s%s[\033[44;1;37m200\033[0m ",
      $name,
      $project->version(),
      str_repeat("\x08", $this->offset + strlen($name) + 1 + strlen($project->version())+ 2)
    );
  }

  public function start(Dependency $dependency) {
    $this->progress= new Progress(self::PW, '#');
  }

  public function update(Dependency $dependency, $percent) {
    $this->progress->update($percent);
  }

  public function stop(Dependency $dependency) {
    $this->progress->update(100);
    $this->out->writeLine();
  }

  public function error(Dependency $dependency, $code) {
    $this->out->writeLinef(
      "%s[\033[41;1;37m%s\033[0m ",
      $code,
      str_repeat("\x08", $this->offset)
    );
  }

  public function conflicts($parent, array $conflicts) {
    foreach ($conflicts as $conflict) {
      $this->out->writeLinef(
        '`- Conflict: %s requires %s @ %s, but %s in use by %s',
        $parent,
        $conflict['module'],
        $conflict['required'],
        $conflict['used'],
        $conflict['by'] ?: 'project'
      );
    }
  }
}
