<?php namespace xp\glue;

use xp\glue\src\Source;

class NoInstallationStatus extends \lang\Object implements InstallationStatus {

  public function enter(Dependency $dep) { }

  public function found(Dependency $dep, Source $source, Project $project) { }

  public function error(Dependency $dep, $code) { }

  public function start(Dependency $dep) { }

  public function update(Dependency $dep, $percent) { }

  public function stop(Dependency $dep) { }

  public function conflicts($parent, array $conflicts) { }
}
