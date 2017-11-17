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

class CopyScriptsParams_Form extends moodleform {

    public function definition() {
        $mform = $this->_form;

        $mform->addelement('header', 'maindbhead', get_string('maindb', 'local_vmoodle'));
        $mform->setExpanded('maindbhead');
        $mform->addElement('text', 'fromversion', get_string('fromversion', 'local_vmoodle'), '');
        $mform->setType('fromversion', PARAM_TEXT);

        $mform->addElement('text', 'toversion', get_string('toversion', 'local_vmoodle'), '');
        $mform->setType('toversion', PARAM_TEXT);

        $mform->addelement('header', 'cronlineshead', get_string('cronlines', 'local_vmoodle'));
        $mform->setExpanded('cronlineshead');
        $cliopstr = get_string('clioperated', 'local_vmoodle');
        $webopstr = get_string('weboperated', 'local_vmoodle');
        $cronoptions = array('cli' => $cliopstr, 'web' => $webopstr);
        $mform->addElement('select', 'cronmode', get_string('cronmode', 'local_vmoodle'), $cronoptions);
        $mform->setType('cronmode', PARAM_TEXT);

        $this->add_action_buttons(true, get_string('generate', 'local_vmoodle'));
    }
}