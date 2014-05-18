<?php namespace xp\glue\src;

use webservices\rest\RestClient;
use webservices\rest\RestRequest;
use peer\http\HttpConnection;
use peer\URL;
use xp\glue\task\Download;
use xp\glue\Project;
use xp\glue\Dependency;
use xp\glue\Requirement;

/**
 * XP Build System source
 *
 * @see  https://github.com/xp-framework/build
 */
class Xpbuild extends Source {
  protected $rest;

  public function __construct($base) {
    $this->rest= new RestClient($base);
    // $this->rest->setTrace((new \util\log\LogCategory(''))->withAppender(new \util\log\ConsoleAppender()));
  }

  /**
   * Select the newest release that matches the given requirement
   *
   * @param  [:var] $releases A map version => info
   * @param  xp.glue.Requirement $requirement
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
    $base= $this->rest->getBase();
    foreach ($res->data()['files'] as $info) {
      if (!strstr($info['name'], '.xar')) continue;

      $file= (new HttpConnection($base->setPath($info['link'])))->get();
      $tasks[]= new Download(
        $file->getInputStream(),
        $info['name'],
        $info['size'],
        $info['sha1']
      );
    }

    $project= new Project($module['vendor'], $module['module'], $release, []);
    return ['project' => $project, 'tasks' => $tasks];
  }
}