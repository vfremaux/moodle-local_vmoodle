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
 * check keys and renew with peers.
 * @package     local_vmoodle
 * @category    local
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/vmoodle/classes/Mnet_Peer.php');
require_once($CFG->dirroot.'/mnet/peer.php');
require_once($CFG->dirroot.'/mnet/lib.php');

use \local_vmoodle\Mnet_Peer;

// Requires patching /mnet/xmlrpc/server.php for mnet_keyswap().
// Requires patching /mnet/lib.php for mnet_keyswap().
function cron_check_mnet_keys() {
    global $DB, $CFG;

    $mnet = get_mnet_environment();

    mtrace("Cron automatic rotation for MNET keys...\n");

    $config = get_config('local_vmoodle');

    // Setting some defaults if the vmoodle config has not been setup.
    if (!isset($config->mnet_key_autorenew_gap)) {
        // Three days.
        set_config('mnet_key_autorenew_gap', 24 * 3, 'local_vmoodle');
    }
    if (!isset($config->mnet_key_autorenew)) {
        // Not activated as a default.
        set_config('mnet_key_autorenew', 0, 'local_vmoodle');
    }
    if (!isset($config->mnet_key_autorenew_hour)) {
        // Midnight.
        set_config('mnet_key_autorenew_hour', 0, 'local_vmoodle');
    }
    if (!isset($config->mnet_key_autorenew_min)) {
        // Midnight.
        set_config('mnet_key_autorenew_min', 0, 'local_vmoodle');
    }

    $config->mnet_key_autorenew_time = $config->mnet_key_autorenew_hour * HOURSECS + $config->mnet_key_autorenew_min * MINSECS;

    // If autorenewal is enabled and we are mnetworking.
    if (!empty($config->mnet_key_autorenew) && ($CFG->mnet_dispatcher_mode != 'none')) {

        // Check if key is getting obsolete.
        $havetorenew = 0;
        $trace = '';

        // Key is getting old : check if it is time to operate.
        if ($mnet->public_key_expires - time() < $config->mnet_key_autorenew_gap * HOURSECS) {

            /*
             * this one is needed as temporary global toggle between distinct cron invocations,
             * but should not be changed through the GUI
             */
            if (empty($config->mnet_autorenew_haveto)) {
                set_config('mnet_autorenew_haveto', 1);
                mtrace('Local key is expiring. Need renewing MNET keys...');
                $trace .= userdate(time()).' SET KEY RENEW ON on '.$CFG->wwwroot."\n";
            } else {

                if (!empty($config->mnet_key_autorenew_time)) {
                    $now = getdate(time());
                    if (($now['hours'] * HOURSECS + $now['minutes'] * MINSECS) > $config->mnet_key_autorenew_time) {
                        $havetorenew = 1;
                    }
                } else {
                    $havetorenew = 1;
                }
            }
        }

        // Renew if needed. This only works for web triggrered cron processing.
        $force = optional_param('forcerenew', 0, PARAM_INT);
        if ($force) {
            mtrace("forced mode");
        }

        if ($havetorenew || $force) {
            mtrace("Local key will expire very soon. Renew MNET keys now !!...\n");
            // Renew local key.

            $mnet->replace_keys();

            // Send new key using key exchange transportation.

            // Make a key and exchange it with all known and active peers.
            $mnetpeers = $DB->get_records('mnet_host', array('deleted' => 0));
            if ($mnetpeers) {
                foreach ($mnetpeers as $peer) {

                    if (($peer->id == $CFG->mnet_all_hosts_id) || ($peer->id == $CFG->mnet_localhost_id)) {
                        continue;
                    }

                    $application = $DB->get_record('mnet_application', array('id' => $peer->applicationid));

                    $mnetpeer = new mnet_peer();
                    $mnetpeer->set_wwwroot($peer->wwwroot);
                    /*
                     * get the sessions for each vmoodle that have same ID Number
                     * we use a force parameter to force fetching the key remotely anyway
                     */
                    $currentkey = mnet_get_public_key($mnetpeer->wwwroot, $application, 1);
                    if ($currentkey) {
                        $mnetpeer->public_key = clean_param($currentkey, PARAM_PEM);
                        $mnetpeer->updateparams = new StdClass();
                        $mnetpeer->updateparams->public_key = clean_param($currentkey, PARAM_PEM);
                        $mnetpeer->public_key_expires = $mnetpeer->check_common_name($currentkey);
                        $mnetpeer->updateparams->public_key_expires = $mnetpeer->check_common_name($currentkey);
                        $mnetpeer->commit();
                        mtrace('My key renewed at '.$peer->wwwroot.' till '.userdate($mnetpeer->public_key_expires));
                        $trace .= userdate(time()).' KEY RENEW from '.$CFG->wwwroot.' to '.$peer->wwwroot." suceeded\n";
                    } else {
                        mtrace('Failed renewing key with '.$peer->wwwroot."\n");
                        $trace .= userdate(time()).' KEY RENEW from '.$CFG->wwwroot.' to '.$peer->wwwroot." failed\n";
                    }
                }
            }
            set_config('mnet_autorenew_haveto', 0);
            $trace .= userdate(time()).' RESET KEY RENEW on '.$CFG->wwwroot."\n";

            // Record trace in trace file (hidden config key).
            if (!empty($CFG->tracevmoodlekeyrenew)) {
                if ($trace = fopen($CFG->dataroot.'/vmoodle_renew.log', 'w+')) {
                    fputs($trace, $trace);
                    fclose($trace);
                }
            }
        }
    } else {
        mtrace("VMoodle Autorenew mode OFF");
    }
}
