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
 * The final step of wizard.
 * Displays report of command command.
 * 
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
defined('MOODLE_INTERNAL') || die();

// Adding requirements.
require_once($CFG->dirroot.'/local/vmoodle/rpclib.php');

// Getting platforms.
$platforms = $SESSION->vmoodle_sa['platforms'];

// Checking commands' states.
$successfull_platforms = array();
$failed_platforms = array();
foreach ($platforms as $host => $platform) {
    if ($command->get_result($host, 'status') == RPC_SUCCESS) {
        $successfull_platforms[$host] = $platform;
    } else {
        $failed_platforms[$host] = $platform;
    }
}

// Displaying general result.
if (!is_null($command->get_result())) {
    echo $command->get_result();
}

// Displaying successfull commands.
$i = 0;
if (!empty($successfull_platforms)) {
    echo $renderer->success_hosts_report($successfull_platforms, $command);
}

// Displaying failed commands.
$i = 0;
if (!empty($failed_platforms)) {
    echo $renderer->failed_hosts_report($failed_platforms, $command);
}

// Displaying controls.
echo '<center>';
$buttonurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin', 'what' => 'runotherpfm'));
echo $OUTPUT->single_button($buttonurl, get_string('runotherplatforms', 'local_vmoodle'), 'get');
$buttonurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin', 'what' => 'runothercmd'));
echo $OUTPUT->single_button($buttonurl, get_string('runothercommand', 'local_vmoodle'), 'get');
$buttonurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin', 'what' => 'newcommand'));
echo $OUTPUT->single_button($buttonurl, get_string('runnewcommand', 'local_vmoodle'), 'get');
echo '</center>';
