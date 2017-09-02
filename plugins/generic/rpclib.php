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
 * Created on 18 nov. 2010
 *
 */

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

require_once $CFG->dirroot.'/local/vmoodle/rpclib.php';

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

function dataexchange_rpc_fetch_config($user, $configkey, $module = '', $jsonrequired = true) {
    global $CFG, $USER;

    // Invoke local user and check his rights.
    if (!preg_match("/$configkey/", @$CFG->dataexchangesafekeys)) {
        if ($auth_response = invoke_local_user((array)$user)) {
            if ($jsonrequired) {
                return $auth_response;
            } else {
                return json_decode($auth_response);
            }
        }
    }

    // Creating response.
    $response = new StdClass();
    $response->status = RPC_SUCCESS;

    $response->value = get_config($module, $configkey);

    if ($jsonrequired) {
        return json_encode($response);
    } else {
        return $response;
    }
}

/**
 * Set on or off maintenance mode.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $message If empty, asks for a maintenance switch off.
 */
function mnetadmin_rpc_set_maintenance($user, $message, $hardmaintenance = false, $jsonrequired = true) {
    global $CFG, $USER;

    debug_trace('RPC '.json_encode($user));

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        } else {
            return json_decode($auth_response);
        }
    }

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    /*
     * Keep old hard signalled maintenance mode of 1.9. Can be usefull in case database stops
     * but needs a patch in config to catch this real case.
     */
    $filename = $CFG->dataroot.'/maintenance.html';

    if ($message != 'OFF') {
        debug_trace('RPC : Setting maintenance on');
        $file = fopen($filename, 'w');
        fwrite($file, stripslashes($message));
        fclose($file);
        set_config('maintenance_enabled', 1);
        set_config('maintenance_message', $message);
    } else {
        debug_trace('RPC : Setting maintenance off');
        unlink($filename);
        set_config('maintenance_enabled', 0);
        set_config('maintenance_message', null);
    }

    debug_trace('RPC Bind : Sending response');

    // Returns response (success or failure).
    return json_encode($response);
}

/**
 * Set some config values.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $key the config key.
 * @param string $value the config value.
 * @param string $plugin the config plugin, core if empty.
 */
function mnetadmin_rpc_set_config($user, $key, $value, $plugin, $jsonrequired = true) {
    global $CFG, $USER;

    debug_trace('RPC '.json_encode($user));

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        } else {
            return json_decode($auth_response);
        }
    }

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    set_config($key, $value, $plugin);

    debug_trace('RPC Bind : Sending response');

    // Returns response (success or failure).
    return json_encode($response);
}

/**
 * Purge internally all caches.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 */
function mnetadmin_rpc_purge_caches($user, $jsonrequired = true) {
    global $CFG, $USER;

    debug_trace('RPC '.json_encode($user));

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        }
        return json_decode($auth_response);
    }

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    purge_all_caches();

    debug_trace('RPC Bind : Sending response');
    // Returns response (success or failure).
    return json_encode($response);
}

/**
 * Receives a in message zip archive all local lang files to replace in the moodledata local lang customisation.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $plugins A comma separated list of plugin names.
 * @param string $langs A comma separated list of langs.
 */
function mnetadmin_rpc_get_local_langs($user, $plugins, $langs, $jsonrequired = true) {
    global $CFG, $USER;

    if (function_exists('debug_trace')) {
        debug_trace('RPC starts : Packing lang customisation');
    }

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        }
        return json_decode($auth_response);
    }

    // Start checking and collecting lang files to prepare.
    $pluginsinset = explode(',', $plugins);
    $langfiles = array();
    if (empty($pluginsinset)) {
        // Creating response.
        $response = new stdClass;
        $response->status = RPC_FAILURE;
        $response->error = "Empty plugin set";
        if ($jsonrequired) {
            return json_encode($response);
        }
        return $response;
    } else {
        if ($plugins == 'all') {
            $plugininset = array_values(VMoodle_CustomLang_Utils::list_components());
        }
        foreach ($plugininset as $inset) {
            $langfiles[] = VMoodle_CustomLang_Utils::get_component_filename($inset);
        }
    }

    // Start checking languages and prepare final archive catalog.
    $languages = explode(',', $langs);
    $locations = array();
    if (empty($languages)) {
        // Creating response.
        $response = new stdClass;
        $response->status = RPC_FAILURE;
        $response->error = "Empty lang set";
        if ($jsonrequired) {
            return json_encode($response);
        }
        return $response;
    } else {
        foreach ($languages as $lang) {
            $location = VMoodle_CustomLang_Utils::get_localpack_location($lang);
            if (is_dir($location)) {
                $locations[] = $location;
            }
        }

        if (empty($locations)) {
            // Creating response.
            $response = new stdClass;
            $response->status = RPC_FAILURE;
            $response->error = "None of the lang is available for customisation";
            if ($jsonrequired) {
                return json_encode($response);
            }
            return $response;
        }
    }

    // Finally build the archive.
    $archivehascontent = false;
    $ziparchive = new zip_archive();
    $tmparchive = $CFG->tempdir.'/vmoodle_rpc_get_customlang_'.uniqid().'.zip';
    $ziparchive->open($tmparchive, file_archive::CREATE, null);
    foreach ($locations as $langloc) {
        foreach ($langfiles as $lfile) {
            $archivefilename = basename($langloc).'/'.$lfile;
            $systemfilename = $langloc.'/'.$lfile;
            if (file_exists($systemfilename)) {
                $ziparchive->add_file_from_pathname($archivefilename, $systemfilename);
                $archivehascontent = true;
            }
        }
    }
    // Close and write.
    $ziparchive->close();

    if (!$archivehascontent) {
        $response = new stdClass;
        $response->status = RPC_FAILURE;
        $response->error = "No files found for customisation. Empty archive.";
        if ($jsonrequired) {
            return json_encode($response);
        }
        return $response;
    }

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    // Read the file and get raw zip content.
    $response->zipcontent = implode('', file($fileh));

    // Clean out the temporary archive.
    unlink($tmparchive);

    if (function_exists('debug_trace')) {
        debug_trace('RPC Bind : Sending response');
    }
    // Returns response (success or failure).
    if ($jsonrequired) {
        return json_encode($response);
    }
    return $response;
}

/**
 * Receives a in message zip archive all local lang files to replace in the moodledata local lang customisation.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $locallangzipcontent A string containing the zip fie content.
 */
function mnetadmin_rpc_set_local_langs($user, $locallangzipcontent, $jsonrequired = true) {
    global $CFG;

    if (function_exists('debug_trace')) {
        debug_trace('RPC starts : Receiving lang customisation');
    }

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        }
        return json_decode($auth_response);
    }

    $tmpfile = $CFG->tempdir.'/vmoodle_rpc_customlang'.uniqid().'.zip';
    if (!$TMP = fopen($tmpfile, 'wb')) {
        // Creating response.
        $response = new stdClass;
        $response->status = RPC_FAILURE;
        $response->error = "Failed writing archive";
    }
    fputs($TMP, $locallangzipcontent);
    fclose($TMP);

    $zippacker = get_file_packer();

    $zippacker->extract_to_pathname($tmpfile, $CFG->langlocalroot, null, null);

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    if (function_exists('debug_trace')) {
        debug_trace('RPC Bind : Sending response');
    }
    // Returns response (success or failure).
    if ($jsonrequired) {
        return json_encode($response);
    }
    return $response;
}
