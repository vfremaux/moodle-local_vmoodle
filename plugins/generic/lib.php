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
 * Created on 22 sept. 2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

require_once $CFG->dirroot.'/mnet/xmlrpc/client.php';

/**
 * fetches remotely a configuration value
 * @param object $mnethost a mnet host record.
 * @param string $configkey the configuration key
 * @param string $module the module (frankenstyle). If empty, will fetch into the global config scope.
 */
function vmoodle_get_remote_config($mnethost, $configkey, $module = '') {
    global $CFG, $USER, $DB, $OUTPUT;

    if (empty($mnethost)) {
        return '';
    }

    if (!isset($USER)) {
        $user = $DB->get_record('user', array('username' => 'guest'));
    } else {
        if (empty($USER->id)) {
            $user = $DB->get_record('user', array('username' => 'guest'));
        } else {
            $user = $DB->get_record('user', array('id' => $USER->id));
        }
    }

    if (!$userhost = $DB->get_record('mnet_host', array('id' => $user->mnethostid, 'deleted' => 0))) {
        return '';
    }
    $user->remoteuserhostroot = $userhost->wwwroot;
    $user->remotehostroot = $CFG->wwwroot;

    // Get the sessions for each vmoodle that have same ID Number.
    $rpcclient = new mnet_xmlrpc_client();
    $rpcclient->set_method('local/vmoodle/plugins/generic/rpclib.php/dataexchange_rpc_fetch_config');

    $rpcclient->add_param($user, 'struct');
    $rpcclient->add_param($configkey, 'string');
    $rpcclient->add_param($module, 'string');

    $mnet_host = new mnet_peer();
    if (empty($mnet_host)) {
        return;
    }

    $mnet_host->set_wwwroot($mnethost->wwwroot);

    if ($rpcclient->send($mnet_host)) {
        $response = json_decode($rpcclient->response);
        if ($response->status == 200) {
            return $response->value;
        } else {
            if (debugging()) {
                echo $OUTPUT->notification('Remote RPC error '.implode('<br/>', $response->errors));
            }
        }
    } else {
        if (debugging()) {
            echo $OUTPUT->notification('Remote RPC failure '.implode('<br/', $rpcclient->error));
        }
    }
}

/**
 * Install generic plugin library.
 * @return boolean true if the installation is successfull, false otherwise.
 */
function genericlib_install() {
    global $DB;

    // No install operation.
    $result = true;

    // Installing Data Exchange.
    if ($previous = get_config('dataexchangesafekeys')) {
        $genericconfigs[] = $previous;
    }
    $genericconfigs[] = 'globaladminmessage';
    $genericconfigs[] = 'globaladminmessagecolor';
    set_config('dataexchangesafekeys', implode(',', $genericconfigs));

    return $result;
}

/**
 * Uninstall generic plugin library.
 * @return boolean true if the uninstallation is successfull, false otherwise.
 */
function genericlib_uninstall() {
    global $DB,$OUTPUT;

    set_config('dataexchangesafekeys', '');

    return true;
}

function vmoodle_get_all_plugins() {
    global $DB;

    function fix_names(&$a) {
        str_replace('/', '_', $a);
    }

    // Get all configurable plugins.
    $sql = '
        SELECT DISTINCT
            plugin
        FROM
            {config_plugins}
        WHERE
             name != "version"
    ';
    $configurables = $DB->get_records_sql($sql);
    $configurablenames = array_keys($configurables);

    $subplugintypes = core_component::get_plugin_types_with_subplugins();
    $subplugintypenames = array_keys($subplugintypes);

    array_walk($configurablenames, 'fix_names');

    $list = array();
    $plugintypes = core_component::get_plugin_types();
    $ptypes = array_keys($plugintypes);
    sort($ptypes);

    foreach ($ptypes as $type) {
        $mayhavesubs = in_array($type, $subplugintypenames);
        $pluginlist = core_component::get_plugin_list($type);
        foreach (array_keys($pluginlist) as $plugin) {
            if ($type != 'mod') {
                $normalized = $type.'_'.$plugin;
                $snormalized = $normalized;
            } else {
                $normalized = 'mod_'.$plugin;
                $snormalized = $plugin;
            }
            if (!in_array($normalized, $configurablenames)) {
                // Rip out all pugins that have no configuration.
                continue;
            }
            if ($type == 'filter') {
                $list[$normalized] = '['.$normalized.'] '.get_string('filtername', $snormalized);
            } else {
                $list[$normalized] = '['.$normalized.'] '.get_string('pluginname', $snormalized);
            }

            if ($mayhavesubs) {
                $plugindir = core_component::get_plugin_directory($type, $plugin);
                if (file_exists($plugindir.'/db/subplugins.php')) {
                    if ($subplugins = core_component::get_subplugins($normalized)) {
                        foreach ($subplugins as $subtype => $subplugins) {
                            foreach ($subplugins as $subplugin) {
                                $normalized = $subtype.'_'.$subplugin;
                                $snormalized = $normalized;
                                if (!in_array($normalized, $configurablenames)) {
                                    // Rip out all pugins that have no configuration.
                                    continue;
                                }
                                $list[$normalized] = '-- ['.$normalized.'] '.get_string('pluginname', $snormalized);
                            }
                        }
                    }
                }
            }
        }
    }
    return $list;
}