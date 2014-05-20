<?php namespace xp\glue;

use io\Folder;
use util\cmd\Console;
use xp\glue\Progress;

class Installation extends \lang\Object {
  const PW = 16;

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
      $this->dependencies[$dependency->compoundName()]= $dependency;
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
  protected function register($parent, $transitive, $target) {
    $conflicts= [];
    $paths= [];
    foreach ($transitive as $dependency) {
      $module= $dependency->compoundName();

      // Make the requirements defined inside the installation directly 
      // precede over any transitive dependency. Think of this like
      // using "--force".
      if (isset($this->dependencies[$module])) {
        $preceding= $this->dependencies[$module];
      } else {
        $preceding= $dependency;
      }

      $paths= array_merge($paths, $this->install($preceding, $target, $parent));
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
  protected function install(Dependency $dependency, Folder $target, $parent= null) {
    $module= $dependency->compoundName();
    if (isset($this->installed[$module])) return [];

    $line= '[>>> '.str_repeat('.', self::PW).'] '.$module.' @ '.$dependency->required()->spec();
    Console::write($line);

    $this->installed[$module]= ['by' => $parent, 'version' => '(recursion)'];
    foreach ($this->sources as $source) {
      if (null === ($resolved= $source->fetch($dependency))) continue;

      $paths= [];
      $this->installed[$module]= ['by' => $parent, 'version' => $resolved['project']->version()];
      $name= $source->compoundName();
      Console::writef(
        ": %s %s%s[\033[44;1;37m200\033[0m ",
        $name,
        $resolved['project']->version(),
        str_repeat("\x08", strlen($line) + strlen($name) + 1 + strlen($resolved['project']->version())+ 2)
      );

      $progress= new Progress(self::PW, '#');
      $steps= sizeof($resolved['tasks']);
      $vendor= new Folder($target, $dependency->vendor());
      foreach ($resolved['tasks'] as $i => $task) {
        $paths[]= $task->perform(
          $dependency,
          $vendor,
          function($percent) use($progress, $i, $steps) {
            $progress->update($percent / $steps * ($i + 1));
          }
        );
      }
      $progress->update(100);
      Console::writeLine();

      return array_merge($paths, $this->register(
        $module,
        $resolved['project']->dependencies(),
        $target
      ));
    }

    Console::writeLinef(
      "%s[\033[41;1;37m404\033[0m ",
      str_repeat("\x08", strlen($line))
    );
    return [];
  }

  /**
   * Run this installation
   *
   * @param  io.Folder $target
   * @return var The installation's result
   */
  public function run(Folder $target) {
    $paths= [];
    foreach ($this->dependencies as $dependency) {
      $paths= array_merge($paths, $this->install($dependency, $target));
    }
    return ['paths' => $paths];
  }
}