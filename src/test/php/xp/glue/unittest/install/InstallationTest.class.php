<?php namespace xp\glue\unittest\install;

use io\Folder;
use xp\glue\Dependency;
use xp\glue\Project;
use xp\glue\install\Installation;
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
    $this->assertEquals(['paths' => [], 'installed' => [], 'errors' => []], $r->run($this->temp));
  }

  #[@test]
  public function install_dependency() {
    $local= new Folder('local-checkout');
    $source= new TestSource([
      'test/test@1.0.0' => [
        'depend'  => [],
        'tasks'   => [new LinkTo(new Folder($local))]
      ]
    ]);

    $r= new Installation([$source], [new Dependency('test', 'test', $this->spec->parse('1.*'))]);
    $this->assertEquals(
      [
        'paths'     => [$local->getURI()],
        'installed' => [
          'test/test' => ['by' => null, 'version' => '1.0.0']
        ],
        'errors'    => []
      ],
      $r->run($this->temp)
    );
  }

  #[@test]
  public function installing_dependency_twice_only_returns_it_once() {
    $local= new Folder('local-checkout');
    $source= new TestSource([
      'test/test@1.0.0' => [
        'depend'  => [],
        'tasks'   => [new LinkTo(new Folder($local))]
      ]
    ]);

    $r= new Installation([$source], [
      new Dependency('test', 'test', $this->spec->parse('1.*')),
      new Dependency('test', 'test', $this->spec->parse('1.*'))
    ]);
    $this->assertEquals(
      [
        'paths'     => [$local->getURI()],
        'installed' => [
          'test/test' => ['by' => null, 'version' => '1.0.0']
        ],
        'errors'    => []
      ],
      $r->run($this->temp)
    );
  }

  #[@test]
  public function install_dependency_with_dependency() {
    $local= new Folder('local-checkout');
    $source= new TestSource([
      'test/test@1.0.0' => [
        'depend'  => [new Dependency('test', 'transitive', $this->spec->parse('2.0.8'))],
        'tasks'   => [new LinkTo(new Folder($local, 'test'))]
      ],
      'test/transitive@2.0.8' => [
        'depend'  => [],
        'tasks'   => [new LinkTo(new Folder($local, 'transitive'))]
      ]
    ]);

    $r= new Installation([$source], [new Dependency('test', 'test', $this->spec->parse('1.*'))]);
    $this->assertEquals(
      [
        'paths'     => [
          (new Folder($local, 'test'))->getURI(),
          (new Folder($local, 'transitive'))->getURI()
        ],
        'installed' => [
          'test/test'       => ['by' => null, 'version' => '1.0.0'],
          'test/transitive' => ['by' => 'test/test', 'version' => '2.0.8'],
        ],
        'errors'    => []
      ],
      $r->run($this->temp)
    );
  }

  #[@test]
  public function handles_recursion() {
    $local= new Folder('local-checkout');
    $source= new TestSource([
      'test/test@1.0.0' => [
        'depend'  => [new Dependency('test', 'transitive', $this->spec->parse('2.0.8'))],
        'tasks'   => [new LinkTo(new Folder($local, 'test'))]
      ],
      'test/transitive@2.0.8' => [
        'depend'  => [new Dependency('test', 'recursion', $this->spec->parse('6.6.6'))],
        'tasks'   => [new LinkTo(new Folder($local, 'transitive'))]
      ],
      'test/recursion@6.6.6' => [
        'depend'  => [new Dependency('test', 'test', $this->spec->parse('~1.0'))],
        'tasks'   => [new LinkTo(new Folder($local, 'recursion'))]
      ]
    ]);

    $r= new Installation([$source], [new Dependency('test', 'test', $this->spec->parse('1.*'))]);
    $this->assertEquals(
      [
        'paths'     => [
          (new Folder($local, 'test'))->getURI(),
          (new Folder($local, 'transitive'))->getURI(),
          (new Folder($local, 'recursion'))->getURI()
        ],
        'installed' => [
          'test/test'       => ['by' => null, 'version' => '1.0.0'],
          'test/transitive' => ['by' => 'test/test', 'version' => '2.0.8'],
          'test/recursion'  => ['by' => 'test/transitive', 'version' => '6.6.6'],
        ],
        'errors'    => []
      ],
      $r->run($this->temp)
    );
  }
}