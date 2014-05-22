<?php namespace xp\glue\unittest;

use io\Folder;
use xp\glue\Installation;
use xp\glue\Dependency;
use xp\glue\Project;
use xp\glue\task\LinkTo;
use xp\glue\version\GlueSpec;

class InstallationTest extends \unittest\TestCase {
  protected $temp;
  protected $spec;

  /**
   * Initializes temp dir and glue requirements specification parser
   */
  public function setUp() {
    $this->temp= new Folder(\lang\System::tempDir());
    $this->spec= new GlueSpec();
  }

  #[@test]
  public function can_create() {
    new Installation([], []);
  }

  #[@test]
  public function run_empty_installation() {
    $r= new Installation([], []);
    $this->assertEquals(['paths' => []], $r->run($this->temp));
  }

  #[@test]
  public function install_dependency() {
    $local= new Folder('local-checkout');
    $source= newinstance('xp.glue.src.Source', [], [
      'fetch' => function(Dependency $d) use($local) {
        return [
          'project' => new Project($d->vendor(), $d->name(), '1.0.0', []),
          'tasks'   => [new LinkTo($local)]
        ];
      }
    ]);
    $r= new Installation([$source], [new Dependency('test', 'test', $this->spec->parse('1.*'))]);
    $this->assertEquals(['paths' => [$local->getURI()]], $r->run($this->temp));
  }
}