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
}