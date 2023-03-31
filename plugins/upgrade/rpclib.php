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
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/local/vmoodle/rpclib.php');
require_once($CFG->dirroot.'/local/vmoodle/lib.php');
require_once($CFG->libdir.'/adminlib.php');       // Various admin-only functions.
require_once($CFG->libdir.'/upgradelib.php');     // General upgrade/install related functions.
require_once($CFG->libdir.'/environmentlib.php');

if (!defined('RPC_SUCCESS')) {
    define('RPC_TEST', 100);
    define('RPC_SUCCESS', 200);
    define('RPC_FAILURE', 500);
    define('RPC_FAILURE_USER', 501);
    define('RPC_FAILURE_CONFIG', 502);
    define('RPC_FAILURE_DATA', 503);
    define('RPC_FAILURE_CAPABILITY', 510);
    define('MNET_FAILURE', 511);
    define('RPC_FAILURE_RECORD', 520);
    define('RPC_FAILURE_RUN', 521);
}

function mnetadmin_rpc_upgrade($user, $jsonresponse = true) {
    global $CFG, $USER;

    if (function_exists('debug_trace')) {
        debug_trace('RPC starts : Upgrade moodle');
    }

    raise_memory_limit(MEMORY_HUGE);
    @set_time_limit(0);

    // Invoke local user and check his rights.
    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonresponse) {
            return $auth_response;
        } else {
            return json_decode($auth_response);
        }
    }

    // Creating response.
    $response = new stdclass();
    $response->status = RPC_SUCCESS;

    require("$CFG->dirroot/version.php");       // Defines version, release, branch and maturity.
    $CFG->target_release = $release;            // Used during installation and upgrades.

    if ($version < $CFG->version) {
        $response->status = RPC_FAILURE_RUN;
        $response->error = get_string('downgradedcore', 'error');
        $response->errors[] = get_string('downgradedcore', 'error');
        if ($jsonresponse){
            return json_encode($response);
        } else {
            return $response;
        }
    }

    $oldversion = "$CFG->release ($CFG->version)";
    $newversion = "$release ($version)";

    if (!moodle_needs_upgrading()) {
        $response->message = get_string('cliupgradenoneed', 'core_admin', $newversion);
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    list($envstatus, $environment_results) = check_moodle_environment(normalize_version($release), ENV_SELECT_NEWER);
    if (!$envstatus) {
        $response->status = RPC_FAILURE_RUN;
        $response->error = vmoodle_get_string('environmentissues', 'vmoodleadminset_upgrade');
        $response->errors[] = vmoodle_get_string('environmentissues', 'vmoodleadminset_upgrade');
        $response->detail = $environment_results;
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    // Test plugin dependencies.
    $failed = array();
    if (!core_plugin_manager::instance()->all_plugins_ok($version, $failed)) {
        $response->status = RPC_FAILURE_RUN;
        $response->error = get_string('pluginschecktodo', 'admin');
        $response->errors[] = get_string('pluginschecktodo', 'admin');
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    if (function_exists('debug_trace')) {
        debug_trace('RPC starts : Starting upgrades');
    }

    ob_start();
    if ($version > $CFG->version) {
        upgrade_core($version, false);
    }
    set_config('release', $release);
    set_config('branch', $branch);

    // Unconditionally upgrade.
    upgrade_noncore(false);

    // Log in as admin - we need doanything permission when applying defaults.
    \core\session\manager::set_user(get_admin());

    // Apply all default settings, just in case do it twice to fill all defaults.
    admin_apply_default_settings(null, false);
    admin_apply_default_settings(null, false);
    ob_end_clean();

    $response->message = get_string('upgradecomplete', 'vmoodleadminset_upgrade', $newversion);

    if ($jsonresponse) {
        return json_encode($response);
    } else {
        return $response;
    }
}

function mnetadmin_rpc_checkstatus($user, $jsonresponse = true) {
    global $CFG, $USER;

    if (function_exists('debug_trace')) {
        debug_trace('RPC starts : Check Upgrade status');
    }

    raise_memory_limit(MEMORY_HUGE);
    @set_time_limit(0);

    // Invoke local user and check his rights.
    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonresponse) {
            return $auth_response;
        } else {
            return json_decode($auth_response);
        }
    }

    // Creating response.
    $response = new stdclass();
    $response->status = RPC_SUCCESS;

    require("$CFG->dirroot/version.php");       // Defines version, release, branch and maturity.
    $CFG->target_release = $release;            // Used during installation and upgrades.

    // Default success message.
    $a = new StdClass;
    $a->old = $oldversion = "$CFG->release ($CFG->version)";
    $a->new = $newversion = "$release ($version)";
    $response->message = get_string('upgradetodo', 'vmoodleadminset_upgrade', $a);

    if ($version < $CFG->version) {
        $response->message = get_string('downgradedcore', 'error');
    }

    if (!moodle_needs_upgrading()) {
        $response->message = get_string('cliupgradenoneed', 'core_admin', $newversion);
    }

    list($envstatus, $environment_results) = check_moodle_environment(normalize_version($release), ENV_SELECT_NEWER);
    if (!$envstatus) {
        $response->message = vmoodle_get_string('environmentissues', 'vmoodleadminset_upgrade');
        $response->message .= $environment_results;
    }

    // Test plugin dependencies.
    $failed = array();
    if (!core_plugin_manager::instance()->all_plugins_ok($version, $failed)) {
        $response->message = get_string('pluginschecktodo', 'admin');
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    if ($jsonresponse) {
        return json_encode($response);
    } else {
        return $response;
    }
}