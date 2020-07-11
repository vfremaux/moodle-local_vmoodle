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
 * Manage files in folder in private area.
 *
 * @package   tool_sync
 * @category  tool
 * @copyright 2010 Valery Fremaux <valery.fremaux@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/tools/files_form.php');

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

if (empty($returnurl)) {
    $returnurl = new moodle_url('/local/vmoodle/tools/generatecopyscripts.php');
}

// Security.

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$title = get_string('copyscripts', 'local_vmoodle');

$PAGE->set_url('/local/vmoodle/tools/filearea.php');
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('mydashboard');
$PAGE->set_pagetype('user-files');

$data = new stdClass();
$data->returnurl = $returnurl;

$options = array('subdirs' => 0, 'maxbytes' => -1, 'maxfiles' => -1, 'accepted_types' => '*', 'areamaxbytes' => -1);

file_prepare_standard_filemanager($data, 'files', $options, $context, 'local_vmoodle', 'migrationscripts', 0);

$mform = new copyscripts_files_form(null, array('data' => $data, 'options' => $options));

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($formdata = $mform->get_data()) {
    $formdata = file_postupdate_standard_filemanager($formdata, 'files', $options, $context, 'local_vmoodle', 'migrationfiles', 0);
    redirect($returnurl);
}

echo $OUTPUT->header();
echo $OUTPUT->box_start('generalbox');
$mform->display();
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
