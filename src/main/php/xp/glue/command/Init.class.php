<?php namespace xp\glue\command;

use xml\XPath;
use xml\Tree;
use io\File;
use util\cmd\Console;

/**
 * Init: Transforms Maven projects to glue
 */
class Init extends Command {

  public function execute(array $args) {
    $pom= new XPath(Tree::fromFile(new File('pom.xml')));
    $pom->context->registerNamespace('pom', 'http://maven.apache.org/POM/4.0.0');
    $textOf= function($node, $child) use($pom) { return $pom->query($child, $node)->item(0)->textContent; };

    $project= [
      'name'    => sprintf('%s/%s', $textOf($pom->document, 'pom:groupId'), $textOf($pom->document, 'pom:artifactId')),
      'version' => strtr($textOf($pom->document, 'pom:version'), ['-SNAPSHOT' => '']),
      'libs'    => []
    ];

    foreach ($pom->query('/pom:project/pom:dependencies/pom:dependency') as $dep) {
      $classifier= $textOf($dep, 'pom:classifier');
      $name= sprintf(
        '%s/%s%s',
        $textOf($dep, 'pom:groupId'),
        $textOf($dep, 'pom:artifactId'),
        $classifier ? '-'.$classifier : ''
      );
      $version= $textOf($dep, 'pom:version');
      Console::writeLine('+ ', $name, ' @ ', $version);
      $project['libs'][]= ['name' => $name, 'version' => $version];
    }

    // Not using JSON encoder since we want to pretty-print
    $target= new File('glue.json');
    with ($out= $target->getOutputStream()); {
      $out->write("{\n");
      $out->write('  "name"    : '.self::$json->encode($project['name']).",\n");
      $out->write('  "version" : '.self::$json->encode($project['version']).",\n");
      $out->write('  "require" : {'."\n");
      $s= sizeof($project['libs']) - 1;
      foreach ($project['libs'] as $i => $lib) {
        $out->write('    '.self::$json->encode($lib['name']).' : '.self::$json->encode($lib['version']));
        $out->write($i < $s ? ",\n" : "\n");
      }
      $out->write("  }\n");
      $out->write("}\n");
    }
    Console::writeLine($target, ' written');
    return 0;
  }
}