<?php namespace xp\glue;

/**
 * Represents a version requirement
 *
 * @see  https://getcomposer.org/doc/01-basic-usage.md#package-versions
 * @see  xp://xp.glue.Dependency
 * @see  php://version_compare
 * @test xp://xp.glue.unittest.RequirementTest
 */
class Requirement extends \lang\Object {
  protected $spec;
  protected $compare;

  protected function normalize($spec, $offset= 0) {
    $minor= $patch= 0;
    sscanf(substr($spec, $offset), '%d.%d.%d', $major, $minor, $patch);
    return sprintf('%d.%d.%d', $major, $minor, $patch);
  }

  /**
   * Creates a new project instance
   *
   * @param  string $spec
   */
  public function __construct($spec) {
    $this->spec= $spec;

    foreach (explode(',', $spec) as $specifier) {
      if (strstr($specifier, '*')) {
        if (sscanf($specifier, '%d.%[0-9*.]', $major, $wildcard) < 2) {
          throw new \lang\FormatException('Invalid wildcard specifier "'.$spec.'"');
        }
        $this->compare[]= function($compare) use($specifier) {
          return 0 === strncmp($compare, $specifier, strlen($specifier)- 1);
        };
      } else if ('~' === $specifier{0}) {
        $c= sscanf($specifier, '~%d.%d.%d', $s, $m, $p);
        $lower= substr($specifier, 1);
        switch ($c) {
          case 2: $upper= sprintf('%d.0.0', $s + 1); break;
          case 3: $upper= sprintf('%d.%d.0', $s, $m + 1); break;
          default: throw new \lang\FormatException('Invalid next significant release specifier "'.$spec.'"');
        }
        $this->compare[]= function($compare) use($lower, $upper) {
          return version_compare($compare, $lower, 'ge') && version_compare($compare, $upper, 'lt');
        };
      } else if ('<' === $specifier{0} || '>' === $specifier{0}) {
        if ('=' === $specifier{1}) {
          $op= $specifier{0}.'=';
          $limit= $this->normalize($specifier, 2);
        } else {
          $op= $specifier{0};
          $limit= $this->normalize($specifier, 1);
        }
        $this->compare[]= function($compare) use($limit, $op) {
          return version_compare($compare, $limit, $op);
        };
      } else if ('!' === $specifier{0} && '=' === $specifier{1}) {
        $exact= $this->normalize(substr($specifier, 2));
        $this->compare[]= function($compare) use($exact) {
          return $compare !== $exact;
        };
      } else {
        $exact= $this->normalize($specifier);
        $this->compare[]= function($compare) use($exact) {
          return $compare === $exact;
        };
      }
    }
  }

  /** @return string */
  public function spec() {
    return $this->spec;
  }

  /**
   * Compares this requirement against a given version
   *
   * @param  string $version
   * @return bool
   */
  public function matches($version) {
    foreach ($this->compare as $f) {
      if (!$f($version)) return false;
    }
    return true;
  }

  /**
   * Creates a string representation
   *
   * @return string
   */
  public function toString() {
    return $this->getClassName().'<'.$this->spec.'>';
  }
}