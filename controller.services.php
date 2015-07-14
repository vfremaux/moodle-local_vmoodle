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
 * This file catches an action and do the corresponding usecase.
 * Called by 'view.php'.
 *
 * @package local_vmoodle
 * @category local
 * @author Moheissen Fabien (fabien.moheissen@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 *
 * @usecase redefineservices
 */
require_once($CFG->dirroot.'/local/vmoodle/classes/ServicesStrategy_Form.php');

// It must be included from 'view.php' in local/vmoodle.
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

// Confirmation message.
$message_object = new stdclass();
$message_object->message = '';
$message_object->style = 'notifyproblem';

/**************************** Define or redefine default services strategy ************/
if ($action == 'redefineservices') {

    // Processing.
    $defaultservices = $DB->get_records('mnet_service', array('offer' => 1), 'name');
    if (!empty($defaultservices)) {

        // Retrieve submitted data, from the services strategy form.
        $services_form = new ServicesStrategy_Form();
        $submitteddata = $services_form->get_data();

        // Saves default services strategy.
        set_config('local_vmoodle_services_strategy', serialize($submitteddata));

        // Every step was SUCCESS.
        $message_object->message = get_string('successstrategyservices', 'local_vmoodle');
        $message_object->style = 'notifysuccess';
    } else {
        $message_object->message = get_string('badservicesnumber', 'local_vmoodle');
    }

    // Save confirm message before redirection.
    $SESSION->vmoodle_ma['confirm_message'] = $message_object;
    redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
    return -1;
}