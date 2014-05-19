<?php namespace xp\glue\src;

use webservices\rest\RestClient;
use webservices\rest\RestRequest;
use peer\http\HttpConnection;
use peer\URL;
use xp\glue\Dependency;
use xp\glue\task\Download;
use xp\glue\input\MavenPOM;
use text\regex\Pattern;

/**
 * Artifactory source
 *
 * @see  http://www.jfrog.com/confluence/display/RTF/Artifactory+REST+API#ArtifactoryRESTAPI-GAVCSearch
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
   * Select matching results
   *
   * @param  var[] $results
   * @param  string $name
   * @param  xp.glue.Requirement $required
   * @return var Newest result matching
   */
  protected function select($results, $name, $required) {
    $pattern= Pattern::compile('('.$name.')/([0-9\.]+)(-SNAPSHOT)?/');

    $selected= [];
    foreach ($results as $result) {
      $version= $pattern->match($result['path'])->group(0)[2];
      if (!$required->matches($version)) {
        continue;
      } else if (isset($selected[$version])) {
        $selected[$version][]= $result;
      } else {
        $selected[$version]= [$result];
      }
    }

    ksort($selected);
    return array_pop($selected);
  }

  /**
   * Fetches the given dependency. Returns NULL if the dependency cannot be found.
   *
   * @param  xp.glue.Dependency $dependency
   * @param  [:var] $result
   */
  public function fetch(Dependency $dependency) {
    $search= (new RestRequest('/api/search/gavc'))
      ->withParameter('g', $dependency->vendor())
      ->withParameter('a', $dependency->name())
      ->withHeader('X-Result-Detail', 'info')
      ->withAccept('application/vnd.org.jfrog.artifactory.search.GavcSearchResult+json')
    ;

    // Optimize case when we have a fixed version
    $required= $dependency->required();
    if ($required->fixed()) {
      $search->addParameter('v', $required->spec());
    }

    $res= $this->rest->execute($search);
    if (200 !== $res->status()) {
      throw new \lang\IllegalStateException('Search returned error '.$res->toString());
    }

    $response= $res->data();
    if (!($results= $this->select($response['results'], $dependency->name(), $required))) {

      // No applicable version found
      return null;
    }

    $base= $this->rest->getBase();
    $project= null;
    $tasks= [];
    foreach ($results as $result) {
      $uri= new URL($result['downloadUri']);
      if ($uri->getHost() === $base->getHost()) {
        $uri->setUser($base->getUser());
        $uri->setPassword($base->getPassword());
      }

      if (strstr($result['path'], '.pom')) {
        $pom= (new HttpConnection($uri))->get();
        $project= (new MavenPOM())->parse($pom->getInputStream());
      } else if (strstr($result['path'], '.xar')) {
        $file= (new HttpConnection($uri))->get();
        $tasks[]= new Download(
          $file->getInputStream(),
          basename($result['path']),
          $result['size'],
          $result['checksums']['sha1']
        );
      }
    }

    if (null === $project) {
      throw new \lang\IllegalStateException('Cannot determine project information from artifact '.\xp::stringOf($response));
    }
    return ['project' => $project, 'tasks' => $tasks];
  }
}