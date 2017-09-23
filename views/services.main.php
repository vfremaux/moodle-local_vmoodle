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
 * Displays default services strategy.
 *
 * @package local_vmoodle
 * @category local
 * @author Moheissen Fabien (fabien.moheissen@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/vmoodle/classes/ServicesStrategy_Form.php');

$defaultservices = $DB->get_records('mnet_service', array('offer' => 1), 'name');

$config = get_config('local_vmoodle');

// Displays the form.
$services_form = new \local_vmoodle\ServicesStrategy_Form();
if ($services = unserialize(@$config->services_strategy)) {
    $services_form->set_data($services);
}

echo $OUTPUT->box_start();
$services_form->display();

echo $OUTPUT->heading(get_string('rawstrategy', 'local_vmoodle'));
echo $OUTPUT->box(get_string('rawstrategy_desc', 'local_vmoodle'));
echo '<pre>';
echo @$config->services_strategy;
echo '</pre>';

echo $OUTPUT->box_end();
