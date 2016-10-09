<?php

namespace vmoodleadminset_roles;
Use \local_vmoodle\commands\Command;
Use \local_vmoodle\commands\Command_Exception;
Use \local_vmoodle\commands\Command_Parameter;
Use \StdClass;
Use \moodle_url;

require_once($CFG->libdir.'/accesslib.php');

/**
 * Describes a role syncrhonisation command.
 * 
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
class Command_Role_Capability_Sync extends Command {

    /**
     * Constructor.
     * @throws Command_Exception.
     */
    public function __construct() {
        global $DB;

        // Getting command description.
        $cmd_name = vmoodle_get_string('cmdsynccapabilityname', 'vmoodleadminset_roles');
        $cmd_desc = vmoodle_get_string('cmdsynccapabilitydesc', 'vmoodleadminset_roles');

        // Creating platform parameter.
        $label = get_string('platformparamsyncdesc', 'vmoodleadminset_roles');
        $platformparam = new Command_Parameter('platform', 'enum', $label, null, get_available_platforms());

        // Getting role parameter.
        $roles = role_fix_names(get_all_roles(), \context_system::instance(), ROLENAME_ORIGINAL);
        $rolemenu = array();

        foreach ($roles as $r) {
            $rolemenu[$r->shortname] = $r->name;
        }
        $label = get_string('roleparamsyncdesc', 'vmoodleadminset_roles');
        $roleparam = new Command_Parameter('role', 'enum', $label, null, $rolemenu);

        // Creating capability parameter.
        $records = $DB->get_records('capabilities', null, 'name', 'name');
        $capabilities = array();

        foreach ($records as $record) {
            $capabilities[$record->name] = get_capability_string($record->name);
        }

        asort($capabilities);
        $label = get_string('capabilityparamsyncdesc', 'vmoodleadminset_roles');
        $capabilityparam = new Command_Parameter('capability', 'enum', $label, null, $capabilities);

        // Creating command.
        parent::__construct($cmdname, $cmddesc, array($platformparam, $roleparam, $capabilityparam));
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
        if (!has_capability('local/vmoodle:execute', \context_system::instance())) {
            throw new Command_Exception('insuffisantcapabilities');
        }

        // Getting role.
        $role = $this->get_parameter('role')->get_value();

        // Getting platform.
        $platform = $this->get_parameter('platform')->get_value();

        // Getting capability.
        $capability = $this->get_parameter('capability')->get_value();

        // Checking hosts.
        if (array_key_exists($platform, $hosts)) {
            $platforms = get_available_platforms();
            throw new Command_Role_Exception('syncwithitself', (object)array('role' => $role, 'platform' => $platforms[$platform]));
        }

        // Creating peer to read role configuration.
        $mnethost = new \mnet_peer();
        if (!$mnethost->bootstrap($this->get_parameter('platform')->get_value(), null, 'moodle')) {
            $response = (object) array(
                            'status' => MNET_FAILURE,
                            'error' => get_string('couldnotcreateclient', 'local_vmoodle', $platform)
                        );
            foreach ($hosts as $host => $name) {
                $this->results[$host] = $response;
            }
            return;
        }

        // Creating XMLRPC client to read role configuration.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/roles/rpclib.php/mnetadmin_rpc_get_role_capabilities');
        $rpcclient->add_param($role, 'string');
        $rpcclient->add_param($capability, 'string');

        // Checking result.
        if (!($rpcclient->send($mnethost) && ($response = json_decode($rpcclient->response)) && (
                $response->status == RPC_SUCCESS ||
                ($response->status == RPC_FAILURE_RECORD && (
                    in_array($response->errors, 'No capabilites for this role.') || 
                    in_array($response->error, 'No role capability found.'))
                )
            ))) {
            // Creating response.
            if (!isset($response)) {
                $response = new \StdClass();
                $response->status = MNET_FAILURE;
                $response->errors[] = implode('<br/>', $rpcclient->get_errors($mnethost));
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

        // Getting role configuration.
        if ($response->status == RPC_FAILURE_RECORD) {
            $rolecapability = array($capability => null);
        } else {
            $rolecapability = (array) $response->value;
        }
        unset($response);

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

        // Creating XMLRPC client.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/roles/rpclib.php/mnetadmin_rpc_set_role_capabilities');
        $rpcclient->add_param($role, 'string');
        $rpcclient->add_param($rolecapability, 'string');
        $rpcclient->add_param(false, 'boolean');

        // Sending requests.
        foreach ($mnethosts as $mnethost) {
            // Sending request.
            if (!$rpcclient->send($mnethost)) {
                $response = new \StdClass();
                $response->status = MNET_FAILURE;
                $response->errors[] = implode('<br/>', $rpcclient->get_errors($mnethost));
                $response->error = 'Remote Set role capability : Remote proc error';
                if (debugging()) {
                    echo '<pre>';
                    var_dump($rpcclient);
                    ob_flush();
                    echo '</pre>';
                }
            } else {
                $response = json_decode($rpcclient->response);
                $response->errors[] = implode('<br/>', $response->errors);
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
        global $CFG, $SESSION, $DB, $OUTPUT;

        // Checking if command has been runned.
        if (!$this->has_run()) {
            throw new Command_Exception('commandnotrun');
        }

        // Checking host (general result isn't provide in this kind of command).
        if (is_null($host)) {
            if (isset($SESSION->vmoodle_sa['rolelib']['command']) && isset($SESSION->vmoodle_sa['rolelib']['platforms'])) {
                $params = array('what' => 'backtocomparison');
                $buttonurl = new moodle_url('/local/vmoodle/plugins/roles/controller.rolelib.sadmin.php', $params);
                $label = get_string('backtocomparison', 'vmoodleadminset_roles');
                return '<center>'.$OUTPUT->single_button($buttonurl, $label, 'get').'</center><br/>';
            } else {
                return null;
            }
        } else if (!array_key_exists($host, $this->results)) {
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