<?php namespace xp\glue\input;

use io\streams\InputStream;
use xp\glue\Project;
use xp\glue\Dependency;
use xp\glue\version\Requirement;
use xp\glue\version\GlueSpec;
use text\json\StreamInput;

/**
 * A gluefile contains project and version information as well as requirements.
 * The names are written in the `vendor/module` convention.
 *
 * ```json
 * {
 *   "name"    : "thekid/dialog",
 *   "version" : "4.0.0",
 *   "require" : {
 *     "xp-forge/mustache" : ">=1.2",
 *     "xp-framework/core" : "~5.9"
 *   }
 * }
 * ```
 */
class GlueFile extends \lang\Object {
  protected $spec;

  /**
   * Constructor
   */
  public function __construct() {
    $this->spec= new GlueSpec();
  }

  /**
   * Verifies the configuraton
   *
   * @param  [:var] $config
   * @return [:var] Verified configuration
   * @throws lang.FormatException
   */
  protected function verify($config) {
    $missing= function($config, $element) {
      throw new \lang\FormatException('Configuration missing '.$element.': '.\xp::stringOf($config));
    };

    // Required
    if (!isset($config['name'])) $missing('name');
    if (!isset($config['version'])) $missing('version');

    // Optional
    if (!isset($config['require'])) $config['require']= [];

    return $config;
  }

  /**
   * Parse a glue.json
   *
   * @param  io.streams.InputStream $in
   * @param  string $source
   * @return xp.glue.Project
   */
  public function parse(InputStream $in, $source= 'glue.json') {
    try {
      $config= (new StreamInput($in))->read();
    } catch (FormatException $e) {
      throw new \lang\FormatException('Cannot parse '.$source, $e);
    }

    $config= $this->verify($config);

    $dependencies= [];
    foreach ($config['require'] as $module => $required) {
      sscanf($module, "%[^/]/%[^\r]", $vendor, $name);
      $dependencies[]= new Dependency($vendor, $name, $this->spec->parse($required));
    }

    sscanf($config['name'], "%[^/]/%[^\r]", $vendor, $name);
    return new Project($vendor, $name, $config['version'], $dependencies);
  }
}
