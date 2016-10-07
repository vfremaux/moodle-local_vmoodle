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

defined('MOODLE_INTERNAL') || die();

// Moodle form's library.

require_once($CFG->libdir.'/formslib.php');

if (file_exists($CFG->libdir.'/pear/HTML/QuickForm/elementgrid.php')) {
    require_once($CFG->libdir.'/pear/HTML/QuickForm/elementgrid.php');
} else {
    require_once('__other/elementgrid.php');
}

class ServicesStrategy_Form extends \moodleform {

    /**
     * Constructor.
     */
    public function __construct() {
        // Calling parent's constructor.
        parent::__construct(new \moodle_url('/local/vmoodle/view.php', array('view' => 'services', 'what' => 'redefineservices')));
    }

    /**
     * Describes the form, with the triplet (service_name, publish, subscribe).
     */
    public function definition() {
        global $CFG, $SESSION, $DB;

        // Settings variables.
        $mform =& $this->_form;

        // Master services.
        $defaultservices = $DB->get_records('mnet_service', array('offer' => 1), 'name');

        // Get version info to get real names.
        $self_mnet_peer = new \mnet_peer();
        $self_mnet_peer->set_id($CFG->mnet_localhost_id);
        $myservices = mnet_get_service_info($self_mnet_peer);

        if (!empty($defaultservices)) {
            // Services fieldset.
            $mform->addElement('header', 'servicesform', get_string('servicesformselection', 'local_vmoodle'));

            $grid = &$mform->addElement('elementgrid', 'grid', get_string('mainservicesformselection', 'local_vmoodle'));

            $row = array();
            $row[] = get_string('publish', 'local_vmoodle');
            $row[] = get_string('subscribe', 'local_vmoodle');
            $row[] = '';
            $row[] = '';

            $grid->setColumnNames($row);

            foreach ($defaultservices as $defaultservice) {
                $row = array();
                $row[] = $mform->createElement('advcheckbox', 'main_'.$defaultservice->name.'_publish');
                $row[] = $mform->createElement('advcheckbox', 'main_'.$defaultservice->name.'_subscribe');
                $row[] = $mform->createElement('static', 'main_'.$defaultservice->name.'_description');
                $row[] = $mform->createElement('hidden', 'main_'.$defaultservice->name.'_id');

                $description = $defaultservice->description;
                if (empty($description)) {
                    if (!empty($myservices[$defaultservice->name])) {
                        $version = current($myservices[$defaultservice->name]);
                        // TODO there should be a moodle-wide way to do this.
                        $langmodule = ($version['plugintype'] == 'mod' ? '' : ($version['plugintype'] . '_')).$version['pluginname'];
                        $description = get_string($defaultservice->name.'_name', $langmodule);
                    } else {
                        $description = '[['$defaultservice->name.'_name]]';
                    }
                }

                $mform->setDefault('main_'.$defaultservice->name.'_description', $description);
                $mform->setDefault('main_'.$defaultservice->name.'_id',    $defaultservice->id);
                $mform->setType('main_'.$defaultservice->name.'_id', PARAM_INT);
                $grid->addRow($row);
            }

            // Services fieldset.

            $grid = &$mform->addElement('elementgrid', 'grid', get_string('peerservicesformselection', 'local_vmoodle'));

            $row = array();
            $row[] = get_string('publish', 'local_vmoodle');
            $row[] = get_string('subscribe', 'local_vmoodle');
            $row[] = '';
            $row[] = '';

            $grid->setColumnNames($row);

            foreach ($defaultservices as $defaultservice) {
                $row = array();
                $row[] = $mform->createElement('advcheckbox', 'peer_'.$defaultservice->name.'_publish');
                $row[] = $mform->createElement('advcheckbox', 'peer_'.$defaultservice->name.'_subscribe');
                $row[] = $mform->createElement('static', 'peer_'.$defaultservice->name.'_description');
                $row[] = $mform->createElement('hidden', 'peer_'.$defaultservice->name.'_id');

                $description = $defaultservice->description;
                if (empty($description)) {
                    if (!empty($myservices[$defaultservice->name])) {
                        $version = current($myservices[$defaultservice->name]);
                        $langmodule = ($version['plugintype'] == 'mod' ? '' : ($version['plugintype'] . '_')).$version['pluginname'];
                        $description = get_string($defaultservice->name.'_name', $langmodule);
                    } else {
                        $description = '[['.$defaultservice->name.'_name]]';
                    }
                }

                $mform->setDefault('peer_'.$defaultservice->name.'_description', $description);
                $mform->setDefault('peer_'.$defaultservice->name.'_id',    $defaultservice->id);
                $mform->setType('peer_'.$defaultservice->name.'_id', PARAM_INT);
                $grid->addRow($row);
            }

            // Submit button.
            $mform->addElement('submit', 'submitbutton', get_string('edit'));

        } else {
            // Confirmation message.
            $message_object = new \stdclass();
            $message_object->message = get_string('badservicesnumber', 'local_vmoodle');
            $message_object->style = 'notifyproblem';

            // Save confirm message before redirection.
            $SESSION->vmoodle_ma['confirm_message'] = $message_object;
            new moodle_url('/local/vmoodle/view.php', array('view' => 'management'));
        }
    }
}