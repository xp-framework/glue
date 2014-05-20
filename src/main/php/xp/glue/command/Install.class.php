<?php namespace xp\glue\command;

use io\File;
use io\Folder;
use util\cmd\Console;
use util\Properties;
use webservices\json\JsonFactory;
use lang\reflect\Package;
use xp\glue\input\GlueFile;
use xp\glue\Progress;
use util\profiling\Timer;

/**
 * Install: Resolves dependencies, downloading and linking as necessary.
 */
class Install extends Command {
  const PW = 16;
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
   * Fetch dependencies and returns URIs ready for adding to class path.
   *
   * @param  io.Folder $libs target folder
   * @param  [:xp.glue.Dependency] $dependencies
   * @return string[]
   */
  protected function fetch(Folder $libs, $dependencies) {
    $paths= [];

    // Don't use foreach() here as that doesn't allow modification during iteration
    while (list($module, $dependency)= each($dependencies)) {
      $line= '[>>> '.str_repeat('.', self::PW).'] '.$module.' @ '.$dependency->required()->spec();
      Console::write($line);

      foreach ($this->sources as $source) {
        if (null !== ($resolved= $source->fetch($dependency))) {
          $name= $source->compoundName();
          Console::writef(
            ": %s %s%s[\033[44;1;37m200\033[0m ",
            $name,
            $resolved['project']->version(),
            str_repeat("\x08", strlen($line) + strlen($name) + 1 + strlen($resolved['project']->version())+ 2)
          );

          $progress= new Progress(self::PW, '#');
          $steps= sizeof($resolved['tasks']);
          $vendor= new Folder($libs, $dependency->vendor());
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

          // Register dependencies
          foreach ($resolved['project']->dependencies() as $dependency) {
            $key= $dependency->vendor().'/'.$dependency->name();
            if (isset($dependencies[$key])) {

              if (!$dependencies[$key]->required()->equals($dependency->required())) {
                Console::writeLine('`- ', $key, ': ', $dependencies[$key], ' vs. ', $dependency);
              }
              // TODO: Check for conflicts!
              continue;
            }

            $dependencies[$key]= $dependency;
            // DEBUG Console::writeLine('`- ', $key, ' @ ', $dependency->required());
          }
          continue 2;
        }
      }

      Console::writeLinef(
        "%s[\033[41;1;37m404\033[0m ",
        str_repeat("\x08", strlen($line))
      );
    }
    return $paths;
  }

  /**
   * Creates a key/value lookup map from a given list of dependencies
   *
   * @param  xp.glue.Dependency[] $dependencies
   * @return [:xp.glue.Dependency] lookup map, keyed by $VENDOR/$NAME
   */
  protected function dependencyLookupOf($dependencies) {
    $lookup= [];
    foreach ($dependencies as $dependency) {
      $lookup[$dependency->vendor().'/'.$dependency->name()]= $dependency;
    }
    return $lookup;
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
      $count= $this->createPathFile(
        new File($cwd, 'glue.pth'),
        $cwd,
        $this->fetch(new Folder($cwd, 'vendor'), $this->dependencyLookupOf($project->dependencies()))
      );
      $result= function() use($project, $count) {
        Console::writeLinef(
          "\033[42;1;37mOK, %d dependencies processed, %d paths registered\033[0m",
          sizeof($project->dependencies()),
          $count
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