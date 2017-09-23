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
 * Enable or disable maintenance mode.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/lib/clilib.php'); // Cli only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array(
        'enable'=>false,
        'enablelater'=>0,
        'enableold'=>false,
        'disable'=>false,
        'help'=>false,
        'host'=>false
    ),
    array(
        'h'=>'help',
        'H' => 'host'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    echo "$unrecognized is not recognized\n";
    exit(1);
}

if ($options['help']) {
    $help =
"Maintenance mode settings.
Current status displayed if not option specified.

Options:
--enable              Enable CLI maintenance mode
--enablelater=MINUTES Number of minutes before entering CLI maintenance mode
--enableold           Enable legacy half-maintenance mode
--disable             Disable maintenance mode
-h, --help            Print out this help
-H, --host            Adds the host configuration

Example:
\$ sudo -u www-data /usr/bin/php admin/cli/maintenance.php --host=<moodleroot>
"; //TODO: localize - to be translated later when everything is finished

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
echo('Config check : playing for '.$CFG->wwwroot."\n");

require_once("$CFG->libdir/adminlib.php");

cli_heading(get_string('sitemaintenancemode', 'admin')." ($CFG->wwwroot)");

if ($options['enablelater']) {
    if (file_exists("$CFG->dataroot/climaintenance.html")) {
        // Already enabled, sorry.
        echo get_string('clistatusenabled', 'admin')."\n";
        return 1;
    }

    $time = time() + ($options['enablelater']*60);
    set_config('maintenance_later', $time);

    echo get_string('clistatusenabledlater', 'admin', userdate($time))."\n";
    return 0;

} else if ($options['enable']) {
    if (file_exists("$CFG->dataroot/climaintenance.html")) {
        // The maintenance is already enabled, nothing to do.
    } else {
        enable_cli_maintenance_mode();
    }
    set_config('maintenance_enabled', 0);
    unset_config('maintenance_later');
    echo get_string('sitemaintenanceoncli', 'admin')."\n";
    exit(0);

} else if ($options['enableold']) {
    set_config('maintenance_enabled', 1);
    unset_config('maintenance_later');
    echo get_string('sitemaintenanceon', 'admin')."\n";
    exit(0);

} else if ($options['disable']) {
    set_config('maintenance_enabled', 0);
    unset_config('maintenance_later');
    if (file_exists("$CFG->dataroot/climaintenance.html")) {
        unlink("$CFG->dataroot/climaintenance.html");
    }
    echo get_string('sitemaintenanceoff', 'admin')."\n";
    exit(0);
}

if (!empty($CFG->maintenance_enabled) or file_exists("$CFG->dataroot/climaintenance.html")) {
    echo get_string('clistatusenabled', 'admin')."\n";

} else if (isset($CFG->maintenance_later)) {
    echo get_string('clistatusenabledlater', 'admin', userdate($CFG->maintenance_later))."\n";

} else {
    echo get_string('clistatusdisabled', 'admin')."\n";
}

exit(0);
