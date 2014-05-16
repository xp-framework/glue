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
    if ('*' === $spec) {
      return key($releases);
    } else {
      foreach ($releases as $release => $info) {
        if (0 === version_compare($spec, $release, 'eq')) {
          return $version;
        }
      }
    }
    return null;
  }

  public function fetch($vendor, $name, $spec) {
    $res= $this->rest->execute((new RestRequest('/vendors/{vendor}/modules/{module}'))
      ->withSegment('vendor', $vendor)
      ->withSegment('module', $name)
    );

    if (200 === $res->status()) {
      $releases= $res->data()['releases'];
      uksort($releases, function($a, $b) {
        return version_compare($a, $b, '<');
      });

      if ($release= $this->select($releases, $spec)) {
        $res= $this->rest->execute((new RestRequest('/vendors/{vendor}/modules/{module}/releases/{release}'))
          ->withSegment('vendor', $vendor)
          ->withSegment('module', $name)
          ->withSegment('release', $release)
        );
        $info= $res->data()['files'][0];
        $base= $this->rest->getBase();
        $file= (new HttpConnection($base->setPath($info['link'])))->get();
        if (200 === $file->statusCode()) return new Download(
          $file->getInputStream(),
          $info['name'],
          $info['size'],
          $info['sha1']
        );
            
        throw new \lang\IllegalStateException('Download link broken '.$file->toString());
      }
    }

    return null;
  }
}