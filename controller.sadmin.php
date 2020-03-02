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
 * Manages the wizard of pool administration.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 */
defined('MOODLE_INTERNAL') || die();

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Parameter;
use \local_vmoodle\commands\Command_Exception;
use \local_vmoodle\commands\Command_Category;
use \local_vmoodle\AdvancedCommand_Form;
use \local_vmoodle\Target_Form;
use \local_vmoodle\Command_Form;
use \vmoodleadminset_sql\Command_Sql;
use \vmoodleadminset_sql\Command_MultiSql;

require_once($CFG->dirroot.'/local/vmoodle/classes/commands/Command_Form.php');
require_once($CFG->dirroot.'/local/vmoodle/classes/commands/AdvancedCommand_Form.php');
require_once($CFG->dirroot.'/local/vmoodle/classes/commands/AdvancedCommand_Upload_Form.php');

$sadminreturnurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin'));

// Checking action to do.

switch ($action) {

    case 'validateassistedcommand':
        // Validating the assisted command.
        // Checking the neeed values.
        $category = optional_param('category_plugin_name', null, PARAM_TEXT);

        $index = optional_param('command_index', -1, PARAM_INT);
        if (is_null($category) || $index < 0) {
            return 0;
        }

        // Loading command's category.
        if (is_dir(VMOODLE_PLUGINS_DIR.$category) && is_readable(VMOODLE_PLUGINS_DIR.$category.'/config.php')) {
            $commandcategory = load_vmplugin($_POST['category_plugin_name']);
        } else {
            return 0;
        }

        // Invoking a form.
        try {
            $command = $commandcategory->get_commands($index);
        } catch (Command_Exception $vce) {
            return 0;
        }
        $commandform = new Command_Form($command, Command_Form::MODE_COMMAND_CHOICE);
        if (!($data = $commandform->get_data())) {
            return 0;
        }

        // Setting parameters' values.
        try {
            $command->populate($data);
        } catch (Exception $exception) {
            $message = $exception->getMessage();
            if (empty($message)) {
                $message = get_string('unablepopulatecommand', 'local_vmoodle');
            }

            echo $OUTPUT->notification($message);
            unset($_POST); // Done to remove form information. Otherwise, it crack all forms.
            return 0;
        }

        // Record the wizard status.
        $SESSION->vmoodle_sa['command'] = serialize($command);
        $SESSION->vmoodle_sa['wizardnow'] = 'targetchoice';

        // Move to the next step.
        redirect($sadminreturnurl);
        break;

    case 'switchtoadvancedcommand':
        // Switching to the advanced mode.
        $SESSION->vmoodle_sa['wizardnow'] = 'advancedcommand';
        redirect($sadminreturnurl);
        break;

    case 'validateadvancedcommand':
        // Validating the advanced command.
        // Invoking form.
        $advancedcommandform = new AdvancedCommand_Form();

        // Checking if the fom is cancelled.
        if ($advancedcommandform->is_cancelled()) {
            $SESSION->vmoodle_sa['wizardnow'] = 'commandchoice';
            header('Location: view.php?view=sadmin');
            return -1;
        }

        // Checking sql command.
        if (!($data = $advancedcommandform->get_data(false))) {
            return 0;
        }

        // Make Command_Parameters

        $params = array();

        if (!empty($data->sqlparam1name)) {
            $param1 = new Command_Parameter($data->sqlparam1name, 'internal', '');
            $param1->set_value($data->sqlparam1value);
            $params[] = $param1;
        }

        if (!empty($data->sqlparam2name)) {
            $param2 = new Command_Parameter($data->sqlparam2name, 'internal', '');
            $param2->set_value($data->sqlparam2value);
            $params[] = $param2;
        }

        if (!empty($data->sqlparam3name)) {
            $param3 = new Command_Parameter($data->sqlparam3name, 'internal', '');
            $param3->set_value($data->sqlparam3value);
            $params[] = $param3;
        }

        // Creating a Command_MultiSql.
        $command = new Command_MultiSql(get_string('manualcommand', 'local_vmoodle'),
                                        get_string('manualcommand', 'local_vmoodle'),
                                        $data->sqlcommand, $params);
        // Record the wizard status.
        $SESSION->vmoodle_sa['command'] = serialize($command);
        $SESSION->vmoodle_sa['wizardnow'] = 'targetchoice';

        // Move to the next step.
        redirect($sadminreturnurl);
        break;

    case 'uploadsqlscript':
        // Uploading a SQL script to fill Command_Sql.
        // Checking uploaded file.
        $advancedcommandform = new AdvancedCommand_Form();
        $advancedcommanduploadform = new AdvancedCommand_Upload_Form();
        if ($filecontent = $advancedcommanduploadform->get_file_content('script')) {
            $advancedcommandform->set_data(array('sqlcommand' => $filecontent));
        }
        break;

    case 'gettargetbyvalue':
        // Getting available platforms by their original value.
        // Including requirements.
        include_once($CFG->dirroot.'/local/vmoodle/classes/Command_Form.php');
        include_once($CFG->dirroot.'/local/vmoodle/rpclib.php');

        // Checking command.
        if (!isset($SESSION->vmoodle_sa['command'])) {
            $SESSION['vmoodle_sa']['wizardnow'] = 'commandchoice';
            return 0;
        }
        // Getting retrieve platforms command.
        $command = unserialize($SESSION->vmoodle_sa['command']);
        $rpcommand = $command->get_rpc_command();
        if (is_null($rpcommand)) {
            return 0;
        }

        // Invoking form.
        $rpcommandform = new Command_Form($rpcommand, Command_Form::MODE_RETRIEVE_PLATFORM);

        // Checking if form is submitted.
        if (!($data = $rpcommandform->get_data())) {
            return 0;
        }

        // Setting parameters' values.
        $rpcommand->populate($data);

        // Sending command on available platforms.
        $platforms = get_available_platforms();
        $rpcommand->setReturned(true);
        $rpcommand->run($platforms);

        // Removing failed platforms.
        foreach ($platforms as $host => $platform) {
            if (!($rpcommand->get_result($host, 'status') == RPC_SUCCESS && $rpcommand->get_result($host, 'value'))) {
                unset($platforms[$host]);
            }
        }

        // Saving selected platforms in session.
        $SESSION->vmoodle_sa['platforms'] = $platforms;

        // Moving to current step.
        redirect($sadminreturnurl);
        break;

    case 'sendcommand':
        // Sending command on virtual platforms.
        // Invoking form.
        $targetform = new Target_Form();

        // Checking if form is cancelled.
        if ($targetform->is_cancelled()) {
            unset($SESSION->vmoodle_sa);
            header('Location: view.php?view=sadmin');
            return -1;
        }

        // Checking data.
        if (!($data = $targetform->get_data())) {
            return 0;
        }

        // Getting platforms // BUGFIX not found why splatforms dont' come into get_data().
        $formplatforms = optional_param_array('splatforms', array(), PARAM_URL);
        if (empty($formplatforms) || (count($formplatforms) == 1 && $formplatforms[0] == '0')) {
            echo $OUTPUT->header();
            throw new Command_Exception('noplatformchosen');
        }

        $platforms = array();
        $allplatforms = get_available_platforms();
        foreach ($formplatforms as $platformroot) {
            if (array_key_exists($platformroot, $allplatforms)) {
                $platforms[$platformroot] = $allplatforms[$platformroot];
            }
        }

        // Checking command.
        if (!isset($SESSION->vmoodle_sa['command'])) {
            $SESSION['vmoodle_sa']['wizardnow'] = 'commandchoice';
            return 0;
        }

        // Running command.
        $command = unserialize($SESSION->vmoodle_sa['command']);
        $command->run($platforms);
        $SESSION->vmoodle_sa['command'] = serialize($command);

        // Saving results to display.
        $SESSION->vmoodle_sa['platforms'] = $platforms;
        $SESSION->vmoodle_sa['wizardnow'] = 'report';

        // Move to the next step.
        redirect($sadminreturnurl);
        break;

    case 'newcommand':
        // Clean up wizard session to run a new command.
        unset($SESSION->vmoodle_sa);
        redirect($sadminreturnurl);
        break;

    case 'runotherpfm':
        // Run command again on other platforms.
        // Removing selected platforms from session.
        if (isset($SESSION->vmoodle_sa['platforms'])) {
            unset($SESSION->vmoodle_sa['platforms']);
            $command = unserialize($SESSION->vmoodle_sa['command']);
            $command->clear_result();
            $SESSION->vmoodle_sa['command'] = serialize($command);
        }

        // Modifying wizard state.
        $SESSION->vmoodle_sa['wizardnow'] = 'targetchoice';

        // Move to the step.
        redirect($sadminreturnurl);
        break;

    case 'runothercmd':
        // Run an other command on selected platforms.
        // Removing selected command from session.
        if (isset($SESSION->vmoodle_sa['command'])) {
            unset($SESSION->vmoodle_sa['command']);
        }

        // Modifying wizard state.
        $SESSION->vmoodle_sa['wizardnow'] = 'commandchoice';

        // Move to the step.
        redirect($sadminreturnurl);
        break;

    case 'runcmdagain':
        // Run the command again on a platform.
        // Checking wizard session.
        if (!isset($SESSION->vmoodle_sa['command'], $_GET['platform'])) {
            echo $OUTPUT->header();
            echo $OUTPUT->notification('No registered command');
            return -1;
        }

        // Getting command.
        $command = unserialize($SESSION->vmoodle_sa['command']);

        // Getting platform.
        $platform = urldecode(required_param('platform', PARAM_TEXT));
        $availableplatforms = get_available_platforms();

        if (!array_key_exists($platform, $availableplatforms)) {
            echo $OUTPUT->header();
            echo $OUTPUT->notification('No registered targets');
            return -1;
        }

        // Running command again on single host.
        $command->run(array($platform => $availableplatforms[$platform]));

        // Saving result.
        $SESSION->vmoodle_sa['command'] = serialize($command);

        // Moving to report step.
        redirect($sadminreturnurl);
        break;
}