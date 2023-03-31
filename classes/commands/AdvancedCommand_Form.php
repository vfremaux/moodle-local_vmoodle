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
 * Define form to input an advanced SQL command.
 *
 * @package     local_vmoodle
 * @category    local
 * @author      Bruce Bujon (bruce.bujon@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace local_vmoodle;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

use \moodle_url;

class AdvancedCommand_Form extends \moodleform {

    /**
     * Constructor.
     */
    public function __construct() {
        // Calling parent's constructor.
        $params = array('view' => 'sadmin', 'what' => 'validateadvancedcommand');
        parent::__construct(new moodle_url('/local/vmoodle/view.php', $params));
    }

    /**
     * Describes form depending on command.
     */
    public function definition() {

        // Setting variables.
        $mform = $this->_form;

        // Adding header.
        $mform->addElement('header', null, get_string('advancedmode', 'local_vmoodle'));

        // Adding field.
        $attrs = 'wrap="virtual" rows="20" cols="50"';
        $mform->addElement('textarea', 'sqlcommand', get_string('sqlcommand', 'local_vmoodle'), $attrs);
        $mform->setType('sqlcommand', PARAM_TEXT);
        $mform->addRule('sqlcommand', null, 'required', null, 'client');

        $group = array();
        $group[0] = &$mform->createElement('text', 'sqlparam1name', '', $attrs);
        $mform->setType('sqlparam1name', PARAM_TEXT);
        $group[1] = &$mform->createElement('text', 'sqlparam1value', '', $attrs);
        $mform->setType('sqlparam1value', PARAM_TEXT);
        $mform->addGroup($group, 'sqlparam1', get_string('sqlparam', 'local_vmoodle').' 1', array(' => '), false);

        $group = array();
        $group[0] = &$mform->createElement('text', 'sqlparam2name', '', $attrs);
        $mform->setType('sqlparam2name', PARAM_TEXT);
        $group[1] = &$mform->createElement('text', 'sqlparam2value', '', $attrs);
        $mform->setType('sqlparam2value', PARAM_TEXT);
        $mform->addGroup($group, 'sqlparam2', get_string('sqlparam', 'local_vmoodle').' 2', array(' => '), false);

        $group = array();
        $group[0] = &$mform->createElement('text', 'sqlparam3name', '', $attrs);
        $mform->setType('sqlparam3name', PARAM_TEXT);
        $group[1] = &$mform->createElement('text', 'sqlparam3value', '', $attrs);
        $mform->setType('sqlparam3value', PARAM_TEXT);
        $mform->addGroup($group, 'sqlparam3', get_string('sqlparam', 'local_vmoodle').' 3', array(' => '), false);

        // Adding buttons.
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', get_string('nextstep', 'local_vmoodle'));
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', null, array(' '), false);
    }
}