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

  public function execute(array $args) {
    $sources= [];
    foreach ($this->conf->readSection('sources')['source'] as $name => $url) {
      sscanf($name, '%[^@]@%s', $impl, $spec);
      $sources[$name]= Package::forName('xp.glue.src')->loadClass(ucfirst($impl))->newInstance($url);
    }

    $project= self::$json->decodeFrom((new File('glue.json'))->getInputStream());
    $cwd= new Folder('.');
    $pth= (new File('glue.pth'))->getOutputStream();
    $libs= new Folder($cwd, 'vendor');
    $dependencies= $project['require'];
    foreach ($dependencies as $dependency => $version) {
      $line= '[>>> '.str_repeat('.', self::PW).'] '.$dependency.' @ '.$version;
      Console::write($line);

      sscanf($dependency, "%[^/]/%[^\r]", $vendor, $lib);
      foreach ($sources as $name => $source) {
        if (null !== ($remote= $source->fetch($vendor, $lib, $version))) {
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

            $pth->write(str_replace(DIRECTORY_SEPARATOR, '/', substr($target->getURI(), strlen($cwd->getURI())))."\n");
          }
          Console::writeLine();
          continue 2;
        }
      }

      Console::writeLinef(
        "%s[\033[41;1;37m404\033[0m ",
        str_repeat("\x08", strlen($line))
      );
    }
  }
}