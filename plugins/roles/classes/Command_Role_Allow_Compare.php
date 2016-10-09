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
 * Describes a role allowance comparison command.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace vmoodleadminset_roles;

defined('MOODLE_INTERNAL') || die;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Parameter;
use \local_vmoodle\commands\Command_Exception;
use \StdClass;
use \moodle_url;

require_once($CFG->libdir.'/accesslib.php');

class Command_Role_Allow_Compare extends Command {

    /**
     * The role capabilities
     */
    private $capabilities = array();

    /**
     * The html report
     */
    private $report;

    /**
     * Constructor.
     * @throws Command_Exception.
     */
    public function __construct() {
        global $DB;

        // Getting command description.
        $cmdname = vmoodle_get_string('cmdallowcomparename', 'vmoodleadminset_roles');
        $cmddesc = vmoodle_get_string('cmdallowcomparedesc', 'vmoodleadminset_roles');

        // Creating table parameter.
        $tables['assign'] = vmoodle_get_string('assigntable', 'vmoodleadminset_roles');
        $tables['override'] = vmoodle_get_string('overridetable', 'vmoodleadminset_roles');
        $tables['switch'] = vmoodle_get_string('switchtable', 'vmoodleadminset_roles');
        $label = get_string('tableparamdesc', 'vmoodleadminset_roles');
        $tableparam = new Command_Parameter('table', 'enum', $label, null, $tables);

        // Creating command.
        parent :: __construct($cmdname, $cmddesc, $tableparam);
    }

    /**
     * Execute the command.
     * @param mixed $hosts The host where run the command (may be wwwroot or an array).
     * @throws Command_Exception.
     */
    public function run($hosts) {
        global $CFG, $USER;

        // Adding constants.
        require_once $CFG->dirroot.'/local/vmoodle/rpclib.php';

        // Checking capabilities.
        if (!has_capability('local/vmoodle:execute', \context_system::instance()))
            throw new Command_Exception('insuffisantcapabilities');

        // Getting role.
        $table = $this->get_parameter('table')->get_value();

        // Creating XMLRPC client to read role configuration.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/roles/rpclib.php/mnetadmin_rpc_get_role_allow_table');
        $rpcclient->add_param($table, 'string');
        $rpcclient->add_param('', 'string'); // Get for all roles.

        // Initializing responses.
        $responses = array();

        // Creating peers.
        $mnethosts = array();
        foreach ($hosts as $host => $name) {
            $mnethost = new \mnet_peer();
            if ($mnethost->bootstrap($host, null, 'moodle')) {
                $mnethosts[] = $mnethost;
            } else {
                $responses[$host] = (object) array(
                    'status' => MNET_FAILURE,
                    'error' => get_string('couldnotcreateclient', 'local_vmoodle', $host)
                );
            }
        }

        // Sending requests.
        foreach ($mnethosts as $mnethost) {
            // Sending request.
            if (!$rpcclient->send($mnethost)) {
                $response = new \StdClass();
                $response->status = MNET_FAILURE;
                $response->errors[] = implode('<br/>', $rpcclient->get_errors($mnethost));
                if (debugging()) {
                    echo '<pre>';
                    var_dump($rpcclient);
                    echo '</pre>';
                }
            } else {
                $response = json_decode($rpcclient->response);
            }
            // Recording response.
            $responses[$mnethost->wwwroot] = $response;
            // Recording capabilities.
            if ($response->status == RPC_SUCCESS) {
                $this->capabilities[$mnethost->wwwroot] = $response->value;
            }
        }
        // Saving results.
        $this->results = $responses + $this->results;

        // Processing results.
        $this->_process();
    }

    /**
     * Get the result of command execution for one host.
     * @param string $host The host to retrieve result (optional, if null, returns general result).
     * @param string $key The information to retrieve (ie status, error / optional).
     * @return mixed The result or null if result does not exist.
     * @throws Command_Exception.
     */
    public function get_result($host = null, $key = null) {
        // Checking if command has been runned.
        if (!$this->has_run())
            throw new Command_Exception('commandnotrun');

        // Checking host (general result isn't provide in this kind of command).
        if (is_null($host)) {
            return $this->report;
        } else {
            if (!array_key_exists($host, $this->results)) {
                return null;
            }
        }
        $result = $this->results[$host];

        // Checking key.
        if (is_null($key)) {
            return $result;
        } else {
            if (property_exists($result, $key)) {
                return $result-> $key;
            } else {
                return null;
            }
        }
    }

    /**
     * Process the role comparision.
     * @throws Commmand_Exception.
     */
    private function _process() {
        global $CFG,$DB,$OUTPUT;

        // Checking if command has been runned.
        if (!$this->has_run()) {
            throw new Command_Exception('commandnotrun');
        }

        // Getting table name.
        $table = $this->get_parameter('table')->get_value();

        // Getting hosts.
        $hosts = array_keys($this->capabilities);
        $host_labels = get_available_platforms();

        // Getting local roles.
        $roles = $DB->get_records('role', null, '', 'sortorder');

        /*
         * processing results
         */

        // Creating header.
        $label = get_string($table.'table', 'vmoodleadminset_roles');
        $help = help_button_vml('rolelib', 'allowcompare', 'vmoodleadminset_roles');
        $this->report = '<h3>'.get_string('allowcompare', 'vmoodleadminset_roles', $label.$help.'</h3>';
        // Adding edit role link.
        $params = array('roleid' => $role->id, 'action' => 'edit');
        $buttonurl = new moodle_url('/admin/roles/allow.php?mode='.$table, $params);
        $label = get_string('editallowtable', 'vmoodleadminset_roles');
        $this->report.= '<center><p>'.$OUTPUT->single_button($buttonurl, $label, 'get').'</p></center>';
        // Creation form.
        $actionurl = new moodle_url('/local/vmoodle/plugins/roles/controller.rolelib.sadmin.php', array('what' => 'syncallow'));
        $this->report .= '<form action="'.$actionurl.'" method="post" onsubmit="return validate_syncrole()">';
        $this->report .= '<input id="target" type="hidden" name="target" value=""/>';
        $this->report .= '<input id="role" type="hidden" name="role" value=""/>';
        $this->report .= '<input id="source_platform" type="hidden" name="source_platform" value=""/>';

        // Creating table.
        $this->report.= '<table id="allowcompare" class="generaltable boxaligncenter" style="min-width: 75%;"><tbody>';

        // Creating header.
        $this->report.= '<tr><th scope="col" class="header c0" style="vertical-align: bottom; text-align: left;">&nbsp</th>';
        $col = 1;
        foreach ($hosts as $host) {
            $this->report.= '<th id="cap_'.$col.'" scope="col" class="header c'.$col.'" style="vertical-align: bottom; text-align: center;"><label for="platform_'.$col.'"><img src="'.$CFG->wwwroot.'/local/vmoodle/plugins/roles/draw_platformname.php?caption='.urlencode($host_labels[$host]).'" alt="'.$host_labels[$host].'"/></label><br/><input id="platform_'.$col.'" type="checkbox" name="platforms[]" value="'.$host.'" disabled="disabled"/></th>';
            $col++;
        }
        $this->report.= '</tr>';

        // Initializing variables.
        $row = 0;
        // Creating table data.
        foreach ($allroles as $rolename => $role) {
            $localrole = $DB->get_field('role', 'name', array('shortname' => $rolename));
            $displayrole = ($localrole) ? $localrole : '--'.$rolename.'--';
            $this->report .= "<tr valign='top'>$displayrole</td>";
            $row++;
        }

        // Closing table.
        $this->report.= '</tboby></table><br/>';
        $this->report .= '<center><input type="submit" value="'.vmoodle_get_string('synchronize', 'vmoodleadminset_roles').'"/>';
        $this->report .= '<div id="allowcompare_validation_message"></div></center></form><br/><br/>';
    }
}