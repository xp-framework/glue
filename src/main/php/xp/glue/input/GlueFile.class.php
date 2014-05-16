<?php namespace xp\glue\input;

use io\streams\InputStream;
use webservices\json\JsonFactory;
use webservices\json\JsonException;
use xp\glue\Project;
use xp\glue\Dependency;

class GlueFile extends \lang\Object {
  protected $json;

  /**
   * Constructor.
   */
  public function __construct() {
    $this->json= JsonFactory::create();
  }

  /**
   * Parse a glue.json
   */
  public function parse(InputStream $in, $source= 'glue.json') {
    try {
      $config= $this->json->decodeFrom($in);
    } catch (JsonException $e) {
      throw new \lang\FormatException('Cannot parse '.$source, $e);
    }

    $dependencies= [];
    foreach ($config['require'] as $module => $required) {
      sscanf($module, "%[^/]/%[^\r]", $vendor, $name);
      $dependencies[]= new Dependency($vendor, $name, $required);
    }

    sscanf($config['name'], "%[^/]/%[^\r]", $vendor, $name);
    return new Project($vendor, $name, $config['version'], $dependencies);
  }
}