<?php namespace xp\glue\src;

use webservices\rest\RestClient;
use webservices\rest\RestRequest;
use peer\http\HttpConnection;
use peer\URL;
use xp\glue\Download;

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

  public function fetch($vendor, $name, $spec) {
    $res= $this->rest->execute((new RestRequest('/vendors/{vendor}/modules/{module}'))
      ->withSegment('vendor', $vendor)
      ->withSegment('module', $name)
    );

    if (200 !== $res->status()) return null;

    $module= $res->data();
    if (!($release= $this->select($module['releases'], $spec))) {

      // Has the module, but not the correct version
      return null;
    }

    $tasks= [];
    $res= $this->rest->execute((new RestRequest('/vendors/{vendor}/modules/{module}/releases/{release}'))
      ->withSegment('vendor', $vendor)
      ->withSegment('module', $name)
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

    $project= [
      'vendor'  => $module['vendor'],
      'name'    => $module['name'],
      'version' => $release,
      'libs'    => [],
    ];
    return ['project' => $project, 'tasks' => $tasks];
  }
}