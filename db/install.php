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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
defined('MOODLE_INTERNAL') || die();

/**
 * We must capture the old block_vmoodle table records and remove the old table
 *
 */
function xmldb_local_vmoodle_install() {
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    $table = new xmldb_table('block_vmoodle');
    if ($dbman->table_exists($table)) {
        $sql = "
            INSERT INTO
                {local_vmoodle}
            SELECT
                *
             FROM
                {block_vmoodle}
        ";
        $DB->execute($sql);

        $table = new xmldb_table('block_vmoodle');
        $dbman->drop_table($table);
    }

    // Register zabbix indicators if installed.
    // Note will only work with report_zabbix "pro" version.
    // This call is only a wrapper.
    if (is_dir($CFG->dirroot.'/report/zabbix')) {
        include_once($CFG->dirroot.'/report/zabbix/xlib.php');
        report_zabbix_register_plugin('local', 'vmoodle');
    }

    set_config('late_install', 1, 'local_vmoodle');

    return true;
}

/**
 * this function is called when viewing the vmoodle register to 
 * fix some mispositionned rpc registration (Moodle bug in upgradelib.php)
 */
function xmldb_local_vmoodle_late_install() {
    global $USER, $DB;

    // Cleanup all old mnetrpc functions related to blocks.
    $oldfunctions = $DB->get_records_select('mnet_rpc', ' xmlrpcpath LIKE "blocks/vmoodle%" ');
    if ($oldfunctions) {
        $DB->delete_records_select('mnet_rpc', ' xmlrpcpath LIKE "blocks/vmoodle%" ', array());
        foreach ($oldfunctions as $f) {
            $DB->delete_records('mnet_service2rpc', array('rpcid' => $f->id));
        }
    }

    $oldfunctions = $DB->get_records_select('mnet_remote_rpc', ' xmlrpcpath LIKE "blocks/vmoodle%" ');
    if ($oldfunctions) {
        $DB->delete_records_select('mnet_remote_rpc', ' xmlrpcpath LIKE "blocks/vmoodle%" ', array());
        foreach ($oldfunctions as $f) {
            $DB->delete_records('mnet_remote_service2rpc', array('rpcid' => $f->id));
        }
    }

    // We need to replace the word "vmoodleadminset/" with real subplugin path "local/vmoodle/plugins/".
    $rpcs = $DB->get_records('mnet_remote_rpc', array('plugintype' => 'vmoodleadminset'));

    if (!empty($rpcs)) {
        foreach ($rpcs as $rpc) {
            // Note that this should NOT happen as we can correctly encode path in db/mnet.php file.
            $rpc->xmlrpcpath = str_replace('vmoodleadminset/', 'local/vmoodle/plugins/', $rpc->xmlrpcpath);
            $DB->update_record('mnet_remote_rpc', $rpc);
        }
    }

    // We need to replace the word "vmoodleadminset/" with real subplugin path "local/vmoodle/plugins/".
    // Here we have a real issue of duplicate registering that needs to be fixed.
    $rpcs = $DB->get_records('mnet_rpc', array('plugintype' => 'vmoodleadminset'));

    if (!empty($rpcs)) {
        foreach ($rpcs as $rpc) {
            if (preg_match('#^local/vmoodle/plugins/#', $rpc->xmlrpcpath)) {
                // Actually nothing to do. Rpc is located fine.
                continue;
            } 

            $xmlrpcpath = str_replace('vmoodleadminset/', 'local/vmoodle/plugins/', $rpc->xmlrpcpath);
            if ($DB->get_record('mnet_rpc', ['xmlrpcpath' => $xmlrpcpath])) {
                // This function is already registered. So discard this record.
                $DB->delete_records('mnet_rpc', ['id' => $rpc->id]);
                $DB->delete_records('mnet_service2rpc', ['rpcid' => $rpc->id]);
            } else {
                // This is a new function. Convert its path.
                $rpc->xmlrpcpath = $xmlrpcpath;
                $DB->update_record('mnet_rpc', $rpc);
            }
        }
    }

    set_config('late_install', null, 'local_vmoodle');
    return true;
}