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
 * Form for adding a virtual host.
 *
 * @package local_vmoodle
 * @category local
 * @author Moheissen Fabien (fabien.moheissen@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

// Loading the library.
Use \local_vmoodle\Host_Form;

$config = get_config('local_vmoodle');

// Print title (heading).
echo $OUTPUT->heading(get_string('newvmoodle', 'local_vmoodle'));

echo $OUTPUT->box_start();

// Displays the form.
if (isset($SESSION->vmoodle_mg['dataform'])) {
    $platform_form = new \local_vmoodle\Host_Form('add', $SESSION->vmoodle_mg['dataform']);
} else {
    $platform_form = new \local_vmoodle\Host_Form('add', null);

    if ($CFG->local_vmoodle_automatedschema) {
        if ($config->mnet == 'NEW') {
            $lastsubnetwork = $DB->get_field('local_vmoodle', 'MAX(mnet)', array());
            $formdata->mnet = $lastsubnetwork + 1;
        } else {
            $formdata->mnet = 0 + $config->mnet;
        }

        $formdata->services = $config->services;
        $platform_form->set_data($formdata);
    }
}

$platform_form->display();
echo $OUTPUT->box_end();
