<?php namespace xp\glue\command;

use xp\glue\input\MavenPOM;
use xml\XPath;
use xml\Tree;
use io\File;
use util\cmd\Console;

/**
 * Init: Transforms Maven projects to glue
 */
class Init extends Command {

  public function execute(array $args) {
    $project= (new MavenPOM())->parse((new File('pom.xml'))->getInputStream());

    // Not using JSON encoder since we want to pretty-print
    $target= new File('glue.json');
    with ($out= $target->getOutputStream()); {
      $out->write("{\n");
      $out->write('  "name"    : '.self::$json->encode($project['vendor'].'/'.$project['name']).",\n");
      $out->write('  "version" : '.self::$json->encode($project['version']).",\n");
      $out->write('  "require" : {'."\n");
      $s= sizeof($project['libs']) - 1;
      foreach ($project['libs'] as $i => $lib) {
        $out->write('    '.self::$json->encode($lib['vendor'].'/'.$lib['name']).' : '.self::$json->encode($lib['version']));
        $out->write($i < $s ? ",\n" : "\n");
      }
      $out->write("  }\n");
      $out->write("}\n");
    }
    Console::writeLine($target, ' written');
    return 0;
  }
}