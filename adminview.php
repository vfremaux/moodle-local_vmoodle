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
 * view.php
 *
 * This file is the main page of vmoodle module which deals with
 * management et super-administration controlers.
 *
 * @package local_vmoodle
 * @category local
 */
// Adding requirements.
require('../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/lib.php');
require_once($CFG->dirroot.'/local/vmoodle/debuglib.php');
require_once($CFG->dirroot.'/mnet/lib.php');

// Loading jQuery.
$PAGE->requires->jquery();
$PAGE->requires->js('/local/vmoodle/js/strings.php');
$PAGE->requires->js('/local/vmoodle/js/target_choice.js');

$PAGE->requires->css('/local/vmoodle/theme/styles.php');


// Declaring parameters.

$view = optional_param('view', 'management', PARAM_TEXT);
$action = optional_param('what', '', PARAM_TEXT);

// Checking login.
$context = context_system::instance();
require_login();

$plugins = core_plugin_panager::get_plugins_of_type('vmoodleadminset');
foreach ($plugins as $plugin) {
    if (file_exists($CFG->dirroot.'/local/vmoodle/plugins/'.$plugin.'/js/strings.php')) {
        $jsfile = '/local/vmoodle/plugins/'.$plugin.'/js/strings.php';
        $PAGE->requires->js($jsfile);
    }

    foreach (glob($CFG->dirroot.'/local/vmoodle/plugins/'.$plugin.'/js/*.js') as $file) {
         $PAGE->requires->js( str_replace($CFG->dirroot, '', $file));
    }
}

// Printing headers.
$strtitle = get_string('vmoodlemanager', 'local_vmoodle');

$CFG->stylesheets[] = $CFG->wwwroot.'/local/vmoodle/theme/styles.php';

// Generating header.

ob_start();
$PAGE->set_context($context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($strtitle);
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add($strtitle, new moodle_url('/local/vmoodle/view.php', array('view' => $view)), 'misc');
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(false);
$PAGE->set_button('');
$PAGE->set_headingmenu('');

$url = new moodle_url('/local/vmoodle/adminview.php');
$PAGE->set_url($url, array('view' => $view, 'what' => $action));

echo $OUTPUT->header();

// Checking rights.
require_capability('local/vmoodle:managevmoodles', $context);

// Adding heading.
echo $OUTPUT->heading(get_string('vmoodleadministration', 'local_vmoodle'));

// Adding tabs.
$tabname = get_string('tabpoolmanage', 'local_vmoodle');
$taburl = new moodle_url('/local/vmoodle/adminview.php', array('view' => 'management'));
$row[] = new tabobject('management', $taburl, $tabname);
$tabname = get_string('tabpoolsadmin', 'local_vmoodle');
$taburl = new moodle_url('/local/vmoodle/adminview.php', array('view' => 'sadmin'));
$row[] = new tabobject('sadmin', $taburl, $tabname);
$tabname = get_string('tabpoolservices', 'local_vmoodle');
$taburl = new moodle_url('/local/vmoodle/adminview.php', array('view' => 'services'));
$row[] = new tabobject('services', $taburl, $tabname);
$tabrows[] = $row;
print_tabs($tabrows, $view);

// Capturing action.
if ($action != '') {
    try {
        switch ($view) {
            case 'management':
                $result = include($CFG->dirroot.'/local/vmoodle/controller.management.php');
                break;
            case 'sadmin':
                $result = $CFG->dirroot.'/local/vmoodle/controller.sadmin.php';
                break;
            case 'services':
                $result = include($CFG->dirroot.'/local/vmoodle/controller.services.php');
                break;
            default:
                $result = -1;
        }
        if ($result == -1) {
            echo $OUTPUT->footer();
            exit();
        }
    } catch (Exception $e) {
        echo $OUTPUT->notification($e->getMessage());
    }
}

// Displaying headers.
ob_end_flush();

// Including contents.
switch ($view) {
    case 'management':
        include($CFG->dirroot.'/local/vmoodle/views/management.main.php');
        break;
    case 'sadmin':
        include($CFG->dirroot.'/local/vmoodle/views/sadmin.main.php');
        break;
    case 'services':
        include($CFG->dirroot.'/local/vmoodle/views/services.main.php');
        break;
}

// Adding footer.
echo $OUTPUT->footer();