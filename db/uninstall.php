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

defined('MOODLE_INTERNAL') || die;

function xmldb_local_vmoodle_uninstall() {
    global $DB;

    // Note this should be NOT really required if standard uninstall does the full job.

    // Dismount all XML-RPC.
    if ($service = $DB->get_record('mnet_service', array('name' => 'mnetadmin'))) {
        $DB->delete_records('mnet_service', array('id' => $service->id));
        $DB->delete_records('mnet_rpc', array('plugintype' => 'vmoodleadminset'));
        $DB->delete_records('mnet_remote_rpc', array('plugintype' => 'vmoodleadminset'));
        $DB->delete_records('mnet_rpc', array('pluginname' => 'vmoodle'));
        $DB->delete_records('mnet_remote_rpc', array('pluginname' => 'vmoodle'));
        $DB->delete_records('mnet_service2rpc', array('serviceid' => $service->id));
        $DB->delete_records('mnet_remote_service2rpc', array('serviceid' => $service->id));
        $DB->delete_records('mnet_host2service', array('serviceid' => $service->id));
    }

    set_config('late_install', null, 'local_vmoodle');

    return true;
}
