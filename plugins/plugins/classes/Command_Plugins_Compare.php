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
 * Describes a role comparison command.
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

global $PAGE;
$PAGE->requires->js('/local/vmoodle/plugins/plugins/js/plugins_compare.js');
$PAGE->requires->js('/local/vmoodle/plugins/plugins/js/strings.php');

class Command_Plugins_Compare extends Command {

    /**
     * The plugintype plugins
     */
    private $plugins = array();

    /**
     * The html report
     */
    private $report;

    /**
     * Constructor.
     * @throws Command_Exception.
     */
    public function __construct() {
        global $DB, $stdplugintypes;

        // Getting command description.
        $cmdname = vmoodle_get_string('cmdcomparename', 'vmoodleadminset_plugins');
        $cmddesc = vmoodle_get_string('cmdcomparedesc', 'vmoodleadminset_plugins');

        $label = get_string('plugintypeparamcomparedesc', 'vmoodleadminset_plugins');
        $pluginparam = new Command_Parameter('plugintype', 'enum', $label, null, $stdplugintypes);

        // Creating command.
        parent :: __construct($cmdname, $cmddesc, $pluginparam);
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

        // Checking capability to run
        if (!has_capability('local/vmoodle:execute', \context_system::instance())) {
            throw new Command_Exception('insuffisantcapabilities');
        }

        // Getting plugin type.
        $plugintype = $this->get_parameter('plugintype')->get_value();

        // Creating XMLRPC client to read plugins configurations.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/plugins/rpclib.php/mnetadmin_rpc_get_plugins_info');
        $rpcclient->add_param($plugintype, 'string');

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

        // Sending requests.
        foreach ($mnethosts as $mnethost) {
            // Sending request.
            if (!$rpcclient->send($mnethost)) {
                $response = new stdclass;
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
            if ($response->status == RPC_SUCCESS)
                $this->plugins[$mnethost->wwwroot] = $response->value;
        }

        // Saving results.
        $this->results = $responses + $this->results;

        // Processing results.
        $this->_process();
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
        if (!$this->has_run())
            throw new Command_Exception('commandnotrun');

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

    /**
     * Process the plugin comparison.
     * @throws Commmand_Exception.
     */
    private function _process() {
        global $CFG, $DB, $OUTPUT, $stdplugintypes, $PAGE;

        $renderer = $PAGE->get_renderer('local_vmoodle');

        // Checking if command has been runned.
        if (!$this->has_run()) {
            throw new Command_Exception('commandnotrun');
        }

        // Getting examined plugintype.
        $plugintype = $this->get_parameter('plugintype')->get_value();

        // Getting hosts.
        $hosts = array_keys($this->plugins);
        $host_labels = get_available_platforms();

        // Getting local plugin info.
        $pm = \plugin_manager::instance();

        $localplugins = $pm->get_plugins();
        $localtypeplugins = $localplugins[$plugintype];

        /*
         * Creating html report.
         */

        // Creating header.
        $this->report = '<link href="'.$CFG->wwwroot.'/local/vmoodle/plugins/plugins/theme/styles.css" rel="stylesheet" type="text/css">';
        $this->report .= '<h3>'.get_string('compareplugins', 'vmoodleadminset_plugins', $stdplugintypes[$plugintype]).'</h3>';

        // Creation form
        $params = array('what' => 'syncplugins');
        $actionurl = new moodle_url('/local/vmoodle/plugins/plugins/controller.pluginlib.sadmin.php', $params);
        $this->report.= '<form action="'.$actionurl.'" method="post" onsubmit="return validate_syncplugins()">';
        $this->report.= '<input id="id_plugin" type="hidden" name="plugin" value=""/>';
        $this->report.= '<input id="source_platform" type="hidden" name="source_platform" value=""/>';

        // Creating table.
        $this->report.= '<table id="plugincompare" class="generaltable boxaligncenter" style="min-width: 75%;">';
        $this->report.= '<tbody>';

        // Creating header.
        $this->report.= '<tr><th scope="col" class="header c0" style="vertical-align: bottom; text-align: left;">&nbsp</th>';
        $col = 1;
        foreach ($hosts as $host) {
            $this->report.= '<th id="plug_'.$col.'" scope="col" class="header c'.$col.'" style="vertical-align: bottom; text-align: center;">';
            $this->report.= '<label for="platform_'.$col.'"><img src="'.$CFG->wwwroot.'/local/vmoodle/plugins/plugins/draw_platformname.php?caption='.urlencode($host_labels[$host]).'" alt="'.$host_labels[$host].'"/></label><br/>';
            $this->report.= '<input id="platform_'.$col.'" type="checkbox" name="platforms[]" value="'.$host.'" disabled="disabled"/></th>';
            $col++;
        }
        $this->report.= '</tr>';

        // Initializing variables.
        $row = 0;

        // Creating table data.
        foreach ($localtypeplugins as $plugin) {

            $col = 1;
            $this->report .= '<tr class="r'.($row % 2).'">';
            $this->report .= '<td id="plug_0_'.$row.'" class="cell c0" style="vertical-align: middle; text-align: left;" onClic="setPLugin('.$col.','.$row.',\''.$plugin->name.'\',\''.$host.'\')">';
            $this->report .= $plugin->displayname;
            $this->report .='</td>';

            foreach ($hosts as $host) {
                $extra_class = false;
                $title = $plugin->displayname.' | '.$host_labels[$host];
                if (array_key_exists($host, $this->plugins) && property_exists($this->plugins[$host], $plugin->name)) {
                    $remote_plugin = $this->plugins[$host]->{$plugin->name};
                    if (is_null($remote_plugin)) {
                        $cell = '<img src="'.$renderer->pix_url('notinstalled', 'vmoodleadminset_plugins').' alt="Not installed" title="'.$title.'" />';
                    } else {
                        if ($remote_plugin->enabled) {
                            $cell = '<img src="'.$renderer->pix_url('enabled', 'vmoodleadminset_plugins').'" title="'.$title.'" />';
                        } else {
                            $cell = '<img src="'.$renderer->pix_url('disabled', 'vmoodleadminset_plugins').'" title="'.$title.'" />';
                        }
                        if ($localtypeplugins[$plugin->name]->versiondb > $remote_plugin->versiondb) {
                            $cell .= '&nbsp;<img src="'.$renderer->pix_url('needsupgrade', 'vmoodleadminset_plugins').'" title="'.$title.'" />';
                        }
                        if ($remote_plugin->versiondisk > $remote_plugin->versiondb) {
                            $cell .= '&nbsp;<img src="'.$renderer->pix_url('needslocalupgrade', 'vmoodleadminset_plugins').'" title="'.$title.'" />';
                        }
                    }
                } else {
                    $cell = '<img src="'.$renderer->pix_url('notinstalled', 'vmoodleadminset_plugins').'" alt="Not installed" title="'.$title.'"/>';
                }
                $this->report.= '<td id="plug_'.$col.'_'.$row.'" class="cell c'.$col.($extra_class ? ' '.$extra_class : '').'" style="vertical-align: middle; text-align: center;" onmouseout="cellOut('.$col.','.$row.');" onmouseover="cellOver('.$col.','.$row.');">'.$cell.'</td>';
                $col++;
            }
            $this->report.= '</tr>';
            $row++;
        }

        // Closing table.
        $this->report.= '</tboby></table><br/><center><input type="submit" value="'.get_string('synchronize', 'vmoodleadminset_plugins').'"/><div id="plugincompare_validation_message"></div></center></form><br/><br/>';
    }

    /**
     * Return counter value.
     * @param array $counter The counter.
     * @return int The counter value.
     */
    private function _get_counter_value($counter) {
        return $counter['count'];
    }
}