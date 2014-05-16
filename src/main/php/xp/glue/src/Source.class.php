<?php namespace xp\glue\src;

/**
 * Source base class
 */
abstract class Source extends \lang\Object {

  abstract public function fetch($vendor, $name, $version);
}