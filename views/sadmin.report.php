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
<<<<<<< HEAD
    echo '<table width="95%" cellspacing="1" cellpadding="5" class="generaltable boxaligncenter">'.
            '<tbody>'.
                '<tr>'.
                    '<th scope="col" class="header c0" style="vertical-align: top; text-align: left; width: 20%; white-space: nowrap;" colspan="3"><b>'.get_string('failedplatforms', 'local_vmoodle').'</b></th>' .
                '</tr>';
    foreach ($failed_platforms as $host => $platform) {
        echo '<tr class="r'.$i.'">' .
                '<td><b>'.$platform.'</b></td>' .
                '<td style="text-align: left;">'.get_string('rpcstatus'.$command->get_result($host, 'status'), 'local_vmoodle').'</td>' .
                '<td style="width: 25%;">';
        if ($command->get_result($host, 'status') > 200 && $command->get_result($host, 'status') < 520) {
            $params = array('view' => 'sadmin', 'what' => 'runcmdagain', 'platform' => urlencode($host));
            echo $OUTPUT->single_button(new moodle_url('view.php', $params), get_string('runcmdagain', 'local_vmoodle'), 'get');
        } else {
            echo '&nbsp;';
        }
        echo '</td>' .
            '</tr>' .
            '<tr class="r'.$i.'" valign="top">' .
                '<td>'.get_string('details', 'local_vmoodle').'</td>' .
                '<td colspan="2">'.implode('<br/>', $command->get_result($host, 'errors')).'</td>' .
            '</tr>';
        $i = ($i+1)%2;
    }
    echo '</tbody>' .
        '</table><br/>';
=======
    echo $renderer->failed_hosts_report($failed_platforms, $command);
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
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
