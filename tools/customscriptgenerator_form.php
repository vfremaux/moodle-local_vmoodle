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
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux <valery.fremaux@gmail.com>, <valery@edunao.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2014 onwards Microsoft Open Technologies, Inc. (http://msopentech.com/)
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/lib/formslib.php');

class CustomScriptGenerator_Form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addelement('header', 'templatehead', get_string('templatehead', 'local_vmoodle'));
        $mform->setExpanded('templatehead');
        $mform->addHelpButton('templatehead', 'generatescripts', 'local_vmoodle');

        $label = get_string('templatetext', 'local_vmoodle');
        $mform->addElement('textarea', 'templatetext', $label, array('cols' => 80, 'rows' => 15));
        $mform->setType('scripttemplate', PARAM_TEXT);

        $commentoptions = array('shell' => 'shell', 'web' => 'HTML', 'sql' => 'SQL');
        $label = get_string('commentformat', 'local_vmoodle');
        $mform->addElement('select', 'commentformat', $label, $commentoptions);
        $mform->setType('commentformat', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('generate', 'local_vmoodle'));
    }
}