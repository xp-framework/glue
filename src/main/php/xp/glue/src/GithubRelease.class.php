<?php namespace xp\glue\src;

use webservices\rest\RestClient;
use webservices\rest\RestRequest;
use peer\http\HttpConnection;
use xp\glue\Dependency;
use xp\glue\task\Download;
use xp\glue\input\GlueFile;
use util\data\Sequence;

/**
 * Github releases source
 *
 * @see  https://developer.github.com/v3/repos/releases/
 */
class GithubRelease extends Source {
  const USER_AGENT = 'xp-forge/glue GitHubRelease 0.8.0';

  protected $rest;

  /**
   * Creates a new instance
   *
   * @param  string $name
   * @param  string $base URL for Github API
   */
  public function __construct($name, $base) {
    parent::__construct($name);
    $this->rest= new RestClient($base ?: 'https://api.github.com/');
    // $this->rest->setTrace((new \util\log\LogCategory(''))->withAppender(new \util\log\ConsoleAppender()));
  }

  /**
   * Searches for a given term
   *
   * @param  string $term
   * @return util.data.Sequence<string>
   */
  public function find($term) {
    return Sequence::of([]);    // TBI
  }

  /**
   * Fetches the given dependency. Returns NULL if the dependency cannot be found.
   *
   * @param  xp.glue.Dependency $dependency
   * @param  [:var] $result
   */
  public function fetch(Dependency $dependency) {
    $required= $dependency->required();
    $res= $this->rest->execute((new RestRequest('/repos/xp-framework/core/releases'))
      ->withHeader('User-Agent', self::USER_AGENT)
      ->withAccept('application/json')
    );

    $release= Sequence::of($res->data())
      ->map(function($release) { $release['version']= ltrim($release['tag_name'], 'rv'); return $release; })
      ->filter(function($release) use($required) { return $required->matches($release['version']); })
      ->sorted(function($a, $b) { return version_compare($a['version'], $b['version']); })
      ->first()
    ;
    if (!$release->present()) return null;

    $tasks= [];
    foreach ($release->get()['assets'] as $asset) {
      if ('glue.json' === $asset['name']) {
        $url= $asset['browser_download_url'];
        do {
          $file= (new HttpConnection($url))->get(null, ['User-Agent' => self::USER_AGENT]);
          $url= $file->header('Location')[0];
        } while (in_array($file->statusCode(), [301, 302, 307]));
        $project= (new GlueFile())->parse($file->getInputStream());
      } else {
        $tasks[]= new Download(
          new HttpConnection($asset['browser_download_url']),
          $asset['name'],
          $asset['size'],
          null
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