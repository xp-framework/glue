<?php namespace xp\glue\src;

use webservices\rest\RestClient;
use webservices\rest\RestRequest;
use peer\http\HttpConnection;
use peer\URL;
use xp\glue\Dependency;
use xp\glue\task\Download;
use xp\glue\input\MavenPOM;
use text\regex\Pattern;
use util\data\Sequence;

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
   * @param  string $name
   * @param  string $base URL to artifactory (including, if necessary, auth)
   */
  public function __construct($name, $base) {
    parent::__construct($name);
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
   * @param  string[] $names
   * @param  xp.glue.version.Requirement $required
   * @return var Newest result matching
   */
  protected function select($results, $names, $required) {
    $base= '('.preg_quote($names[0], '/').')-([0-9\.]+)(-SNAPSHOT)?';
    if (isset($names[1])) {
      $pattern= Pattern::compile($base.'(-'.preg_quote($names[1], '/').'\.xar|\.pom)$');
    } else {
      $pattern= Pattern::compile($base.'\.(xar|pom)$');
    }

    $selected= [];
    foreach ($results as $result) {
      $match= $pattern->match($result['path']);
      if ($match->length() < 1) continue;

      $version= $match->group(0)[2];
      if (!$required->matches($version)) continue;

      if (isset($selected[$version])) {
        $selected[$version][]= $result;
      } else {
        $selected[$version]= [$result];
      }
    }

    ksort($selected);
    return array_pop($selected);
  }

  /**
   * Searches for a given term
   *
   * @param  string $term
   * @return util.data.Sequence<xp.glue.Project>
   */
  public function find($term) {
    return Sequence::$EMPTY;      // TBI
  }

  /**
   * Fetches the given dependency. Returns NULL if the dependency cannot be found.
   *
   * @param  xp.glue.Dependency $dependency
   * @param  [:var] $result
   */
  public function fetch(Dependency $dependency) {
    $names= explode('~', $dependency->name());
    $search= (new RestRequest('/api/search/gavc'))
      ->withParameter('g', $dependency->vendor())
      ->withParameter('a', $names[0])
      ->withHeader('X-Result-Detail', 'info')
      ->withAccept('application/vnd.org.jfrog.artifactory.search.GavcSearchResult+json')
    ;

    // Optimize case when we have a fixed version
    $required= $dependency->required();
    if (null !== ($fixed= $required->fixed())) {
      $search->addParameter('v', $fixed);
    }

    $res= $this->rest->execute($search);
    if (200 !== $res->status()) {
      throw new \lang\IllegalStateException('Search returned error '.$res->toString());
    }

    $response= $res->data();
    if (!($results= $this->select($response['results'], $names, $required))) {

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
        $tasks[]= new Download(
          new HttpConnection($uri),
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

  /** @return string */
  public function toString() {
    $cloned= clone $this->rest->getBase();
    return $this->getClassName().'(->'.($cloned->setPassword('...')->getURL()).')';
  }
}