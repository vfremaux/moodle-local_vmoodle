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
 * Defines forms to set Command.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace local_vmoodle;

defined('MOODLE_INTERNAL') || die;

use \local_vmoodle\commands\Command_Exception;
use \local_vmoodle\commands\Command;

require_once($CFG->libdir.'/formslib.php');

class Command_Form extends \moodleform {

    /**
     * Form modes
     */
    const MODE_COMMAND_CHOICE = 1;
    const MODE_RETRIEVE_PLATFORM = 2;
    const MODE_DISPLAY_COMMAND = 3;

    /**
     * Command linked to the form
     */
    public $command;

    /**
     * Form mode
     */
    public $mode;

    /**
     * Constructor.
     * @param Command $command The Command to link to the form.
     * @param int $mode The form mode.
     */
    public function __construct(Command $command, $mode) {
        // Checking command.
        if (is_null($command)) {
            throw new Command_Exception('commandformnotlinked');
        }

        // Linking the command and her category.
        $this->command = $command;

        // Setting configuration.
        $this->mode = $mode;

        // Setting form action.
        switch($mode) {
            case self::MODE_COMMAND_CHOICE:
                $url = new \moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin', 'what' => 'validateassistedcommand'));
                break;
            case self::MODE_RETRIEVE_PLATFORM:
                $url = new \moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin', 'what' => 'gettargetbyvalue'));
                break;
            case self::MODE_DISPLAY_COMMAND:
                $url = new \moodle_url('/local/vmoodle/view.php', array('view' => 'targetchoice'));
                break;
            default:
                throw new Command_Exception('badformmode');
                break;
        }
        // Calling parent's constructor.
        parent::__construct($url->out(false));
    }

    /**
     * Describes form depending on command.
     * @throws Command_Exception.
     */
    public function definition() {

        // Setting variables.
        $mform =& $this->_form;
        $command = $this->command;
        $parameters = $command->get_parameters();

        // Adding fieldset.
        $mform->addElement('header', null, $command->get_name());

        // Adding hidden fields.
        if ($this->mode == self::MODE_COMMAND_CHOICE) {
            $mform->addElement('hidden', 'category_name', $command->get_category()->get_name());
            $mform->setType('category_name', PARAM_TEXT);

            $mform->addElement('hidden', 'category_plugin_name', $command->get_category()->get_plugin_name());
            $mform->setType('category_plugin_name', PARAM_TEXT);

            $mform->addElement('hidden', 'command_index', $command->get_index());
            $mform->setType('command_index', PARAM_TEXT);
        }

        // Adding command's description.
        $mform->addElement('static', 'description', get_string('commanddescription', 'local_vmoodle'), $command->get_description());

        // Adding elements depending on command's parameter.
        if (!is_null($parameters)) {
            foreach ($parameters as $parameter) {
                switch ($parameter->get_type()) {
                    case 'boolean':
                        $mform->addElement('checkbox', $parameter->get_name(), $parameter->get_description());
                        break;
                    case 'enum':
                        $label = $parameter->get_name();
                        $desc = $parameter->get_description();
                        $options = $parameter->get_choices();
                        $mform->addElement('select', $label, $desc, $options);
                        break;
                    case 'text':
                        $mform->addElement('text', $parameter->get_name(), $parameter->get_description());
                        $mform->setType($parameter->get_name(), PARAM_TEXT);
                        break;
                    case 'ltext':
                        $attrs = 'wrap="virtual" rows="20" cols="50"';
                        $mform->addElement('textarea', $parameter->get_name(), $parameter->get_description(), $attrs);
                        $mform->setType($parameter->get_name(), PARAM_TEXT);
                        break;
                    case 'internal':
                        continue 2;
                }
                // Defining value.
                if ($this->mode == self::MODE_DISPLAY_COMMAND) {
                    $mform->setDefault($parameter->get_name(), $parameter->get_value());
                    $mform->freeze($parameter->get_name());
                } else if (!is_null($parameter->get_default())) {
                    $mform->setDefault($parameter->get_name(), $parameter->get_default());
                }
            }
        }

        // Adding submit button.
        switch($this->mode) {
            case self::MODE_COMMAND_CHOICE:
                $mform->addElement('submit', 'submitbutton', get_string('nextstep', 'local_vmoodle'));
                break;
            case self::MODE_RETRIEVE_PLATFORM:
                $mform->addElement('submit', 'submitbutton', get_string('retrieveplatforms', 'local_vmoodle'));
                break;
        }
    }
}