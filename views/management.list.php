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

// Check status of previous action.
if (isset($SESSION->vmoodle_ma['confirm_message'])) {
    if (is_object($SESSION->vmoodle_ma['confirm_message'])) {
        echo $OUTPUT->notification($SESSION->vmoodle_ma['confirm_message']->message, $SESSION->vmoodle_ma['confirm_message']->style);
    } else {
        echo $OUTPUT->notification($SESSION->vmoodle_ma['confirm_message']);
    }
    echo '<br/>';
    unset($SESSION->vmoodle_ma['confirm_message']);
}

// If controller results, print them.
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
    $table->head = array('',
                         "<b>$strname</b>",
                         "<b>$strhost</b>",
                         "<b>$strstatus</b>",
                         "<b>$strmnet</b>",
                         "<b>$strcrons</b>",
                         "<b>$strlastcron</b>",
                         "<b>$strlastcrongap</b>",
                         "<b>$strcmds</b>");
    $table->align = array ('CENTER', 'LEFT', 'LEFT', 'CENTER', 'CENTER', 'CENTER', 'CENTER', 'CENTER', 'CENTER');
    $table->size = array('2%', '20%', '30%', '11%', '10%', '8%', '8%', '8%', '8%');
    $table->width = '98%';

    foreach ($vmoodles as $vmoodle) {

        $vmoodlecheck = '<input type="checkbox" name="vmoodleids[]" value="'.$vmoodle->id.'" />';

        $vmoodlecmd = '';
        $editurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'edit', 'id' => $vmoodle->id));
        $pix = $OUTPUT->pix_url('t/edit','core');
        $label = get_string('edithost', 'local_vmoodle');
        $vmoodlecmd .= '<a href="'.$editurl.'"><img src="'.$pix.'" title="'.$label.'" /></a>';

        if ($vmoodle->enabled == 1) {
            $deleteurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'delete', 'id' => $vmoodle->id));
            $pix = $OUTPUT->pix_url('t/delete');
            $label = get_string('deletehost', 'local_vmoodle');
            $jshandler = 'return confirm(\''.get_string('confirmdelete', 'block_vmoodle').'\')';
            $vmoodlecmd .= '&nbsp;<a href="'.$deleteurl.'" onclick="'.$jshandler.'"><img src="'.$pix.'" title="'.$label.'" /></a>';
        } else {
            $fulldeleteurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'fulldelete', 'id' => $vmoodle->id));
            $pix = $OUTPUT->pix_url('t/delete');
            $label = get_string('fulldeletehost', 'local_vmoodle');
            $jshandler = 'return confirm(\''.get_string('confirmfulldelete', 'block_vmoodle').'\')';
            $vmoodlecmd .= '&nbsp;<a href="'.$fulldeleteurl.'" onclick="'.$jshandler.'"><img src="'.$pix.'" title="'.$label.'" /></a>';
        }

        $params = array('view' => 'management', 'what' => 'snapshot', 'wwwroot' => $vmoodle->vhostname);
        $snapurl = new moodle_url('/local/vmoodle/view.php', $params);
        $pix = $OUTPUT->pix_url('snapshot', 'local_vmoodle');
        $label = get_string('snapshothost', 'local_vmoodle');
        $vmoodlecmd .= '&nbsp;<a href="'.$snapurl.'"><img src="'.$pix.'" title="'.$label.'" /></a>';
        $vmoodlestatus = vmoodle_print_status($vmoodle, true);
        $strmnet = $vmoodle->mnet;
        if ($strmnet < 0) {
            $strmnet = get_string('mnetdisabled', 'local_vmoodle');
        } else if ($strmnet == 0) {
            $strmnet = get_string('mnetfree', 'local_vmoodle');
        }

        $auth = is_enabled_auth('multimnet') ? 'multimnet' : 'mnet';
        $jumpurl = new moodle_url('/auth/'.$auth.'/jump.php', array('hostwwwroot' => $vmoodle->vhostname));
        $vmoodlelnk = '<a href="'.$jumpurl.'" target="_blank" >'.$vmoodle->name.'</a>';

        $hostlnk = "<a href=\"{$vmoodle->vhostname}\" target=\"_blank\">{$vmoodle->vhostname}</a>";
        $crongapstr = "<span style=\"color:red\">$vmoodle->lastcrongap s.</span>";
        $crongap = ($vmoodle->lastcrongap > DAYSECS) ? $crongapstr : $vmoodle->lastcrongap ." s.";

        $table->data[] = array($vmoodlecheck, $vmoodlelnk, $hostlnk, $vmoodlestatus, $strmnet, $vmoodle->croncount,
                               userdate($vmoodle->lastcron), $crongap, $vmoodlecmd);
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
    $attrs = array('onchange' => 'return vmoodle_manager_confirm(this, \''.get_string('deleteconfirm', 'local_vmoodle').'\');');
    echo html_writer::select($cmdoptions, 'what', '', array('' => 'choosedots'), $attrs);
    echo '</div>';
    echo '</form>';
    echo '</center>';
} else {
    echo $OUTPUT->notification(get_string('novmoodles', 'local_vmoodle'));
}

$params = array('view' => 'management', 'what' => 'snapshot', 'wwwroot' => $CFG->wwwroot);
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', $params), get_string('snapshotmaster', 'local_vmoodle'), 'get');

// Displays buttons for adding a new virtual host and renewing all keys.

echo '<br/>';

$templates = vmoodle_get_available_templates();
$params = array('view' => 'management', 'what' => 'add');
if (empty($templates)) {
    $buttonurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'add'));
    $label = get_string('notemplates', 'local_vmoodle');
    echo $OUTPUT->single_button($buttonurl, $label, 'get', array('tooltip' => null, 'disabled' => true));
} else {
    $buttonurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'add'));
    echo $OUTPUT->single_button($buttonurl, get_string('addvmoodle', 'local_vmoodle'), 'get');
}

echo '<br/>';
echo '<div class="vmoodle-tools-row">';
echo '<div class="vmoodle-tool">';
$params = array('view' => 'management', 'what' => 'generateconfigs');
$label = get_string('generateconfigs', 'local_vmoodle');
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', $params), $label, 'get');
echo '</div>';
echo '<div class="vmoodle-tool">';
$label = get_string('generatecopyscripts', 'local_vmoodle');
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/tools/generatecopyscripts.php', $params), $label, 'get');
echo '</div>';
echo '<div class="vmoodle-tool">';
$label = get_string('generatecustomscripts', 'local_vmoodle');
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/tools/generatecustomscripts.php', $params), $label, 'get');
echo '</div>';
echo '<div class="vmoodle-tool">';
$params = array('view' => 'management', 'what' => 'renewall');
$label = get_string('renewallbindings', 'local_vmoodle');
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', $params), $label, 'get');
echo '</div>';
echo '<div class="vmoodle-tool">';
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/vcron.php'), get_string('runvcron', 'local_vmoodle'), 'get');
echo '</div>';
echo '</div>';
echo '</center>';
