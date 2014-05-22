<?php namespace xp\glue;

use io\Folder;
use util\cmd\Console;

/**
 * Installs dependencies from a given list of sources.
 *
 * @test  xp://xp.glue.unittest.InstallationTest
 */
class Installation extends \lang\Object {
  protected $installed;
  protected $sources;
  protected $dependencies;

  /**
   * Creates a new installation
   *
   * @param  xp.glue.src.Source[] $sources
   * @param  xp.glue.Dependency[] $dependencies
   */
  public function __construct($sources, $dependencies) {
    $this->installed= [];
    $this->sources= $sources;
    $this->dependencies= [];
    foreach ($dependencies as $dependency) {
      $this->dependencies[$dependency->module()]= $dependency;
    }
  }

  /**
   * Register transitive (that is: the dependency's) dependencies. In
   * case we're already using a given module and the transitive dependency's
   * requirement doesn't match that module's version, we have a possible 
   * conflict situation.
   *
   * Example
   * -------
   * Given an module A requires both B @ 1.0 and C @ 2.0,
   *
   * - If B requires C @ 2.*, that's OK, since 2.* matches 2.0
   * - If B requires C @ <= 2.0, we have a problem.
   *
   * @param  string $parent
   * @param  xp.glue.Dependency[] $transitive
   * @param  io.Folder $target
   * @return string[]
   */
  protected function register($parent, $transitive, $target, $status) {
    $conflicts= [];
    $paths= [];
    foreach ($transitive as $dependency) {
      $module= $dependency->module();

      // Make the requirements defined inside the installation directly 
      // precede over any transitive dependency. Think of this like
      // using "--force".
      if (isset($this->dependencies[$module])) {
        $preceding= $this->dependencies[$module];
      } else {
        $preceding= $dependency;
      }

      $paths= array_merge($paths, $this->install($preceding, $target, $parent, $status));
      if (!$preceding->required()->matches($this->installed[$module]['version'])) {
        $conflicts[]= [
          'module'   => $module,
          'used'     => $this->installed[$module]['version'],
          'required' => $preceding->required()->spec(),
          'by'       => $this->installed[$module]['by']
        ];
      }
    }

    foreach ($conflicts as $conflict) {
      Console::writeLinef(
        '`- Conflict: %s requires %s @ %s, but %s in use by %s',
        $parent,
        $conflict['module'],
        $conflict['required'],
        $conflict['used'],
        $conflict['by'] ?: 'project'
      );
    }

    return $paths;
  }

  /**
   * Install a given dependency and its transitive dependencies
   *
   * @param  xp.glue.Dependency $dependency
   * @param  io.Folder $target
   * @param  string $parent
   * @return string[]
   */
  protected function install(Dependency $dependency, Folder $target, $parent, $status) {
    $module= $dependency->module();
    if (isset($this->installed[$module])) return [];

    $context= [];
    isset($status['enter']) && $status['enter']($dependency, $context);

    $this->installed[$module]= ['by' => $parent];
    foreach ($this->sources as $source) {
      if (null === ($resolved= $source->fetch($dependency))) continue;

      $paths= [];
      $this->installed[$module]['version']= $resolved['project']->version();
      isset($status['found']) && $status['found']($dependency, $source, $resolved, $context);

      $progress= isset($status['start']) ? $status['start']($dependency, $context) : null;
      $steps= sizeof($resolved['tasks']);
      $vendor= new Folder($target, $dependency->vendor());
      foreach ($resolved['tasks'] as $i => $task) {
        $paths[]= $task->perform(
          $dependency,
          $vendor,
          function($percent) use($progress, $i, $steps) {
            $progress && $progress->update($percent / $steps * ($i + 1));
          }
        );
      }
      isset($status['stop']) && $status['stop']($dependency, $progress, $context);

      return array_merge($paths, $this->register(
        $module,
        $resolved['project']->dependencies(),
        $target,
        $status
      ));
    }

    isset($status['error']) && $status['error']($dependency, 404, $context);
    return [];
  }

  /**
   * Run this installation
   *
   * @param  io.Folder $target
   * @return var The installation's result
   */
  public function run(Folder $target, $status= []) {
    $paths= [];
    foreach ($this->dependencies as $dependency) {
      $paths= array_merge($paths, $this->install($dependency, $target, null, $status));
    }
    return ['paths' => $paths];
  }
}