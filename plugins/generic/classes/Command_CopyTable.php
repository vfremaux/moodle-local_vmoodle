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
 * Describes meta-administration plugin's command for copying a full table from a source to a set of targets.
 * 
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace vmoodleadminset_generic;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Parameter;
use \StdClass;
use \context_system;

class Command_CopyTable extends Command {

    /**
     * If command's result should be returned.
     */
    private $returned;

    /**
     * Constructor.
     * @param string $name Command's name.
     * @param string $description Command's description.
     * @param string $sql SQL command.
     * @param string $parameters Command's parameters (optional / could be null, Command_Parameter object or Command_Parameter array).
     * @param Command $rpcommand Retrieve platforms command (optional / could be null or Command object).
     * @throws Command_Exception
     */
    public function __construct($rpcommand = null) {
        global $vmcommandconstants, $DB;

        // Getting command description.
        $cmdname = vmoodle_get_string('cmdcopytable', 'vmoodleadminset_generic');
        $cmddesc = vmoodle_get_string('cmdcopytable_desc', 'vmoodleadminset_generic');

        $platforms = get_available_platforms();

        $platforms = array_merge(array('0' => get_string('localtable', 'vmoodleadminset_generic')), $platforms);

        // Creating platform parameter.
        $label = get_string('platformparamtable_desc', 'vmoodleadminset_generic');
        $platformparam = new Command_Parameter('platform', 'enum', $label, null, $platforms);

        // Get all system level files that are true files.
        $params = array('contextid' => context_system::instance()->id);
        $tables = $DB->get_tables(); // Come without prefix. Note that only tables installed in main moodle can be moved.

        $tablesmenu = array();
        if (!empty($tables)) {
            foreach($tables as $table) {
                $tablesmenu[$table] = $table;
            }
        }

        // Creating platform parameter. This is the source platform.
        $label = get_string('tableparam_desc', 'vmoodleadminset_generic');
        $tableparam = new Command_Parameter('table', 'enum', $label, null, $tablesmenu);

        // Creating platform parameter. This is the source platform.
        $label = get_string('selectparam_desc', 'vmoodleadminset_generic');
        $selectparam = new Command_Parameter('select', 'text', $label, null);

        // Creating Command.
        parent::__construct($cmdname, $cmddesc, array($platformparam, $tableparam, $selectparam), $rpcommand);
    }

    /**
     * Execute the command.
     * @param mixed $host The hosts where run the command (may be wwwroot or an array).
     * @throws Command_SetConfig_Exception
     */
    public function run($hosts) {
        global $CFG, $USER;

        // Set Config. Adding constants.
        require_once $CFG->dirroot.'/local/vmoodle/rpclib.php';

        // Set Config. Checking host.
        if (!is_array($hosts)) {
            $hosts = array($hosts => 'Unnamed host');
        }

        // Set Config. Checking capabilities.
        if (!has_capability('local/vmoodle:execute', \context_system::instance())) {
            throw new Command_CopyTable_Exception('insuffisantcapabilities');
        }

        // Set Config. Initializing responses.
        $responses = array();

        // Creating peers.
        $mnet_hosts = array();
        foreach ($hosts as $host => $name) {
            $mnet_host = new \mnet_peer();
            if ($mnet_host->bootstrap($host, null, 'moodle')) {
                $mnet_hosts[] = $mnet_host;
            } else {
                $responses[$host] = (object) array('status' => MNET_FAILURE, 'error' => get_string('couldnotcreateclient', 'local_vmoodle', $host));
            }
        }

        // Set Config. Getting command.
        $command = $this->is_returned();

        // Get table name.
        $table = $this->get_parameter('table')->get_value();

        // Get the select clause
        $select = $this->get_parameter('select')->get_value();

        // Resolve table source and get a remote table content if remote.
        $source = $this->get_parameter('platform')->get_value();

        if ($source) {
            // Creating peer to read plugins configuration from the designated peer.
            $mnethost = new \mnet_peer();
            if (!$mnethost->bootstrap($this->get_parameter('platform')->get_value(), null, 'moodle')) {
                $response = (object) array(
                    'status' => MNET_FAILURE,
                    'error' => get_string('couldnotcreateclient', 'local_vmoodle', $platform)
                );

                // If we fail, we fail for all.
                foreach ($hosts as $host => $name) {
                    $this->results[$host] = $response;
                }
                return;
            }

            // debug_trace('Launching get_table_data on source ');
            // Creating XMLRPC client to get the remote customisation language pack.
            $rpcclient = new \local_vmoodle\XmlRpc_Client();
            $rpcclient->set_method('local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_get_table_data');
            $rpcclient->add_param($table, 'string'); // tablename.
            $rpcclient->add_param($select, 'string'); // filtering select clause.
            $rpcclient->add_param(true, 'string'); // jsonrequired.

            // debug_trace($rpcclient->response);

            // Checking result.
            if (!($rpcclient->send($mnethost) && ($response = json_decode($rpcclient->response)) && $response->status == RPC_SUCCESS)) {
                // Creating response.
                if (!isset($response)) {
                    $response = new Stdclass();
                    $response->status = MNET_FAILURE;
                    $response->errors[] = implode('<br/>', $rpcclient->get_errors($mnethost));
                    $response->error = implode('<br/>', $rpcclient->get_errors($mnethost));
                }

                $responses = array();
                // Sending requests.
                foreach ($hosts as $host => $name) {
                    $responses[$host] = $response;
                }

                $this->results = $responses + $this->results;

                // Don't go futher.
                return;
            } else {
                // We have a remote file.
                $tablecontent = $response->tablecontent;
            }
        } else {
            // If data is local use the local content of the given table.
            $tablecontent = json_encode($DB->get_records($table));
        }

        // Creating XMLRPC client.
        $rpc_client = new \local_vmoodle\XmlRpc_Client();
        $rpc_client->set_method('local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_import_table_content');
        $rpc_client->add_param($table, 'string');
        $rpc_client->add_param($tablecontent, 'string');
        $rpc_client->add_param(true, 'boolean');

        // debug_trace("VMOODLE import_table_content : Sending table $table content as\n$tablecontent");

        // Set Config. Sending requests.
        foreach($mnet_hosts as $mnet_host) {
            // Sending request.
            if (!$rpc_client->send($mnet_host)) {
                $response = new StdClass();
                $response->status = MNET_FAILURE;
                $response->errors[] = implode('<br/>', $rpc_client->get_errors($mnet_host));
            } else {
                $response = json_decode($rpc_client->response);
            }
            // Recording response.
            $responses[$mnet_host->wwwroot] = $response;
        }

        // Set Config. Saving results.
        $this->results = $responses + $this->results;
    }

    /**
     * Get the result of command execution for one host.
     * @param string $host The host to retrieve result (optional, if null, returns general result).
     * @param string $key The information to retrieve (ie status, error / optional).
     * @throws Command_Sql_Exception
     */
    public function get_result($host = null, $key = null) {
        // Checking if command has been runned.
        if (is_null($this->results)) {
            throw new Command_Exception('commandnotrun');
        }

        // Set Config. Checking host (general result isn't provide in this kind of command).
        if (is_null($host) || !array_key_exists($host, $this->results)) {
            return null;
        }
        $result = $this->results[$host];

        // Set Config. Checking key.
        if (is_null($key)) {
            return $result;
        } else if (property_exists($result, $key)) {
            return $result->$key;
        } else {
            return null;
        }
    }

    /**
     * Get if the command's result is returned.
     * @return bool True if the command's result should be returned, false otherwise.
     */
    public function is_returned() {
        return $this->returned;
    }

    /**
     * Set if the command's result is returned.
     * @param bool $returned True if the command's result should be returned, false otherwise.
     */
    public function set_returned($returned) {
        $this->returned = $returned;
    }
}