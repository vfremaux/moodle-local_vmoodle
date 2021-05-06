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
 * Describes set_plugins enable state command.
 * for all 2 states plugins.
 * 
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace vmoodleadminset_plugins;

defined('MOODLE_INTERNAL') || die;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Exception;
use \local_vmoodle\commands\Command_Parameter;

require_once($CFG->libdir.'/accesslib.php');
require_once($CFG->dirroot.'/local/vmoodle/plugins/plugins/rpclib.php');
require_once($CFG->dirroot.'/local/vmoodle/plugins/plugins/lib.php');

class Command_Plugin_Set_State extends Command {

    /**
     * The plugintype
     */
    private $plugintype;

    /**
     * The plugin
     */
    private $plugin;

    /**
     * The html report
     */
    private $report;

    /**
     * Constructor.
     * @throws Command_Exception.
     */
    public function __construct() {
        global $DB, $STANDARD_PLUGIN_TYPES;

        // Getting command description.
        $cmdname = get_string('cmdpluginsetupname', 'vmoodleadminset_plugins');
        $cmddesc = get_string('cmdpluginsetupdesc', 'vmoodleadminset_plugins');

        $pm = \core_plugin_manager::instance();

        $allplugins = $pm->get_plugins();

        $pluginlist = array();
        foreach ($allplugins as $type => $plugins) {
            if ($type == 'filter') {
                continue;
            }
            foreach ($plugins as $p) {
                if (array_key_exists($type, $STANDARD_PLUGIN_TYPES)) {
                    $pluginlist[$type.'/'.$p->name] = $STANDARD_PLUGIN_TYPES[$type].' : '.$p->displayname;
                }
            }
        }

        asort($pluginlist, SORT_STRING);

        $label = get_string('pluginparamdesc', 'vmoodleadminset_plugins');
        $pluginparam = new Command_Parameter('plugin', 'enum', $label, null, $pluginlist);

        $states = array();
        $states['enable'] = vmoodle_get_string('enable', 'vmoodleadminset_plugins');
        $states['disable'] = vmoodle_get_string('disable', 'vmoodleadminset_plugins');
        $label = get_string('pluginstateparamdesc', 'vmoodleadminset_plugins');
        $stateparam = new Command_Parameter('state', 'enum', $label, null, $states);

        // Creating command.
        parent :: __construct($cmdname, $cmddesc, array($pluginparam, $stateparam));
    }

    /**
     * Execute the command.
     * @param mixed $hosts The host where run the command (may be wwwroot or an array).
     * @throws Command_Exception.
     */
    public function run($hosts) {
        global $CFG, $USER;

        // Adding constants.
        include_once($CFG->dirroot.'/local/vmoodle/rpclib.php');

        // Checking capability to run.
        if (!has_capability('local/vmoodle:execute', \context_system::instance()))
            throw new Command_Exception('insuffisantcapabilities');

        // Getting plugin.
        list($type, $plugin) = explode('/', $this->get_parameter('plugin')->get_value());

        // Getting the state.
        $state = $this->get_parameter('state')->get_value();

        $stateval = 0;
        if ($state == 'enable') {
            $stateval = 1;
        }
        $plugininfos = array($plugin => $stateval);

        // Creating XMLRPC client to change remote configuration.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/plugins/rpclib.php/mnetadmin_rpc_set_plugins_states');
        $rpcclient->add_param($type, 'string');
        $rpcclient->add_param($plugininfos, 'array');

        // Initializing responses.
        $responses = array();

        // Creating peers.
        $mnethosts = array();
        if (!empty($hosts)) {
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

            // Recording plugin descriptors.
            if ($response->status == RPC_SUCCESS) {
                $this->plugins[$mnethost->wwwroot] = @$response->value;
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
}