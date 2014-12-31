<?php namespace xp\glue\src;

use xp\glue\Dependency;
use xp\glue\Project;
use xp\glue\version\GlueSpec;
use xp\glue\task\ZipDownload;
use xp\glue\task\Autoloader;
use peer\http\HttpConnection;
use peer\http\HttpConstants;
use peer\http\HttpRequest;
use text\json\StreamInput;
use util\data\Sequence;
use lang\IndexOutOfBoundsException;
use lang\FormatException;

/**
 * Packagist source
 *
 * @see  https://packagist.org/
 */
class Packagist extends Source {
  protected $conn;

  /**
   * Creates a new instance
   *
   * @param  string $name
   * @param  string $base URL to packagist (including, if necessary, auth)
   */
  public function __construct($name, $base) {
    parent::__construct($name);
    $this->conn= new HttpConnection($base);
  }

  /**
   * Searches for a given term
   *
   * @param  string $term
   * @return util.data.Sequence<string>
   */
  public function find($term) {
    raise('lang.MethodNotImplementedException', 'Find', __METHOD__);
  }

  /**
   * Fetches the given dependency. Returns NULL if the dependency cannot be found.
   *
   * ```sh
   * $ curl https://packagist.org/p/xp-forge/sequence.json | json_pp
   * ```
   *
   * @param  xp.glue.Dependency $dependency
   * @param  [:var] $result
   */
  public function fetch(Dependency $dependency) {

    // TODO: Add caching!
    $req= $this->conn->create(new HttpRequest());
    $req->setTarget('/p/'.$dependency->module().'.json');
    $res= $this->conn->send($req);

    if (HttpConstants::STATUS_OK === $res->statusCode()) {
      $matches= function($package, $version) use ($dependency) {
        return $dependency->required()->matches(ltrim($version, 'v'));
      };

      $stream= new StreamInput($res->in());
      try {
        $selected= Sequence::of($stream->read()['packages'][$dependency->module()])
          ->filter($matches)
          ->first()
        ;
      } catch (IndexOutOfBoundsException $e) {
        throw new FormatException('Unexpected input format for '.$req->getUrl()->getURL(), $e);
      }

      if ($selected->present()) {
        $package= $selected->get();

        $spec= new GlueSpec();
        $dependencies= Sequence::of($package['require'])
          ->filter(function($required, $module) { return 'php' !== $module; })
          ->map(function($required, $module) use($spec) {
            sscanf($module, "%[^/]/%[^\r]", $vendor, $name);
            return new Dependency($vendor, $name, $spec->parse($required));
          })
          ->toArray()
        ;

        $project= new Project($dependency->vendor(), $dependency->name(), ltrim($package['version'], 'v'), $dependencies);
        $tasks= [
          new Autoloader($package['autoload'], new ZipDownload(
            new HttpConnection($package['dist']['url']),
            $dependency->name().'-'.$package['version_normalized'])
          )
        ];

        return ['project' => $project, 'tasks' => $tasks];
      }
    }

    return null;
  }

  /** @return string */
  public function toString() {
    return $this->getClassName().'('.$this->conn->getUrl()->getURL().')';
  }
}