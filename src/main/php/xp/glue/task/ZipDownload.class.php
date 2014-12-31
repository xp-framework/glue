<?php namespace xp\glue\task;

use peer\http\HttpConnection;
use io\Folder;
use io\File;
use io\Path;
use io\FileUtil;
use io\streams\StreamTransfer;
use xp\glue\Dependency;
use io\archive\zip\ZipFile;
use util\data\Sequence;
use util\Objects;
use lang\IllegalStateException;

/**
 * The "ZipDownload" task fetches a ZIP file from a given stream and stores
 * its contents locally.
 */
class ZipDownload extends Task {
  const USER_AGENT = 'xp-forge/glue ZipDownload 1.0.0';

  protected $conn, $folder;

  /**
   * Constructor
   *
   * @param  peer.http.HttpConnection $conn
   * @param  string $folder
   */
  public function __construct(HttpConnection $conn, $folder) {
    $this->conn= $conn;
    $this->folder= $folder;
  }

  /**
   * Extract a ZIP file into a given target directory
   *
   * @param  io.archive.zip.ZipArchiveReader $zip
   * @param  io.Folder $target
   * @param  function(var): void $status
   */
  protected function extract($zip, $target, $status) {
    $it= $zip->iterator();
    $first= $it->next();
    $base= $first->isDirectory() ? $first->getName() : dirname($first->getName());

    Sequence::concat([$first], $it)
      ->peek($status)
      ->each(function($entry) use($target, $base) {
        $rel= (new Path($entry->getName()))->relativeTo($base);
        if ($entry->isDirectory()) {
          $dir= new Folder($target, $rel);
          $dir->exists() || $dir->create(0755);
        } else {
          $file= new File($target, $rel);
          with (new StreamTransfer($entry->getInputStream(), $file->out()), function($t) {
            $t->transferAll();
          });
        }
      })
    ;
  }

  /**
   * Perform this task and return a URI useable for the class path
   *
   * @param  xp.glue.Dependency $dependency
   * @param  io.Folder $folder
   * @param  var $status
   * @return string
   */
  public function perform(Dependency $dependency, Folder $folder, $status) {
    $folder->exists() || $folder->create(0755);

    $headers= ['User-Agent' => self::USER_AGENT, 'Accept' => '*/*'];
    $target= new Folder($folder, $this->folder);

    $cache= new File($target, '.etag');
    if ($cache->exists()) {
      $headers['If-Modified-Since']= gmdate('D, d M Y H:i:s \G\M\T', $cache->lastModified());
      $headers['If-None-Match']= FileUtil::getContents($cache);
    }

    $response= $this->conn->request('GET', [], $headers);
    if (304 === $response->statusCode()) {
      $status->report($dependency, $this, 304);
    } else if (in_array($response->statusCode(), [301, 302, 307])) {
      $redirect= new self(new HttpConnection($response->header('Location')[0]), $this->folder);
      return $redirect->perform($dependency, $folder, $status);
    } else if ($cache->exists() && Objects::equal($response->header('ETag'), [$headers['If-None-Match']])) {

      // GitHub reports ETags but doesn't send "Not Modified" even if given in If-None-Match.
      // Work around this for the moment; support issue has been created.
      $status->report($dependency, $this, 104);
    } else {
      $status->report($dependency, $this, $response->statusCode());

      $extracted= 0;
      $this->extract(ZipFile::open($response->in()), $target, function($entry) use(&$extracted, $status, $dependency) {
        $status->progress($dependency, $this, $extracted++ % 100);
      });
      $status->progress($dependency, $this, 100);

      if ($etag= $response->header('ETag')) {
        FileUtil::setContents($cache, $etag[0]);
      } else {
        $cache->unlink();
      }
    }

    return $target->getURI();
  }

  /**
   * Returns a status to be used in the installation's output
   *
   * @return string
   */
  public function status() {
    return '@//'.$this->conn->getURL()->getHost().'/ -> '.$this->folder;
  }
}