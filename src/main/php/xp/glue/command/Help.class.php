<?php namespace xp\glue\command;

use util\cmd\Console;

/**
 * Shows help
 */
class Help extends Command {

  public function execute(array $args) {
    Console::writeLine('Usage: glue <command> [args...]');
    Console::writeLine();
    Console::writeLine('- glue init    : Initialize glue project');
    Console::writeLine('- glue install : Installs dependencies');
    return 0xff;
  }
}