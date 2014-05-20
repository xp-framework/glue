<?php namespace xp\glue\command;

use xp\glue\input\MavenPOM;
use xp\glue\input\GlueFile;
use xp\glue\Project;
use io\File;
use io\Folder;
use util\cmd\Console;
use lang\System;

/**
 * Init: Transforms existing projects to glue
 */
class Init extends Command {

  public function execute(array $args) {
    if (file_exists('glue.json')) {
      $project= (new GlueFile())->parse((new File('glue.json'))->getInputStream());
      Console::writeLine('Project already initialized: ', $project);
      return 1;
    } else if (file_exists('pom.xml')) {
      $project= (new MavenPOM())->parse((new File('pom.xml'))->getInputStream());
      Console::writeLine('Importing Maven POM ', $project);
    } else {
      $project= new Project(
        System::getProperty('user.name'),
        (new Folder('.'))->dirname,
        '0.0.1',
        []
      );
      Console::writeLine('Creating empty project ', $project);
    }

    // Not using JSON encoder since we want to pretty-print
    $target= new File('glue.json');
    with ($out= $target->getOutputStream()); {
      $out->write("{\n");
      $out->write('  "name"    : "'.$project->vendor().'/'.$project->name()."\",\n");
      $out->write('  "version" : "'.$project->version()."\",\n");
      $out->write('  "require" : {'."\n");

      $dependencies= $project->dependencies();
      $s= sizeof($dependencies) - 1;
      foreach ($dependencies as $i => $dep) {
        $out->write(sprintf(
          '    "%s/%s" : "%s"',
          $dep->vendor(),
          $dep->name(),
          $dep->required()->spec()
        ));
        $out->write($i < $s ? ",\n" : "\n");
      }
      $out->write("  }\n");
      $out->write("}\n");
    }
    Console::writeLine($target, ' written');
    return 0;
  }
}