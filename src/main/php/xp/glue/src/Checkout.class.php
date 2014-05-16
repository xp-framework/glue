<?php namespace xp\glue\src;

use io\Folder;
use io\File;
use xp\glue\input\GlueFile;
use xp\glue\task\LinkTo;

/**
 * GIT checkout in the local file system
 */
class Checkout extends Source {
  protected $bases;

  public function __construct($lookup) {
    $this->bases= [];
    foreach (explode('|', $lookup) as $spec) {
      sscanf($spec, '%[^@]@%[^|]', $vendor, $path);
      $this->bases[$vendor]= new Folder($path);
    }
  }

  public function fetch($vendor, $name, $spec) {
    if (!isset($this->bases[$vendor])) return null;

    $target= new Folder($this->bases[$vendor], $name);
    $glue= new File($target, 'glue.json');
    if (!$target->exists()) return null;

    $tasks= [];
    foreach (['main', 'test'] as $f) {
      $f= new Folder($target, 'src', $f);
      while ($entry= $f->getEntry()) {
        $tasks[]= new LinkTo(new Folder($f, $entry));
      }
      $f->close();
    }

    return [
      'project' => (new GlueFile())->parse($glue->getInputStream()),
      'tasks'   => $tasks
    ];
  }
}