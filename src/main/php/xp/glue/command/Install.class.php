<?php namespace xp\glue\command;

use io\File;
use io\Folder;
use util\cmd\Console;
use util\Properties;
use webservices\json\JsonFactory;
use lang\reflect\Package;
use xp\glue\input\GlueFile;
use xp\glue\src\Source;
use xp\glue\Progress;
use xp\glue\Project;
use xp\glue\Dependency;
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
   * @param  io.File $file
   * @param  io.Folder $cwd
   * @param  string[] $paths
   * @return int
   */
  protected function createPathFile($file, $cwd, array $paths) {
    $count= 0;
    $pth= $file->getOutputStream();
    $base= $cwd->getURI();
    foreach ($paths as $path) {
      if (0 === substr_compare($base, $path, 0, strlen($base))) {
        $entry= str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($base)));
      } else {
        $entry= $path;
      }
      $pth->write($entry."\n");
      $count++;
    }
    $pth->close();
    return $count;
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

  /** @return xp.glue.install.Status */
  protected function status() {
    return newinstance('xp.glue.install.Status', [], [
      'PW'        => 16,
      'offset'    => null,
      'progress'  => null,
      'enter'     => function(Dependency $dependency) {
        $l= sprintf(
          '[>>> %s] %s @ %s',
          str_repeat('.', $this->PW),
          $dependency->module(),
          $dependency->required()->spec()
        );
        $this->offset= strlen($l);
        Console::write($l);
      },
      'found'     => function(Dependency $dependency, Source $source, Project $project) {
        $name= $source->compoundName();
        Console::writef(
          ": %s %s%s[\033[44;1;37m200\033[0m ",
          $name,
          $project->version(),
          str_repeat("\x08", $this->offset + strlen($name) + 1 + strlen($project->version())+ 2)
        );
      },
      'start'     => function(Dependency $dependency) {
        $this->progress= new Progress($this->PW, '#');
      },
      'update'    => function(Dependency $dependency, $percent) {
        $this->progress->update($percent);
      },
      'stop'      => function(Dependency $dependency) {
        $this->progress->update(100);
        Console::writeLine();
      },
      'error'     => function(Dependency $dependency, $code) {
        Console::writeLinef(
          "%s[\033[41;1;37m%s\033[0m ",
          $code,
          str_repeat("\x08", $this->offset)
        );
      },
      'conflicts' => function($parent, array $conflicts) {
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
      }
    ]);
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
    $project= (new GlueFile())->parse((new File($cwd, 'glue.json'))->getInputStream());

    try {
      $result= $this->install(new Folder($cwd, 'vendor'), $project->dependencies(), $this->status());
      $this->createPathFile(new File($cwd, 'glue.pth'), $cwd, $result['paths']);

      $result= function() use($project, $result) {
        Console::writeLinef(
          "\033[42;1;37mOK, %d dependencies processed, %d modules installed, %d paths registered\033[0m",
          sizeof($project->dependencies()),
          sizeof($result['installed']),
          sizeof($result['paths'])
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