<?php namespace xp\glue\version;

use lang\FormatException;

/**
 * Parses Maven dependency versions and version ranges
 *
 * - `(, )`: Exclusive quantifiers
 * - `[, ]`: Inclusive quantifiers
 *
 * @see  http://books.sonatype.com/mvnref-book/reference/pom-relationships-sect-project-dependencies.html
 */
class MavenVersions extends RequirementsParser {

  /**
   * Parses a specification
   *
   * @param  string $spec
   * @return xp.glue.version.Requirement
   */
  public function parse($spec) {
    static $lo= ['(' => '>', '[' => '>='];
    static $hi= [')' => '<', ']' => '<='];

    if ('' === $spec) {
      throw new FormatException('Invalid dependency versions: <empty>');
    }

    if ('(' === $spec{0} || '[' === $spec{0}) {
      $l= strlen($spec) - 1;
      if (')' === $spec{$l} || ']' === $spec{$l}) {
        if (strstr($spec, ',')) {
          $limit= explode(',', substr($spec, 1, -1));
          if ('' !== $limit[0]) $conditions[]= new CompareUsing($lo[$spec{0}], $limit[0]);
          if ('' !== $limit[1]) $conditions[]= new CompareUsing($hi[$spec{$l}], $limit[1]);
        } else {
          $conditions= [new Equals(substr($spec, 1, -1))];
        }
      } else {
        throw new FormatException('Invalid dependency version range: "'.$spec.'"');
      }
    } else {
      $conditions= [new Preferred($spec)];
    }

    return $this->requirementOf($conditions);
  }
}