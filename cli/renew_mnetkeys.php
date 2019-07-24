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
 * This scripts renews the local key and force distribution of the key to all
 * known peers.
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
          'help'              => false,
          'debug'             => false,
          ),
    array('h' => 'help',
          'H' => 'host',
          'd' => 'debug',
          )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("This option is not recognized: $unrecognized");
}

if ($options['help']) {
    $help = "
Command line for renewing all peer ssh keys.
Please note you should execute this script with the same uid as apache!

Options:
    --host                Switches to this host virtual configuration before processing.
    -h, --help            Print out this help.
    -d, --debug           Turn on debug mode.

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/renew_mnetkeys.php --host=http://my.virtual.moodle.org

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
require_once($CFG->dirroot.'/mnet/lib.php');

if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

echo "Start.\n";

// Restart local mnet.
$MNET = new mnet_environment();
// Ensure we have a fresh key ourself.
$MNET->replace_keys();

echo "Mnet config updated.\n";

/* Exchange new key with all known and active peers. This relies on special VMoodle 
 * patch that allows forcing key renewal by hosts that are already known with an old key
 * (and NOT being explicitely deleted in MNET register).
 */
$mnetpeers = $DB->get_records('mnet_host', array('deleted' => 0));
if ($mnetpeers) {
    foreach ($mnetpeers as $peer) {

        if (($peer->id == $CFG->mnet_all_hosts_id) || ($peer->id == $CFG->mnet_localhost_id)) {
            echo 'Skipping match all or self '."\n";
            continue;
        }

        echo 'Renewing for '.$peer->wwwroot."\n";

        $application = $DB->get_record('mnet_application', array('id' => $peer->applicationid));

        $mnetpeer = new mnet_peer();
        $mnetpeer->set_wwwroot($peer->wwwroot);

        /*
         * get the sessions for each vmoodle that have same ID Number
         * we use a force = 1 (3rd) parameter to force fetching the key remotely anyway
         * based on mnet vmoodle pached version.
         */
        $currentkey = mnet_get_public_key($mnetpeer->wwwroot, $application, 1); // Use "force" to force renewing.
        if ($currentkey) {
            $mnetpeer->public_key = clean_param($currentkey, PARAM_PEM);
            $mnetpeer->updateparams = new StdClass();
            $mnetpeer->updateparams->public_key = clean_param($currentkey, PARAM_PEM);
            $mnetpeer->public_key_expires = $mnetpeer->check_common_name($currentkey);
            $mnetpeer->updateparams->public_key_expires = $mnetpeer->check_common_name($currentkey);
            $mnetpeer->commit();
            mtrace('My key renewed at '.$peer->wwwroot.' till '.userdate($mnetpeer->public_key_expires));

        } else {
            mtrace('Failed renewing key with '.$peer->wwwroot."\n");
        }
    }
}

exit(0);