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
 * Describes meta-administration multiple upgrade command.
 *
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux (valery.Fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace vmoodleadminset_upgrade;

defined('MOODLE_INTERNAL') || die;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Exception;
use \local_vmoodle\commands\Command_Parameter;
use \StdClass;

require_once($CFG->dirroot.'/local/vmoodle/rpclib.php');

if (!defined('RPC_SUCCESS')) {
    define('RPC_TEST', 100);
    define('RPC_SUCCESS', 200);
    define('RPC_FAILURE', 500);
    define('RPC_FAILURE_USER', 501);
    define('RPC_FAILURE_CONFIG', 502);
    define('RPC_FAILURE_DATA', 503);
    define('RPC_FAILURE_CAPABILITY', 510);
    define('RPC_FAILURE_RECORD', 520);
    define('RPC_FAILURE_RUN', 521);
}

if (!defined('RPC_FAILURE_RUN')) {
    define('RPC_FAILURE_RUN', 521);
}
if (!defined('MNET_FAILURE')) {
    define('MNET_FAILURE', 511);
}

/**
 * Describes a platform update command.
 */
class Command_Upgrade extends Command {

    /**
     * The cURL timeout
     */
    const CURL_TIMEOUT = 30;

    /**
     * Constructor.
     * @throws                Command_Exception.
     */
    public function __construct() {

        // Getting command description.
        $cmd_name = vmoodle_get_string('cmdupgradename', 'vmoodleadminset_upgrade');
        $cmd_desc = vmoodle_get_string('cmdupgradedesc', 'vmoodleadminset_upgrade');

        // Creating command.
        parent::__construct($cmd_name, $cmd_desc);
    }

    public function run($hosts) {
        global $CFG, $USER, $DB;

        // Adding constants.
        require_once $CFG->dirroot.'/local/vmoodle/rpclib.php';

        // Checking host.
        if (!is_array($hosts)) {
            $hosts = array($hosts => 'Unnamed host');
        }

        // Checking capabilities.
        if (!has_capability('local/vmoodle:execute', \context_system::instance())) {
            throw new Command_Upgrade_Exception('insuffisantcapabilities');
        }

        // Initializing responses.
        $responses = array();

        // Creating peers.
        $mnethosts = array();
        foreach ($hosts as $host => $name) {
            $mnethost = new \mnet_peer();
            if ($mnethost->bootstrap($host, null, 'moodle')) {
                $mnethosts[] = $mnethost;
            } else {
                $responses[$host] = (object) array('status' => RPC_FAILURE, 'error' => get_string('couldnotcreateclient', 'local_vmoodle', $host));
            }
        }

        // Creating XMLRPC client.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/upgrade/rpclib.php/mnetadmin_rpc_upgrade');

        // Sending requests.
        foreach ($mnethosts as $mnethost) {

            // Sending request.
            if (!$rpcclient->send($mnethost)) {
                $response = new StdClass();
                $response->status = RPC_FAILURE;
                $response->errors[] = implode('<br/>', $rpcclient->get_errors($mnethost));
            } else {
                $response = json_decode($rpc_client->response);
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
            return '';
        }
    }
}