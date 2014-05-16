<?php namespace xp\glue\command;

use io\File;
use io\Folder;
use util\cmd\Console;
use util\Properties;
use webservices\json\JsonFactory;
use lang\reflect\Package;

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
      $line= '[>>> '.str_repeat('.', self::PW).'] '.$module.' @ '.$dependency['version'];
      Console::write($line);

      foreach ($this->sources as $name => $source) {
        if (null !== ($remote= $source->fetch($dependency['vendor'], $dependency['name'], $dependency['version']))) {
          Console::writef(
            ": %s %s%s[\033[44;1;37m200\033[0m ",
            $name,
            $remote['project']['version'],
            str_repeat("\x08", strlen($line) + strlen($name) + 1 + strlen($remote['project']['version'])+ 2)
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
          foreach ($remote['project']['libs'] as $lib) {
            $key= $lib['vendor'].'/'.$lib['name'];
            if (isset($dependencies[$key])) {

              // TODO: Check for conflicts!
              continue;
            }

            $dependencies[$key]= $lib;
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

  public function execute(array $args) {
    $project= self::$json->decodeFrom((new File('glue.json'))->getInputStream());
    $cwd= new Folder('.');

    $dependencies= [];
    foreach ($project['require'] as $module => $version) {
      sscanf($module, "%[^/]/%[^\r]", $vendor, $name);
      $dependencies[$module]= [
        'vendor'  => $vendor,
        'name'    => $name,
        'version' => $version
      ];
    }

    $paths= $this->fetch(new Folder($cwd, 'vendor'), $dependencies);

    $pth= (new File($cwd, 'glue.pth'))->getOutputStream();
    foreach ($paths as $path) {
      $pth->write(str_replace(DIRECTORY_SEPARATOR, '/', substr($path, strlen($cwd->getURI())))."\n");
    }
    $pth->close();
  }
}