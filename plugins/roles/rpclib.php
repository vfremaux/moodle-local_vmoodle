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
 * Get role capabilities of a virtual platform.
 * @param mixed $user The calling user.
 * @param string $role The role to read capabilities.
 * @param mixed $capabilities The capabilities to read (optional / may be string or array).
 */
function mnetadmin_rpc_get_role_capabilities($user, $role, $capabilities = null, $jsonresponse = true) {
    global $CFG, $USER, $DB;

    // Invoke local user and check his rights.
    if ($authresponse = invoke_local_user((array)$user, 'local/vmoodle:execute')) {
        if ($jsonresponse) {
            return $authresponse;
        } else {
            return json_decode($authresponse);
        }
    }
    $response->errors = array();
    $response->error = '';

    // Creating response.
    $response = new stdclass;
    $response->status = RPC_SUCCESS;

    // Getting role.
    $recordrole = $DB->get_record('role', array('shortname' => $role));
    if (!$recordrole) {
        $response->status = RPC_FAILURE_RECORD;
        $response->errors[] = 'Unable to retrieve role on source host.';
        $response->error = 'Unable to retrieve role on source host.';
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    // Getting capabilities.
    $where = '';
    $inparams = array();
    if (!empty($capabilities)) {
        list($insql, $inparams) = $DB->get_in_or_equal($capabilities);
        $where = "
            WHERE
                name $insql
        ";
    }

    $sql = "
        SELECT
            name,
            contextlevel
        FROM
            {capabilities}
        $where
    ";
    debug_trace("Getting caps ".implode(',', $inparams));
    $recordscapabilities = $DB->get_records_sql($sql, $inparams);

    if (!$recordscapabilities) {
        $response->status = RPC_FAILURE_RECORD;
        $response->errors[] = 'Unable to retrieve capabilities.';
        $response->error = 'Unable to retrieve capabilities.';
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    $capabilityclause = '';
    $inparams = array();
    if (!empty($capabilities)) {
        list($insql, $inparams) = $DB->get_in_or_equal($capabilities);
        $capabilityclause = " AND capability $insql ";
    }

    $params = array($recordrole->id);

    // Getting role capabilities.
    $sql = "
        SELECT
            capability,
            contextid,
            permission
        FROM
            {role_capabilities}
        WHERE
            roleid = ? AND
            contextid = 1
            $capabilityclause
    ";
    $recordsrolecapabilities = $DB->get_records_sql($sql, array_merge($params, $inparams));
    @ob_clean();
    ob_start();    // Used to prevent HTML output from dmllib methods and capture errors
    if (!$recordsrolecapabilities) {
        $sqlerror = parse_wlerror();

        // Checking if there was a sql error.
        if (empty($sqlerror)) {
            // Defining empty record set.
            $recordsrolecapabilities = array();
        } else {
            // Returning error.
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = 'Unable to retrieve role capabilites: '.$sqlerror;
            $response->error = 'Unable to retrieve role capabilites: '.$sqlerror;
            if ($jsonresponse) {
                return json_encode($response);
            } else {
                return $response;
            }
        }
    }
    ob_end_clean();

    // Creating result.
    $result = array();
    foreach ($recordscapabilities as $name => $recordcapability) {

        // Checking if role_capability is set.
        if (!array_key_exists($name, $recordsrolecapabilities)) {
            $result[$name] = null;
        } else {
            // Getting role capability.
            $rolecapability = $recordsrolecapabilities[$name];

            // Adding capability contextlevel.
            $rolecapability->contextlevel = $recordcapability->contextlevel;
            $result[$name] = $rolecapability;
        }
    }

    // Setting value.
    $response->value = $result;

    // Returning response.
    if ($jsonresponse) {
        return json_encode($response);
    } else {
        return $response;
    }
}

/**
 * Set role capabilities of a virtual platform.
 * @param string $user The calling user.
 * @param string $role The role to set capabilities.
 * @param mixed $rolecapabilities The role capabilities (array or object due to xmlrpc failures).
 * @param bool $clear True if the role capabilities should be cleared before, false otherwise.
 */
function mnetadmin_rpc_set_role_capabilities($user, $role, $rolecapabilities, $clear = false, $jsonresponse = true) {
    global $CFG, $USER, $DB;

    // Creating response.
    $response = new stdclass;
    $response->status = RPC_SUCCESS;
    $response->errors = array();
    $response->error = '';

    // Invoke local user and check his rights.
    if ($authresponse = invoke_local_user((array)$user, 'local/vmoodle:execute')) {
        if ($jsonresponse) {
            // We could not have a credential.
            return $authresponse;
        } else {
            return json_decode($authresponse);
        }
    }

    // Getting role.
    $recordrole = $DB->get_record('role', array('shortname' => $role));
    if (!$recordrole) {
        $response->status = RPC_FAILURE_RECORD;
        $response->errors[] = 'Set role capability : Unable to retrieve role.';
        $response->error = 'Set role capability : Unable to retrieve role.';
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    // Formatting role capabilities.
    foreach ($rolecapabilities as $name => $rolecapability) {
        $rolecapabilities[$name] = $rolecapability ? (object) $rolecapability : null;
    }

    // Getting capabilities.
    $recordscapabilities = $DB->get_records('capabilities', null, '', 'name,id,captype,contextlevel,component,riskbitmask');
    if (!$recordscapabilities) {
        $response->status = RPC_FAILURE_RECORD;
        $response->errors[] = 'Set role capability : Unable to retrieve capabilities.';
        $response->error = 'Set role capability : Unable to retrieve capabilities.';
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }
    if ($clear) {
        // Removing current role capabilities.
        $DB->delete_records('role_capabilities', array('roleid' => $recordrole->id));
    } else {
        // Getting current role capabilities.
        $recordscurrentrolecapabilities = $DB->get_records('role_capabilities', array('roleid' => $recordrole->id));
        $currentrolecapabilities = array();
        // Removing other role capabilities.
        foreach ($recordscurrentrolecapabilities as $id => $recordcurrentrolecapability) {
            foreach ($rolecapabilities as $name => $rolecapability) {
                if ($recordcurrentrolecapability->capability == $name) {
                    $currentrolecapabilities[$recordcurrentrolecapability->capability] = $recordcurrentrolecapability;
                    break;
                }
            }
        }
    }

    // Setting role capabilities.
    @ob_clean(); ob_start();    // Used to prevent HTML output from dmllib methods and capture errors
    foreach ($rolecapabilities as $name => $rolecapability) {
        // Checking if capability exists.
        if (!array_key_exists($name, $recordscapabilities)) {
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = 'Set role capability : Capability "'.$name.'" does not exist.';
            $response->error = 'Set role capability : Capability "'.$name.'" does not exist.';
            continue;
        }

        // Checking if role capability should be removed.
        if (is_null($rolecapability)) {
            @ob_clean();
            if (!$DB->delete_records('role_capabilities', array('roleid' => $recordrole->id, 'capability' => $name))) {
                $response->status = RPC_FAILURE_RECORD;
                $sqlerror = parse_wlerror();
                $errstr = (empty($sqlerror) ? '.' : ': '.$sqlerror.'.');
                $response->errors[] = 'Set role capability : Unable to remove role capability "'.$name.'"'.$errstr;
                $response->error = 'Set role capability : Unable to remove role capability "'.$name.'"'.$errstr;
            }
            continue;
        }

        // Checking capability contextlevel.
        $capability = $recordscapabilities[$name];
        if ($capability->contextlevel != $rolecapability->contextlevel) {
            @ob_clean();
            $capability->contextlevel = $rolecapability->contextlevel;
            if (!$DB->update_record('capabilities', $capability)) {
                $response->status = RPC_FAILURE_RECORD;
                $sqlerror = parse_wlerror();
                $errstr = (empty($sqlerror) ? '.' : ': '.$sqlerror);
                $response->errors[] = 'Set role capability : Unable to fix contextlevel of capability "'.$capability->name.'"'.$errstr;
                $response->error = 'Set role capability : Unable to fix contextlevel of capability "'.$capability->name.'"'.$errstr;
                continue;
            }
        }

        // Checking if role capability should be created.
        if ($clear || !array_key_exists($rolecapability->capability, $currentrolecapabilities)) {
            // Creating record.
            $record = $rolecapability;
            $record->roleid = $recordrole->id;
            $record->timemodified = time();
            $record->modifierid = $USER->id;
            unset($record->contextlevel);

            // Inserting role capability.
            @ob_clean();
            if (!$DB->insert_record('role_capabilities', $record)) {
                $response->status = RPC_FAILURE_RECORD;
                $sqlerror = parse_wlerror();
                $errstr = (empty($sqlerror) ? '.' : ': '.$sqlerror);
                $response->errors[] = 'Set role capability : Unable to insert role capability "'.$record->capability.'"'.$errstr;
                $response->error = 'Set role capability : Unable to insert role capability "'.$record->capability.'"'.$errstr;
            }
        } else if (!$clear && array_key_exists($name, $currentrolecapabilities) &&
                $current_role_capabilities[$name]->permission != $rolecapability->permission) {
            // Checking if role capability should be updated.
            // Modifying record.
            $record = $currentrolecapabilities[$rolecapability->capability];
            $record->permission = $rolecapability->permission;

            // Updating record.
            @ob_clean();
            if (!$DB->update_record('role_capabilities', $record)) {
                $response->status = RPC_FAILURE_RECORD;
                $sqlerror = parse_wlerror();
                $errstr = (empty($sqlerror) ? '.' : ': '.$sqlerror);
                $response->errors[] = 'Set role capability : Unable to update role capability "'.$record->capability.'"'.$errstr;
                $response->error = 'Set role capability : Unable to update role capability "'.$record->capability.'"'.$errstr;
            }
        }
    }

    // Returning response.
    if ($jsonresponse) {
        return json_encode($response);
    } else {
        return $response;
    }
}

/**
 * Get role allowances of a virtual platform.
 * As being a cross platform match, do only rely on shortnames and never on ids
 * @param mixed $user The calling user.
 * @param string $table 'assign' or 'override'.
 * @param string $rolename The role shortname to get allowance from.
 */
function mnetadmin_rpc_get_role_allow_table($user, $table, $rolename = '', $jsonresponse = true) {
    global $CFG, $USER, $DB;

    // Invoke local user and check his rights.
    if ($authresponse = invoke_local_user((array)$user, 'local/vmoodle:execute')) {
        if ($jsonresponse) {
            return $authresponse;
        } else {
            return json_decode($authresponse);
        }
    }
    $response->errors = array();
    $response->error = '';

    // Creating response.
    $response = new StdClass();
    $response->status = RPC_SUCCESS;

    // Getting allowance records.
    if ($rolename) {
        if (!$role = $DB->get_record('role', array('shortname' => $rolename))) {
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = "Unknown role $rolename in remote.";
            $response->error = "Unknown role $rolename in remote.";
            if ($jsonresponse) {
                return json_encode($response);
            } else {
                return $response;
            }
        }
        $allows = $DB->get_records('role_allow_'.$table, array('roleid', $role->id));
    } else {
        $allows = $DB->get_records('role_allow_'.$table, array());
    }
    $result = array();
    if ($allows) {
        foreach ($allows as $a) {
            $rolename = $DB->get_field('role', 'shortname', array('id' => $a->roleid));
            $key = 'allow'.$table;
            $targetname = $DB->get_field('role', 'shortname', array('id' => $a->$key));
            $result[$rolename][] = $targetname;
        }
    }

    // Setting value.
    $response->value = $result;

    // Returning response.
    if ($jsonresponse) {
        return json_encode($response);
    } else {
        return $response;
    }
}

/**
 * Get role allowances of a virtual platform.
 * @param mixed $user The calling user.
 * @param string $table 'assign' or 'override'.
 * @param string $rolename The role shortname to get allowance from.
 * @param string $targetrolenames comma separated lists of role shortnames.
 */
function mnetadmin_rpc_set_role_allow($user, $table, $rolename, $targetrolenames, $jsonresponse = true) {
    global $CFG, $USER, $DB;

    // Invoke local user and check his rights.
    if ($authresponse = invoke_local_user((array)$user, 'local/vmoodle:execute')) {
        if ($jsonresponse) {
            return $authresponse;
        } else {
            return json_decode($authresponse);
        }
    }
    $response->errors = array();
    $response->error = '';

    // Creating response.
    $response = new StdClass();
    $response->status = RPC_SUCCESS;

    // Getting allowance records.
    if ($rolename) {
        if ($role = $DB->get_record('role', array('shortname' => $rolename))) {
            $DB->delete_records('role_allow_'.$table, array('roleid' => $role->id));
            $targets = explode(',', $targetrolenames);
            foreach ($targets as $targetname) {
                if ($targetrole = $DB->get_record('role', array('shortname' => $targetname))) {
                    $key = 'allow'.$table;
                    $roleallow = new StdClass();
                    $roleallow->roleid = $role->id;
                    $roleallow->$key = $targetrole->id;
                    $DB->insert_record('role_allow_'.$table, $roleallow);
                } else {
                    $response->errors[] = "Bad target role shortname $targetname.";
                    $response->error = "Some target role shortname error.";
                }
            }
        } else {
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = "Bad source role shortname $rolename.";
            $response->error = "Bad source role shortname $rolename.";
            if ($jsonresponse) {
                return json_encode($response);
            } else {
                return $response;
            }
        }
    } else {
        $response->status = RPC_FAILURE_RECORD;
        $response->errors[] = 'Bad role id.';
        $response->error = 'Unable to retrieve assign allowance table on source host.';
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    // Setting value.
    $response->value = '';

    // Returning response.
    if ($jsonresponse) {
        return json_encode($response);
    } else {
        return $response;
    }
}

/**
 * asks for a role assignation on a context.
 * @param object $callinguser The calling user.
 * @param string $targetuser The username of the user to assign a role remotely.
 * @param mixed $rolename The role shortname to assign
 * @param string $contextidentityfield Tells the field to use to get context real object instance
 * @param integer $contextlevel The contextlevel concerned, defaults to SYSTEM 
 * @param string $contextidentity Some identifying value allowing to remotely point the context instance
 *
 * Identifying context from remote aplications : 
 * CONTEXT_SYSTEM : unused
 * CONTEXT_COURSECAT : not implemented
 * CONTEXT_COURSE : the course shortname is used
 * CONTEXT_MODULE : the coursemodule IDnumber is used
 * CONTEXT_USER : the username is used
 */
function mnetadmin_rpc_has_role($callinguser, $targetuser, $userhostroot, $rolename, $contextidentityfield = '', $contextlevel = CONTEXT_SYSTEM, $contextidentity = '', $whereroot = '', $jsonresponse = true) {
    global $CFG, $USER, $DB;

    $response = new StdClass();
    $response->status = RPC_SUCCESS;
    $response->errors = array();
    $response->error = '';

    // Invoke local user and check his rights.
    if ($authresponse = invoke_local_user((array)$callinguser, 'local/vmoodle:execute')) {
        if ($jsonresponse) {
            return $authresponse;
        } else {
            return json_decode($authresponse);
        }
    }

    if (empty($whereroot) || $whereroot == $CFG->wwwroot) {

        // Check it locally.
        if (function_exists('debug_trace')) {
            $trace = "check locally for $targetuser as $rolename in context $contextidentity of level $contextlevel";
            $trace .= " keyed by $contextidentityfield in ".$whereroot;
            debug_trace($trace);
        }

        // Getting role.
        $recordrole = $DB->get_record('role', array('shortname' => $rolename));
        if (!$recordrole) {
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = 'Unkown role.';
            $response->error = 'Unkown role.';
            if ($jsonresponse) {
                return json_encode($response);
            } else {
                return $response;
            }
        }

        $context = rpc_check_context_target($contextlevel, $contextidentityfield, $contextidentity, $response);
        if ($response->status != RPC_SUCCESS) {
            if ($jsonresponse) {
                return json_encode($response);
            } else {
                return $response;
            }
        }
        if ($userhostroot == '') {
            $userhostroot = $CFG->wwwroot;
        }
        if (!$usermnet = $DB->get_record('mnet_host', array('wwwroot' => $userhostroot))) {
            $response->status = RPC_FAILURE;
            $response->errors[] = "Unknown user host reference";
            $response->error = "Unknown user host reference";
            if ($jsonresponse) {
                return json_encode($response);
            } else {
                return $response;
            }
        }

        if (!$user = $DB->get_record('user', array('username' => $targetuser, 'mnethostid' => $usermnet->id))) {
            $response->status = RPC_FAILURE_USER;
            $response->errors[] = "Unknown user";
            $response->error = "Unknown user";
            if ($jsonresponse) {
                return json_encode($response);
            } else {
                return $response;
            }
        }

        $params = array('userid' => $user->id, 'roleid' => $recordrole->id, 'contextid' => $context->id);
        if (!$DB->record_exists('role_assignments', $params)) {
            $response->status = RPC_FAILURE;
            $response->errors[] = "Has no role here";
            $response->error = "Has no role here";
            if ($jsonresponse) {
                return json_encode($response);
            } else {
                return $response;
            }
        }
        $response->message = "Has role";
    } else {
        // Make remote call.
        $userhostroot = $DB->get_field_select('mnet_host', 'wwwroot', " id = ? AND deleted = 0 ", array($USER->mnethostid)); 
        if (!$userhostroot) {
            $extresponse->error = 'Unkown userroot (or deleted).';
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }

        if ($remotedeleted = $DB->get_field('mnet_host', 'deleted', array('wwwroot' => $whereroot))) {
            $extresponse->error = 'Unkown whereroot.';
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }
        $rpcclient = new mnet_xmlrpc_client();
        $rpcclient->set_method('local/vmoodle/plugins/roles/rpclib.php/mnetadmin_rpc_has_role');
        $caller->username = $USER->username;
        $caller->remoteuserhostroot = $userhostroot;
        $caller->remotehostroot = $CFG->wwwroot;
        $rpcclient->add_param($caller, 'struct'); // caller user
        $rpcclient->add_param($targetuser, 'string');
        $rpcclient->add_param($userhostroot, 'string');
        $rpcclient->add_param($rolename, 'string');
        $rpcclient->add_param($contextidentityfield, 'string');
        $rpcclient->add_param($contextlevel, 'string');
        $rpcclient->add_param($contextidentity, 'string');
        $mnet_host = new mnet_peer();
        $mnet_host->set_wwwroot($whereroot);
        if (!$rpcclient->send($mnet_host)) {
            $extresponse->status = RPC_FAILURE;
            $extresponse->errors[] = 'REMOTE : '.implode("<br/>\n", $rpcclient->errors);        
            $extresponse->errors[] = json_encode($rpcclient);
            $extresponse->error = 'REMOTE : '.implode("<br/>\n", $rpcclient->errors);        
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }
        $response = json_decode($rpcclient->response);
        if ($response->status != RPC_SUCCESS) {
            $extresponse->status = $response->status;
            $extresponse->errors[] = 'Remote application error : ';
            $extresponse->errors[] = $response->errors;
            $extresponse->error = 'Remote application error : '. implode("\n", $response->errors);
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }
    }

    if ($jsonresponse) {
        return json_encode($response);
    } else {
        return $response;
    }
}

function mnetadmin_rpc_has_role_wrapped($wrap) {
    // debug_trace("WRAP mnetadmin_rpc_has_role : ".json_encode($wrap));
    return mnetadmin_rpc_has_role(@$wrap['callinguser'], @$wrap['targetuser'], @$wrap['userhostroot'], @$wrap['rolename'],
                                  @$wrap['contextidentityfield'], @$wrap['contextlevel'], @$wrap['contextidentity'],
                                  @$wrap['whereroot'], @$wrap['json_response']);
}

/**
 * assign remotely a role based on role shortname and user username.
 * @param object $callinguser The calling user.
 * @param string $targetuser The username of the user to assign a role remotely.
 * @param string $rolename The role shortname to assign
 * @param string $contextidentityfield Tells the field to use to get context real object instance
 * @param integer $contextlevel The contextlevel concerned, defaults to SYSTEM 
 * @param string $contextidentity Some identifying value allowing to remotely point the context instance
 *
 * Identifying context from remote aplications : 
 * CONTEXT_SYSTEM : unused
 * CONTEXT_COURSECAT : not implemented
 * CONTEXT_COURSE : the course shortname is used
 * CONTEXT_MODULE : the coursemodule IDnumber is used
 * CONTEXT_USER : the username is used
 */
function mnetadmin_rpc_assign_role($callinguser, $targetuser, $rolename, $contextidentityfield = '', $contextlevel = CONTEXT_SYSTEM,
                                   $contextidentity = '', $starttime = 0, $endtime = 0, $jsonresponse = true) {
    global $CFG, $USER, $DB;

    if (function_exists('debug_trace')) {
        debug_trace("mnetadmin_rpc_assign_role: Starting");
    }

    $response = new stdclass;
    $response->status = RPC_SUCCESS;
    $response->errors = array();
    $response->error = '';
    $response->message = '';

    // Invoke local user and check his rights.
    if ($authresponse = invoke_local_user((array)$callinguser, 'local/vmoodle:execute')) {
        if ($jsonresponse) {
            return $authresponse;
        } else {
            return json_decode($authresponse);
        }
    }

    // Getting true role.
    $unassign = (strstr($rolename, '-') !== false);
    $rolename = str_replace('-', '', $rolename);

    $siteadmin = (strstr($rolename, '+') !== false);
    $rolename = str_replace('+', '', $rolename);

    $params = array('username' => $targetuser);
    if ($targetuser != 'admin') {
        // Here we assume that username is unique.
        $targetuser = $DB->get_record('user', $params);
    } else {
        /*
         * If account is admin it must be necessarily the global administrator comming from remote site.
         */
        $adminhost = $DB->get_record('mnet_host', array('wwwroot' => $callinguser['remotehostroot']));
        $params['mnethostid'] = $adminhost->id;
        $targetuser = $DB->get_record('user', $params);
    }

    // Process site admin operation.
    if (!$targetuser) {
        $response->status = RPC_FAILURE_RECORD;
        $response->errors[] = 'mnetadmin_rpc_assign_role: Not such target user.';
        $response->error = 'mnetadmin_rpc_assign_role: Not such target user.';
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    if ($siteadmin) {
        if (function_exists('debug_trace')) {
            debug_trace("mnetadmin_rpc_assign_role: Setting $targetuser->username($targetuser->id) as site admin");
        }
        $response->message .= '<br/>'.fullname($targetuser).' is now site administrator ';

        // Cleanup in case we have a malformed list.
        $siteadmins = preg_replace('/,+/', ',', $CFG->siteadmins);
        $siteadmins = trim($siteadmins, ',');
        $siteadminsarr = explode(',', $siteadmins);

        if (!in_array($targetuser->id, $siteadminsarr)) {
            $siteadminsarr[] = $targetuser->id;
            set_config('siteadmins', implode(',', $siteadminsarr));
        }
    } else {
        if (preg_match('/\b'.$targetuser->id.'\b/', $CFG->siteadmins)) {
            if (function_exists('debug_trace')) {
                debug_trace("mnetadmin_rpc_assign_role: Unset $targetuser->username as site admin");
            }

            // Cleanup in case we have a malformed list.
            $siteadmins = preg_replace('/,+/', ',', $CFG->siteadmins);
            $siteadmins = trim($siteadmins, ',');
            $siteadminsarr = explode(',', $siteadmins);

            // Ensure user IS NOT in admins.
            $newadmins = array();
            foreach ($siteadminsarr as $adm) {
                if ($adm != $targetuser->id) {
                    $newadmins[] = $adm;
                }
                set_config('siteadmins', implode(',', $newadmins));
            }

            $response->message .= '<br/>'.fullname($targetuser).' discarded from site administrators ';
        }
    }

    // We admit null role operations for site admin only changes.
    if (empty($rolename)) {
        if (function_exists('debug_trace')) {
            debug_trace("mnetadmin_rpc_assign_role: Site admin only operation");
        }
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    $recordrole = $DB->get_record('role', array('shortname' => $rolename));
    if (!$recordrole) {
        $response->status = RPC_FAILURE_RECORD;
        $response->errors[] = ' Role assign : Unkown role '.$rolename.'.';
        $response->error = ' Role assign : Unkown role '.$rolename.'.';
        if ($jsonresponse) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    // Check context target.

    switch ($contextlevel) {
        case CONTEXT_SYSTEM:
            $context = context_system::instance();
            break;

        case CONTEXT_COURSE:
            if (!preg_match('/id|shortname|idnumber/', $contextidentityfield)) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = ' Role assign : This fieldname does\'nt apply for this context level.';
                $response->error = ' Role assign : This fieldname does\'nt apply for this context level.';
                if ($jsonresponse) {
                    return json_encode($response);
                } else {
                    return $response;
                }
            }
            if (!$course = $DB->get_record('course', array($contextidentityfield => $contextidentity))) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = ' Role assign : Course Context not found.';
                $response->error = ' Role assign : Course Context not found.';
                if ($jsonresponse) {
                    return json_encode($response);
                } else {
                    return $response;
                }
            }
            $context = context_course::instance($course->id);
            break;

        case CONTEXT_MODULE:
            if (!preg_match('/id|idnumber/', $contextidentityfield)) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = ' Role assign : This fieldname does\'nt apply for this context level.';
                $response->error = ' Role assign : This fieldname does\'nt apply for this context level.';
                if ($jsonresponse) {
                    return json_encode($response);
                } else {
                    return $response;
                }
            }
            if (!$cm = $DB->get_record('course_modules', array($contextidentityfield => $contextidentity))) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = ' Role assign : Course Module not found.';
                $response->error = ' Role assign : Course Module not found.';
                if ($jsonresponse) {
                    return json_encode($response);
                } else {
                    return $response;
                }
            }
            if (!$context = context_module::instance($cm->id)) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = ' Role assign : Course Module context not found.';
                $response->error = ' Role assign : Course Module context not found.';
                if ($jsonresponse) {
                    return json_encode($response);
                } else {
                    return $response;
                }
            }
            break;

        case CONTEXT_USER:
            if (!preg_match('/id|username|email|idnumber', $contextidentityfield)) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = ' Role assign : This fieldname does\'nt apply for this context level.';
                $response->error = ' Role assign : This fieldname does\'nt apply for this context level.';
                if ($jsonresponse) {
                    return json_encode($response);
                } else {
                    return $response;
                }
            }
            if (!$user = $DB->get_record('user', array($contextidentityfield => $contextidentity))) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = ' Role assign : User not found.';
                $response->error = ' Role assign : User not found.';
                if ($jsonresponse) {
                    return json_encode($response);
                } else {
                    return $response;
                }
            }
            if (!$context = context_user::instance($cm)) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = ' Role assign : User context not found.';
                $response->error = ' Role assign : User context not found.';
                if ($jsonresponse) {
                    return json_encode($response);
                } else {
                    return $response;
                }
            }
            break;

        default:
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = ' Role assign : Context not implemented.';
            $response->error = ' Role assign : Context not implemented.';
            if ($jsonresponse) {
                return json_encode($response);
            } else {
                return $response;
            }
    }

    if (function_exists('debug_trace')) {
        debug_trace("mnetadmin_rpc_assign_role: Got context $contextlevel");
    }

    if ($unassign) {
        if (function_exists('debug_trace')) {
            debug_trace("role_unassign($recordrole->id, $targetuser->id, null, $context->id)");
        }
        if (role_unassign($recordrole->id, $targetuser->id, $context->id)) {
            $response->status = RPC_SUCCESS;
            $response->message = "<br/>Role $recordrole->name unassigned from ". fullname($targetuser);
            if (function_exists('debug_trace')) {
                debug_trace("Role $recordrole->name unassigned for ". fullname($targetuser));
            }
        } else {
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = "Could not unassign $targetuser->username on context $context->id for role $rolename";
            $response->error = "Could not unassign $targetuser->username on context $context->id for role $rolename";
            if (function_exists('debug_trace')) {
                debug_trace("Could not unassign role $rolename to $targetuser->username on context $context->id");
            }
        }
    } else {
        if (function_exists('debug_trace')) {
            debug_trace("role_assign($recordrole->id, $targetuser->id, null, $context->id, $starttime, $endtime)");
        }
        if ($starttime && $endtime && ($starttime > $endtime)) {
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = "Cannot assign when starttime is above endtime";
            $response->error = "Cannot assign when starttime is above endtime";
            if (function_exists('debug_trace')) {
                debug_trace("Bad times for role assign");
            }
        } else {
            if (role_assign($recordrole->id, $targetuser->id, $context->id, '', 0, $starttime, $endtime)) {
                $response->status = RPC_SUCCESS;
                $response->message .= "<br/>Role $recordrole->name assigned to ". fullname($targetuser);
                if (function_exists('debug_trace')) {
                    debug_trace("Role $recordrole->name assigned to ". fullname($targetuser));
                }
            } else {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = "Could not assign role $rolename to $targetuser->username on context $context->id";
                $response->error = "Could not assign role $rolename to $targetuser->username on context $context->id";
                if (function_exists('debug_trace')) {
                    debug_trace("Could not assign role $rolename to $targetuser->username on context $context->id");
                }
            }
        }
    }

    if ($jsonresponse) {
        return json_encode($response);
    } else {
        return $response;
    }
}

function mnetadmin_rpc_assign_role_wrapped($wrap) {
    if (function_exists('debug_trace')) {
        debug_trace("WRAP mnetadmin_rpc_assign_role : ".json_encode($wrap));
    }
    return mnetadmin_rpc_assign_role(@$wrap['callinguser'], @$wrap['targetuser'], @$wrap['rolename'],
                                     @$wrap['contextidentityfield'], @$wrap['contextlevel'], @$wrap['contextidentity'],
                                     @$wrap['starttime'], @$wrap['endtime'], @$wrap['json_response']);
}

/**
 * allows checking if a user exists.
 * @param object $callinguser The calling user.
 * @param string $targetuser The username of the user to be created.
 * @param string $whereroot the user's supposed origin .
 *
 * if userhostname is empty, the user is checked locally and his known userhost is mentionned.
 *
 */
function mnetadmin_rpc_user_exists($callinguser, $targetuser, $whereroot = '', $jsonresponse = true) {
    global $CFG, $USER, $DB;

    if (function_exists('debug_trace')) {
        debug_trace("$CFG->wwwroot : mnetadmin_rpc_user_exists entry");
    }

    $response = new stdclass;
    $response->status = RPC_SUCCESS;
    $response->errors = array();
    $response->error = '';

    if ($authresponse = invoke_local_user((array)$callinguser, 'local/vmoodle:execute')) {
        if ($jsonresponse) {
            return $authresponse;
        } else {
            return json_decode($authresponse);
        }
    }

    // Local search.
    if (function_exists('debug_trace')) {
        debug_trace("$CFG->wwwroot : asked for $whereroot");
    }
    if (empty($whereroot) || $whereroot == $CFG->wwwroot) {
        /*
         * Here we ask to ourself for a local user.
         */
        if (function_exists('debug_trace')) {
            debug_trace("mnetadmin_rpc_user_exists : local resolution");
        }
        $params = array('username' => $targetuser, 'mnethostid' => $CFG->mnet_localhost_id);
        if (!$response->user = $DB->get_record('user', $params)) {
            if (function_exists('debug_trace')) {
                debug_trace("User exists : $targetuser did not matched locally.");
            }
            $response->location = 'local';
            $response->errors[] = "Unknown user.";
            $response->error = "Unknown user.";
            if ($jsonresponse) {
                return json_encode($response);
            } else {
                return $response;
            }
        }
        if (function_exists('debug_trace')) {
            debug_trace("User exists : $targetuser matched locally.");
        }
        $userhostid = $response->user->mnethostid;
        $response->user->userknownhost = $DB->get_field_select('mnet_host', 'wwwroot', " id = {$userhostid} AND deleted = 0 ");
    } else {
        /*
         * Here we ask to another host if a user comming from us is available there.
         */
        if (function_exists('debug_trace')) {
            debug_trace("mnetadmin_rpc_user_exists : remote resolution in $whereroot");
        }
        // Make remote call.
        $userhostroot = $DB->get_field_select('mnet_host', 'wwwroot', " id = $USER->mnethostid AND deleted = 0 ");

        if (!$userhostroot) {
            $extresponse->status = RPC_FAILURE_DATA;
            $extresponse->location = 'remote';
            $extresponse->error = 'Unknown userroot (or deleted).';
            $extresponse->errors[] = 'Unknown userroot (or deleted).';
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }

        if (!$DB->record_exists('mnet_host', array('wwwroot' => $whereroot, 'deleted' => 0))) {
            $extresponse->status = RPC_FAILURE_DATA;
            $extresponse->location = 'remote';
            $extresponse->error = "Unknown host $whereroot (or deleted).";
            $extresponse->errors[] = "Unknown host $whereroot (or deleted).";
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }
        $rpcclient = new mnet_xmlrpc_client();
        $rpcclient->set_method('local/vmoodle/plugins/roles/rpclib.php/mnetadmin_rpc_user_exists');

        $caller = new StdClass();
        $caller->username = $USER->username;
        $caller->remoteuserhostroot = $userhostroot;
        $caller->remotehostroot = $CFG->wwwroot;
        $rpcclient->add_param($caller, 'struct'); // Caller user full identity

        $rpcclient->add_param($targetuser, 'string');
        $rpcclient->add_param($CFG->wwwroot, 'string'); // Ask for a user comming from us.
        $mnet_host = new mnet_peer();
        $mnet_host->set_wwwroot($whereroot); // Go to the target host.
        if (!$response = $rpcclient->send($mnet_host)) {
            $extresponse = new StdClass();
            $extresponse->status = RPC_FAILURE;
            $extresponse->errors[] = "REMOTE RPC ERRORS \n";
            $extresponse->error = 'Remote rpc error.';
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }
        $response = json_decode($rpcclient->response);
        if ($response->status != RPC_SUCCESS) {
            $extresponse = new StdClass();
            $extresponse->status = $response->status;
            $extresponse->errors[] = 'Remote application error : ';
            $extresponse->errors[] = $response->errors;
            $extresponse->error = 'Remote application error : '. implode("\n", $response->errors);
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }
    }
    if ($jsonresponse) {
        return json_encode($response);
    } else {
        return $response;
    }
}

function mnetadmin_rpc_user_exists_wrapped($wrap) {
    if (function_exists('debug_trace')) debug_trace("WRAP mnetadmin_rpc_user_exists : ".json_encode($wrap));    
    return mnetadmin_rpc_user_exists(@$wrap['callinguser'], @$wrap['targetuser'], @$wrap['whereroot'], @$wrap['json_response']);
}

/**
 * force user account creation.
 * @param object $callinguser The calling user.
 * @param string $targetuser The username of the user to be created.
 * @param array $userparams an array containing all data for user.
 * @param string $userhostname the user's origin account.
 * @param array $bounceto an array of or a string containing hostnames to propagate users to.
 * @param boolean $onlybounce if true, do not try to create the user locally, just bounce.
 *
 * if userhostname is empty, the user is created with an account bound to the localhost mnethost id (local account) and
 * reset to manual auth if the auth is 'mnet' (note the auth will remain unchanged if other than mnet, so it is possible to preset
 * an SAML or LDAP bound account.
 * If userhostname is not empty, the call forces auth being mnet, whatever the auth field was set to, and the hostname is searched
 * for a local matching host in mnet_hosts.
 *
 * If bounceto is not empty, the account will be propagated to matching mnet_hosts in the MNET proximity.
 * The onlybounce feature is provided for using this rpc function using a local direct call to propagate a user programatically
 * a user to some bounce locations
 */
function mnetadmin_rpc_create_user($callinguser, $targetuser, $userparams, $userhostname = '', $bounceto = null,
                                   $onlybounce = false, $jsonresponse = true, $overridecapability = false) {
    global $CFG, $USER, $DB;

    $response = new StdClass();
    $response->status = RPC_SUCCESS;
    $response->errors = array();
    $response->error = '';

    $userparamsarr = (array)$userparams;

    $capability = '';
    if (!$overridecapability) {
        $capability = 'local/vmoodle:execute';
    }

    if ($authresponse = invoke_local_user((array)$callinguser, $capability)) {
        if ($jsonresponse) {
            return $authresponse;
        } else {
            return json_decode($authresponse);
        }
    }

    // Be sure of our structure type.
    $callinguser = (object)$callinguser;

    if (!$onlybounce) {
        if (function_exists('debug_trace')) {
            debug_trace("mnetadmin_rpc_create_user: Up to create $targetuser ");
        }

        $params = array('username' => $targetuser);
        if ($targetuser != 'admin') {
            // Assuming unique username. TODO : reinforce incomming identity and wrap to user_mnet_hosts
            // policy for unifying users.
            $user = $DB->get_record('user', $params);
        } else {
            // Find an admin comming from caller. It will be the superadmin.
            if (function_exists('debug_trace')) {
                debug_trace("mnetadmin_rpc_create_user: search admin ".print_r($callinguser, true));
            }
            $adminhost = $DB->get_record('mnet_host', array('wwwroot' => $callinguser->remotehostroot));
            if (function_exists('debug_trace')) {
                debug_trace("mnetadmin_rpc_create_user: host admin ".print_r($adminhost, true));
            }
            $params['mnethostid'] = $adminhost->id;
            if (function_exists('debug_trace')) {
                debug_trace("mnetadmin_rpc_create_user: search admin ".print_r($params, true));
            }
            $user = $DB->get_record('user', $params);
        }

        if (!$user) {

            // Collect eventual profilefields and cleanup user record from them.
            foreach ($userparamsarr as $key => $value) {
                if (preg_match('/^profile_field_/', $key)) {
                    $profilefields[$key] = $value;
                    unset($userparams[$key]);
                }
            }

            if (function_exists('debug_trace')) {
                debug_trace("mnetadmin_rpc_create_user: Making new user record");
            }

            $newuser = (object)$userparams;
            $newuser->username = $targetuser;
            // Remap local mnethostid and auth method if needed.

            if (!empty($userhostname)) {

                if (!$originuserhost = $DB->get_record('mnet_host', array('wwwroot' => $userhostname))) {
                    // If we fail to find real origin host for the user, take request host as failover.
                    if (function_exists('debug_trace')) {
                        debug_trace("REMOTE CALL ERROR : Bad origin host. Trying $callinguser->remotehostroot as failover");
                    }
                    if (!$originuserhost = $DB->get_record('mnet_host', array('wwwroot' => $callinguser->remotehostroot))) {

                        if (function_exists('debug_trace')) {
                            debug_trace("REMOTE CALL ERROR : Bad origin host ". json_encode($userhostname));
                        }
                        $response = new StdClass();
                        $response->status = 510;
                        $errorstr = "Bad origin host ".json_encode($userhostname);
                        $errorstr .= ', or origin host of the user is not known by this host.';
                        $response->errors[] = $errorstr;
                        $response->error = $errorstr;
                        if ($jsonresponse) {
                            return json_encode($response);
                        } else {
                            return $response;
                        }
                    }
                }

                $newuser->mnethostid = $originuserhost->id;
                if (($originuserhost->id != $CFG->mnet_localhost_id) && (empty($newuser->auth) || ($newuser->auth == 'manual'))) {
                    $newuser->auth = 'mnet';
                } else {
                    if (empty($newuser->auth) || $newuser->auth == 'mnet') {
                        $newuser->auth = 'manual';
                    }
                }
            } else {
                $newuser->mnethostid = $CFG->mnet_localhost_id;
                if (empty($newuser->auth) || $newuser->auth == 'mnet') {
                    $newuser->auth = 'manual';
                }
            }
            $newuser->confirmed = 1;
            $newuser->timemodified = time();
            if (function_exists('debug_trace')) {
                debug_trace("REMOTE CALL : recording user");
            }
            if (!$userid = $DB->insert_record('user', $newuser)) {
                if (function_exists('debug_trace')) {
                    debug_trace("mnetadmin_rpc_create_user: User creation failure");
                }
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = "Could not create the user.";
                $response->error = "Could not create the user.";
                if ($jsonresponse) {
                    return json_encode($response);
                } else {
                    return $response;
                }
            }
            $response->userid = $userid;

            // add profilefields
            if (function_exists('debug_trace')) {
                debug_trace("mnetadmin_rpc_create_user: Adding profile fields");
            }
            if (!empty($profilefields)) {
                foreach ($profilefields as $key => $value) {
                    $key = str_replace('profile_field_', '', $key); // Extract real shortname.
                    if ($field = $DB->get_record('user_info_field', array('shortname' => $key))) {
                        // Do insert only if known field. Ignore others.
                        $valuerec->userid = $userid;
                        $valuerec->fieldid = $field->id;
                        $valuerec->data = $value;
                        $DB->insert_record('user_info_data', $valuerec);
                    }
                }
            }
        } else {
            if ($user->deleted == 1) {
                if (function_exists('debug_trace')) {
                    debug_trace("mnetadmin_rpc_create_user: Reviving user");
                }
                $user->deleted = 0;
                foreach ($userparams as $key => $value) {
                    $user->$key = $value;
                }
                $user->username = $targetuser;
                if (!$userid = $DB->update_record('user', $user)) {
                    if (function_exists('debug_trace')) {
                        debug_trace("mnetadmin_rpc_create_user: User revival failure");
                    }
                    $response->status = RPC_FAILURE_RECORD;
                    $response->errors[] = "Create user REMOTE CALL : Could not revive the user.";
                    $response->error = "Create user REMOTE CALL : Could not revive the user.";
                    if ($jsonresponse) {
                        return json_encode($response);
                    } else {
                        return $response;
                    }
                }
                $response->userid = $userid;
            } else {
                if (function_exists('debug_trace')) {
                    debug_trace("mnetadmin_rpc_create_user: User exists");
                }
            }
        }
    } else {
        if (!$userparams = $DB->get_record('user', array('username' => $targetuser))) {
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = "Create user REMOTE CALL : No such user to propagate.";
            $response->error = "Create user REMOTE CALL : No such user to propagate.";
            if ($jsonresponse) {
                return json_encode($response);
            } else {
                return $response;
            }
        }
        if (function_exists('debug_trace')) {
            debug_trace('mnetadmin_rpc_create_user: got user data as '.print_r($userparams, true));
        }
    }

    // Now proceed to bounces if any.
    if (!empty($bounceto)) {
        if (is_string($bounceto)) {
            $bounceto = explode(';', $bounceto);
        }

        foreach ($bounceto as $bouncehost) {
            // Check if known as mnet_hosts and possible to send admin requests.
            $sql = "
                SELECT
                    COUNT(*)
                FROM
                    {mnet_host} as mh,
                    {mnet_service} as ms,
                    {mnet_host2service} as h2s
                WHERE
                    mh.wwwroot = '$bouncehost' AND
                    mh.id = h2s.hostid AND
                    mh.deleted = 0 AND
                    h2s.serviceid = ms.id AND
                    ms.name = 'mnetadmin' AND
                    h2s.subscribe = 1
            ";
            $ok = $DB->count_records_sql($sql);
            if ($ok) {
                // We can do it.
                $userhostroot = $DB->get_field('mnet_host', 'wwwroot', array('id' => $USER->mnethostid));
                $rpc_client = new \local_vmoodle\XmlRpc_Client();
                $rpc_client->reset_method();
                $rpc_client->set_method('local/vmoodle/plugins/roles/rpclib.php/mnetadmin_rpc_create_user');
                $caller = new StdClass();
                $caller->username = $USER->username;
                $caller->remoteuserhostroot = $userhostroot;
                $caller->remotehostroot = $CFG->wwwroot;
                $rpc_client->add_param($caller, 'struct'); // username
                $rpc_client->add_param($targetuser, 'string');
                $rpc_client->add_param($userparams, 'struct');
                if ($userhostname == '') {
                    $rpc_client->add_param($CFG->wwwroot, 'string');
                } else {
                    $rpc_client->add_param($userhostname, 'string');
                }
                if (function_exists('debug_trace')) {
                    debug_trace("mnetadmin_rpc_create_user: Bouncing to $bouncehost ");
                }
                $mnet_host = new mnet_peer();
                if ($mnet_host->set_wwwroot($bouncehost)) {
                    $result = $rpc_client->send($mnet_host);
                    if (empty($result)) {
                        $response->errors[] = 'Create user : bounce failed rpc transaction to '.$bouncehost;
                        $response->errors[] = $rpc_client->get_errors();
                        $response->error = 'Create user : bounce failed rpc transaction to '.$bouncehost;
                    } else {
                        // Whatever we have, aggregate eventual remote errors to error stack.
                        $res = json_decode($rpc_client->response);
                        if (!empty($res->errors)) {
                            foreach ($res->errors as $remoteerror) {
                                $response->errors[] = 'REMOTE: '.implode(' ', (array)$remoteerror);
                                $response->error = 'REMOTE : bounce failed rpc some of transactions to '.$bouncehost;
                            }
                        }
                    }
                } else {
                    // Silently ignore unless debugging.
                    if (function_exists('debug_trace')) {
                        debug_trace("mnetadmin_rpc_create_user: Bounce ignored  : No service capability for $bouncehost ");
                    }
                    $errorstr = 'Create user : (last error) ignoring bounce to '.$bouncehost.' because host communication failed.';
                    $response->errors[] = $errorstr;
                    $response->error = $errorstr;
                }
            } else {
                $response->errors[] = 'Create user : ignoring bounce to '.$bouncehost.' because host unregistered.';
                $response->error = 'Create user : (last error) ignoring bounce to '.$bouncehost.' because host unregistered.';
            }
        }
    }
    if ($jsonresponse) {
        return json_encode($response);
    } else {
        return $response;
    }
}

function mnetadmin_rpc_create_user_wrapped($wrap) {
    if (function_exists('debug_trace')) debug_trace("WRAP mnetadmin_rpc_create_user : ".json_encode($wrap));
    return mnetadmin_rpc_create_user(@$wrap['callinguser'], @$wrap['targetuser'], @$wrap['userparams'],
                                     @$wrap['userhostname'], @$wrap['bounceto'], @$wrap['onlybounce'],
                                     @$wrap['json_response']);
}

/**
 * require remote enrollement on a MNET satellite.
 * This XML-RPC call fetches for a remotely known course and enroll the user inside
 * This is essentially intended to use by foreign systems to slave the user management
 * in a MNET network.
 * @param string $callinguser The calling user.
 * @param string $targetuser The username or user identifier of the user to assign a role remotely.
 * @param string $useridfield The field used for identifying the user (id, idnumber or username).
 * @param string $courseidfield The identifying value of the remote course 
 * @param string $courseidentifier The identifying value of the remote course 
 * @param string $rolename The remote role name to be assigned as
 * @param string $starttime The starting date
 * @param string $endtime The enrollement ending date
 *
 */
function mnetadmin_rpc_remote_enrol($callinguser, $targetuser, $rolename, $whereroot, $courseidfield, $courseidentifier,
                                    $starttime = 0, $endtime = 0, $jsonresponse = true) {
    global $CFG, $USER, $DB;

    $extresponse = new stdclass;
    $extresponse->status = RPC_SUCCESS;
    $extresponse->errors = array();
    $extresponse->error = '';

    // Invoke local user and check his rights.
    if ($authresponse = invoke_local_user((array)$callinguser, 'local/vmoodle:execute')) {
        if ($jsonresponse) {
            return $authresponse;
        } else {
            return json_decode($authresponse);
        }
    }

    if ($whereroot == $CFG->wwwroot) {
        if (function_exists('debug_trace')) {
            $trace = "local enrol process for $targetuser as $rolename in $courseidentifier ";
            $trace .= "by $courseidfield from $starttime to $endtime";
            debug_trace($trace);
        }

        // Getting remote_course definition.
        switch ($courseidfield) {
            case 'id':
                $course = $DB->get_record('course', array('id' => $courseidentifier));
                break;

            case 'shortname':
                $course = $DB->get_record('course', array('shortname' => $courseidentifier));
                break;

            case 'idnumber':
                $course = $DB->get_record('course', array('idnumber' => $courseidentifier));
                break;
        }

        if (!$course) {
            $extresponse->status = RPC_FAILURE_RECORD;
            $extresponse->errors[] = "Unkown course $courseidentifier based on $courseidfield.";
            $extresponse->error = "Unkown course $courseidentifier based on $courseidfield.";
            if (function_exists('debug_trace')) {
                debug_trace("Unkown course based on $courseidfield with $courseidentifier ");
            }
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }

        // Getting role if default.
        if (empty($rolename)) {
            $rolename = $course->defaultrolename;
        }
        if (function_exists('debug_trace')) {
            debug_trace("Bounce to mnetadmin_rpc_assignrole");
        }
        $extresponse = mnetadmin_rpc_assign_role($callinguser, $targetuser, $rolename, 'id', CONTEXT_COURSE, $course->id,
                                                 $starttime, $endtime, $jsonresponse);
        if (!$jsonresponse) {
            return json_decode($extresponse);
        } else {
            return $extresponse;
        }
    } else {
        if (function_exists('debug_trace')) {
            debug_trace('remote source process');
        }

        // Make remote call.
        $userhostroot = $DB->get_field_select('mnet_host', 'wwwroot', " id = $USER->mnethostid AND deleted = 0 "); 
        if (!$userhostroot) {
            $extresponse->error = 'Unkown user host root (or deleted).';
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }

        if (!$DB->record_exists('mnet_host', array('wwwroot' => $whereroot, 'deleted' => 0))) {
            $extresponse->error = '$whereroot is unknown host or deleted.';
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }
        $rpcclient = new mnet_xmlrpc_client();
        $rpcclient->set_method('local/vmoodle/plugins/roles/rpclib.php/mnetadmin_rpc_remote_enrol');
        $caller = new StdClass();
        $caller->username = $USER->username;
        $caller->remoteuserhostroot = $userhostroot;
        $caller->remotehostroot = $CFG->wwwroot;
        $rpcclient->add_param($caller, 'struct'); // Caller user.
        $rpcclient->add_param($targetuser, 'string');
        $rpcclient->add_param($rolename, 'string');
        $rpcclient->add_param($whereroot, 'string');
        $rpcclient->add_param($courseidfield, 'string');
        $rpcclient->add_param($courseidentifier, 'string');
        $rpcclient->add_param($starttime, 'int');
        $rpcclient->add_param($endtime, 'int');
        $mnet_host = new mnet_peer();
        $mnet_host->set_wwwroot($whereroot);
        if (!$rpcclient->send($mnet_host)) {
            $extresponse->status = RPC_FAILURE;
            $extresponse->errors[] = 'REMOTE : '.implode("<br/>\n", @$rpcclient->errors);
            $extresponse->error = 'REMOTE : '.implode("<br/>\n", @$rpcclient->errors);
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }
        $response = json_decode($rpcclient->response);
        if ($response->status == 200) {
            $extresponse->message = 'remote enrol success';
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        } else {
            $extresponse->status = RPC_FAILURE;
            $extresponse->errors = array();
            $extresponse->errors[] = 'Remote application errors : ';
            $extresponse->errors = array_merge($extresponse->errors, $response->errors);
            $extresponse->error = 'Remote application error.';
            if ($jsonresponse) {
                return json_encode($extresponse);
            } else {
                return $extresponse;
            }
        }
    }
}

function mnetadmin_rpc_remote_enrol_wrapped($wrap) {
    if (function_exists('debug_trace')) {
        debug_trace("WRAP mnetadmin_rpc_remote_enrol : ".json_encode($wrap));
    }
    return mnetadmin_rpc_remote_enrol(@$wrap['callinguser'], @$wrap['targetuser'], @$wrap['rolename'], @$wrap['whereroot'],
                                      @$wrap['courseidfield'], @$wrap['courseidentifier'], @$wrap['starttime'], @$wrap['endtime'],
                                      @$wrap['json_response']);
}

/* ********** Utilities **************** */

function rpc_check_context_target($contextlevel, $contextidentityfield, $contextidentity, &$response, $jsonresponse) {
    global $DB;

    // Check context target.
    switch($contextlevel) {
        case CONTEXT_SYSTEM:
            $context = context_system::instance();
            break;

        case CONTEXT_COURSE:
            if (!preg_match('/id|shortname|idnumber/', $contextidentityfield)) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = "This fieldname ($contextidentityfield) does\'nt apply for this course context level.";
                $response->error = "This fieldname ($contextidentityfield) does\'nt apply for this course context level.";
                if ($jsonresponse) {
                    return json_encode($response);
                } else {
                    return $response;
                }
            }
            if (!$course = $DB->get_record('course', array($contextidentityfield => $contextidentity))) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = " Course Context $contextidentity not found based on $contextidentityfield.";
                $response->error = " Course Context $contextidentity not found based on $contextidentityfield.";
                if ($jsonresponse) {
                    return json_encode($response);
                } else {
                    return $response;
                }
            }
            $context = context_course::instance($course->id);
            break;

        case CONTEXT_MODULE:
            if (!preg_match('/id|idnumber/', $contextidentityfield)) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = " This fieldname ($contextidentityfield) does\'nt apply for this module context level.";
                $response->error = " This fieldname ($contextidentityfield) does\'nt apply for this module context level.";
            }
            if (!$cm = $DB->get_record('course_modules', array($contextidentityfield => $contextidentity))) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = " Course Module $contextidentity not found based on $contextidentityfield.";
                $response->error = " Course Module $contextidentity not found based on $contextidentityfield.";
            }
            if (!$context = context_module::instance($cm->id)) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = 'Course Module context not found.';
                $response->error = 'Course Module context not found.';
            }
            break;

        case CONTEXT_USER:
            if (!preg_match('/id|username|email|idnumber', $contextidentityfield)) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = " This fieldname ($contextidentityfield) does\'nt apply for this user context level.";
                $response->error = " This fieldname ($contextidentityfield) does\'nt apply for this user context level.";
            }
            if (!$user = $DB->get_record('user', array($contextidentityfield => $contextidentity))) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = " User $contextidentity not found based on $contextidentityfield. ";
                $response->error = " User $contextidentity not found based on $contextidentityfield. ";
            }
            if (!$context = context_user::instance($user->id)) {
                $response->status = RPC_FAILURE_RECORD;
                $response->errors[] = 'User context not found with userid '.$user->id.'.';
                $response->error = 'User context not found with userid '.$user->id.'.';
            }
            break;

        default:
            $response->status = RPC_FAILURE_RECORD;
            $response->errors[] = "Context level ($contextlevel) not implemented.";
            $response->error = "Context level ($contextlevel) not implemented.";
    }
    if (function_exists('debug_trace')) {
        debug_trace("Got context $contextlevel");
    }
    return $context;
}
