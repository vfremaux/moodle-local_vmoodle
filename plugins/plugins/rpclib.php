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
 * Declare RPC functions for syncrolelib.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

require_once($CFG->dirroot.'/local/vmoodle/rpclib.php');
require_once($CFG->dirroot.'/mnet/xmlrpc/client.php');
require_once($CFG->dirroot.'/local/vmoodle/plugins/plugins/pluginscontrolslib.php');

if (!defined('RPC_SUCCESS')) {
    define('RPC_TEST', 100);
    define('RPC_SUCCESS', 200);
    define('RPC_FAILURE', 500);
    define('RPC_FAILURE_USER', 501);
    define('RPC_FAILURE_CONFIG', 502);
    define('RPC_FAILURE_DATA', 503); 
    define('RPC_FAILURE_CAPABILITY', 510);
    define('RPC_FAILURE_RECORD', 520);
    define('RPC_FAILURE_RUN', 521);
}

if (!defined('MNET_FAILURE')) {
    define('MNET_FAILURE', 511);
}

define('VMOODLE_PLUGIN_ENABLE', 1);
define('VMOODLE_PLUGIN_DISABLE', 0);

/**
 * Get role capabilities of a virtual platform.
 * @param mixed $user The calling user.
 * @param string $role The role to read capabilities.
 * @param mixed $capabilities The capabilities to read (optional / may be string or array).
 */
function mnetadmin_rpc_get_plugins_info($user, $plugintype, $jsonrequired = true) {
    global $CFG, $USER, $DB;

    // Invoke local user and check his rights.
    if ($auth_response = invoke_local_user((array)$user, 'local/vmoodle:execute')) {
        if ($jsonrequired) {
            return $auth_response;
        } else {
            return json_decode($auth_response);
        }
    }

    $response = new StdClass();
    $response->errors = array();
    $response->error = '';

    // Creating response.
    $response->status = RPC_SUCCESS;

    // Getting role.
    $pm = core_plugin_manager::instance();

    $allplugins = $pm->get_plugins();

    if (!array_key_exists($plugintype, $allplugins)) {
        $response->status = RPC_FAILURE_RECORD;
        $response->errors[] = "Non existant plugin type $plugintype.";
        $response->error = "Non existant plugin type $plugintype.";
        if ($jsonrequired) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    // Setting result value.
    $plugins = (array)$allplugins[$plugintype];
    // $response->value = (array)$allplugins[$plugintype];
    $response->value = array();

    $actionclass = $plugintype.'_remote_control';

    // Get activation status.
    foreach ($plugins as $pluginname => $foobar) {

        // Ignore non implemented.
        if (!class_exists($actionclass)) {
            debug_trace("failing running remote action on $actionclass. Class not found");
            continue;
        }

        $control = new $actionclass($pluginname);
        $response->value[$pluginname] = $control->is_enabled();
    }

    // Returning response.
    if ($jsonrequired) {
        return json_encode($response);
    } else {
        return $response;
    }
}

/**
 * Enables or disables a plugin of a virtual platform.
 * @param string $user The calling user.
 * @param string $plugininfos a structure with info for each plugin to setup.
 */
function mnetadmin_rpc_set_plugins_states($user, $plugintype, $plugininfos, $jsonrequired = true) {
    global $CFG, $USER, $DB;

    // Creating response.
    $response = new Stdclass();
    $response->status = RPC_SUCCESS;
    $response->errors = array();
    $response->error = '';

    // Invoke local user and check his rights.
    if ($auth_response = invoke_local_user((array)$user, 'local/vmoodle:execute')) {
        if ($jsonrequired) {
            // We could not have a credential.
            return $auth_response;
        }
        return json_decode($auth_response);
    }

    $actionclass = $plugintype.'_remote_control';

    // Non implemented.
    if (!class_exists($actionclass)) {
        $response->status = RPC_FAILURE;
        $response->errors[] = "mnetadmin_rpc_set_plugins_states : State control class not implmeented for type: $plugintype";
        $response->error = "mnetadmin_rpc_set_plugins_states : State control class not implmeented for type: $plugintype";
        if (function_exists('debug_trace')) {
            debug_trace("mnetadmin_rpc_set_plugins_states: failing running remote action on $actionclass. Class not found");
        }
        if ($jsonrequired) {
            // We could not have a credential.
            return $auth_response;
        }
        return json_decode($auth_response);
    }

    // Getting plugin enable/disable method.
    if (!empty($plugininfos)) {

        foreach ($plugininfos as $plugin => $state) {

            if ($state == 1) {
                $action = 'enable';
            } else {
                $action = 'disable';
            }

            if (function_exists('debug_trace')) {
                debug_trace("Setting state: $plugin with action $action using $actionclass");
            }

            $control = new $actionclass($plugin);

            $return = $control->action($action);
            if ($return !== 0) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = "Failed action $action on $plugin ";
                $response->error = "Failed action $action on $plugin ";
            }
        }

        cache_helper::invalidate_by_definition('core', 'plugin_manager');
        cache_helper::invalidate_by_definition('core', 'config');

    } else {
        if (function_exists('debug_trace')) {
            debug_trace("Empty plugininfo structure submitted");
        }
    }

    $response->error = implode(', ', $response->errors);

    // Returning response.
    if ($jsonrequired) {
        return json_encode($response);
    } else {
        return $response;
    }
}
