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
 * Declare RPC function set and utilities for rpc calls.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 * The VMOODLING rpclib function set allow calling
 * Moodle management primitives over a full vmoodle secured network
 * Functions can only be invoked upon vmoodle peers that will check
 * the call comes from a legacy known mnet_host node.
 * Calling that function is submited to local and remote authorisation
 * using peer configuration
 *
 * All calls are checked before complete execution against a local
 * translated user that MUST have capability of perfoming remote
 * management tasks (bock/vmoodle:canexecuteremotecalls)
 */

// Including libraries.
require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->libdir.'/dmllib.php');

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

/**
 * Invoke the local user who make the RPC call and check his rights.
 * @param object $user The calling user.
 * @param string $capability The capability to check.
 * @param int $context The capability's context (optional / CONTEXT_SYSTEM by default).
 */
function invoke_local_user($user, $capability = false, $context = null) {
    global $CFG, $USER, $DB;

    // Creating response.
    $response = new stdclass;
    $response->status = RPC_SUCCESS;

    // Checking user.
    if (!array_key_exists('username', $user) ||
            !array_key_exists('remoteuserhostroot', $user) ||
                    !array_key_exists('remotehostroot', $user)) {
        debug_trace("USER CHECK FAILED 1 bad user structure : ".json_encode($user));
        $response->status = RPC_FAILURE_USER;
        $response->errors[] = 'Bad client user format.';
        $response->error = 'Bad client user format.';
        return(json_encode($response));
    }

    if (empty($user['username'])) {
        debug_trace("USER CHECK FAILED 2 empty username : ".json_encode($user));
        $response->status = RPC_FAILURE_USER;
        $response->errors[] = 'Empty username.';
        $response->error = 'Empty username.';
        return(json_encode($response));
    }

    // Check user mnet host single identity condition
    if (is_dir($CFG->dirroot.'/blocks/user_mnet_hosts')) {
        $config = get_config('block_user_mnet_hosts');
        if (!empty($config->singleaccountcheck) && ($user['username'] != 'admin')) {
            $params = array('username' => addslashes($user['username']), 'deleted' => 0);
            if ($localuser = $DB->get_record('user', $params)) {
                debug_trace("USER CHECK SUCCESS : ".json_encode($user));
                $USER = $localuser;
                return '';
            }
        }
    }

    // Get local identity.
    if (!$remotehost = $DB->get_record('mnet_host', array('wwwroot' => $user['remotehostroot']))) {
        debug_trace("USER CHECK FAILED 3 (unregistered host) : ".json_encode($user));
        $response->status = RPC_FAILURE;
        $response->errors[] = 'Calling host is not registered. Check MNET configuration';
        $response->error = 'Calling host is not registered. Check MNET configuration';
        return(json_encode($response));
    }

    $userhost = $DB->get_record('mnet_host', array('wwwroot' => $user['remoteuserhostroot']));

    /*
     * special case : incoming user is Primary Moodle admin. Let go as super administrator and endorse local admin 
     * for operations. this is a super privilege to do anything.
     */
    if (($user['username'] == 'admin') && ($userhost->wwwroot == $CFG->mainwwwroot)) {
        $USER = get_admin();
        return '';
    }

    if (!$localuser = $DB->get_record('user', array('username' => addslashes($user['username']), 'mnethostid' => $userhost->id))) {
        debug_trace("USER CHECK FAILED 4 (account) : ".json_encode($user));
        $response->status = RPC_FAILURE_USER;
        $response->errors[] = "Calling user has no local account. Register remote user first";
        $response->error = "Calling user has no local account. Register remote user first";
        return(json_encode($response));
    }
    // Replacing current user by remote user.

    $USER = $localuser;

    // Checking capabilities.
    if ($capability) {
        if (is_null($context)) {
            $context = context_system::instance();
        }

        if (!has_capability($capability, $context)) {
            debug_trace("USER CHECK FAILED 5 (capability check) : ".json_encode($user));
            $response->status = RPC_FAILURE_CAPABILITY;
            $response->errors[] = 'Local user\'s identity has no capability to run';
            $response->error = 'Local user\'s identity has no capability to run';
            return(json_encode($response));
        }
    }
    return '';
}

/**
 * Adds a new peer to the known hosts, with its public key. Binding
 * an old record (deleted) will revive it.
 * @param string $username The calling user.
 * @param string $userhost The calling user's host.
 * @param string $remotehost The calling host.
 * @param array $new_peer The peer to add as a complete mnet_host record.
 */
function mnetadmin_rpc_bind_peer($username, $userhost, $remotehost, $new_peer, $servicestrategy) {
    global $CFG, $USER, $DB;

    // Invoke distant user who makes the call and checks his rights.
    $user['username'] = $username;
    $user['remoteuserhostroot'] = $userhost;
    $user['remotehostroot'] = $remotehost;

    debug_trace('RPC '.json_encode($user));

    invoke_local_user($user, 'local/vmoodle:managevmoodles');

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    // Add the new peer.
    $peerobj = (object)$new_peer;
    unset($peerobj->id);

    if ($oldpeer = $DB->get_record('mnet_host', array('wwwroot' => $peerobj->wwwroot))) {
        $peerobj->id = $oldpeer->id;
        if (!$DB->update_record('mnet_host', $peerobj)) {
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = 'Error renewing the mnet record';
            $response->error = 'Error renewing the mnet record';
            return json_encode($response);
        }
    } else {
        if (!$peerobj->id = $DB->insert_record('mnet_host', $peerobj)) {
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = 'Error recording the mnet record';
            $response->error = 'Error recording the mnet record';
            return json_encode($response);
        }
    }

    debug_trace('RPC : Binding service strategy');
    // Bind the service strategy.
    if (!empty($servicestrategy)) {
        $DB->delete_records('mnet_host2service', array('hostid' => $peerobj->id)); // Eventually deletes something on the way.
        foreach ($servicestrategy as $servicename => $servicestate) {
            $servicestate = (object)$servicestate; // Ensure it is object.
            $service = $DB->get_record('mnet_service', array('name' => $servicename));
            $host2service = new stdclass();
            $host2service->hostid = $peerobj->id;
            $host2service->serviceid = $service->id;
            $host2service->publish = 0 + $servicestate->publish;
            $host2service->subscribe = 0 + $servicestate->subscribe;
            $DB->insert_record('mnet_host2service', $host2service);
        }
    }

    debug_trace('RPC Bind : Sending response');
    // Returns response (success or failure).
    return json_encode($response);
}

/**
 * Deletes a peer by unmarking it.
 * @param string $username The calling user.
 * @param string $userhost The calling user's host.
 * @param string $remotehost The calling host.
 * @param string $peer_wwwroot The peer's wwwroot to delete.
 */
function mnetadmin_rpc_unbind_peer($username, $userhost, $remotehost, $peer_wwwroot) {
    global $CFG, $USER, $DB;

    // Invoke distant user who makes the call and checks his rights.
    $user['username'] = $username;
    $user['remoteuserhostroot'] = $userhost;
    $user['remotehostroot'] = $remotehost;
    invoke_local_user($user, 'local/vmoodle:managevmoodles');

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    // Retrieves the peer record, edits it and inserts it.
    if ($vmoodle_host = $DB->get_record('mnet_host', array('wwwroot' => $peer_wwwroot))) {
        $vmoodle_host->deleted = 1;
        if (!$DB->update_record('mnet_host', $vmoodle_host)) {
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = 'Error when updating the host \''.$vmoodle_host->name.'\'.';
            $response->error = 'Error when updating the host \''.$vmoodle_host->name.'\'.';
        }
    } else {
        // If host cannot be find. LET IT SILENT, it is unbound !
        assert(true);
    }

    // Returns response (success or failure).
    return json_encode($response);
}

/**
 * Parse the error generated by weblib from php buffer.
 * @param string $contents The HTML error.
 * @return string The error message.
 */
function parse_wlerror() {
    // Getting contents form PHP buffer.
    $contents = ob_get_contents();
    $contents = str_replace(array('<br/>', '<br />', '<br>'), ' ', $contents);
    // Checking if is a notify message.
    if (!substr_compare($contents, '<div class="notifytiny"', 0, 13)) {
        // Checking if stacktrace is present.
        if ($pos = strpos($contents, '<ul')) {
            $contents = substr($contents, 0, $pos);
        }
    }
    // Removing all tags for XML RPC.
    return strip_tags($contents);
}

/**
 * NOT WORKING
 * reimplementation of system.keyswapcall with capability of forcing the local renew
 *
 */
function mnetadmin_keyswap($function, $params) {
    global $CFG, $MNET;

    $return = array();

    $wwwroot        = $params[0];
    $pubkey         = $params[1];
    $application    = $params[2];
    $forcerenew     = $params[3];
    if ($forcerenew == 0){
        // Standard keyswap for first key recording.
        if (!empty($CFG->mnet_register_allhosts)) {
            $mnet_peer = new mnet_peer();
            $keyok = $mnet_peer->bootstrap($wwwroot, $pubkey, $application);
            if ($keyok) {
                $mnet_peer->commit();
            }
        }
    } else {
        $mnet_peer = new mnet_peer();
        // We can only renew hosts that we know something about.
        if ($mnet_peer->set_wwwroot($wwwroot)) {
            $mnet_peer->public_key = clean_param($pubkey, PARAM_PEM);
            $mnet_peer->public_key_expires = $mnet_peer->check_common_name($pubkey);
            $mnet_peer->updateparams->public_key = clean_param($pubkey, PARAM_PEM);
            $mnet_peer->updateparams->public_key_expires = $mnet_peer->check_common_name($pubkey);
            $mnet_peer->commit();
        } else {
            // Avoid giving our key to unkown hosts.
            return false;
        }
    }
    return $MNET->public_key;
}

/**
 * Load plugins' RPC functions.
 */
foreach (glob($CFG->dirroot.'/local/vmoodle/plugins/*/rpclib.php') as $rpclib) {
    require_once $rpclib;
}