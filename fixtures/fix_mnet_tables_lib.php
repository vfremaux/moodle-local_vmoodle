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

function fix_mnet_tables_fixture() {
    global $DB;

    $rpcs = array();
    $services = array();
    $badrpcs = array();
    $badservices = array();

    echo "<pre>\n";

    mtrace('Fixing vmoodle subplugins rpcs');
    $select = ' xmlrpcpath LIKE "vmoodleadminset%" ';
    $badvmoodlesubs = $DB->get_records_select('mnet_rpc', $select);
    if (!empty($badvmoodlesubs)) {
        $deleted = 0;
        $renamed = 0;
        foreach ($badvmoodlesubs as $sub) {
            $sub->xmlrpcpath = str_replace('vmoodleadminset', 'local/vmoodle/plugins', $sub->xmlrpcpath);
            $select = ' xmlrpcpath = ? AND id <> ? ';
            if ($DB->record_exists_select('mnet_rpc', $select, array($sub->xmlrpcpath, $sub->id))) {
                // Another record is in place for this rpc function. Delete current. Further will clean it all.
                $DB->delete_records('mnet_rpc', array('id' => $sub->id));
                $deleted++;
            } else {
                // Keep this one remapped.
                $DB->update_record('mnet_rpc', $sub);
                $renamed++;
            }
        }
        mtrace("Fixed vmoodle subs rpcs : $deleted were deleted, $renamed where renamed");
    }

    // Preclean all bindings that are not mapped to real records.
    mtrace('Fixing unattached bindings');
    $DB->execute(" DELETE FROM {mnet_service2rpc} WHERE rpcid NOT IN (SELECT id FROM {mnet_rpc}) ");
    $DB->execute(" DELETE FROM {mnet_service2rpc} WHERE serviceid NOT IN (SELECT id FROM {mnet_service}) ");
    $DB->execute(" DELETE FROM {mnet_host2service} WHERE hostid NOT IN (SELECT id FROM {mnet_host}) ");
    $DB->execute(" DELETE FROM {mnet_host2service} WHERE serviceid NOT IN (SELECT id FROM {mnet_service}) ");

    if ($allrpcs = $DB->get_records('mnet_rpc', array(), 'id')) {

        // First destroy any surnumerous rpc (higher id, same path).
        mtrace('Cleaning RPC records');
        $g = 0;
        $b = 0;
        foreach ($allrpcs as $rpc) {
            if (array_key_exists($rpc->xmlrpcpath, $rpcs)) {
                // Register and remove.
                $badrpcs[$rpc->id] = $rpcs[$rpc->xmlrpcpath]->id;
                $DB->delete_records('mnet_rpc', array('id' => $rpc->id));
                $b++;
            } else {
                // Record xmlRPC and indexes.
                $rpcs[$rpc->xmlrpcpath] = $rpc;
                $rpcids[$rpc->id] = $rpc->xmlrpcpath;
                $g++;
            }
        }
        mtrace("$b bad / $g good records found.");

        // Second destroy any surnumerous service (higher id, same name) and keep ids.
        mtrace('Cleaning Service records');
        $g = 0;
        $b = 0;
        $allservices = $DB->get_records('mnet_service', array(), 'id');
        foreach ($allservices as $s) {
            if (array_key_exists($s->name, $services)) {
                // Register and remove.
                $badservices[$s->id] = $services[$s->name]->id;
                $DB->delete_records('mnet_service', array('id' => $s->id));
                $b++;
            } else {
                // Record service and indexes.
                $services[$s->name] = $s;
                $servicesids[$s->id] = $s->name;
                $g++;
            }
        }
        mtrace("$b bad / $g good records found.");

        // Now control if some bad services were host bound.
        mtrace('Checking RPC to Service bindings');
        foreach ($badrpcs as $badid => $goodid) {
            if ($bindings = $DB->get_records('mnet_service2rpc', array('rpcid' => $badid))) {
                foreach ($bindings as $b) {
                    if (array_key_exists($b->serviceid, $badservices)) {
                        // Bad rpc is registered in bad service. Just check good ones are bind them correctly if missing.
                        $goodservice = $servicesids[$badservices[$b->serviceid]];
                        $params = array('rpcid' => $goodid, 'serviceid' => $goodservice);
                        if (!$goodbind = $DB->get_record('mnet_service2rpc', $params)) {
                            $binding = new StdClass();
                            $binding->rpcid = $goodrpc;
                            $binding->serviceid = $goodservice;
                            $DB->insert_record('mnet_service2rpc', $binding);
                        }
                    }
                }
            }
        }

        // Finally clean all bindings that are surnumerous.
        mtrace('Back cleaning');
        $DB->execute(" DELETE FROM {mnet_service2rpc} WHERE rpcid NOT IN (SELECT id FROM {mnet_rpc}) ");
        $DB->execute(" DELETE FROM {mnet_service2rpc} WHERE serviceid NOT IN (SELECT id FROM {mnet_service}) ");

        // Now eliminate all bad host to service mapping.

        mtrace('Checking host bindings');
        $b = 0;
        $g = 0;
        if ($hostbindings = $DB->get_records('mnet_host2service')) {
            mtrace("fixing host bindings");
            foreach ($hostbindings as $hb) {
                if (array_key_exists($hb->serviceid, $servicesids)) {
                    // This is a good case. Good serviceid.
                    $g++;
                    continue;
                }
                if (array_key_exists($hb->serviceid, $badservices)) {
                    $goodservice = $servicesids[$badservices[$hb->serviceid]];
                    $params = array('hostid' => $hb->hostid, 'serviceid' => $goodservice);
                    if (!$goodbind = $DB->get_record('mnet_host2service', $params)) {
                        $binding = new StdClass();
                        $binding->hostid = $hb->hostid;
                        $binding->serviceid = $goodservice;
                        $DB->insert_record('mnet_service2rpc', $binding);
                        $b++;
                    } else {
                        $g++;
                    }
                }
            }
        }
        mtrace("$b bad fixed / $g good host bindings found.");

        mtrace('Finished');

        echo '</pre>';
    }
}