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
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace local_vmoodle;

require_once($CFG->libdir.'/formslib.php');

/**
 * Define forms to filter platforms.
 */
class Target_Filter_Form extends \moodleform {

    /**
     * Describes form.
     */
    public function definition() {

        // Setting variables.
        $mform = &$this->_form;
        $filtertype = array('contains' => get_string('contains', 'local_vmoodle'),
                            'notcontains' => get_string('notcontains', 'local_vmoodle'),
                            'regexp' => get_string('regexp', 'local_vmoodle'));

        // Adding fieldset.
        $mform->addElement('header', 'pfilterform', get_string('filter', 'local_vmoodle'));

        // Adding group.
        $filterarray = array();
        $filterarray[] = &$mform->createElement('select', 'filtertype', null, $filtertype);
        $filterarray[] = &$mform->createElement('text', 'filtervalue', null, 'size="25"');
        $label = get_string('filter', 'local_vmoodle');
        $filterarray[] = &$mform->createElement('submit', null, $label, 'onclick="add_filter(); return false;"');
        $mform->addGroup($filterarray, 'filterparam', get_string('platformname', 'local_vmoodle'), '', false);
        $mform->setType('filtervalue', PARAM_TEXT);
    }
}