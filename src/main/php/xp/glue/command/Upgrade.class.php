<?php namespace xp\glue\command;

use io\File;
use io\Folder;
use util\cmd\Console;
use xp\glue\input\GlueFile;
use xp\glue\src\Source;
use xp\glue\Project;
use xp\glue\Dependency;
use xp\glue\version\Requirement;
use xp\glue\version\Equals;
use xp\glue\install\Installation;
use util\profiling\Timer;
use text\regex\Pattern;

/**
 * Upgrade previously installed libraries.
 */
class Upgrade extends Command {

  /**
   * Install dependencies and returns URIs ready for adding to class path.
   *
   * @param  io.Folder $libs target folder
   * @param  xp.glue.Dependency[] $dependencies
   * @return [:var] Installation result
   */
  protected function installInto(Folder $libs, $dependencies) {
    $installation= new Installation($this->sources, $dependencies);
    return $installation->run($libs, new InstallationStatus(Console::$out));
  }

  /**
   * Creates a .pth file. Uses relative path entries whenever possible.
   *
   * @param  io.Folder $cwd
   * @param  string[] $paths
   */
  protected function createPathFile($cwd, array $paths) {
    with ($pth= (new File($cwd, 'glue.pth'))->getOutputStream()); {
      $base= $cwd->getURI();
      foreach ($paths as $path) {
        if (0 === substr_compare($base, $path, 0, strlen($base))) {
          $entry= str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($base)));
        } else {
          $entry= $path;
        }
        $pth->write($entry."\n");
      }
      $pth->close();
    }
  }

  /**
   * Creates a glue.lock file.
   *
   * @param  io.File $file
   * @param  io.Folder $cwd
   * @param  string[] $installed
   */
  protected function createLockFile($cwd, array $installed) {
    with ($lock= (new File($cwd, 'glue.lock'))->getOutputStream()); {
      $lock->write("{\n");
      $s= sizeof($installed);
      $i= 0;
      foreach ($installed as $module => $def) {
        $lock->write('  "'.$module.'": "'.$def['version'].'"');
        if (++$i < $s) $lock->write(",\n");
      }
      $lock->write("\n}\n");
      $lock->close();
    }
  }

  /**
   * Summarize results
   *
   * @param  var $result
   * @param  double $elapsed
   * @return int exit code
   */
  protected function summarize($result, $elapsed) {
    $rt= \lang\Runtime::getInstance();

    Console::writeLine();
    $exit= $result();
    Console::writeLinef(
      "Memory used: %.2f kB (%.2f kB peak)\nTime taken: %.3f seconds",
      $rt->memoryUsage() / 1024,
      $rt->peakMemoryUsage() / 1024,
      $elapsed
    );

    return $exit;
  }

  /**
   * Gets the project in the given folder
   *
   * @param  io.Folder $origin
   * @return xp.glue.Project
   * @throws io.FileNotFoundException if no project can be found
   */
  protected function projectIn($origin) {
    return (new GlueFile())->parse((new File($origin, 'glue.json'))->getInputStream());
  }

  /**
   * Execute this action
   *
   * @param  string[] $args
   */
  public function execute(array $args) {
    $timer= new Timer();
    $timer->start();

    $cwd= new Folder('.');
    $project= $this->projectIn($cwd);

    $locked= self::$json->decodeFrom((new File($cwd, 'glue.lock'))->getInputStream());

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
    $project= new Project($project->vendor(), $project->name(), $project->version(), $dependencies);

    try {
      $installation= $this->installInto(new Folder($cwd, 'vendor'), $project->dependencies());
      $this->createPathFile($cwd, $installation['paths']);
      $this->createLockFile($cwd, $installation['installed']);

      $result= function() use($project, $installation) {
        Console::writeLinef(
          "\033[42;1;37mOK, %d dependencies processed, %d modules installed, %d paths registered\033[0m",
          sizeof($project->dependencies()),
          sizeof($installation['installed']),
          sizeof($installation['paths'])
        );
      };
    } catch (\lang\Throwable $t) {
      $result= function() use($t) {
        $error= explode("\n", $t->toString(), 2);
        Console::writeLinef("\n\033[41;1;37mFAIL: %s\033[0m\n%s", $error[0], $error[1]);
        return 1;
      };
    }

    $timer->stop();
    return $this->summarize($result, $timer->elapsedTime());
  }
}