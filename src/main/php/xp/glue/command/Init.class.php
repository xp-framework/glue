<?php namespace xp\glue\command;

use xp\glue\input\MavenPOM;
use io\File;
use io\Folder;
use util\cmd\Console;

/**
 * Init: Transforms existing projects to glue
 */
class Init extends Command {

  public function execute(array $args) {
    if (file_exists('pom.xml')) {
      $project= (new MavenPOM())->parse((new File('pom.xml'))->getInputStream());
      Console::writeLine('Importing Maven POM ', $project);
    } else {
      $project= [
        'vendor'  => \lang\System::getProperty('user.name'),
        'name'    => (new Folder('.'))->dirname,
        'version' => '0.0.1'
      ];
      Console::writeLine('Creating empty project ', $project);
    }

    // Not using JSON encoder since we want to pretty-print
    $target= new File('glue.json');
    with ($out= $target->getOutputStream()); {
      $out->write("{\n");
      $out->write('  "name"    : '.self::$json->encode($project->vendor().'/'.$project->name()).",\n");
      $out->write('  "version" : '.self::$json->encode($project->version()).",\n");
      $out->write('  "require" : {'."\n");

      $dependencies= $project->dependencies();
      $s= sizeof($dependencies) - 1;
      foreach ($dependencies as $i => $dep) {
        $out->write('    '.self::$json->encode($dep->vendor().'/'.$dep->name()).' : '.self::$json->encode($dep->required()));
        $out->write($i < $s ? ",\n" : "\n");
      }
      $out->write("  }\n");
      $out->write("}\n");
    }
    Console::writeLine($target, ' written');
    return 0;
  }
}