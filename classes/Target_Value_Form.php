<?php

namespace local_vmoodle;

require_once($CFG->libdir.'/formslib.php');

/**
 * Define forms to get platforms by original value.
 * 
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
// TODO Comming.
class Target_Value_Form extends \moodleform {

    /**
     * Constructor.
     */
    function __construct() {
        parent::__construct(new moodle_url('/local/vmoodle/view.php', array('view' => 'sadmin', 'what' => 'gettargetbyvalue')));
    }

    /**
     * Describes form.
     */
    public function definition() {
        // Setting variables.
        $mform = &$this->_form;
    }
}