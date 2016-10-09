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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This script is a fixture that checks the whole rpc/service/host mnet tables to eliminate surnumerous rpc declares
 *
 */
require('../../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/fixtures/fix_mnet_tables_lib.php');

// Security.

$context = context_system::instance();
$url = new moodle_url('/local/vmoodle/fixtures/fix_mnet_tables.php');
$PAGE->set_url($url);
$PAGE->set_context($context);

require_login();
require_capability('moodle/site:config', $context);

$PAGE->set_pagelayout('admin');
$PAGE->set_heading($SITE->fullname);
$PAGE->navbar->add('MNET Fixes');

echo $OUTPUT->header();

echo $OUTPUT->heading('Mnet Tables Consistancy Cleaner');

fix_mnet_tables_fixture();

echo $OUTPUT->footer();
