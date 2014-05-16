<?php namespace xp\glue\src;

use webservices\rest\RestClient;
use webservices\rest\RestRequest;
use peer\http\HttpConnection;
use peer\URL;
use xp\glue\Download;
use xp\glue\input\MavenPOM;

/**
 * Artifactory source
 */
class Artifactory extends Source {
  protected $rest;

  public function __construct($base) {
    $this->rest= new RestClient($base);
    // $this->rest->setTrace((new \util\log\LogCategory(''))->withAppender(new \util\log\ConsoleAppender()));
  }

  public function relativeUri(URL $base, $uri) {
    return substr((new URL($uri))->getPath(), strlen($base->getPath()));
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
      $base= $this->rest->getBase();
      foreach ($response['results'] as $result) {
        $info= $this->rest->execute((new RestRequest($this->relativeUri($base, $result['uri'])))
          ->withAccept('application/vnd.org.jfrog.artifactory.storage.FileInfo+json')
        )->data();

        $uri= new URL($info['downloadUri']);
        if ($uri->getHost() === $base->getHost()) {
          $uri->setUser($base->getUser());
          $uri->setPassword($base->getPassword());
        }

        if (strstr($result['uri'], '.pom')) {
          $pom= (new HttpConnection($uri))->get();

          $project= (new MavenPOM())->parse($pom->getInputStream());
          var_dump($project);  // XXX TODO 

        } else if (strstr($result['uri'], '.xar')) {
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