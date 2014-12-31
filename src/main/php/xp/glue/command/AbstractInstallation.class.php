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
use text\json\FileInput;
use text\json\FileOutput;
use text\json\Types;
use text\json\WrappedFormat;
use text\json\Format;

/**
 * Base class for all installations
 */
abstract class AbstractInstallation extends Command {

  /**
   * Creates a .pth file. Uses relative path entries whenever possible.
   *
   * @param  io.Folder $cwd
   * @param  [:var] $result
   */
  protected function createPathFile($cwd, array $result) {
    with ((new File($cwd, 'glue.pth'))->out(), function($out) use($cwd, $result) {
      $base= $cwd->getURI();
      foreach ($result as $module => $def) {
        foreach ($def['paths'] as $path) {
          if (0 === substr_compare($base, $path, 0, strlen($base))) {
            $entry= str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($base)));
          } else {
            $entry= $path;
          }
          $out->write($entry."\n");
        }
      }
    });
  }

  /**
   * Creates a glue.lock file.
   *
   * @param  io.File $file
   * @param  io.Folder $cwd
   * @param  [:var] $result
   */
  protected function createLockFile($cwd, array $result) {
    $lock= new FileOutput(new File($cwd, 'glue.lock'), new WrappedFormat('  ', ~Format::ESCAPE_SLASHES));
    with ($lock->begin(Types::$OBJECT), function($versions) use($result) {
      foreach ($result as $module => $def) {
        if (!isset($def['version'])) continue;
        $versions->pair($module, $def['version']);
      }
    });
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
   * Gets the project in the given folder
   *
   * @param  io.Folder $origin
   * @return xp.glue.Project
   * @throws io.FileNotFoundException if no project can be found
   */
  protected function locksIn($origin) {
    $lock= new File($origin, 'glue.lock');
    if ($lock->exists()) {
      return (new FileInput($lock))->read();
    } else {
      return null;
    }
  }

  /**
   * Gets dependencies to be processed
   *
   * @param  xp.glue.Project $project
   * @param  [:string] $locked
   * @param  string[] $args
   * @return xp.glue.Dependency[]
   */
  protected abstract function dependenciesFor($project, $locked, $args);

  /**
   * Execute this action
   *
   * @param  string[] $args
   */
  public function execute(array $args) {
    $timer= new Timer();
    $timer->start();

    $cwd= new Folder('.');
    $dependencies= $this->dependenciesFor($this->projectIn($cwd), $this->locksIn($cwd), $args);
    $installation= new Installation($this->sources, $dependencies);

    Console::writeLine('===> Running ', $installation);
    try {
      $result= $installation->run(new Folder($cwd, 'vendor'), new InstallationStatus(Console::$out));

      $this->createPathFile($cwd, $result['installed']);
      $this->createLockFile($cwd, $result['installed']);

      if (!empty($result['errors'])) {
        $result= function() use($dependencies, $result) {
          Console::writeLinef(
            "\033[41;1;37mFAIL, %d dependencies processed, %d modules installed, %d error(s) occured\033[0m",
            sizeof($dependencies),
            sizeof($result['installed']),
            sizeof($result['errors'])
          );
        };
      } else {
        $result= function() use($dependencies, $result) {
          Console::writeLinef(
            "\033[42;1;37mOK, %d dependencies processed, %d modules installed, 0 error(s) occured\033[0m",
            sizeof($dependencies),
            sizeof($result['installed'])
          );
        };
      }
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