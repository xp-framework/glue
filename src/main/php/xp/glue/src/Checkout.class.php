<?php namespace xp\glue\src;

use io\Folder;
use io\File;
use io\collections\iterate\IOCollectionIterator;
use io\collections\FileCollection;
use io\collections\iterate\CollectionFilter;
use xp\glue\input\GlueFile;
use xp\glue\task\LinkTo;
use xp\glue\Dependency;
use util\Objects;
use util\data\Sequence;
use text\regex\Pattern;

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
   * Searches for a given term
   *
   * @param  string $term
   * @return util.data.Sequence<string>
   */
  public function find($term) {
    $glue= new GlueFile();
    $pattern= Pattern::compile($term, Pattern::CASE_INSENSITIVE);
    return Sequence::of($this->bases)
      ->distinct()
      ->map(function($folder) { return new IOCollectionIterator(new FileCollection($folder)); })
      ->flatten(function($iterator) { return Sequence::of($iterator)->filter([new CollectionFilter(), 'accept']); })
      ->map(function($collection) { return new File($collection->getUri(), 'glue.json'); })
      ->filter(function($file) { return $file->exists(); })
      ->map(function($file) use($glue) { return $glue->parse($file->getInputStream()); })
      ->filter(function($project) use($pattern) { return $pattern->matches($project->vendor().'/'.$project->name()); })
      ->map(function($project) { return $project->vendor().'/'.$project->name(); })
    ;
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

  /** @return string */
  public function toString() {
    return $this->getClassName().'@'.Objects::stringOf($this->bases);
  }
}