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
require('../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/lib.php');
require_once($CFG->dirroot.'/local/vmoodle/debuglib.php');
require_once($CFG->dirroot.'/mnet/lib.php');

// Finish install if ever never done.
if (get_config('local_vmoodle', 'late_install')) {
    // Need performing some corrections on some db recordings, specially subplugins mnet function records.
    require_once $CFG->dirroot.'/local/vmoodle/db/install.php';
    xmldb_local_vmoodle_late_install();
}

// Loading jQuery.
$PAGE->requires->jquery();

// Loading javascript files.

$PAGE->requires->js('/local/vmoodle/js/strings.php');
$PAGE->requires->js ('/local/vmoodle/js/target_choice.js');
$PAGE->requires->js('/local/vmoodle/js/management.js');

$PAGE->requires->css ('/local/vmoodle/theme/styles.php');

// Report dead end trap.
// Checking if command were executed and return back to idle state.

if ((@$SESSION->vmoodle_sa['wizardnow'] == 'report')
        && !(isset($SESSION->vmoodle_sa['command'])
             && ($command = unserialize($SESSION->vmoodle_sa['command']))
                && $command->has_run())) {
    $SESSION->vmoodle_sa['wizardnow'] = 'commandchoice';
    redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin')));
}

// Declaring parameters.

$view = optional_param('view', 'management', PARAM_TEXT);
$action = optional_param('what', '', PARAM_TEXT);

// Security.

$system_context = context_system::instance();
require_login();
require_capability('local/vmoodle:managevmoodles', $system_context);

$manager = core_plugin_manager::instance();
$plugins = $manager->get_plugins_of_type('vmoodleadminset');
foreach ($plugins as $plugin) {
    if (file_exists($CFG->dirroot.'/local/vmoodle/plugins/'.$plugin->name.'/js/strings.php')) {
        $js_file = '/local/vmoodle/plugins/'.$plugin->name.'/js/strings.php';
        $PAGE->requires->js($js_file);
    }

    foreach (glob($CFG->dirroot.'/local/vmoodle/plugins/'.$plugin->name.'/js/*.js') as $file) {
         $PAGE->requires->js( str_replace($CFG->dirroot, '', $file));
    }
}

// Printing headers.

$strtitle = get_string('vmoodlemanager', 'local_vmoodle');

$PAGE->requires->css('/local/vmoodle/theme/styles.php');

// Generating header.

ob_start();
$PAGE->set_context($system_context);
$PAGE->set_pagelayout('admin');
$PAGE->set_title($strtitle);
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add($strtitle, new moodle_url('/local/vmoodle/view.php', array('view' => $view)),'misc');
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(false);
$PAGE->set_button('');
$PAGE->set_headingmenu('');

$url = new moodle_url('/local/vmoodle/view.php');
$PAGE->set_url($url,array('view' => $view, 'what' => $action));

// Capturing action.

if ($action != '') {
    try {
        switch ($view) {
            case 'management': {
                $result = include($CFG->dirroot.'/local/vmoodle/controller.management.php');
            }
            break;

            case 'sadmin': {
                $result = include($CFG->dirroot.'/local/vmoodle/controller.sadmin.php');
            }
            break;

            case 'services': {
                $result = include($CFG->dirroot.'/local/vmoodle/controller.services.php');
            }
            break;

            default: {
                $result = -1;
            }
        }
        if ($result == -1) {
            echo $OUTPUT->footer();
            exit();
        }
    }
    catch (Exception $e) {
        echo $OUTPUT->header(); 
        echo $OUTPUT->notification($e->getMessage());
        echo $OUTPUT->footer();
        exit();
    }
}

echo $OUTPUT->header();

// Adding heading.

echo $OUTPUT->heading(get_string('vmoodleadministration', 'local_vmoodle'));

// Adding tabs.

$renderer = local_vmoodle_get_renderer();
debug_trace(get_class($renderer), TRACE_DEBUG);
echo $renderer->tabs($view);

// Displaying headers.

ob_end_flush();

// Including contents.

$renderer = $PAGE->get_renderer('local_vmoodle');

switch($view) {
    case 'management': {
        include($CFG->dirroot.'/local/vmoodle/views/management.main.php');
    }
    break;
    case 'sadmin': {
        include($CFG->dirroot.'/local/vmoodle/views/sadmin.main.php');
    }
    break;
    case 'services': {
        include($CFG->dirroot.'/local/vmoodle/views/services.main.php');
    }
    break;
}

echo $OUTPUT->footer();