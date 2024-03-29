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
 * Describes a role allowance sync command.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

namespace vmoodleadminset_roles;

defined('MOODLE_INTERNAL') || die;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Exception;
use \local_vmoodle\commands\Command_Parameter;
use \context_system;
use \StdClass;
use \moodle_url;

class Command_Role_Allow_Sync extends Command {

    /**
     * Constructor.
     * @throws Command_Exception.
     */
    public function __construct() {
        global $DB;

        // Getting command description.
        $cmdname = vmoodle_get_string('cmdallowsyncname', 'vmoodleadminset_roles');
        $cmddesc = vmoodle_get_string('cmdallowsyncdesc', 'vmoodleadminset_roles');

        // Creating platform parameter.
        $label = get_string('platformparamsyncdesc', 'vmoodleadminset_roles');
        $platformparam = new Command_Parameter('platform', 'enum', $label, null, get_available_platforms());

        // Creating table parameter.
        $tables['assign'] = vmoodle_get_string('assigntable', 'vmoodleadminset_roles');
        $tables['override'] = vmoodle_get_string('overridetable', 'vmoodleadminset_roles');
        $tables['switch'] = vmoodle_get_string('switchtable', 'vmoodleadminset_roles');
        $tableparam = new Command_Parameter('table', 'enum', get_string('tableparamdesc', 'vmoodleadminset_roles'), null, $tables);

        // Creating role parameter.
        $roles = role_fix_names(get_all_roles(), \context_system::instance(), ROLENAME_ORIGINAL);
        $rolemenu = array();
        foreach ($roles as $r) {
            $rolemenu[$r->shortname] = $r->localname;
        }
        $label = get_string('roleparamsyncdesc', 'vmoodleadminset_roles');
        $roleparam = new Command_Parameter('role', 'enum', $label, null, $rolemenu);

        // Creating command.
        parent::__construct($cmdname, $cmddesc, array($platformparam, $tableparam, $roleparam));
    }

    /**
     * Execute the command.
     * @param mixed $hosts The host where run the command (may be wwwroot or an array).
     * @throws Command_Exception
     */
    public function run($hosts) {
        global $CFG, $USER;

        // Adding constants.
        require_once $CFG->dirroot.'/local/vmoodle/rpclib.php';

        // Checking capabilities.
        if (!has_capability('local/vmoodle:execute', \context_system::instance())) {
            throw new Command_Exception('insuffisantcapabilities');
        }

        // Getting role.
        $role = $this->get_parameter('role')->get_value();
        // Getting table.
        $table = $this->get_parameter('table')->get_value();
        // Checking hosts.
        $platform = $this->get_parameter('platform')->get_value();

        if (array_key_exists($platform, $hosts)) {
            $platforms = get_available_platforms();
            throw new Command_Role_Exception('syncwithitself', (object)array('role' => $role, 'platform' => $platforms[$platform]));
        }

        // Creating peer to read role configuration.
        $mnethost = new \mnet_peer();
        if (!$mnethost->bootstrap($this->get_parameter('platform')->get_value(), null, 'moodle')) {
            $response = (object) array(
                            'status' => RPC_FAILURE,
                            'error' => get_string('couldnotcreateclient', 'local_vmoodle', $platform)
                        );
            foreach ($hosts as $host => $name) {
                $this->results[$host] = $response;
            }
            return;
        }

        // Creating XMLRPC client to read role configuration in self.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/roles/rpclib.php/mnetadmin_rpc_get_role_allow_table');
        $rpcclient->add_param($table, 'string');
        $rpcclient->add_param($role, 'string');

        // Checking result.
        if (!($rpcclient->send($mnethost) && ($response = json_decode($rpcclient->response)) && $response->status == RPC_SUCCESS)) {
            // Creating response.
            if (!isset($response)) {
                $response = new \StdClass();
                $response->status = MNET_FAILURE;
                $response->errors[] = implode('<br/>', $rpcclient->get_errors($mnethost));
                $response->error = implode('<br/>', $rpcclient->get_errors($mnethost));
            }
            if (debugging()) {
                echo '<pre>';
                var_dump($rpcclient);
                ob_flush();
                echo '</pre>';
            }
            // Saving results.
            foreach ($hosts as $host => $name) {
                $this->results[$host] = $response;
            }
            return;
        }

        // Cleaning up some memory.
        $allowtable = $response->value;

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

        // Creating XMLRPC client.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/roles/rpclib.php/mnetadmin_rpc_set_role_allow');
        $rpcclient->add_param($table, 'string');
        $rpcclient->add_param($allowtable, 'array'); // Allow table to send
        $rpcclient->add_param(true, 'boolean'); // Json return.

        // Sending requests.
        foreach ($mnethosts as $mnethost) {
            // Sending request.
            if (!$rpcclient->send($mnethost)) {
                $response = new \StdClass();
                $response->status = RPC_FAILURE;
                $response->errors[] = implode('<br/>', $rpcclient->get_errors($mnethost));
                $response->error = 'Set remote role capability : Remote call error';
                if (debugging()) {
                    echo '<pre>';
                    var_dump($rpcclient);
                    ob_flush();
                    echo '</pre>';
                }
            } else {
                // Expecting empty string. Just SUCCESS signal.
                $response = json_decode($rpcclient->response);
            }
            // Recording response.
            $responses[$mnethost->wwwroot] = $response;
        }
        // Saving results.
        $this->results = $responses + $this->results;
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
        if (!$this->has_run()) {
            throw new Command_Exception('commandnotrun');
        }
        // Checking host (general result isn't provide in this kind of command).
        if (is_null($host) || !array_key_exists($host, $this->results)) {
            return null;
        }
        $result = $this->results[$host];
        // Checking key.
        if (is_null($key)) {
            return $result;
        } else if (property_exists($result, $key)) {
            return $result->$key;
        } else {
            return null;
        }
    }
}