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

/**
 * CLI cron
 *
 * This script looks through all the module directories for cron.php files
 * and runs them.  These files can contain cleanup functions, email functions
 * or anything that needs to be run on a regular basis.
 * this script will override a virtual Moodle identity on base of an input parameter
 *
 * @package    local_vmoodle
 * @subpackage local
 * @copyright  2008 Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
global $CLI_VMOODLE_PRECHECK;

$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

// Config preload to get real roots.
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/lib/clilib.php');      // Cli only functions.
require_once($CFG->dirroot.'/lib/cronlib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('help' => false,
                                                     'debug' => false,
                                                     'host' => false),
                                               array('h' => 'help',
                                                     'd' => 'debug',
                                                     'H' => 'host'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error($unrecognized." is not a recognized option\n");
}

if ($options['help']) {
    $help = "
Execute periodic cron actions.

Options:
-h, --help            Print out this help
-d, --debug           Forces cron to run with debugging mode.
-H, --host            The host name to work for

Example:
\$sudo -u www-data /usr/bin/php admin/cli/cron.php
";

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
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
}
echo('Config check : playing for '.$CFG->wwwroot);

if (!empty($options['debug'])) {
    switch ($options['debug']) {
        case 'minimal': {
            $CFG->debug = DEBUG_MINIMAL;
        }

        case 'normal': {
            $CFG->debug = DEBUG_NORMAL;
        }

        case 'all': {
            $CFG->debug = DEBUG_ALL;
        }

        case 'developer': {
            $CFG->debug = DEBUG_DEVELOPER;
        }
    }
}

cron_run();
