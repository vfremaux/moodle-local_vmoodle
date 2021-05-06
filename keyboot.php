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
 * @package local_vmoodle
 * @category local
 *
 * this script is indented to provide a secured mechanism to reboot the initial local MNET key
 * when newly instanciated. This results in executing a primary $mnet->replace_keys(), so the new
 * instance has a valid own MNET setup. This script must be checked against security concerns as
 * not being accessible from any unkown host. The way we know our trusted master is to checkback
 * the incoming public key and search for a matching key in known hosts.
 *
 * This is a first security check that might not prevent for key steeling attacks.
 *
 * We cannot use usual MNET functions as impacting on behaviour of core mnet lib. this script can only be used once
 * at platform instanciation.
 *
 */
require('../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/debuglib.php'); // Fakes existance of a debug lib.

require_once($CFG->dirroot.'/mnet/lib.php');

/*
 * This is a workaround to $_POST loosing long values.
 * @see http://stackoverflow.com/questions/5077969/php-some-post-values-missing-but-are-present-in-php-input
 */
$_POST = get_real_post();

$test = 0;
$masterpk = required_param('pk', PARAM_RAW);

if (!$test) {
    if (empty($masterpk)) {
        echo "ERROR : Empty PK ";
    }
}

/*
 * avoid shooting in yourself (@see locallib.php�vmoodle_fix_database() )
 * VMoodle Master identity has been forced in remote database with its current public key, so we should find it.
 * whatever the case, the master record is always added as an "extra" mnet_host record, after "self", and "all Hosts".
 */

$select = " TRIM(REPLACE(public_key, '\r', '')) = TRIM(REPLACE('$masterpk', '\r', '')) AND id > 1 ";
$remotehost = $DB->get_record_select('mnet_host', $select);

if ($remotehost || $test) {

    if (function_exists('debug_trace')) {
        debug_trace("Calling Host found with the incomming key as $remotehost->wwwroot ");
    }

    /*
     * $CFG->bootstrap_init is a key that has been added by master when postprocessing the deployment template
     * We check that the public key given matches the identity of the master who initiated the platform restoring.
     */

    // Get it hard.
    $initroot = $DB->get_field('config', array('name' => 'bootstrap_init'));

    if ($test || ($initroot == $remotehost->wwwroot)) {

        if (function_exists('debug_trace')) {
            debug_trace("Calling Host identity verified as accepted booter.");
        }

        /*
         * at this time, the local platform may not have self key, or may inherit
         * an obsolete key from the template SQL backup.
         * we must fix that forcing a local key replacemen
         */
        $mnet = new mnet_environment();
        $mnet->init();
        $mnet->name = '';
        $oldkey = $mnet->public_key;
        $mnet->replace_keys();

        // Finally we disable the keyboot script locking definitively the door.
        set_config('bootstrap_init', null);
        if (function_exists('debug_trace')) {
            debug_trace("Bootkey window closed.");
        }
        echo "SUCCESS";

    } else {
        echo "ERROR : Calling net booting host {$remotehost->wwwroot} don't match with master : {$initroot}";
    }
} else {
    echo "ERROR : Master host not found or master host key is empty";
}

function get_real_post() {
    $pairs = explode("&", file_get_contents("php://input"));
    $vars = array();
    if (!empty($pairs)) {
        foreach ($pairs as $pair) {
            if (empty($pair)) {
                continue;
            }
            $nv = explode("=", $pair);
            $name = urldecode($nv[0]);
            $value = urldecode($nv[1]);
            $vars[$name] = $value;
        }
    }
    return $vars;
}