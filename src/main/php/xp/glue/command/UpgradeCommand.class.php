<?php namespace xp\glue\command;

use text\regex\Pattern;
use util\cmd\Console;
use xp\glue\Dependency;
use xp\glue\version\Requirement;

/**
 * Upgrade previously installed libraries.
 */
class UpgradeCommand extends AbstractInstallation {

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
      Console::writeLine('===> No locked versions found, using project dependencies');
      return $project->dependencies();
    }

    // No arguments: Just update all dependencies. Otherwise only selectively,
    // leaving all others locked.
    if (empty($args)) {
      $include= function($module) { return true; };
    } else {
      $pattern= Pattern::compile('^('.implode('|', $args).')');
      $include= function($module) use($pattern) { return $pattern->matches($module); };
    }

    $dependencies= [];
    foreach ($project->dependencies() as $dependency) {
      $module= $dependency->module();

      if (!isset($locked[$module])) {
        $dependencies[]= $dependency;
      } else if ($include($module)) {
        $dependencies[]= $dependency;
        Console::writeLinef(
          'Upgrading %s from %s to %s',
          $module,
          $locked[$module],
          $dependency->required()->spec()
        );
      } else {
        $version= Requirement::equal($locked[$module]);
        Console::writeLinef(
          'Keeping %s @ %s',
          $module,
          $locked[$module]
        );
        $dependencies[]= new Dependency($dependency->vendor(), $dependency->name(), $version);
      }
    }
    return $dependencies;
  }
}