<?php namespace xp\glue\command;

use io\File;
use io\Folder;
use util\cmd\Console;
use util\Properties;
use webservices\json\JsonFactory;
use lang\reflect\Package;
use xp\glue\input\GlueFile;

/**
 * Install: Downloads dependencies
 */
class Install extends Command {
  const PW = 10;
  protected $sources= [];

  public function configure(Properties $conf) {
    parent::configure($conf);
    $this->sources= [];
    foreach ($this->conf->readSection('sources')['source'] as $name => $url) {
      sscanf($name, '%[^@]@%s', $impl, $spec);
      $this->sources[$name]= Package::forName('xp.glue.src')->loadClass(ucfirst($impl))->newInstance($url);
    }
  }

  protected function fetch(Folder $libs, $dependencies) {
    $paths= [];

    // Don't use foreach() here as that doesn't allow modification during iteration
    while (list($module, $dependency)= each($dependencies)) {
      $line= '[>>> '.str_repeat('.', self::PW).'] '.$module.' @ '.$dependency->required();
      Console::write($line);

      foreach ($this->sources as $name => $source) {
        if (null !== ($remote= $source->fetch($dependency->vendor(), $dependency->name(), $dependency->required()))) {
          Console::writef(
            ": %s %s%s[\033[44;1;37m200\033[0m ",
            $name,
            $remote['project']->version(),
            str_repeat("\x08", strlen($line) + strlen($name) + 1 + strlen($remote['project']->version())+ 2)
          );

          // Prepare output folder
          $folder= new Folder($libs, $vendor);
          $folder->exists() || $folder->create(0755);

          // Perform tasks
          $step= floor(self::PW / sizeof($remote['tasks']));
          foreach ($remote['tasks'] as $download) {   // XXX: May be local-link task
            $target= new File($folder, $download->file());
            $bytes= $download->size();

            with ($in= $download->stream(), $out= $target->getOutputStream()); {
              $c= 0; $progress= 0;
              while ($in->available()) {
                $chunk= $in->read();
                $progress+= strlen($chunk);
                $out->write($chunk);

                $d= ceil(($progress / $bytes) * $step);
                if ($d == $c) continue;
                Console::write(str_repeat('#', $d- $c));
                $c= $d;
              }

              $in->close();
              $out->close();
            }
            $paths[]= $target->getURI();
          }
          Console::writeLine();

          // Register dependencies
          foreach ($remote['project']->dependencies() as $dependency) {
            $key= $dependency->vendor().'/'.$dependency->name();
            if (isset($dependencies[$key])) {

              // TODO: Check for conflicts!
              continue;
            }

            $dependencies[$key]= $dependency;
            Console::writeLine('`- ', $key, ' @ ', $dependency->required());
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
   * Execute this action
   *
   * @param  string[] $args
   */
  public function execute(array $args) {
    $cwd= new Folder('.');
    $project= (new GlueFile())->parse((new File($cwd, 'glue.json'))->getInputStream());

    $paths= $this->fetch(new Folder($cwd, 'vendor'), $this->dependencyLookupOf($project->dependencies()));
    $pth= (new File($cwd, 'glue.pth'))->getOutputStream();
    foreach ($paths as $path) {
      $pth->write(str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($cwd->getURI())))."\n");
    }
    $pth->close();
  }
}