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
 * @copyright  2009 Valery Fremaux (http://www.mylearningfactory.com)
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
    array('bindhost'          => false,
          'subnet'            => false,
          'debug'             => false,
          'host'              => false,
          'test'              => false,
          'help'              => false),
    array('b' => 'bindhost',
          's' => 'subnet',
          'd' => 'debug',
          'h' => 'help',
          'H' => 'host')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("This option is not recognized: $unrecognized");
}

if ($options['help']) {
    $help = "
Command line Moodle MNET init.
Please note you must execute this script with the same uid as apache!

Site defaults may be changed via local/defaults.php.

Options:
    -b, --bindhost        Remote host to bind to. If bind host is 'subs', then we perform a master to child binding. If set to a vmoodle wwwroot
                          , will bind the child to the master and it's subnet peers.
    -s, --subnet          An optional vmoodle subnet number. If given, changes the host vmoodle subnet.
    -H, --host            Switches to this host virtual configuration before processing.
    -h, --help            Print out this help.
    -d, --debug           Turns on debug mode.

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/init_mnet_node.php --host=http://my.virtual.moodle.org ---bind=http://my.master.moodle.org

Binding master to subs :
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/init_mnet_node.php --host=http://my.virtual.moodle.org ---bind=subs

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

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
echo 'Config check : playing for '.$CFG->wwwroot."\n";

if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

require_once($CFG->dirroot.'/mnet/environment.php');
require_once($CFG->dirroot.'/mnet/lib.php');
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

echo "Starting MNET environment\n";

global $MNET;

$mnetstate = get_config('moodle', 'mnet_dispatcher_mode');
if ($mnetstate != 'strict') {
    set_config('mnet_dispatcher_mode', 'strict');
    $MNET = new mnet_environment();
    $MNET->init();
    // Ensure we have a fresh key ourself.
    $MNET->replace_keys();
}
cache_helper::invalidate_by_definition('core', 'config');

if ($options['bindhost'] == 'subs') {
    $subs = $DB->get_records('local_vmoodle', array('enabled' => 1));
    foreach ($subs as $sub) {
        bind($MNET, $sub);
    }
    echo "Mnet binding service successful (main to subs).\n";
} else {
    bind($MNET, null, $options['bindhost']);
    echo "Mnet binding service successful (sub to main).\n";
}

exit(0); // 0 means success.


/**
 * Binds a vmoodle definition or an external url to us.
 *
 */
function bind($mnet, $vmoodlesub, $url = '') {
    global $DB, $MNET;
    static $application;

    if (empty($application)) {
        $application = $DB->get_record('mnet_application', array('name' => 'moodle'));
    }

    if (!empty($vmoodlesub)) {
        $remoteurl = $vmoodlesub->vhostname;
    } else {
        if (!empty($url)) {
            $remoteurl = $url;
        } else {
            die ("No url given to bind to\nExiting...\n");
        }
    }

    $mnetpeer = new mnet_peer();
    $mnetpeer->wwwroot = $remoteurl;
    $mnetpeer->bootstrap($mnetpeer->wwwroot, null, $application->id, true);
    $mnetpeer->commit();
    cache_helper::invalidate_by_definition('core', 'config');

    /*
     * Get default strategy for main to sub service exchanges.
     * All nodes in the network should have the same vmoodle services settings so
     * each node knows the main and the sub behaviour.
     */
    vmoodle_get_service_strategy(null, $mainstrategy, $peerstrategy, 'main');

    // Bind main services.
    if (!empty($vmoodlesub)) {
        // We are in a main.
        // Bind our main strategy to the peer (which is a sub).
        mtrace("Binding main strategy to peer $mnetpeer->name\n");
        try {
            vmoodle_bind_services($mnetpeer, $mainstrategy);
        } catch (Exception $ex) {
            die("Worker has thrown exception : ".$ex->getMessage()."\n");
        }

    } else {
        // We are in a sub so the peer strategy applies to us regarding the mnetpeer (which is main)
        // Bind the sub strategy to main.
        mtrace("Binding sub strategy to peer $mnetpeer->name\n");
        vmoodle_bind_services($mnetpeer, $peerstrategy);
    }
}