<?php namespace xp\glue\src;

use webservices\rest\RestClient;
use webservices\rest\RestRequest;
use peer\http\HttpConnection;
use peer\URL;
use xp\glue\Download;

/**
 * Artifactory source
 */
class Artifactory extends Source {
  protected $rest;

  public function __construct($base) {
    $this->rest= new RestClient($base);
    // $this->rest->setTrace((new \util\log\LogCategory(''))->withAppender(new \util\log\ConsoleAppender()));
  }

  public function fetch($vendor, $name, $version) {
    $res= $this->rest->execute((new RestRequest('/api/search/gavc'))
      ->withParameter('g', $vendor)
      ->withParameter('a', $name)
      ->withParameter('v', $version)
      ->withAccept('application/vnd.org.jfrog.artifactory.search.GavcSearchResult+json')
    );

    if (200 === $res->status()) {
      $response= $res->data();
      foreach ($response['results'] as $result) {
        if (strstr($result['uri'], '.xar')) {
          $base= $this->rest->getBase();

          $relative= substr((new URL($result['uri']))->getPath(), strlen($base->getPath()));
          $info= $this->rest->execute((new RestRequest($relative))
            ->withAccept('application/vnd.org.jfrog.artifactory.storage.FileInfo+json')
          )->data();

          $uri= new URL($info['downloadUri']);
          if ($uri->getHost() === $base->getHost()) {
            $uri->setUser($base->getUser());
            $uri->setPassword($base->getPassword());
          }
          $file= (new HttpConnection($uri))->get();
          if (200 === $file->statusCode()) return new Download(
            $file->getInputStream(),
            basename($info['path']),
            $info['size'],
            $info['checksums']['sha1']
          );
          
          throw new \lang\IllegalStateException('Download link broken '.$file->toString());
        }
      }
      return null;
    } else if (204 === $res->status()) {
      return null;
    } else {
      throw new \lang\IllegalStateException('Expecting either 200 or 204, have '.$res->toString());
    }
  }
}