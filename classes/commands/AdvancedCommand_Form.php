<?php

require_once($CFG->libdir.'/formslib.php');

/**
 * Define form to input an advanced SQL command.
 * 
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
class AdvancedCommand_Form extends moodleform {

    /**
     * Constructor.
     */
    public function __construct() {
        // Calling parent's constructor
        parent::__construct(new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin', 'what' => 'validateadvancedcommand')));
    }
    
    /**
     * Describes form depending on command.
     */
    public function definition() {
        // Setting variables
        $mform =& $this->_form;
        // Adding header
        $mform->addElement('header', null, get_string('advancedmode', 'local_vmoodle'));

        // Adding field
        $mform->addElement('textarea', 'sqlcommand', get_string('sqlcommand', 'local_vmoodle'), 'wrap="virtual" rows="20" cols="50"');
        $mform->setType('sqlcommand', PARAM_TEXT);
        $mform->addRule('sqlcommand', null, 'required', null, 'client');

        // Adding buttons
        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton', get_string('nextstep', 'local_vmoodle'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', null, array(' '), false);
    }
}