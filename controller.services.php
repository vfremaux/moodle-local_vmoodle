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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/vmoodle/classes/ServicesStrategy_Form.php');

// Confirmation message.
$messageobject = new stdclass();
$messageobject->message = '';
$messageobject->style = 'notifyproblem';

/* *************************** Define or redefine default services strategy *********** */
if ($action == 'redefineservices') {

    // Processing.
    $defaultservices = $DB->get_records('mnet_service', array('offer' => 1), 'name');
    if (!empty($defaultservices)) {

        // Retrieve submitted data, from the services strategy form.
        $servicesform = new ServicesStrategy_Form();
        $submitteddata = $servicesform->get_data();

        // Saves default services strategy.
        set_config('local_vmoodle_services_strategy', serialize($submitteddata));

        // Every step was SUCCESS.
        $messageobject->message = get_string('successstrategyservices', 'local_vmoodle');
        $messageobject->style = 'notifysuccess';
    } else {
        $messageobject->message = get_string('badservicesnumber', 'local_vmoodle');
    }

    // Save confirm message before redirection.
    $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
    redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
    return -1;
}