<?php namespace xp\glue\input;

use xml\parser\XMLParser;
use xml\parser\StreamInputSource;
use xml\XPath;
use xml\Tree;
use io\streams\InputStream;
use xp\glue\Project;
use xp\glue\Dependency;
use xp\glue\Requirement;

class MavenPOM extends \lang\Object {
  protected $parser;

  /**
   * Constructor
   */
  public function __construct() {
    $this->parser= new XMLParser();
  }

  /**
   * Parse a pom.xml file
   *
   * @param  io.streams.InputStream $in
   * @param  string $source
   * @return xp.glue.Project
   */
  public function parse(InputStream $in, $source= 'pom.xml') {
    $tree= new Tree();
    $this->parser->withCallback($tree)->parse(new StreamInputSource($in, $source));

    try {
      $pom= new XPath($tree);
      $pom->context->registerNamespace('pom', 'http://maven.apache.org/POM/4.0.0');
      $resolve= function($matches) use($pom) {
        $query= '/'.implode('/', array_map(function($e) { return 'pom:'.$e; }, explode('.', $matches[1])));
        return $pom->query($query)->item(0)->textContent;
      };
      $textOf= function($node, $child) use($pom, $resolve) {
        $item= $pom->query($child, $node)->item(0);
        return $item ? preg_replace_callback('/\$\{([^\}]+)\}/', $resolve, $item->textContent) : null;
      };

      $dependencies= [];
      foreach ($pom->query('/pom:project/pom:dependencies/pom:dependency') as $dep) {
        $classifier= $textOf($dep, 'pom:classifier');
        $dependencies[]= new Dependency(
          $textOf($dep, 'pom:groupId'),
          $textOf($dep, 'pom:artifactId').($classifier ? '~'.$classifier : ''),
          new Requirement($textOf($dep, 'pom:version'))
        );
      }

      return new Project(
        $textOf($pom->document, 'pom:groupId'),
        $textOf($pom->document, 'pom:artifactId'),
        strtr($textOf($pom->document, 'pom:version'), ['-SNAPSHOT' => '']),
        $dependencies
      );
    } catch (\lang\XPException $e) {
      \util\cmd\Console::writeLine($tree->getSource());
      throw new \lang\FormatException('Errors processing '.$source, $e);
    }
  }
}
