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
 * Redirection to a certain page of Vmoodle management.
 *
 * @package local_vmoodle
 * @category local
 * @author Moheissen Fabien (fabien.moheissen@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

// Check status of previous action
if (isset($SESSION->vmoodle_ma['confirm_message'])) {
    echo $OUTPUT->notification($SESSION->vmoodle_ma['confirm_message']->message, $SESSION->vmoodle_ma['confirm_message']->style);
    echo '<br/>';
    unset($SESSION->vmoodle_ma['confirm_message']);
}

// if controller results, print them
if (!empty($controllerresult)) {
    echo '<pre>';
    echo $controllerresult;
    echo '</pre>';
}

$page = optional_param('vpage', 0, PARAM_INT);
$perpage = 35;

// Retrieves all virtuals hosts.
$totalcount = $DB->count_records('local_vmoodle', array());
$vmoodles = $DB->get_records('local_vmoodle', null, 'name,enabled', '*', $page * $perpage, $perpage);

// If one or more virtual hosts exists.
if ($vmoodles) {
    $strname = get_string('name');
    $strhost = get_string('vhostname', 'local_vmoodle');
    $strstatus = get_string('status', 'local_vmoodle');
    $strmnet = get_string('mnet', 'local_vmoodle');
    $strlastcron = get_string('lastcron', 'local_vmoodle');
    $strlastcrongap = get_string('lastcrongap', 'local_vmoodle');
    $strcrons = get_string('crons', 'local_vmoodle');
    $strcmds = get_string('commands', 'local_vmoodle');

    // Defining html table.
    $table = new html_table();
    $table->head = array('', "<b>$strname</b>","<b>$strhost</b>","<b>$strstatus</b>","<b>$strmnet</b>","<b>$strcrons</b>","<b>$strlastcron</b>","<b>$strlastcrongap</b>","<b>$strcmds</b>");
    $table->align = array ('CENTER', 'LEFT', 'LEFT', 'CENTER', 'CENTER', 'CENTER', 'CENTER', 'CENTER', 'CENTER');
    $table->size = array('2%', '20%', '30%', '11%', '10%', '8%', '8%', '8%', '8%');
    $table->width = '98%';

    foreach ($vmoodles as $vmoodle) {

        $vmoodlecheck = '<input type="checkbox" name="vmoodleids[]" value="'.$vmoodle->id.'" />';

        $vmoodlecmd = '';
        $editurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'edit', 'id' => $vmoodle->id));
        $vmoodlecmd .= '<a href="'.$editurl.'"><img src="'.$OUTPUT->pix_url('t/edit','core').'" title="'.get_string('edithost', 'local_vmoodle').'" /></a>';
        if ($vmoodle->enabled == 1) {
            $deleteurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'delete', 'id' => $vmoodle->id));
            $vmoodlecmd .= ' <a href="'.$deleteurl.'" onclick="return confirm(\''.get_string('confirmdelete', 'local_vmoodle').'\');"><img src="'.$OUTPUT->pix_url('t/delete').'" title="'.get_string('deletehost', 'local_vmoodle').'" /></a>';
        } else {
            $fulldeleteurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'fulldelete', 'id' => $vmoodle->id));
            $vmoodlecmd .= ' <a href="'.$fulldemeteurl.'" onclick="return confirm(\''.get_string('confirmfulldelete', 'local_vmoodle').'\');"><img src="'.$OUTPUT->pix_url('t/delete').'" title="'.get_string('fulldeletehost', 'local_vmoodle').'" /></a>';
        }
        $nsapshoturl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'snapshot', 'wwwroot' => $vmoodle->vhostname));
        $vmoodlecmd .= ' <a href="'.$snapshoturl.'"><img src="'.$OUTPUT->pix_url('snapshot', 'local_vmoodle').'" title="'.get_string('snapshothost', 'local_vmoodle').'" /></a>';
        $vmoodlestatus = vmoodle_print_status($vmoodle, true);
        $strmnet = $vmoodle->mnet;
        if ($strmnet < 0) {
            $strmnet = get_string('mnetdisabled', 'local_vmoodle');
        } elseif ($strmnet == 0) {
            $strmnet = get_string('mnetfree', 'local_vmoodle');
        }
        $vmoodleurl = new moodle_url('/auth/mnet/jump.php', array('hostwwwroot' => urlencode($vmoodle->vhostname)));
        $vmoodlelnk = '<a href="'.$vmoodleurl.'" target="_blank" >'.$vmoodle->name.'</a>';
        $hostlnk = '<a href="'.$vmoodle->vhostname.'" target="_blank">'.$vmoodle->vhostname.'</a>';
        $crongap = ($vmoodle->lastcrongap > DAYSECS) ? '<span style="color:red">'.$vmoodle->lastcrongap.' s.</span>' : $vmoodle->lastcrongap .' s.';

        $table->data[] = array($vmoodlecheck, $vmoodlelnk, $hostlnk, $vmoodlestatus, $strmnet, $vmoodle->croncount, userdate($vmoodle->lastcron), $crongap, $vmoodlecmd);
    }

    $returnurl = new moodle_url('/local/vmoodle/view.php', array('view' => $view,'what' => $action));

    echo '<center>';
    echo '<p>'.$OUTPUT->paging_bar($totalcount, $page, $perpage, $returnurl, 'vpage').'</p>';
    echo '<form name="vmoodlesform" action="'.$returnurl.'" method="POST" >';
    echo html_writer::table($table);
    
    echo '<div class="vmoodle-group-cmd">';
    print_string('withselection', 'local_vmoodle');
    $cmdoptions = array(
        'enableinstances' => get_string('enableinstances', 'local_vmoodle'),
        'disableinstances' => get_string('disableinstances', 'local_vmoodle'),
        'deleteinstances' => get_string('deleteinstances', 'local_vmoodle'),
    );
    echo html_writer::select($cmdoptions, 'what', '', array('' => 'choosedots'), array('onchange' => 'return vmoodle_manager_confirm(this, \''.get_string('deleteconfirm', 'local_vmoodle').'\');'));
    echo '</div>';
    echo '</form>';
    echo '</center>';
} else {
    echo $OUTPUT->box(get_string('novmoodles', 'local_vmoodle'));
}

echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'snapshot', 'wwwroot' => $CFG->wwwroot)), get_string('snapshotmaster', 'local_vmoodle'), 'get');

// Displays buttons for adding a new virtual host and renewing all keys.

echo '<br/>';

$templates = vmoodle_get_available_templates();
if (empty($templates)) {
    echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'add')), get_string('notemplates', 'local_vmoodle'), 'get', array('tooltip' => null, 'disabled' => true));
} else {
    echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'add')), get_string('addvmoodle', 'local_vmoodle'), 'get');
}

echo '<br/>';
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'generateconfigs')), get_string('generateconfigs', 'local_vmoodle'), 'get');
echo '<br/>';
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'renewall')), get_string('renewallbindings', 'local_vmoodle'), 'get');
echo '<br/>';
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/vcron.php'), get_string('runvcron', 'local_vmoodle'), 'get');
echo '</center>';
