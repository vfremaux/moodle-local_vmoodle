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
 * This script creates config.php file and prepares database.
 *
 * This script is not intended for beginners!
 * Potential problems:
 * - su to apache account or sudo before execution
 * - not compatible with Windows platform
 *
 * @package    local_vmoodle
 * @category local
 * @subpackage cli
 * @revised by Valery Fremaux for VMoodle upgrades
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Force OPcache reset if used, we do not want any stale caches
// When detecting if upgrade necessary or when running upgrade.
if (function_exists('opcache_reset') && !isset($_SERVER['REMOTE_ADDR'])) {
    opcache_reset();
}

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true;

// Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

if (!isset($CFG->dirroot)) {
    die ('$CFG->dirroot must be explicitely defined in moodle config.php for this script to be used');
}

require_once($CFG->dirroot.'/lib/clilib.php');

// Cli only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array('debug'             => false,
          'host'              => false,
          'help'              => false
    ),
    array('h' => 'help',
          'H' => 'host',
          'd' => 'debug'
    )
);

$interactive = empty($options['non-interactive']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
VMoodle fix Rpcs.
While installing or upgrading VMoodle infrastructure, There is a bug in moodle for handling Mnet functions
in subplugins of local plugins. This script fixes the xmlrpcpaths of VMoodle subplugins to be correctly located.
This script should be triggers after a moodle upgrade when VMoodle is upgraded.

Options:
    -H, --host            Switches to this host virtual configuration before processing
    -d, --debug           Set debug mode on
    -h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/fix_vmoodle_rpcs.php --host=http://my.virtual.moodle.org
"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
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
echo 'Config check : playing for '.$CFG->wwwroot."\n";

require_once($CFG->dirroot.'/local/vmoodle/db/install.php');

if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

xmldb_local_vmoodle_late_install();

exit(0); // 0 means success.
