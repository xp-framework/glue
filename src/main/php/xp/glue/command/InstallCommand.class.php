<?php namespace xp\glue\command;

use xp\glue\Dependency;
use xp\glue\version\Requirement;

/**
 * Install: Resolves dependencies, downloading and linking as necessary.
 */
class InstallCommand extends AbstractInstallation {

  /**
   * Gets dependencies to be processed
   *
   * @param  xp.glue.Project $project The project parsed from glue.json
   * @param  [:string] $locked
   * @param  string[] $args
   * @return xp.glue.Dependency[]
   */
  protected function dependenciesFor($project, $locked, $args) {
    if (null === $locked) {
      return $project;
    } else {
      $dependencies= [];
      foreach ($project->dependencies() as $dep) {
        $module= $dep->module();
        if (isset($locked[$module])) {
          $dependencies[]= new Dependency($dep->vendor(), $dep->name(), Requirement::equal($locked[$module]));
        } else {
          $dependencies[]= $dep;
        }
      }
      return $dependencies;
    }
  }
}