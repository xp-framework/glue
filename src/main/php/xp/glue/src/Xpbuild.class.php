<?php namespace xp\glue\src;

use webservices\rest\RestClient;
use webservices\rest\RestRequest;
use peer\http\HttpConnection;
use peer\URL;
use xp\glue\task\Download;
use xp\glue\Project;
use xp\glue\Dependency;

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

  protected function select($releases, $spec) {
    uksort($releases, function($a, $b) {
      return version_compare($a, $b, '<');
    });

    if ('*' === $spec) {
      return key($releases);
    } else {
      foreach ($releases as $release => $info) {
        if (version_compare($spec, $release, 'eq')) return $release;
      }
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