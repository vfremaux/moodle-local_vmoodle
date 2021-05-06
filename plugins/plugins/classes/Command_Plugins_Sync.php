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
 * Describes a command that allows synchronising plugin state.
 * 
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace vmoodleadminset_plugins;

defined('MOODLE_INTERNAL') || die;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Parameter;
use \local_vmoodle\commands\Command_Exception;
use \StdClass;
use \context_system;
use \mnet_peer;

class Command_Plugins_Sync extends Command {

    /**
     * Constructor.
     * @throws Command_Exception.
     */
    public function __construct() {
        global $DB;

        // Getting command description.
        $cmdname = vmoodle_get_string('cmdsyncname', 'vmoodleadminset_plugins');
        $cmddesc = vmoodle_get_string('cmdsyncdesc', 'vmoodleadminset_plugins');

        // Creating platform parameter. This is the source platform.
        $label = get_string('platformparamsyncdesc', 'vmoodleadminset_plugins');
        $platformparam = new Command_Parameter('platform', 'enum', $label, null, get_available_platforms());

        /*
         * Creating plugins type parameter. If this parameter has a value, 
         * then all plugins in this type will be synchronized
         */
        $pm = \core_plugin_manager::instance();

        $plugintypes = $pm->get_plugin_types();
        $label = get_string('plugintypeparamsyncdesc', 'vmoodleadminset_plugins');
        $plugintypeparam = new Command_Parameter('plugintype', 'enum', $label, null, $plugintypes);

        // Creating command.
        parent::__construct($cmdname, $cmddesc, array($platformparam, $plugintypeparam));
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
        if (!has_capability('local/vmoodle:execute', context_system::instance())) {
            throw new Command_Exception('insuffisantcapabilities');
        }

        // Getting plugintype.
        $plugintype = $this->get_parameter('plugintype')->get_value();

        // Checking hosts.
        $platform = $this->get_parameter('platform')->get_value();
        if (array_key_exists($platform, $hosts)) {
            $platforms = get_available_platforms();
            throw new Command_Plugins_Exception('syncwithitself');
        }

        // Creating peer to read plugins configuration from the designated peer.
        $mnethost = new mnet_peer();
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

        // Creating XMLRPC client to read plugins configuration.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/plugins/rpclib.php/mnetadmin_rpc_get_plugins_info');
        $rpcclient->add_param($plugintype, 'string');

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

            return;
        } else {
            // Result is a plugin info array that needs be replicated remotely to all targets.
            $plugininfos = (array) $response->value;

            // Initializing responses.
            $responses = array();

            // Creating peers.
            $mnethosts = array();
            foreach ($hosts as $host => $name) {
                $mnethost = new mnet_peer();
                if ($mnethost->bootstrap($host, null, 'moodle')) {
                    $mnethosts[] = $mnethost;
                } else {
                    $responses[$host] = (object) array(
                        'status' => MNET_FAILURE,
                        'error' => get_string('couldnotcreateclient', 'local_vmoodle', $host),
                    );
                }
            }

            // Creating XMLRPC client.
            $rpcclient2 = new \local_vmoodle\XmlRpc_Client();
            $rpcclient2->set_method('local/vmoodle/plugins/plugins/rpclib.php/mnetadmin_rpc_set_plugins_states');
            $rpcclient2->add_param($plugintype, 'string'); // plugintype.
            $rpcclient2->add_param($plugininfos, 'struct'); // Serialized plugininfos structure.

            // Sending requests.
            foreach ($mnethosts as $mnethost) {

                // Sending request.
                if (!$rpcclient2->send($mnethost)) {
                    $response = new Stdclass();
                    $response->status = MNET_FAILURE;
                    $response->errors[] = implode('<br/>', $rpcclient2->get_errors($mnethost));
                    $response->error = 'Set plugin state failed : Remote call error';
                    if (debugging()) {
                        echo '<pre>';
                        var_dump($rpcclient2);
                        ob_flush();
                        echo '</pre>';
                    }
                } else {
                    $response = json_decode($rpcclient2->response);
                }

                // Recording response.
                $responses[$mnethost->wwwroot] = $response;
            }
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