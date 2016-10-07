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
 * admin.php
 * 
 * This file is the administration page of super-administration.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

// Adding requirements.
require_once('../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

// Checking login.
require_login();

// Printing headers.
$strtitle = get_string('vmoodlemanager', 'local_vmoodle');

$PAGE->set_title($strtitle);
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add($strtitle, new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin')));
$PAGE->navbar->add(get_string('administration', 'local_vmoodle'));
$PAGE->set_focuscontrol('');
$PAGE->set_cacheable(false);
$PAGE->set_button('');
$PAGE->set_headingmenu('');

echo $OUTPUT->header();

// Checking rights.
require_capability('local/vmoodle:managevmoodles', context_system::instance());

// Getting action.
$action = optional_param('action');
switch ($action) {
    case 'enablecommands':
        // Getting commands plugin path.
        $commands = optional_param('commands');
        if (is_null($commands)) {
            echo $OUTPUT->notification(get_string('wrongplugin', 'local_vmoodle'));
            break;
        }
        $plugin_path = $CFG->dirroot.'/local/vmoodle/plugins/'.$commands;
        $new_plugin_path = $CFG->dirroot.'/local/vmoodle/plugins/'.substr($commands, 1);

        // Checking if plugin exists.
        if (!is_dir($plugin_path)) {
            echo $OUTPUT->notification(get_string('wrongplugin', 'local_vmoodle'));
            break;
        }

        // Enabling plugin.
        if (!rename($plugin_path, $new_plugin_path)) {
            echo $OUTPUT->notification(get_string('pluginnotenabled', 'local_vmoodle'));
            break;
        }
        echo $OUTPUT->notification(get_string('pluginenabled', 'local_vmoodle'), 'notifysuccess');
        echo '<center>';
        echo $OUTPUT->single_button('admin.php', 'OK', 'get');
        echo '</center>';
        echo $OUTPUT->footer();
        exit();
    case 'disablecommands':
        // Getting commands plugin path.
        $commands = optional_param('commands');
        if (is_null($commands)) {
            echo $OUTPUT->notification(get_string('wrongplugin', 'local_vmoodle'));
            break;
        }
        $plugin_path = $CFG->dirroot.'/local/vmoodle/plugins/'.$commands;
        $new_plugin_path = $CFG->dirroot.'/local/vmoodle/plugins/_'.$commands;
        // Checking if plugin exists.
        if (!is_dir($plugin_path)) {
            echo $OUTPUT->notification(get_string('wrongplugin', 'local_vmoodle'));
            break;
        }

        // Disabling plugin.
        if (!rename($plugin_path, $new_plugin_path)) {
            echo $OUTPUT->notification(get_string('pluginnotdisabled', 'local_vmoodle'));
            break;
        }
        echo $OUTPUT->notification(get_string('plugindisabled', 'local_vmoodle'), 'notifysuccess');
        echo '<center>';
        echo $OUTPUT->single_button('admin.php', 'OK', 'get');
        echo '</center>';
        echo $OUTPUT->footer();
        exit();
    case 'uninstallplugin':

        // Getting plugin.
        $plugin = optional_param('plugin');
        if (is_null($plugin) || !file_exists($CFG->dirroot.'/local/vmoodle/plugins/'.$plugin.'/lib.php')) {
            echo $OUTPUT->notification(get_string('wrongplugin', 'local_vmoodle'));
            break;
        }

        // Loading plugin library.
        include_once($CFG->dirroot.'/local/vmoodle/plugins/'.$plugin.'/lib.php');

        // Removing plugin library.
        $uninstall_function = $plugin.'_uninstall';
        if ((function_exists($uninstall_function) && !$uninstall_function()) || !unset_config('vmoodle_lib_'.$plugin.'_version')) {
            echo $OUTPUT->notification(get_string('pluginnotuninstalled', 'local_vmoodle', $plugin));
            break;
        }
        echo $OUTPUT->notification(get_string('pluginuninstalled', 'local_vmoodle'), 'notify_success');
        echo '<center>';
        echo $OUTPUT->single_button('admin.php', 'OK', 'get');
        echo '</center>';
        echo $OUTPUT->footer();
        exit();
}

// Retrieving commands plugins.
$assistedcommands_conffiles = glob($CFG->dirroot.'/local/vmoodle/plugins/*/config.php');

// Creating table.
$table = new stdclass;
$table->head = array('<b>'.get_string('commands', 'local_vmoodle').'</b>', '<b>'.get_string('operation', 'local_vmoodle').'</b>');
$table->align = array('LEFT', 'CENTER');
$table->size = array('70%', '30%');
$table->width = '80%';

// Adding commands plugins.
foreach ($assistedcommands_conffiles as $conffile) {
    $path = explode('/', $conffile);
    $category = $path[count($path)-2];
    $vmoodle_category = load_vmplugin($category);

    $params = array('action' => 'enablecommands', 'commands' => $category);
    $button1 = $OUTPUT->single_button(new moodle_url('/local/vmoodle/admin.php', $params), get_string('enable'), 'get');
    $params = array('action' => 'disablecommands', 'commands' => $category);
    $button2 = $OUTPUT->single_button(new moodle_url('/local/vmoodle/admin.php', $params), get_string('disable'), 'get');

    $label = get_string('elements', 'local_vmoodle');
    $table->data[] = array($vmoodle_category->get_name().'<br/> > '.$vmoodle_category->count().' '.$label,
                           ($category[0] == '_' ? $button1 : $button2));
}

// Displaying commands plugins.
echo $OUTPUT->heading(get_string('commandsadministration', 'local_vmoodle'));
echo '<br/>';
echo html_writer::table($table);
echo '<br/>';

// Retrieving vmoodle plugins.
$plugins = core_plugin_manager::get_plugins_of_type('vmoodleadminset');
foreach ($plugins as $key => $plugin) {
    if (!$DB->get_record('config', array('name' => 'vmoodle_lib_'.$plugin.'_version'))) {
        unset($plugins[$key]);
    }
}

// Creating table.
$table = new stdclass;
$table->head = array('<b>'.get_string('plugin', 'local_vmoodle').'</b>', '<b>'.get_string('operation', 'local_vmoodle').'</b>');
$table->align = array('LEFT', 'CENTER');
$table->size = array('70%', '30%');
$table->width = '80%';

// Adding plugins.
foreach ($plugins as $plugin) {
    $params = array('action' => 'uninstallplugin', 'plugin' => $plugin);
    $adminurl = new moodle_url('/local/vmoodle/admin.php', $params);
    $table->data[] = array($plugin, $OUTPUT->single_button($adminurl, get_string('uninstall', 'local_vmoodle'), 'get'));
}

// Displaying plugins.
echo $OUTPUT->heading(get_string('pluginsadministration', 'local_vmoodle'));
echo '<br/>';
echo html_writer::table($table);

// Adding go back menu.
echo '<br/><center>';
echo $OUTPUT->single_button(new moodle_url('view.php', array('view' => 'sadmin')), get_string('tabpoolsadmin', 'local_vmoodle'), 'get');
echo '</center>';

// Adding footer.
echo $OUTPUT->footer();