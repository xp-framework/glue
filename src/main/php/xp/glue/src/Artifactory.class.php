<?php namespace xp\glue\src;

use webservices\rest\RestClient;
use webservices\rest\RestRequest;
use peer\http\HttpConnection;
use peer\URL;
use xp\glue\Dependency;
use xp\glue\task\Download;
use xp\glue\input\MavenPOM;

/**
 * Artifactory source
 */
class Artifactory extends Source {
  protected $rest;

  /**
   * Creates a new instance
   *
   * @param  string $base URL to artifactory (including, if necessary, auth)
   */
  public function __construct($base) {
    $this->rest= new RestClient($base);
    // $this->rest->setTrace((new \util\log\LogCategory(''))->withAppender(new \util\log\ConsoleAppender()));
  }

  /**
   * Creates relative URL
   *
   * @param  peer.URL $base
   * @param  string $uri
   * @return string
   */
  protected function relativeUri(URL $base, $uri) {
    return substr((new URL($uri))->getPath(), strlen($base->getPath()));
  }

  /**
   * Fetches the given dependency. Returns NULL if the dependency cannot be found.
   *
   * @param  xp.glue.Dependency $dependency
   * @param  [:var] $result
   */
  public function fetch(Dependency $dependency) {
    $res= $this->rest->execute((new RestRequest('/api/search/gavc'))
      ->withParameter('g', $dependency->vendor())
      ->withParameter('a', $dependency->name())
      ->withParameter('v', $dependency->required())
      ->withAccept('application/vnd.org.jfrog.artifactory.search.GavcSearchResult+json')
    );

    if (200 !== $res->status()) {
      throw new \lang\IllegalStateException('Search returned error '.$res->toString());
    }

    $response= $res->data();
    if (empty($response['results'])) return null;

    $base= $this->rest->getBase();
    $project= null;
    $tasks= [];
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
      } else if (strstr($result['uri'], '.xar')) {
        $file= (new HttpConnection($uri))->get();
        $tasks[]= new Download(
          $file->getInputStream(),
          basename($info['path']),
          $info['size'],
          $info['checksums']['sha1']
        );
      }
    }
    if (null === $project) {
      throw new \lang\IllegalStateException('Cannot determine project information from artifact '.\xp::stringOf($response));
    }
    return ['project' => $project, 'tasks' => $tasks];
  }
}