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
 * Describes meta-administration plugin's command for copying
 * the full set of config values of a plugin.
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

class Command_CopyFullPluginConfig extends Command {

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
    public function __construct() {
        global $vmcommandconstants;

        $name = vmoodle_get_string('cmdplugin', 'vmoodleadminset_generic');
        $description = vmoodle_get_string('cmdplugin_desc', 'vmoodleadminset_generic');

        $param1 = new Command_Parameter(
            'plugin',
            'senum',
            vmoodle_get_string('plugin', 'vmoodleadminset_generic'),
            null,
            vmoodle_get_all_plugins());

        // Creating Command.
        parent::__construct($name, $description, array($param1), null);
    }

    /**
     * Execute the command.
     * @param mixed $host The hosts where run the command (may be wwwroot or an array).
     * @throws Command_SetConfig_Exception
     */
    public function run($hosts) {
        global $CFG, $USER;

        // Adding constants.
        require_once $CFG->dirroot.'/local/vmoodle/rpclib.php';

        // Checking host.
        if (!is_array($hosts)) {
            $hosts = array($hosts => 'Unnamed host');
        }

        // Checking capabilities.
        if (!has_capability('local/vmoodle:execute', \context_system::instance())) {
            throw new Command_SetConfig_Exception('insuffisantcapabilities');
        }

        // Initializing responses.
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

        // Getting command.
        $command = $this->is_returned();

        // Creating XMLRPC client.
        $rpc_client = new \local_vmoodle\XmlRpc_Client();
        $rpc_client->set_method('local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_load_plugin_config');

        $plugin = $this->get_parameter('plugin')->get_value();

        if (strpos($plugin, 'mod_') === 0) {
            /*
             * Historic architecture has standard mod names without prefix.
             * some plugin authors may have configured using the frankenstyle normalized name.
             */
            $pluginalt = $plugin;
            $plugin = str_replace('mod_', '', $plugin);
            $config = get_config($plugin);
            $config2 = get_config($pluginalt);
            if (empty($config)) {
                $config = $config2;
            } else {
                $plugin = $pluginalt;
            }
        }

        if (strpos($plugin, 'auth_') === 0) {
            /*
             * Historic architecture has standard auth names using slash.
             * new (most) plugins are now using the frankenstyle normalized name.
             */
            $pluginalt = str_replace('auth_', 'auth/', $plugin);
            $config = get_config($pluginalt);
            $config2 = get_config($plugin);
            if (empty($config)) {
                $config = $config2;
            } else {
                $plugin = $pluginalt;
            }
        }

        $config = get_config($plugin);
        $serialized = json_encode($config);

        $rpc_client->add_param($plugin, 'string');
        $rpc_client->add_param($serialized, 'string');
        $rpc_client->add_param($command, 'boolean');

        // Sending requests.
        foreach ($mnet_hosts as $mnet_host) {
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

        // Saving results.
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

        // Checking host (general result isn't provide in this kind of command).
        if (is_null($host) || !array_key_exists($host, $this->results)) {
            return null;
        }
        $result = $this->results[$host];

        // Checking key.
        if (is_null($key)) {
            return $result;
        } elseif (property_exists($result, $key)) {
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