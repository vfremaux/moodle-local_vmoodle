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
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace vmoodleadminset_roles;

defined('MOODLE_INTERNAL') || die;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Exception;
use \local_vmoodle\commands\Command_Parameter;
use \context_system;
use \StdClass;
use \moodle_url;

require_once($CFG->libdir.'/accesslib.php');

class Command_Role_Compare extends Command {

    /**
     * The role capabilities
     */
    private $capabilities = array();

    /**
     * The html report
     */
    private $report;

    /**
     * Constructor.
     * @throws Command_Exception.
     */
    public function __construct() {
        global $DB;

        // Getting command description.
        $cmdname = get_string('cmdcomparename', 'vmoodleadminset_roles');
        $cmddesc = get_string('cmdcomparedesc', 'vmoodleadminset_roles');

        // Getting role parameter.
        $roles = role_fix_names(get_all_roles(), \context_system::instance(), ROLENAME_ORIGINAL);
        $rolemenu = array();
        foreach ($roles as $r) {
            $rolemenu[$r->shortname] = $r->localname;
        }
        $label = get_string('roleparamcomparedesc', 'vmoodleadminset_roles');
        $roleparam = new Command_Parameter('role', 'enum', $label, null, $rolemenu);

        // Creating command.
        parent :: __construct($cmdname, $cmddesc, $roleparam);
    }

    /**
     * Execute the command.
     * @param mixed $hosts The host where run the command (may be wwwroot or an array).
     * @throws Command_Exception.
     */
    public function run($hosts) {
        global $CFG, $USER;

        // Adding constants.
        require_once($CFG->dirroot.'/local/vmoodle/rpclib.php');

        // Checking capabilities.
        if (!has_capability('local/vmoodle:execute', \context_system::instance())) {
            throw new Command_Exception('insuffisantcapabilities');
        }

        // Getting role.
        $role = $this->get_parameter('role')->get_value();

        // Creating XMLRPC client to read role configuration.
        $rpcclient = new \local_vmoodle\XmlRpc_Client();
        $rpcclient->set_method('local/vmoodle/plugins/roles/rpclib.php/mnetadmin_rpc_get_role_capabilities');
        $rpcclient->add_param($role, 'string');

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
                    'status' => RPC_FAILURE,
                    'error' => get_string('couldnotcreateclient', 'local_vmoodle', $host)
                );
            }
        }

        // Sending requests.
        foreach ($mnethosts as $mnethost) {
            // Sending request.
            if (!$rpcclient->send($mnethost)) {
                $response = new \StdClass();
                $response->status = RPC_FAILURE;
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
            // Recording capabilities.
            if ($response->status == RPC_SUCCESS)
                $this->capabilities[$mnethost->wwwroot] = $response->value;
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

    /**
     * Process the role comparison.
     * @throws Commmand_Exception.
     */
    private function _process() {
        global $CFG, $DB, $OUTPUT;

        // Checking if command has been runned.
        if (!$this->has_run()) {
            throw new Command_Exception('commandnotrun');
        }

        // Defining capabilities values.
        $cappermissions = array(
            CAP_ALLOW => array('count' => 0, 'label' => 1, 'name' => 'allow'),
            CAP_PREVENT => array('count' => 0, 'label' => 2, 'name' => 'prevent'),
            CAP_PROHIBIT => array('count' => 0, 'label' => 3, 'name' => 'prohibit')
        );

        // Defining capabilities context.
        $capcontexts = array(
            CONTEXT_BLOCK => array('count' => 0, 'label' => 'B', 'name' => 'block'),
            CONTEXT_COURSE => array('count' => 0, 'label' => 'C', 'name' => 'course'),
            CONTEXT_COURSECAT => array('count' => 0, 'label' => 'CC', 'name' => 'coursecat'),
            CONTEXT_MODULE => array('count' => 0, 'label' => 'M', 'name' => 'module'),
            CONTEXT_SYSTEM => array('count' => 0, 'label' => 'S', 'name' => 'system'),
            CONTEXT_USER => array('count' => 0, 'label' => 'U', 'name' => 'user')
        );

        // Getting role name.
        $role = $this->get_parameter('role')->get_value();
        $role = $DB->get_record('role', array('shortname' => $role));

        // Getting hosts.
        $hosts = array_keys($this->capabilities);
        $hostlabels = get_available_platforms();

        // Getting capabilities.
        $recordscapabilities = $DB->get_records('capabilities', null, '', 'name,contextlevel,component');

        // Getting lang.
        $lang = str_replace('_utf8', '', current_language());
        $strcapabilities = s(get_string('capabilities', 'role')); // 'Capabilities' MDL-11687

        // Getting all capabilities names.
        $capabilitynames = array();
        foreach ($this->capabilities as $platformcapabilities) {
            $platformcapabilities = array_keys((array) $platformcapabilities);
            $capabilitynames = array_merge($capabilitynames, $platformcapabilities);
        }
        $capabilitynames = array_unique($capabilitynames);
        // Getting problematic component name.
        $problematiccomponentname = get_string('problematiccomponent', 'vmoodleadminset_roles');

        // Creating normalized capabilities.
        $capabilities = array();
        foreach ($capabilitynames as $capabilityname) {
            // Creating capability.
            $capability = new \StdClass();
            $capability->name = $capabilityname;

            // Initializing counters.
            $cappermissions[CAP_ALLOW]['count'] = $cappermissions[CAP_PREVENT]['count'] =
            $cappermissions[CAP_PROHIBIT]['count'] = 0;
            $capcontexts[CONTEXT_BLOCK]['count'] = $capcontexts[CONTEXT_COURSE]['count'] =
            $capcontexts[CONTEXT_COURSECAT]['count'] = /*$capcontexts[CONTEXT_GROUP]['count'] =*/
            $capcontexts[CONTEXT_MODULE]['count'] = $capcontexts[CONTEXT_SYSTEM]['count'] =
            $capcontexts[CONTEXT_USER]['count'] = 0;

            // Counting.
            foreach ($this->capabilities as $platformcapabilities) {
                if (!property_exists($platformcapabilities, $capabilityname) || is_null($platformcapabilities->$capabilityname)) {
                    continue;
                }
                $platformcapability = $platformcapabilities->$capabilityname;
                $cappermissions[$platformcapability->permission]['count']++;
                $capcontexts[$platformcapability->contextlevel]['count']++;
            }

            // Getting major values.
            $nbrvaluemax = max(array_map(array($this, 'get_counter_value'), $cappermissions));
            $nbrcontextmax = max(array_map(array($this, 'get_counter_value'), $capcontexts));

            // Setting major permission.
            foreach ($cappermissions as $permission => $cappermission) {
                if ($cappermission['count'] == $nbrvaluemax) {
                    $capability->major_permission = $permission;
                    break;
                }
            }

            // Setting major contexlevel.
            foreach ($capcontexts as $contextlevel => $cap_context) {
                if ($cap_context['count'] == $nbrcontextmax) {
                    $capability->major_contextlevel = $contextlevel;
                    break;
                }
            }

            // Setting component.
            $capability->component = isset($recordscapabilities[$capabilityname]) ? $recordscapabilities[$capabilityname]->component : $problematiccomponentname;

            // Setting capability contextlevel.
            $capability->contextlevel = isset($recordscapabilities[$capabilityname]) ? $recordscapabilities[$capabilityname]->contextlevel : CONTEXT_SYSTEM;

            // Adding capability.
            $capabilities[$capabilityname] = $capability;
        }

        // Sort capabilities on contextlevel, component and name.
        uasort($capabilities, array($this, 'order_capability'));

        /*
         * Creating html report.
         */

        // Creating header.
        $this->report = '<h3>'.get_string('comparerole', 'vmoodleadminset_roles', $role->name).help_button_vml('rolelib', 'rolecompare', 'rolecompare').'</h3>';

        // Adding edit role link.
        $this->report.= '<center><p>'.$OUTPUT->single_button(new moodle_url('/admin/roles/define.php', array('roleid' => $role->id, 'action' => 'edit')), get_string('editrole', 'vmoodleadminset_roles'), 'get').'</p></center>';

        // Adding a capability client side filter.
        $this->report .= get_string('capfilter', 'local_vmoodle').': '.'<input type="text" name="capfilter" value="" onchange="filtercapabilitytable(this)" />';

        // Creation form.
        $rolecapsyncurl = new moodle_url('/local/vmoodle/plugins/roles/controller.rolelib.sadmin.php', array('what' => 'syncrole'));
        $this->report .= '<form action="'.$rolecapsyncurl.'" method="post" onsubmit="return validate_syncrole()">';
        $this->report .= '<input id="capability" type="hidden" name="capability" value="" />';
        $this->report .= '<input id="source_platform" type="hidden" name="source_platform" value="" />';

        // Creating table.
        $this->report.= '<table id="rolecompare" cellspacing="1" cellpadding="5" class="generaltable boxaligncenter" style="min-width: 75%;"><tbody>';

        // Creating header.
        $this->report.= '<tr><th scope="col" class="header c0" style="vertical-align: bottom; text-align: left;">&nbsp</th>';
        $col = 1;
        foreach ($hosts as $host) {
            $this->report.= '<th id="cap_'.$col.'" scope="col" class="header c'.$col.'" style="vertical-align: bottom; text-align: center;"><label for="platform_'.$col.'"><img src="'.$CFG->wwwroot.'/local/vmoodle/plugins/roles/draw_platformname.php?caption='.urlencode($hostlabels[$host]).'" alt="'.$hostlabels[$host].'"/></label><br/><input id="platform_'.$col.'" type="checkbox" name="platforms[]" value="'.$host.'" disabled="disabled"/></th>';
            $col++;
        }
        $this->report.= '</tr>';

        // Initializing variables.
        $row = 0;
        $contextlevel = 0;
        $component = '';
        $rowtitleids = array();

        // Creating table data.
        foreach ($capabilities as $capability) {
            $col = 1;

            $componentlevelchanged = component_level_changed($capability, $component, $contextlevel);

            // Recording context.
            $contextlevel = $capability->contextlevel;
            $component = $capability->component;
            $rowtitleids[] = $capability->name;
            $rowcontent = '<tr class="r'.($row % 2).' capabilityrow" id="'.$capability->name.'">';
            $rowcontent .= '<td id="cap_0_'.$row.'" class="cell c0" style="vertical-align: middle; text-align: left;"><a onclick="this.target=\'docspopup\'" href="'.$CFG->docroot.'/'.$lang.'/'.$strcapabilities.'/'.$capability->name.'">'.get_capability_string($capability->name).'</a><br/>'.$capability->name.'</td>';

            foreach ($hosts as $host) {
                $extraclass = false;
                $title = get_capability_string($capability->name).' | '.$hostlabels[$host];
                if (array_key_exists($host, $this->capabilities) && property_exists($this->capabilities[$host], $capability->name)) {
                    $platformcapability = $this->capabilities[$host]->{$capability->name};
                    if (is_null($platformcapability)) {
                        $cell = '<img src="'.$CFG->wwwroot.'/local/vmoodle/plugins/roles/pix/norolecapability.png" alt="No role capability" title="'.$title.'" onclick="setCapability('.$col.','.$row.',\''.$capability->name.'\',\''.$host.'\');"/>';
                    } else {
                        $cell = '<img src="'.$CFG->wwwroot.'/local/vmoodle/plugins/roles/pix/compare'.$cappermissions[$platformcapability->permission]['label'].$capcontexts[$platformcapability->contextlevel]['label'].'.png" alt="Permission: '.$cappermissions[$platformcapability->permission]['name'].' | Context: '.$capcontexts[$platformcapability->contextlevel]['name'].'" title="'.$title.'" onclick="setCapability('.$col.','.$row.',\''.$capability->name.'\',\''.$host.'\');"/>';
                        if ($platformcapability->permission != $capabilities[$platformcapability->capability]->major_permission) {
                            $extraclass = 'wrongvalue';
                        } else if ($platformcapability->contextlevel != $capabilities[$platformcapability->capability]->major_contextlevel) {
                            $extraclass = 'wrongcontext';
                        }
                    }
                } else {
                    $cell = '<img src="'.$CFG->wwwroot.'/local/vmoodle/plugins/roles/pix/nocapability.png" alt="No capability" title="'.$title.'"/>';
                }
                $rowcontent .= '<td id="cap_'.$col.'_'.$row.'" class="cell c'.$col.($extraclass ? ' '.$extraclass : '').'" style="vertical-align: middle; text-align: center;" onmouseout="cellOut('.$col.','.$row.');" onmouseover="cellOver('.$col.','.$row.');">'.$cell.'</td>';
                $col++;
            }

            // Adding contextual heading.
            if ($componentlevelchanged) {
                $rowhead = '<tr class="capabilityrow" id="'.implode(',', $rowtitleids).'">';
                $rowhead .= '<td colspan="'.(count($hosts)+1).'" class="header">';
                $rowhead .= '<strong>'.($capability->component == $problematiccomponentname ? $problematiccomponentname : get_component_string($capability->component, $capability->contextlevel)).'</strong></td></tr>';
                $rowtitleids = array();
            }

            $this->report .= $rowhead.$rowcontent.'</tr>';

            $row++;
        }

        // Closing table.
        $this->report.= '</tboby></table><br/><center><input type="submit" value="'.get_string('synchronize', 'vmoodleadminset_roles').'"/><div id="rolecompare_validation_message"></div></center></form><br/><br/>';
    }

    /**
     * Return counter value.
     * @param array $counter The counter.
     * @return int The counter value.
     */
    private function get_counter_value($counter) {
        return $counter['count'];
    }

    /**
     * Give an order to capabilities (on component, contextlevel then name).
     * @param object $cap1 The first capability to compare.
     * @param object $cap2 The second capability to compare.
     * @return int Return -1 if $cap1 is less than $cap2, 1 if more than $cap2, 0 otherwise.
     */
    private function order_capability($cap1, $cap2) {
        if (!($cmp = strcmp($cap1->component, $cap2->component))) {
            return $cmp;
        } else if ($cap1->contextlevel < $cap2->contextlevel) {
            return -1;
        } else if ($cap1->contextlevel > $cap2->contextlevel) {
            return 1;
        } else {
            return strcmp($cap1->name, $cap2->name);
        }
    }
}