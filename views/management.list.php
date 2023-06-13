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
$namefilter = optional_param('namefilter', '', PARAM_TEXT);
$perpage = 35;

// Retrieves all virtuals hosts.
$totalcount = $DB->count_records('local_vmoodle', array());
if (empty($namefilter)) {
    $vmoodles = $DB->get_records('local_vmoodle', null, 'name,enabled', '*', $page * $perpage, $perpage);
} else {
    $select = ' name LIKE ? OR shortname LIKE ? OR vhostname LIKE ? ';
    $vmoodles = $DB->get_records_select('local_vmoodle', $select, ['%'.$namefilter.'%', '%'.$namefilter.'%', '%'.$namefilter.'%'], 'name,enabled', '*', $page * $perpage, $perpage);
}

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
        $vmoodlecmd .= '<a href="'.$editurl.'">'.$OUTPUT->pix_icon('t/edit', get_string('edithost', 'local_vmoodle')).'</a>';

        if ($vmoodle->enabled == 1) {
            $deleteurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'delete', 'id' => $vmoodle->id));
            $jshandler = 'return confirm(\''.get_string('confirmdelete', 'local_vmoodle').'\')';
            $vmoodlecmd .= '&nbsp;<a href="'.$deleteurl.'" onclick="'.$jshandler.'">'.$OUTPUT->pix_icon('t/delete', get_string('deletehost', 'local_vmoodle')).'</a>';
        } else {
            $fulldeleteurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'fulldelete', 'id' => $vmoodle->id));
            $label = get_string('fulldeletehost', 'local_vmoodle');
            $jshandler = 'return confirm(\''.get_string('confirmfulldelete', 'local_vmoodle').'\')';
            $vmoodlecmd .= '&nbsp;<a href="'.$fulldeleteurl.'" onclick="'.$jshandler.'">'.$OUTPUT->pix_icon('t/delete', get_string('deletehost', 'local_vmoodle')).'</a>';
        }

        $params = array('view' => 'management', 'what' => 'snapshot', 'wwwroot' => $vmoodle->vhostname);
        $snapurl = new moodle_url('/local/vmoodle/view.php', $params);
        $pixicon = $OUTPUT->pix_icon('snapshot', get_string('snapshothost', 'local_vmoodle'), 'local_vmoodle');
        $vmoodlecmd .= '&nbsp;<a href="'.$snapurl.'">'.$pixicon.'</a>';

        // Check current host key and report if something wrong.
        $mnet_peer = new mnet_peer();
        $mnetstate = 'unbound';
        $hostid = $DB->get_field('mnet_host', 'id', ['wwwroot' => $vmoodle->vhostname]);
        if ($hostid) {
            // Too heavy to be done in direct query.
            /*
            $mnet_peer->set_id($hostid);
            $mnet_peer->currentkey = mnet_get_public_key($mnet_peer->wwwroot, $mnet_peer->application);
            // Secures the comparison.
            $mnet_peer->currentkey = str_replace("\r", '', trim($mnet_peer->currentkey));
            $mnet_peer->public_key = str_replace("\r", '', trim($mnet_peer->public_key));
            if ($mnet_peer->currentkey == $mnet_peer->public_key) {
                $mnetstate = 'good';
            } else {
                $mnetstate = 'bad';
            }
            */
        }

        $params = array('view' => 'management', 'what' => 'renewkey', 'id' => $vmoodle->id);
        $renewkeyurl = new moodle_url('/local/vmoodle/view.php', $params);
        $pixicon = $OUTPUT->pix_icon('i/key', get_string('renewmnetkey', 'local_vmoodle'), 'moodle');
        $vmoodlecmd .= '&nbsp;<a href="'.$renewkeyurl.'" data-mnetid="'.$hostid.'" class="mnet-key-query mnet-key-'.$mnetstate.'">'.$pixicon.'</a>';

        $vmoodlestatus = vmoodle_print_status($vmoodle, true);
        $strmnet = $vmoodle->mnet;
        if ($strmnet < 0) {
            $strmnet = get_string('mnetdisabled', 'local_vmoodle');
        } else if ($strmnet == 0) {
            $strmnet = get_string('mnetfree', 'local_vmoodle');
        }

        $auth = is_enabled_auth('multimnet') ? 'multimnet' : 'mnet';
        $jumpurl = new moodle_url('/auth/'.$auth.'/jump.php', array('hostwwwroot' => $vmoodle->vhostname));

        $mnethost = $DB->get_record('mnet_host', array('wwwroot' => $vmoodle->vhostname));
        if (empty($vmoodle->name)) {
            if (!empty($mnethost)) {
                $vmoodle->name = $mnethost->name;
            }
        }
        $vmoodlelnk = '<a href="'.$jumpurl.'" target="_blank" >'.$vmoodle->name.'</a>';
        if (!empty($mnethost)) {
            $vmoodlelnk .= '<br/>'.$mnethost->name;
        }

        $hostlnk = "<a href=\"{$vmoodle->vhostname}\" target=\"_blank\">{$vmoodle->vhostname}</a>";
        $crongapstr = "<span style=\"color:red\">$vmoodle->lastcrongap s.</span>";
        $crongap = ($vmoodle->lastcrongap > DAYSECS) ? $crongapstr : $vmoodle->lastcrongap ." s.";

        $table->data[] = array($vmoodlecheck, $vmoodlelnk, $hostlnk, $vmoodlestatus, $strmnet, $vmoodle->croncount,
                               userdate($vmoodle->lastcron), $crongap, $vmoodlecmd);
    }

    $returnurl = new moodle_url('/local/vmoodle/view.php', array('view' => $view,'what' => $action));

    echo '<center>';
    echo '<p>'.$OUTPUT->paging_bar($totalcount, $page, $perpage, $returnurl, 'vpage').'</p>';
    echo $renderer->namefilter($namefilter);
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
    echo $renderer->namefilter($namefilter);
    echo $OUTPUT->notification(get_string('novmoodles', 'local_vmoodle'));
}

$params = array('view' => 'management', 'what' => 'snapshot', 'wwwroot' => $CFG->wwwroot);
echo '<div class="vmoodle-tools-row">';
echo '<div class="vmoodle-tool">';
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', $params), get_string('snapshotmaster', 'local_vmoodle'), 'get');
echo '</div>';

// Displays buttons for adding a new virtual host and renewing all keys.

$templates = vmoodle_get_available_templates();
$params = array('view' => 'management', 'what' => 'add');
if (empty($templates)) {
    $buttonurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'add'));
    $label = get_string('notemplates', 'local_vmoodle');
    echo '<div class="vmoodle-tool">';
    echo $OUTPUT->single_button($buttonurl, $label, 'get', array('tooltip' => null, 'disabled' => true));
    echo '</div>';
} else {
    $buttonurl = new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'add'));
    echo '<div class="vmoodle-tool">';
    echo $OUTPUT->single_button($buttonurl, get_string('addvmoodle', 'local_vmoodle'), 'get');
    echo '</div>';
}
echo '</div>';

echo '<br/>';
echo '<div class="vmoodle-tools-row">';

if (local_vmoodle_supports_feature('instances/tools')) {
    require_once($CFG->dirroot.'/local/vmoodle/pro/locallib.php');
    echo local_vmoodle_add_extra_instance_tools();
}

echo '<div class="vmoodle-tool">';
$params = array('view' => 'management', 'what' => 'renewall');
$label = get_string('renewallbindings', 'local_vmoodle');
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', $params), $label, 'get');
echo '</div>';
echo '<div class="vmoodle-tool">';
$params = array('view' => 'management', 'what' => 'syncregister');
$label = get_string('syncvmoodleregister', 'local_vmoodle');
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', $params), $label, 'get');
echo '</div>';
echo '<div class="vmoodle-tool">';
echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/vcron.php'), get_string('runvcron', 'local_vmoodle'), 'get');
echo '</div>';
echo '</div>';
echo '</center>';
