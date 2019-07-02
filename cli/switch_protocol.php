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
 * @package    local_vmoodle
 * @category local
 * @subpackage cli
 * @revised by Valery Fremaux for VMoodle upgrades
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
    array('host'              => false,
          'protocol'          => false,
          'help'              => false,
          'debug'             => false,
          ),
    array('h' => 'help',
          'H' => 'host',
          'p' => 'protocol',
          'd' => 'debug',
          )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("This option is not recognized: $unrecognized");
}

if ($options['help']) {
    $help = "
Command line for switching protocol from http to https.
Please note you must execute this script with the same uid as apache!

Prerequisites : your main moodle needs being already switched to https and certificates
match all subdomains.

Options:
    --host                Switches to this host virtual configuration before processing.
    -p, --protocol        the target protocol, http or https.
    -h, --help            Print out this help.
    -d, --debug           Turn on debug mode.

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/switch_protocol.php --host=http://my.virtual.moodle.org

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

if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

if (empty($options['protocol'])) {
    die("Protocol option is mandatory.\n");
}

if ($options['protocol'] == 'https') {
    $sql = "
        UPDATE
            {mnet_host}
        SET
            wwwroot = REPLACE(wwwroot, 'http://', 'https://')
    ";
} else {
    $sql = "
        UPDATE
            {mnet_host}
        SET
            wwwroot = REPLACE(wwwroot, 'https://', 'http://')
    ";
}

$DB->execute($sql);

if ($options['protocol'] == 'https') {
    $sql = "
        UPDATE
            {local_vmoodle}
        SET
            vhostname = REPLACE(vhostname, 'http://', 'https://')
    ";
} else {
    $sql = "
        UPDATE
            {local_vmoodle}
        SET
            vhostname = REPLACE(vhostname, 'https://', 'http://')
    ";
}

$DB->execute($sql);


exit(0);