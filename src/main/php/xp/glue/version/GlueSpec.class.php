<?php namespace xp\glue\version;

use lang\FormatException;

/**
 * Parses a glue version requirement specification
 *
 * | Specifier         | Meaning |
 * | ----------------- | --------|
 * | `1.0.0`, `1.0`    | Exact version match required. |
 * | `!=1.0.0`         | Any version not equal to `1.0.0` will match this. |
 * | `>1.2`, `>=1.2.3` | A greater than / great than or equal to constraint. |
 * | `<1.2`, `<=1.2.3` | A less than / less than or equal to constraint. |
 * | `>=1.2,<1.3`      | Use commas to separate multiple conditions applied with a logical **and**. |
 * | `~1.2`            | The next significant release, meaning `>=1.2,<2.0`, so any 1.x version is OK. |
 * | `1.2.*`           | Any version starting with `1.2` matches this wildcard. |
 *
 * @test xp://xp.glue.unittest.version.GlueSpecTest
 * @see  https://getcomposer.org/doc/01-basic-usage.md#package-versions
 */
class GlueSpec extends RequirementsParser {

  /**
   * Normalize a specifier to three-digit notation
   *
   * @param  string $spec
   * @param  int $offset
   * @return string
   * @throws lang.FormatException
   */
  protected function normalize($spec, $offset= 0) {
    $scan= substr($spec, $offset);
    if (!preg_match('/^([0-9]+)(\.([0-9]+)(\.([0-9]+))?((alpha|beta|RC|pl)([0-9]+))?)?$/', $scan, $matches)) {
      throw new FormatException('Invalid specifier "'.$scan.'"');
    }
    return sprintf(
      '%d.%d.%d%s',
      $matches[1],
      isset($matches[3]) ? $matches[3] : 0,
      isset($matches[5]) ? $matches[5] : 0,
      isset($matches[6]) ? $matches[6] : ''
    );
  }

  /**
   * Parses a specification
   *
   * @param  string $spec
   * @return xp.glue.version.Requirement
   */
  public function parse($spec) {
    $conditions= [];
    foreach (explode(',', $spec) as $specifier) {
      $specifier= trim($specifier);
      if ('' === $specifier) {
        throw new FormatException('Invalid specifier: <empty>');
      } else if (strstr($specifier, '*')) {
        if (sscanf($specifier, '%d.%[0-9*.]', $major, $wildcard) < 2) {
          throw new FormatException('Invalid wildcard specifier "'.$spec.'"');
        }
        $conditions[]= new StartsWith(substr($specifier, 0, -1));
      } else if ('~' === $specifier{0}) {
        $c= sscanf($specifier, '~%d.%d.%d', $s, $m, $p);
        $lower= substr($specifier, 1);
        switch ($c) {
          case 2: $upper= sprintf('%d.0.0', $s + 1); break;
          case 3: $upper= sprintf('%d.%d.0', $s, $m + 1); break;
          default: throw new FormatException('Invalid next significant release specifier "'.$spec.'"');
        }
        $conditions[]= new AllOf([new CompareUsing('>=', $lower), new CompareUsing('<', $upper)]);
      } else if ('<' === $specifier{0} || '>' === $specifier{0}) {
        if ('=' === $specifier{1}) {
          $op= $specifier{0}.'=';
          $limit= $this->normalize($specifier, 2);
        } else {
          $op= $specifier{0};
          $limit= $this->normalize($specifier, 1);
        }
        $conditions[]= new CompareUsing($op, $limit);
      } else if ('!' === $specifier{0} && '=' === $specifier{1}) {
        $conditions[]= new Exclude($this->normalize($specifier, 2));
      } else {
        $conditions[]= new Equals($this->normalize($specifier));
      }
    }

    return $this->requirementOf($conditions);
  }
}