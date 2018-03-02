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
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace vmoodleadminset_sql;

defined('MOODLE_INTERNAL') || die;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Exception;
use \StdClass;

/**
 * Describes meta-administration plugin's SQL command.
 */
class Command_Sql extends Command {

    /**
     * SQL command
     */
    private $sql;

    /**
     * If command's result should be returned
     */
    private $returned;

    /**
     * if commands has place holders, they are converted into Moodle SQL named variables
     */
    private $values;

    /**
     * Some SQL changes may need caches being refreshed.
     */
    private $needspurgecaches;

    /**
     * Constructor.
     * @param string $name Command's name.
     * @param string $description Command's description.
     * @param string $sql SQL command.
     * @param mixed $parameters Command's parameters (optional / could be null, Command_Parameter object or Command_Parameter array).
     * @param Command $rpcommand Retrieve platforms command (optional / could be null or Command object).
     * @throws    Command_Exception
     */
    public function __construct($name, $description, $sql, $parameters = null, $rpcommand = null) {
        global $vmcommandconstants;

        // Creating Command.
        parent::__construct($name, $description, $parameters, $rpcommand);

        // Checking SQL command.
        if (empty($sql)) {
            throw new Command_Sql_Exception('sqlemtpycommand', $this->name);
        } else {
            // Looking for parameters
            preg_match_all(self::PLACEHOLDER, $sql, $sql_vars);

            // Checking parameters to show.
            foreach ($sql_vars[2] as $key => $sql_var) {
                $is_param = !(empty($sql_vars[1][$key]));
                if (!$is_param && !array_key_exists($sql_var, $vmcommandconstants)) {
                    throw new Command_Sql_Exception('sqlconstantnotgiven', (object)array('constant_name' => $sql_var, 'command_name' => $this->name));
                } else if ($is_param && !array_key_exists($sql_var, $this->parameters)) {
                    throw new Command_Sql_Exception('sqlparameternotgiven', (object)array('parameter_name' => $sql_var, 'command_name' => $this->name));
                }
            }
            $this->sql = $sql;
        }
        $this->values = array();
    }

    /**
     * Allow some command constructs to purgecache after SQL operation.
     */
    public function set_purgecaches($purgecaches) {
        $this->needspurgecaches = $purgecaches;
    }

    /**
     * Execute the command.
     * @param mixed $host The hosts where run the command (may be wwwroot or an array).
     * @throws Command_Sql_Exception
     */
    public function run($hosts) {
        global $CFG;

        // Adding constants.
        require_once $CFG->dirroot.'/local/vmoodle/rpclib.php';

        // Checking host.
        if (!is_array($hosts)) {
            $hosts = array($hosts => 'Unnamed host');
        }

        // Checking capabilities.
        if (!has_capability('local/vmoodle:execute', \context_system::instance())) {
            throw new Command_Sql_Exception('insuffisantcapabilities');
        }

        // Initializing responses.
        $responses = array();

        // Creating peers.
        $mnethosts = array();

        foreach (array_keys($hosts) as $host) {
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

        // Getting command.
        $return = $this->is_returned();

        // Creating XMLRPC client.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/sql/rpclib.php/mnetadmin_rpc_run_sql_command');
        $rpcclient->add_param($this->_get_generated_command(), 'string');
        $rpcclient->add_param($this->values, 'array');
        $rpcclient->add_param($return, 'boolean');
        $rpcclient->add_param($this->needspurgecaches, 'int');

        // Sending requests.
        foreach ($mnethosts as $mnethost) {

            // Sending request.
            if (!$rpcclient->send($mnethost)) {
                $response = new StdClass;
                $response->status = MNET_FAILURE;
                $response->errors[] = implode('<br/>', $rpcclient->get_errors($mnethost));
            } else {
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
     * @param string $host   The host to retrieve result (optional, if null, returns general result).
     * @param string $key   The information to retrieve (ie status, error / optional).
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
        } else if (property_exists($result, $key)) {
            return $result->$key;
        } else {
            return null;
        }
    }

    /**
     * Get SQL command.
     * @return SQL command.
     */
    public function get_sql() {
        return $this->sql;
    }

    /**
     * Get if the command's result is returned.
     * @return boolean   True if the command's result should be returned, false otherwise.
     */
    public function is_returned() {
        return $this->returned;
    }

    /**
     * Set if the command's result is returned.
     * @param boolean $returned   True if the command's result should be returned, false otherwise.
     */
    public function set_returned($returned) {
        $this->returned = $returned;
    }

    /**
     * Get the command to execute.
     * @return string   The final SQL command to execute.
     */
    private function _get_generated_command() {
        return preg_replace_callback(self::PLACEHOLDER, array($this, '_replace_parameters_values'), $this->get_sql());
    }

    /**
     * Bind the replace_parameters_values function to create a callback.
     * @param array $matches   The placeholders found.
     * @return string|array   The parameters' values.
     */
    private function _replace_parameters_values($matches) {

        list($paramname, $paramvalue) = replace_parameters_values($matches, $this->get_parameters(), true, false);

        $this->values[$paramname] = $paramvalue;

        // Return the named placeholder.
        return ':'.$paramname;
    }
}