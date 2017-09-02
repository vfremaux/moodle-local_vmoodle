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
 * Describes meta-administration plugin's command for synchronizing language customisations.
 * 
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace vmoodleadminset_generic;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Parameter;
use \local_vmoodle\commands\Command_Exception;
use \StdClass;

class Command_SyncLangCustomisation extends Command {

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

        // Getting command description.
        $cmdname = vmoodle_get_string('cmdlangsynccustomisation', 'vmoodleadminset_generic');
        $cmddesc = vmoodle_get_string('cmdlangsynccustomisation_desc', 'vmoodleadminset_generic');

        // Creating platform parameter. This is the source platform.
        $label = get_string('platformparamlangsyncdesc', 'vmoodleadminset_generic');
        $platformparam = new Command_Parameter('platform', 'enum', $label, null, get_available_platforms());

        /*
         * Creating plugins type parameter. If this parameter has a value, 
         * then all plugins in this type will be synchronized
         */
        $pm = \core_plugin_manager::instance();

        $allplugins = $pm->get_plugins();

        $pluginsopts = array();
        foreach ($allplugins as $type => $typelist) {
            foreach ($typelist as $plugin => $plugininfo) {
                $pluginsopts[$type.'/'.$plugin] = $type.' / '.$plugininfo->displayname;
            }
        }

        $arr = array('all' => get_string('allplugins', 'vmoodleadminset_generic'),
                     'core' => get_string('core', 'vmoodleadminset_generic'),
        );
        $pluginsopts = array_merge($arr, $pluginsopts);
        $label = get_string('pluginparamdesc', 'vmoodleadminset_generic');
        $pluginparam = new Command_Parameter('plugin', 'mhenum', $label, null, $pluginsopts);

        $langsopts = get_string_manager()->get_list_of_translations(true);
        $arr = array('all' => get_string('alllanguages', 'vmoodleadminset_generic'),
        );
        $pluginsopts = array_merge($arr, $langsopts);
        $label = get_string('langparamdesc', 'vmoodleadminset_generic');
        $langparam = new Command_Parameter('lang', 'menum', $label, null, $pluginsopts);

        // Creating command.
        parent::__construct($cmdname, $cmddesc, array($platformparam, $pluginparam, $langparam));
    }

    /**
     * Execute the command.
     * @param mixed $host The hosts where run the command (may be wwwroot or an array).
     * @throws Command_SetConfig_Exception
     */
    public function run($hosts) {
        global $CFG, $USER;

        // Set Config. Adding constants.
        require_once($CFG->dirroot.'/local/vmoodle/rpclib.php');

        // Set Config. Checking host.
        if (!is_array($hosts)) {
            $hosts = array($hosts => 'Unnamed host');
        }

        // Set Config. Checking capabilities.
        if (!has_capability('local/vmoodle:execute', \context_system::instance())) {
            throw new Command_SyncLangCustomisation_Exception('insuffisantcapabilities');
        }

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

        $plugins = $this->get_parameter('plugins')->get_value();

        $langs = $this->get_parameter('lang')->get_value();

        // Creating XMLRPC client to get the remote customisation language pack.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/plugins/rpclib.php/mnetadmin_rpc_get_local_langs');
        $rpcclient->add_param($plugins, 'string');
        $rpcclient->add_param($langs, 'string');

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

            // Creating XMLRPC client.
            $rpc_client = new \local_vmoodle\XmlRpc_Client();
            $rpc_client->set_method('local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_copy_local_lang');
            $rpc_client->add_param($this->get_parameter('platform')->get_value(), 'string');
            $rpc_client->add_param($this->get_parameter('plugin')->get_value(), 'string');
            $rpc_client->add_param($this->get_parameter('lang')->get_value(), 'string');
            $rpc_client->add_param(null, 'string');
            $rpc_client->add_param($command, 'boolean');

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