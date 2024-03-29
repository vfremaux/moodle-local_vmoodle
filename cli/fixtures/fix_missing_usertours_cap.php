<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

// Security.

define('CLI_SCRIPT', true);
global $CLI_VMOODLE_PRECHECK;

$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.
require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array('verbose' => false,
          'help'    => false,
          'host'    => false,
          'debug'   => false
    ),
    array('h' => 'help',
          'v' => 'verbose',
          'H' => 'host',
          'd' => 'debug'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error($unrecognized.' are not recognized options ');
}

if ($options['help']) {
    $help = "
    Fixes missing capability for usertours after a 31 => 35 migration.

    Options:
    --verbose           Provides lot of output
    -h, --help          Print out this help
    -H, --host          Set the host (physical or virtual) to operate on
    -d, --debug         Turns debug mode on.
"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

if (!defined('MOODLE_INTERNAL')) {
    require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php'); // Global moodle config file.
    echo('Config check : playing for '.$CFG->wwwroot);
}

if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

require_once($CFG->dirroot.'/local/vmoodle/fixtures/fix_missing_usertours_cap_lib.php'); // Fixture primitives.

fix_usertours_missing_capabilities();