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
 * @package    local_vmoodle
 * @subpackage cli
 * @copyright  2017 Valery Fremaux (valery.fremaux@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

if (!isset($CFG->dirroot)) {
    die ('$CFG->dirroot must be explicitely defined in moodle config.php for this script to be used');
}

require_once($CFG->dirroot.'/lib/clilib.php'); // Cli only functions.

list($options, $unrecognized) = cli_get_params(
    array('help' => false,
          'host' => false,
          'debug' => false,
    ),
    array('h' => 'help',
          'H' => 'host',
          'd' => 'debug',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n", $unrecognized);
    cli_error("Not recognized options ".$unrecognized);
}

if ($options['help']) {
    $help = "
Batch converts all pending old assigns.

Options:
    -h, --help            Print out this help
    -H, --host            the virtual host you are working for
    -d, --debug           Turn on debug mode

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/upgrade_assignments.php
";

    echo $help;
    exit(0);
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
}
echo('Config check : playing for '.$CFG->wwwroot."\n");

if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/assignmentupgrade/locallib.php');
require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/assignmentupgrade/upgradableassignmentstable.php');
require_once($CFG->dirroot . '/'.$CFG->admin.'/tool/assignmentupgrade/upgradableassignmentsbatchform.php');

$current = 0;

mtrace("Converting old assignments...");

global $USER;
$admin = get_admin();
$USER = $admin;

$assignmentids = tool_assignmentupgrade_load_all_upgradable_assignmentids();

$total = count($assignmentids);

if ($assignmentids) {
    foreach ($assignmentids as $assignmentid) {
        list($summary, $success, $log) = tool_assignmentupgrade_upgrade_assignment($assignmentid);
        $current += 1;
        $params = array('current' => $current, 'total' => $total);
        mtrace("Progress : $current / $total");
        mtrace(' >> '.$summary->shortname.' '.$summary->name);
        mtrace('Success state : '.$success);
        mtrace($log);
    }
} else {
    mtrace("\t...Nothing to do");
}

mtrace('Done.');
exit(0);