<?php namespace xp\glue\command;

use io\File;
use io\Folder;
use util\cmd\Console;
use util\Properties;
use webservices\json\JsonFactory;
use lang\reflect\Package;
use xp\glue\input\GlueFile;
use xp\glue\src\Source;
use xp\glue\Project;
use xp\glue\Dependency;
use xp\glue\version\Requirement;
use xp\glue\version\Equals;
use xp\glue\install\Installation;
use util\profiling\Timer;

/**
 * Install: Resolves dependencies, downloading and linking as necessary.
 */
class Install extends Command {
  protected $sources= [];

  /**
   * Configure this command
   *
   * @param  util.Properties $conf
   * @return void
   */
  public function configure(Properties $conf) {
    parent::configure($conf);
    $this->sources= [];
    foreach ($this->conf->readSection('sources') as $name => $url) {
      sscanf($name, '%[^@]@%s', $impl, $spec);
      $this->sources[]= Package::forName('xp.glue.src')->loadClass(ucfirst($impl))->newInstance($spec, $url);
    }
  }

  /**
   * Install dependencies and returns URIs ready for adding to class path.
   *
   * @param  io.Folder $libs target folder
   * @param  xp.glue.Dependency[] $dependencies
   * @return string[]
   */
  protected function install(Folder $libs, $dependencies, $status) {
    return (new Installation($this->sources, $dependencies))->run($libs, $status);
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
    $project= (new GlueFile())->parse((new File($origin, 'glue.json'))->getInputStream());

    $lock= new File($origin, 'glue.lock');
    if ($lock->exists()) {
      $dependencies= [];
      foreach (self::$json->decodeFrom($lock->getInputStream()) as $module => $version) {
        sscanf($module, "%[^/]/%[^\r]", $vendor, $name);
        $dependencies[]= new Dependency($vendor, $name, Requirement::equal($version));
      }
      return new Project($project->vendor(), $project->name(), $project->version(), $dependencies);
    } else {
      return $project;
    }
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
    // Console::writeLine($project);

    try {
      $installation= $this->install(new Folder($cwd, 'vendor'), $project->dependencies(), new InstallationStatus());
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