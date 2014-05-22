<?php namespace xp\glue\install;

use xp\glue\src\Source;
use xp\glue\Dependency;
use xp\glue\Project;

class NoInstallationStatus extends \lang\Object implements Status {

  public function enter(Dependency $dep) { }

  public function found(Dependency $dep, Source $source, Project $project) { }

  public function error(Dependency $dep, $code) { }

  public function start(Dependency $dep) { }

  public function update(Dependency $dep, $percent) { }

  public function stop(Dependency $dep) { }

  public function conflicts($parent, array $conflicts) { }
}
