<?php namespace xp\glue\input;

use xml\parser\XMLParser;
use xml\parser\StreamInputSource;
use xml\XPath;
use xml\Tree;
use io\streams\InputStream;

class MavenPOM extends \lang\Object {
  protected $parser;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->parser= new XMLParser();
  }

  /**
   * Parse a pom.xml file
   */
  public function parse(InputStream $in, $source= 'pom.xml') {
    $tree= new Tree();
    $this->parser->withCallback($tree)->parse(new StreamInputSource($in, $source));

    $pom= new XPath($tree);
    $pom->context->registerNamespace('pom', 'http://maven.apache.org/POM/4.0.0');
    $textOf= function($node, $child) use($pom) { return $pom->query($child, $node)->item(0)->textContent; };

    $project= [
      'vendor'  => $textOf($pom->document, 'pom:groupId'),
      'name'    => $textOf($pom->document, 'pom:artifactId'),
      'version' => strtr($textOf($pom->document, 'pom:version'), ['-SNAPSHOT' => '']),
      'libs'    => []
    ];

    foreach ($pom->query('/pom:project/pom:dependencies/pom:dependency') as $dep) {
      $project['libs'][]= [
        'vendor'  => $textOf($dep, 'pom:groupId'),
        'name'    => $textOf($dep, 'pom:artifactId').($classifier ? '-'.$classifier : ''),
        'version' => $textOf($dep, 'pom:version')
      ];
    }

    return $project;
  }
}
