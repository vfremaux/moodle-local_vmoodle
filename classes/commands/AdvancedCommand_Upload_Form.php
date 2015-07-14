<?php

require_once ($CFG->libdir.'/formslib.php');

/**
 * Define form to upload a SQL script.
 * 
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
class AdvancedCommand_Upload_Form extends moodleform {
    /**
     * Constructor.
     */
    public function __construct() {
        // Calling parent's constructor
        parent::__construct(new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin', 'what' => 'uploadsqlscript')));
    }
    
    /**
     * Describes form depending on command.
     */
    public function definition() {
        // Setting variables.
        $mform =& $this->_form;

        // Adding header
        $mform->addElement('header', null, get_string('uploadscript', 'local_vmoodle'));

        // Adding field
        $mform->addElement('file', 'script', get_string('sqlfile', 'local_vmoodle'));
        $mform->setType('script', PARAM_FILE);

        // Adding submit button
        $mform->addElement('submit', 'uploadbutton', get_string('uploadscript', 'local_vmoodle'));
    }
}