<?php namespace xp\glue\install;

use io\Folder;
use xp\glue\Dependency;

/**
 * Installs dependencies from a given list of sources.
 *
 * @test  xp://xp.glue.unittest.install.InstallationTest
 */
class Installation extends \lang\Object {
  protected static $NO_STATUS;

  protected $installed;
  protected $errors;
  protected $sources;
  protected $dependencies;

  static function __static() {
    self::$NO_STATUS= new NoInstallationStatus();
  }

  /**
   * Creates a new installation
   *
   * @param  xp.glue.src.Source[] $sources
   * @param  xp.glue.Dependency[] $dependencies
   */
  public function __construct($sources, $dependencies) {
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
   * @param  xp.glue.InstallationStatus $status
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

    if (!empty($conflicts)) {
      $status->conflicts($parent, $conflicts);
    }

    return $paths;
  }

  /**
   * Install a given dependency and its transitive dependencies
   *
   * @param  xp.glue.Dependency $dependency
   * @param  io.Folder $target
   * @param  string $parent
   * @param  xp.glue.InstallationStatus $status
   * @return string[]
   */
  protected function install(Dependency $dependency, Folder $target, $parent, $status) {
    $module= $dependency->module();
    if (isset($this->errors[$module])) return [];     // Do not try again
    if (isset($this->installed[$module])) return [];  // Paths already registered
    $status->enter($dependency);

    $this->installed[$module]= ['by' => $parent];
    foreach ($this->sources as $source) {
      if (null === ($resolved= $source->fetch($dependency))) continue;

      $paths= [];
      $this->installed[$module]['version']= $resolved['project']->version();
      $status->found($dependency, $source, $resolved['project']);

      $vendor= new Folder($target, $dependency->vendor());
      foreach ($resolved['tasks'] as $i => $task) {
        $status->start($dependency, $task);
        $paths[]= $task->perform($dependency, $vendor, $status);
        $status->stop($dependency, $task);
      }

      return array_merge($paths, $this->register(
        $module,
        $resolved['project']->dependencies(),
        $target,
        $status
      ));
    }

    unset($this->installed[$module]);
    $this->errors[$module]= new NotFound($this->sources);
    $status->error($dependency, $this->errors[$module]);
    return [];
  }

  /**
   * Run this installation
   *
   * @param  io.Folder $target
   * @param  xp.glue.install.Status $status
   * @return var The installation's result
   */
  public function run(Folder $target, Status $status= null) {
    $this->installed= [];
    $this->errors= [];
    $paths= [];
    $status || $status= self::$NO_STATUS;
    foreach ($this->dependencies as $dependency) {
      $paths= array_merge($paths, $this->install($dependency, $target, null, $status));
    }
    return ['paths' => $paths, 'installed' => $this->installed, 'errors' => $this->errors];
  }
}