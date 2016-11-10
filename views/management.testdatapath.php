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
 * Tests database connection.
 *
 * @package local_vmoodle
 * @category local
 * @author Moheissen Fabien (fabien.moheissen@gmail.com)
 * @copyright valeisti (http://www.valeisti.fr)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

// Loading $CFG configuration.
include('../../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/filesystemlib.php');

$context = context_system::instance();

require_login();

$PAGE->set_context($context);
$PAGE->set_pagelayout('popup');
$PAGE->set_url(new moodle_url('/local/vmoodle/views/management.testdatapath.php'));

echo $OUTPUT->header();
echo "<p>";

// Retrieve parameters for database connection test.
$dataroot = required_param('dataroot', PARAM_TEXT);

if (is_dir($dataroot)) {
    $DIR = opendir($dataroot); 
    $cpt = 0;
    $hasfiles = false;
    while (($file = readdir($DIR)) && !$hasfiles) {
        if (!preg_match("/^\\./", $file)) {
            $hasfiles = true;
        }
    }
    closedir($DIR);

    if ($hasfiles) {
        echo $OUTPUT->box(get_string('datapathnotavailable', 'local_vmoodle'), 'error');
    } else {
        echo(get_string('datapathavailable', 'local_vmoodle'));
    }
} else {
    if (filesystem_create_dir('', true, $dataroot)) {
        echo get_string('datapathcreated', 'local_vmoodle');
    } else {
        echo $OUTPUT->box(get_string('couldnotcreatedataroot', 'local_vmoodle', $dataroot), 'error');
    }
    echo stripslashes($dataroot);
}

echo "</p>";

$closestr = get_string('closewindow', 'local_vmoodle');
echo '<center>';
echo '<input type="button" name="close" value="'.$closestr.'" onclick="self.close();" />';
echo '</center>';

echo $OUTPUT->footer();
