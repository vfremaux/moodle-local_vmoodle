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

require_once($CFG->dirroot.'/local/vmoodle/plugins/generic/classes/Tool_CustomLang_Utils.php');

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

    // Be really sure we drop caches.
    cache_helper::invalidate_by_definition('core', 'config');

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
 * Set a full set of configs in a pugin definition.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $key the config key.
 * @param string $value the config value.
 * @param string $plugin the config plugin, core if empty.
 */
function mnetadmin_rpc_load_plugin_config($user, $plugin, $configstub, $jsonrequired = true) {
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

    $config = json_decode($configstub);
    foreach ($config as $key => $value) {
        if ($key == 'version') {
            // Protect version.
            continue;
        }
        // Invalidates cache.
        set_config($key, $value, $plugin);
    }

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
    $langfiles = array();
    if (empty($plugins)) {
        // Creating response.
        $response = new stdClass;
        $response->status = RPC_FAILURE;
        $response->error = "Empty plugin set";
        debug_trace("Empty pluginset");
        if ($jsonrequired) {
            return json_encode($response);
        }
        return $response;
    } else {
        if (in_array('all', $plugins)) {
            $plugininset = array_values(\vmoodleadminset_generic\VMoodle_CustomLang_Utils::list_components());
        }
        foreach ($plugins as $inset) {
            $langfiles[] = \vmoodleadminset_generic\VMoodle_CustomLang_Utils::get_component_filename($inset);
        }
    }

    // Start checking languages and prepare final archive catalog.
    $locations = array();
    if (empty($langs)) {
        // Creating response.
        $response = new stdClass;
        $response->status = RPC_FAILURE;
        $response->error = "Empty lang set";

        if ($jsonrequired) {
            return json_encode($response);
        }
        return $response;
    } else {

        if (in_array('all', $langs)) {
            $langs = \vmoodleadminset_generic\VMoodle_CustomLang_Utils::get_installed_langs();
        }

        foreach ($langs as $lang) {
            $location = \vmoodleadminset_generic\VMoodle_CustomLang_Utils::get_localpack_location($lang);
            if (is_dir($location)) {
                $locations[] = $location;
            }
        }

        if (empty($locations)) {
            // Creating response.
            $response = new stdClass;
            $response->status = RPC_FAILURE_DATA;
            $response->error = "None of the lang is available for customisation";
            $response->errors = "None of the lang is available for customisation";
            if (function_exists('debug_trace')) {
                debug_trace("No locations available for custom lang strings ");
            }
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
    if (function_exists('debug_trace')) {
        debug_trace("Creating zip archive $tmparchive");
    }
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
        $response->errors = "No files found for customisation. Empty archive.";
        if ($jsonrequired) {
            return json_encode($response);
        }
        return $response;
    }

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    // Read the file and get raw zip content.
    $response->zipcontent = base64_encode(implode('', file($tmparchive)));

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
        $response->errors = "Failed writing archive";
    }
    fputs($TMP, base64_decode($locallangzipcontent));
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

/**
 * Receives file content an file identitiy descriptor and stores an identical file in the local filesystem. Only site level
 * files can be exchanged.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $component The component name.
 * @param string $filearea the file area.
 * @param string $itemid The origin itemid. Usually should be 0, but some other cases may arise.
 * @param string $filename The full pathed name of the file.
 * @param string $filecontent The encoded (base64) file content.
 * @param boolean $jsonrequired Is json required for return ?.
 */
function mnetadmin_rpc_import_file($user, $component, $filearea, $itemid, $filename, $filecontent, $jsonrequired = true) {

    if (function_exists('debug_trace')) {
        debug_trace('RPC starts : Receiving moodle file');
    }

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        }
        return json_decode($auth_response);
    }

    $context = context_system::instance();

    $filerec = new StdClass;
    $filerec->contextid = $context->id;
    $filerec->component = $component;
    $filerec->filearea = $filearea;
    $filerec->itemid = $itemid;
    $filerec->filepath = dirname($filename).'/';
    $filerec->filepath = str_replace('//', '/', $filerec->filepath); // fixes eventual slash doubling
    $filerec->filename = basename($filename);

    $fs = get_file_storage();

    // Delete old file in the way.

    if ($oldfile = $fs->get_file($filerec->contextid,
                                 $filerec->component,
                                 $filerec->filearea,
                                 $filerec->itemid,
                                 $filerec->filepath,
                                 $filerec->filename)) {
        if (function_exists('debug_trace')) {
            debug_trace("Deleting old file ".print_r($filerec, true));
        }
        $oldfile->delete();
    }

    // Store new file.
    if (function_exists('debug_trace')) {
        debug_trace("Creating old file ".print_r($filerec, true));
    }
    $newfile = $fs->create_file_from_string($filerec, base64_decode($filecontent));

    if ($newfile) {
        $return = new StdClass;
        $return->status = RPC_SUCCESS;
    } else {
        $return = new StdClass;
        $return->status = RPC_FAILURE_DATA;
        $return->error = "Failed to create local file";
        $return->errors[] = "Failed to create local file";
    }

    if ($jsonrequired) {
        return json_encode($return);
    }
    return $return;
}

/**
 * Get a remote file and send it to the caller if exists.
 * this is similar to the file download WS procedure but within a mnet trusted network and without token setup.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $component The component name.
 * @param string $filearea the file area.
 * @param string $itemid The origin itemid. Usually should be 0, but some other cases may arise.
 * @param string $filename The full pathed name of the file.
 * @param boolean $jsonrequired Is json required for return ?.
 */
function mnetadmin_rpc_get_remote_file($user, $component, $filearea, $itemid, $filename, $jsonrequired = true) {

    if (function_exists('debug_trace')) {
        debug_trace('RPC starts : Getting local moodle file');
    }

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        }
        return json_decode($auth_response);
    }

    $context = context_system::instance();

    $filerec = new StdClass;
    $filerec->contextid = $context->id;
    $filerec->component = $component;
    $filerec->filearea = $filearea;
    $filerec->itemid = $itemid;
    $filerec->filepath = dirname($filename).'/';
    $filerec->filename = basename($filename);

    $fs = get_file_storage();

    if ($file = $fs->get_file($filerec->contextid,
                                 $filerec->component,
                                 $filerec->filerarea,
                                 $filerec->itemid,
                                 $filerec->filepath,
                                 $filerec->filename)) {
        $return = new StdClass;
        $return->filecontent = base64_encode($file->get_content());
        $return->status = RPC_SUCCESS;
    } else {
        $return = new StdClass;
        $return->status = RPC_FAILURE_DATA;
        $return->error = "Cannot find required file";
        $return->errors[] = "Cannot find required file";
    }

    if ($jsonrequired) {
        return json_encode($return);
    }
    return $return;
}

/**
 * Get a local table content and send it to caller as an encoded data serialzed blob..
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $table The table name.
 * @param boolean $jsonrequired Is json required for return ?.
 */
function mnetadmin_rpc_get_table_data($user, $table, $select, $jsonrequired = true) {
    global $DB;

    if (function_exists('debug_trace')) {
        debug_trace('RPC starts : Getting local moodle data records');
    }

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        }
        return json_decode($auth_response);
    }

    try {
        $totalcount = $DB->count_records($table, []);
        if (!empty($select)) {
            $records = $DB->get_records_select($table, $select, []);
        } else {
            $records = $DB->get_records($table, []);
        }
        $return = new StdClass;
        $return->status = RPC_SUCCESS;
        if (!empty($records)) {
            $data = json_encode($records);
            $return->tablecontent = base64_encode($data); // Ensures any binary content passes through.
            $return->count = count($records);
            $return->countall = $totalcount;
        } else {
            $return->tablecontent = ''; // Empty response.
            $return->count = 0; // Empty response.
            $return->countall = $totalcount;
        }
    } catch (Exception $e) {
        $return = new StdClass;
        $return->status = RPC_FAILURE_DATA;
        $return->error = "Cannot find required data from table";
        $return->errors[] = "Cannot find required data from table";
        $return->errors[] = $DB->get_last_error();
    }

    debug_trace($return);

    if ($jsonrequired) {
        return json_encode($return);
    }
    return $return;
}

/**
 * Import a table dataset and replace the existing data with it.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $table The table name.
 * @param string $content The records as base64 encoded serialized json.
 * @param boolean $jsonrequired Is json required for return ?.
 */
function mnetadmin_rpc_import_table_content($user, $table, $content, $jsonrequired = true) {
    global $DB;

    if (function_exists('debug_trace')) {
        debug_trace('RPC starts : Importing data in table '.$table);
    }

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        }
        return json_decode($auth_response);
    }

    try {
        // Delete all records.
        $DB->delete_records($table, []);

        $deserialized = base64_decode($content);
        $records = json_decode($deserialized);

        $cpt = 0;
        if (!empty($records)) {
            foreach($records as $rec) {
                $DB->insert_record($table, $rec);
                $cpt++;
            }
        }

        $return = new StdClass;
        $return->status = RPC_SUCCESS;
        $return->message = $cpt.' records imported.';
    } catch (Exception $e) {
        $return = new StdClass;
        $return->status = RPC_FAILURE_DATA;
        $return->error = "Cannot import required data in table";
        $return->errors[] = "Cannot import required data in table";
        $return->errors[] = $DB->get_last_error();
    }

    if ($jsonrequired) {
        return json_encode($return);
    }
    return $return;
}