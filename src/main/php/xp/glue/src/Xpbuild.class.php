<?php namespace xp\glue\src;

use webservices\rest\RestClient;
use webservices\rest\RestRequest;
use peer\http\HttpConnection;
use peer\URL;
use xp\glue\task\Download;
use xp\glue\version\Requirement;
use xp\glue\Project;
use xp\glue\Dependency;
use util\data\Sequence;

/**
 * XP Build System source
 *
 * @see  https://github.com/xp-framework/build
 */
class Xpbuild extends Source {
  protected $rest;

  /**
   * Creates a new instance
   *
   * @param  string $name
   * @param  string $base URL to artifactory (including, if necessary, auth)
   */
  public function __construct($name, $base) {
    parent::__construct($name);
    $this->rest= new RestClient($base);
    // $this->rest->setTrace((new \util\log\LogCategory(''))->withAppender(new \util\log\ConsoleAppender()));
  }

  /**
   * Select the newest release that matches the given requirement
   *
   * @param  [:var] $releases A map version => info
   * @param  xp.glue.version.Requirement $requirement
   * @param  string The selected version, or NULL
   */
  protected function select($releases, Requirement $requirement) {
    uksort($releases, function($a, $b) {
      return version_compare($a, $b, '<');
    });

    foreach ($releases as $version => $info) {
      if ($requirement->matches($version)) return $version;
    }
    return null;
  }

  /**
   * Searches for a given term
   *
   * @param  string $term
   * @return util.data.Sequence<xp.glue.Project>
   */
  public function find($term) {
    $res= $this->rest->execute((new RestRequest('/search'))->withParameter('q', $term));
    return Sequence::of($res->data())
      ->map(function($data) { return new Project($data['vendor'], $data['module'], '*', []); })
    ;
  }

  /**
   * Fetches the given dependency. Returns NULL if the dependency cannot be found.
   *
   * @param  xp.glue.Dependency $dependency
   * @param  [:var] $result
   */
  public function fetch(Dependency $dependency) {
    $res= $this->rest->execute((new RestRequest('/vendors/{vendor}/modules/{module}'))
      ->withSegment('vendor', $dependency->vendor())
      ->withSegment('module', $dependency->name())
    );

    if (200 !== $res->status()) return null;

    $module= $res->data();
    if (!($release= $this->select($module['releases'], $dependency->required()))) {

      // Has the module, but not the correct version
      return null;
    }

    $tasks= [];
    $res= $this->rest->execute((new RestRequest('/vendors/{vendor}/modules/{module}/releases/{release}'))
      ->withSegment('vendor', $module['vendor'])
      ->withSegment('module', $module['module'])
      ->withSegment('release', $release)
    );
    $base= clone $this->rest->getBase();
    foreach ($res->data()['files'] as $info) {
      if (!strstr($info['name'], '.xar')) continue;

      $tasks[]= new Download(
        new HttpConnection($base->setPath($info['link'])),
        $info['name'],
        $info['size'],
        $info['sha1']
      );
    }

    $project= new Project($module['vendor'], $module['module'], $release, []);
    return ['project' => $project, 'tasks' => $tasks];
  }

  /** @return string */
  public function toString() {
    $cloned= clone $this->rest->getBase();
    return $this->getClassName().'(->'.($cloned->setPassword('...')->getURL()).')';
  }
}