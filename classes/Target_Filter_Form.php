<?php

namespace local_vmoodle;

require_once($CFG->libdir.'/formslib.php');

/**
 * Define forms to filter platforms..
 * 
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
class Target_Filter_Form extends \moodleform {

    /**
     * Describes form.
     */
    public function definition() {

        // Setting variables.
        $mform = &$this->_form;
        $filtertype = array(
                        'contains' => get_string('contains', 'local_vmoodle'),
                        'notcontains' => get_string('notcontains', 'local_vmoodle'),
                        'regexp' => get_string('regexp', 'local_vmoodle')
                    );
        
        // Adding fieldset.
        $mform->addElement('header', 'pfilterform', get_string('filter', 'local_vmoodle'));

        // Adding group.
        $filterarray = array();
        $filterarray[] = &$mform->createElement('select', 'filtertype', null, $filtertype);
        $filterarray[] = &$mform->createElement('text', 'filtervalue', null, 'size="25"');
        $filterarray[] = &$mform->createElement('submit', null, get_string('filter', 'local_vmoodle'), 'onclick="add_filter(); return false;"');
        $mform->addGroup($filterarray, 'filterparam', get_string('platformname', 'local_vmoodle'), '', false);
        $mform->setType('filtervalue', PARAM_TEXT);
    }
}