<?php namespace xp\glue\src;

use io\Folder;
use io\File;
use xp\glue\input\GlueFile;
use xp\glue\task\LinkTo;
use xp\glue\Dependency;

/**
 * GIT checkout in the local file system
 */
class Checkout extends Source {
  protected $bases;

  /**
   * Creates a new instance 
   *
   * @param  string $name
   * @param  string $lookup
   */
  public function __construct($name, $lookup) {
    parent::__construct($name);
    $this->bases= [];
    foreach (explode('|', $lookup) as $spec) {
      sscanf($spec, '%[^@]@%[^|]', $vendor, $path);
      $this->bases[$vendor]= new Folder($path);
    }
  }

  /**
   * Fetches the given dependency. Returns NULL if the dependency cannot be found.
   *
   * @param  xp.glue.Dependency $dependency
   * @param  [:var] $result
   */
  public function fetch(Dependency $dependency) {
    if (!isset($this->bases[$dependency->vendor()])) return null;

    $target= new Folder($this->bases[$dependency->vendor()], $dependency->name());
    $glue= new File($target, 'glue.json');
    if (!$target->exists()) return null;

    $project= (new GlueFile())->parse($glue->getInputStream());
    if (!$dependency->required()->matches($project->version())) {
      return null;
    }

    $tasks= [];
    foreach (['main', 'test'] as $f) {
      $f= new Folder($target, 'src', $f);
      if (!$f->exists()) continue;
      while ($entry= $f->getEntry()) {
        $tasks[]= new LinkTo(new Folder($f, $entry));
      }
      $f->close();
    }

    return [
      'project' => $project,
      'tasks'   => $tasks
    ];
  }
}