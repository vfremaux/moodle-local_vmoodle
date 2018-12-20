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
 * This file contains the mnet services for the user_mnet_host plugin
 *
 * @since 2.0
 * @package local
 * @subpackage vmoodle
 * @copyright 2012 Valery Fremaux
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

$publishes = array('dataexchange' => array(
                   'servicename' => 'dataexchange',
                   'description' => vmoodle_get_string('dataexchange_name', 'vmoodleadminset_generic'),
                   'apiversion' => 1,
                   'classname'  => '',
                   'filename'   => 'rpclib.php',
                   'methods'    => array(
                   'dataexchange_rpc_fetch_config'),
    ),
    'mnetadmin' => array('servicename' => 'mnetadmin',
                        'description' => get_string('mnetadmin_name', 'local_vmoodle'),
                        'apiversion' => 1,
                        'classname'  => '',
                        'filename'   => 'rpclib.php',
                        'methods'    => array('mnetadmin_rpc_set_config',
                                              'mnetadmin_rpc_set_maintenance',
                                              'mnetadmin_rpc_purge_caches',
                                              'mnetadmin_rpc_get_local_langs',
                                              'mnetadmin_rpc_set_local_langs',
                                              'mnetadmin_rpc_import_file',
                                              'mnetadmin_rpc_get_remote_file',
                                              'mnetadmin_rpc_load_plugin_config'),
    ),
);
$subscribes = array(
    'dataexchange' => array('dataexchange_rpc_fetch_config' => 'local/vmoodle/plugins/generic/rpclib.php/dataexchange_rpc_fetch_config'),
    'mnetadmin' => array('mnetadmin_rpc_set_maintenance' => 'local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_set_maintenance',
                         'mnetadmin_rpc_set_config' => 'local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_set_config',
                         'mnetadmin_rpc_get_local_langs' => 'local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_get_local_langs',
                         'mnetadmin_rpc_set_local_langs' => 'local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_set_local_langs',
                         'mnetadmin_rpc_purge_caches' => 'local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_purge_caches',
                         'mnetadmin_rpc_import_file' => 'local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_import_file',
                         'mnetadmin_rpc_get_remote_file' => 'local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_get_remote_file',
                         'mnetadmin_rpc_load_plugin_config' => 'local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_load_plugin_config',
                         ),
);
