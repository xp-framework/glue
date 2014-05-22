<?php namespace xp\glue\unittest\install;

use io\Folder;
use xp\glue\Dependency;
use xp\glue\install\Installation;
use xp\glue\version\GlueSpec;

class ConflictsTest extends \unittest\TestCase {
  protected $temp;
  protected $spec;
  protected $conflicts;

  /**
   * Initializes temp dir and glue requirements specification parser
   */
  public function setUp() {
    $this->temp= new Folder(\lang\System::tempDir());
    $this->spec= new GlueSpec();
    $this->conflicts= newinstance('xp.glue.install.NoInstallationStatus', [], [
      'list'      => [],
      'conflicts' => function($parent, array $conflicts) {
        if (!isset($this->list[$parent])) {
          $this->list[$parent]= $conflicts;
        } else {
          $this->list[$parent]= array_merge($this->conflicts[$parent], $conflicts);
        }
      }
    ]);
  }

  #[@test]
  public function two_dependencies_require_different_versions_of_transitive_dependency() {
    $source= new TestSource([
      'test/test@1.0.0' => [
        'depend'  => [new Dependency('test', 'transitive', $this->spec->parse('2.0.8'))],
        'tasks'   => []
      ],
      'test/cause-of-conflict@6.6.6' => [
        'depend'  => [new Dependency('test', 'transitive', $this->spec->parse('2.0.9'))],
        'tasks'   => []
      ],
      'test/transitive@2.0.8' => [
        'depend'  => [],
        'tasks'   => []
      ],
    ]);

    $r= new Installation([$source], [
      new Dependency('test', 'test', $this->spec->parse('1.*')),
      new Dependency('test', 'cause-of-conflict', $this->spec->parse('6.*'))
    ]);
    $r->run($this->temp, $this->conflicts);
    $this->assertEquals(
      ['test/cause-of-conflict' => [
        [
          'module'   => 'test/transitive',
          'used'     => '2.0.8',
          'required' => '2.0.9',
          'by'       => 'test/test'
        ]
      ]],
      $this->conflicts->list
    );
  }

  #[@test]
  public function transitive_dependency_requires_different_version_of_dependency() {
    $source= new TestSource([
      'test/test@1.0.0' => [
        'depend'  => [
          new Dependency('test', 'cause-of-conflict', $this->spec->parse('6.*')),
          new Dependency('test', 'transitive', $this->spec->parse('2.0.8'))
        ],
        'tasks'   => []
      ],
      'test/cause-of-conflict@6.6.6' => [
        'depend'  => [new Dependency('test', 'transitive', $this->spec->parse('2.0.9'))],
        'tasks'   => []
      ],
      'test/transitive@2.0.8' => [
        'depend'  => [],
        'tasks'   => []
      ],
    ]);

    $r= new Installation([$source], [new Dependency('test', 'test', $this->spec->parse('1.*'))]);
    $r->run($this->temp, $this->conflicts);
    $this->assertEquals(
      ['test/cause-of-conflict' => [
        [
          'module'   => 'test/transitive',
          'used'     => '2.0.8',
          'required' => '2.0.9',
          'by'       => 'test/cause-of-conflict'
        ]
      ]],
      $this->conflicts->list
    );
  }
}