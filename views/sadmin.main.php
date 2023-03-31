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
 * Manage the command wizard.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
defined('MOODLE_INTERNAL') || die();

$PAGE->requires->js_call_amd('local_vmoodle/vmoodle', 'init');

// Declaring parameters.
if (isset($SESSION->vmoodle_sa['wizardnow'])) {
    $wizardnow = $SESSION->vmoodle_sa['wizardnow'];
} else {
    $wizardnow = 'commandchoice';
}

// Include the step wizard.
switch ($wizardnow) {
    case 'commandchoice':
        $result = include($CFG->dirroot.'/local/vmoodle/views/sadmin.commandchoice.php');
        break;

    case 'advancedcommand':
        $result = include($CFG->dirroot.'/local/vmoodle/views/sadmin.advancedcommand.php');
        break;

    case 'targetchoice':
        $result = include($CFG->dirroot.'/local/vmoodle/views/sadmin.targetchoice.php');
        break;

    case 'report':
        $result = include($CFG->dirroot.'/local/vmoodle/views/sadmin.report.php');
        break;

    default:
        $result = -1;
}

// If an error happens.
if ($result == -1) {
<<<<<<< HEAD
    unset($SESSION->vmoodle_sa['command']);
    $buttonurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin'));
    echo $OUTPUT->singlebutton($buttonurl, get_string('restart', 'local_vmoodle'));
=======
    unset($SESSION->vmoodle_sa);
    $buttonurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin'));
    echo $OUTPUT->single_button($buttonurl, get_string('restart', 'local_vmoodle'));
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
    echo $OUTPUT->footer();
    exit(0);
}