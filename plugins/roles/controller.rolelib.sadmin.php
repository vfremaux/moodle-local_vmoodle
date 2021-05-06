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
 * Chains commands of rolelib plugin library.
 * 
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

// Adding requirements.
require('../../../../config.php');

// Adding libraries
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

// Checking login.
require_login();

// Checking rights.
if (!has_capability('local/vmoodle:managevmoodles', context_system::instance())) {
    print_error('onlyadministrators', 'local_vmoodle');
}

// Declaring parameters.
$action = optional_param('what', '', PARAM_TEXT);

// Checking action to do.
switch ($action) {

    // Run sync role command.
    case 'syncrole':
        // Getting parameters.
        $capability = optional_param('capability', '', PARAM_RAW);
        $source_platform = optional_param('source_platform', '', PARAM_RAW);
        $wwwroot_platforms = optional_param_array('platforms', null, PARAM_RAW);

        // Checking platforms.
        $valid = true;
        $available_plaforms = get_available_platforms();
        if (!array_key_exists($source_platform, $available_plaforms)) {
            $valid = false;
        } else {
            $platforms = array();
            foreach ($wwwroot_platforms as $wwwroot_platform) {
                if (!array_key_exists($wwwroot_platform, $available_plaforms)) {
                    $valid = false;
                    break;
                }
                $platforms[$wwwroot_platform] = $available_plaforms[$wwwroot_platform];
            }
        }
        if (!$valid) {
            redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin')));
        }

        // Retrieving previous command.
        $command = unserialize($SESSION->vmoodle_sa['command']);
        if ($SESSION->vmoodle_sa['wizardnow'] != 'report' || !($command instanceof \vmoodleadminset_roles\Command_Role_Compare)) {
            redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin')));
        }
        $role = $command->get_parameter('role')->get_value();

        // Saving previous context.
        $SESSION->vmoodle_sa['rolelib']['command'] = $SESSION->vmoodle_sa['command'];
        $SESSION->vmoodle_sa['rolelib']['platforms'] = $SESSION->vmoodle_sa['platforms'];

        // Creating RoleSyncCommand.
        $rolesync_command = new \vmoodleadminset_roles\Command_Role_Capability_Sync();
        $rolesync_command->get_parameter('platform')->set_value($source_platform);
        $rolesync_command->get_parameter('role')->set_value($role);
        $rolesync_command->get_parameter('capability')->set_value($capability);

        // Running command.
        $rolesync_command->run($platforms);

        // Saving new context.
        $SESSION->vmoodle_sa['command'] = serialize($rolesync_command);
        $SESSION->vmoodle_sa['platforms'] = $platforms;

        // Moving to the report.
        redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin')));
        break;

    // Going back to role comparison.
    case 'backtocomparison':
        // Getting old command.
        if (!isset($SESSION->vmoodle_sa['rolelib']['command']) || !isset($SESSION->vmoodle_sa['rolelib']['platforms']) || !($SESSION->vmoodle_sa['rolelib']['command'] instanceof \vmoodleadminset_roles\Command_Role_Compare)) {
            redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin')));
        }
        $command = unserialize($SESSION->vmoodle_sa['rolelib']['command']);
        $platforms = $SESSION->vmoodle_sa['rolelib']['platforms'];

        // Running command to actualize.
        $command->run($platforms);

        // Saving new context.
        $SESSION->vmoodle_sa['command'] = serialize($command);
        $SESSION->vmoodle_sa['platforms'] = $platforms;

        // Moving to the report.
        redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin')));
        break;

    // Redirecting to super admin view.
    default:
        redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin')));
}