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

namespace vmoodleadminset_courses;

defined('MOODLE_INTERNAL') || die;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Parameter;
use \StdClass;

/**
 * Describes meta-administration plugin's command for Maintenance setup.
 *
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
class Command_RestoreCourse extends Command {

    /**
     * Maintenance message. Sets maintenance mode off if empty.
     */
    private $message;

    /**
     * If command's result should be returned.
     */
    private $returned;

    /**
     * Constructor.
     * @param string $name Command's name.
     * @param string $description Command's description.
     * @param string $sql SQL command.
     * @param string $parameters Command's parameters (optional / could be null, Command_Parameter object
     * or Command_Parameter array).
     * @param Command $rpcommand Retrieve platforms command (optional / could be null or Command object).
     * @throws Command_Exception
     */
    public function __construct() {
        global $vmcommandconstants;

        $name = vmoodle_get_string('cmdrestorecourse', 'vmoodleadminset_courses');
        $description = vmoodle_get_string('cmdrestorecourse_desc', 'vmoodleadminset_courses');

        $parameters[] = new Command_Parameter(
            'shortname',
            'text',
            get_string('shortname'),
            null,
            null);

        $parameters[] = new Command_Parameter(
            'fullname',
            'text',
            get_string('fullname'),
            null,
            null,
            array('size' => 80));

        $parameters[] = new Command_Parameter(
            'idnumber',
            'text',
            get_string('idnumber'),
            null,
            null);

        $parameters[] = new Command_Parameter(
            'catidnumber',
            'text',
            vmoodle_get_string('restorecatidnumber', 'vmoodleadminset_courses'),
            null,
            null);

        $parameters[] = new Command_Parameter(
            'location',
            'text',
            vmoodle_get_string('location', 'vmoodleadminset_courses'),
            null,
            null,
            array('size' => 80));

        // Set visbility at creation time.
        $parameters[] = new Command_Parameter(
            'visible',
            'boolean',
            vmoodle_get_string('coursevisible', 'vmoodleadminset_courses'),
            1,
            null);

        $choices = [
            '' => get_string('noenrol', 'vmoodleadminset_courses'),
            'managers' => get_string('managersonly', 'vmoodleadminset_courses'),
            'siteadmins' => get_string('siteadmins', 'vmoodleadminset_courses'),
            'adminsandmanagers' => get_string('bothadminsandmanagers', 'vmoodleadminset_courses'),
        ];

        // Enrol all admins.
        $parameters[] = new Command_Parameter(
            'enroladmins',
            'enum',
            vmoodle_get_string('enroladmins', 'vmoodleadminset_courses'),
            'managers',
            $choices);

        $parameters[] = new Command_Parameter(
            'delay',
            'text',
            vmoodle_get_string('rundelay', 'vmoodleadminset_courses'),
            60,
            null);

        $parameters[] = new Command_Parameter(
            'spread',
            'text',
            vmoodle_get_string('spread', 'vmoodleadminset_courses'),
            60,
            null);

        $parameters[] = new Command_Parameter(
            'seed',
            'text',
            vmoodle_get_string('seed', 'vmoodleadminset_courses'),
            '',
            null);

        // Creating Command.
        parent::__construct($name, $description, $parameters, null);
    }

    /**
     * Execute the command.
     * @param mixed $host The hosts where run the command (may be wwwroot or an array).
     * @throws Command_Maintenance_Exception
     */
    public function run($hosts) {
        global $CFG, $USER;

        // Adding constants.
        require_once($CFG->dirroot.'/local/vmoodle/rpclib.php');

        // Checking host.
        if (!is_array($hosts)) {
            $hosts = array($hosts => 'Unnamed host');
        }

        // Maintenance. Checking capabilities.
        if (!has_capability('local/vmoodle:execute', \context_system::instance())) {
            throw new Command_RestoreCourse_Exception('insuffisantcapabilities');
        }

        // Maintenance. Initializing responses.
        $responses = array();

        // Maintenance. Creating peers.
        $mnethosts = array();
        foreach ($hosts as $host => $name) {
            $mnethost = new \mnet_peer();
            if ($mnethost->bootstrap($host, null, 'moodle')) {
                $mnethosts[] = $mnethost;
            } else {
                $errorstr = get_string('couldnotcreateclient', 'local_vmoodle', $host);
                $responses[$host] = (object) array('status' => MNET_FAILURE, 'error' => $errorstr);
            }
        }

        // Creating XMLRPC client.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/courses/rpclib.php/mnetadmin_rpc_restore_course');
        $rpcclient->add_param($this->get_parameter('shortname')->get_value(), 'string');
        $rpcclient->add_param($this->get_parameter('fullname')->get_value(), 'string');
        $rpcclient->add_param($this->get_parameter('idnumber')->get_value(), 'string');
        $rpcclient->add_param($this->get_parameter('catidnumber')->get_value(), 'string');
        $rpcclient->add_param($this->get_parameter('location')->get_value(), 'string');
        $rpcclient->add_param($this->get_parameter('enroladmins')->get_value(), 'string');
        $rpcclient->add_param($this->get_parameter('delay')->get_value(), 'string');
        $rpcclient->add_param($this->get_parameter('spread')->get_value(), 'string');
        $rpcclient->add_param($this->get_parameter('seed')->get_value(), 'string');
        $rpcclient->add_param(true, 'boolean'); // Json required.

        // Maintenance. Sending requests.
        foreach ($mnethosts as $mnethost) {
            // Sending request.
            if (!$rpcclient->send($mnethost)) {
                $response = new StdClass();
                $response->status = MNET_FAILURE;
                $response->errors[] = implode('<br/>', $rpcclient->get_errors($mnethost));
            } else {
                $response = json_decode($rpcclient->response);
            }
            // Recording response.
            $responses[$mnethost->wwwroot] = $response;
        }

        // Maintenance. Saving results.
        $this->results = $responses + $this->results;
    }

    /**
     * Get the result of command execution for one host.
     * @param string $host The host to retrieve result (optional, if null, returns general result).
     * @param string $key The information to retrieve (ie status, error / optional).
     * @throws Command_Sql_Exception
     */
    public function get_result($host = null, $key = null) {

        // Maintenance. Checking if command has been runned.
        if (is_null($this->results)) {
            throw new Command_Exception('commandnotrun');
        }

        // Maintenance. Checking host (general result isn't provide in this kind of command).
        if (is_null($host) || !array_key_exists($host, $this->results)) {
            return null;
        }
        $result = $this->results[$host];

        // Maintenance. Checking key.
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