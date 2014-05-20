<?php namespace xp\glue\version;

/**
 * Base class for requirements spec parsing
 */
abstract class RequirementsParser extends \lang\Object {

  /**
   * Parses a specification
   *
   * @param  string $spec
   * @return xp.glue.version.Requirement
   */
  public abstract function parse($spec);

  /**
   * Creates new Requirement instances from a given list of conditions.
   * Optimizes the case when only one condition is available.
   *
   * @param  xp.glue.version.Condition[] $conditions
   * @return xp.glue.version.Requirement
   */
  protected function requirementOf($conditions) {
    if (1 === sizeof($conditions)) {
      return new Requirement($conditions[0]);
    } else {
      return new Requirement(new AllOf($conditions));
    }
  }
}