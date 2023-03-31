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
 * Adhoc task that will restore a course at a delayed time from setup
 *
 * @package         local_vmoodle
 * @subpackage      vmoodleadminset_courses
 * @copyright  2022 Valery Fremaux (https://www.activeprolearn.com) 
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace vmoodleadminset_courses\task;

require_once($CFG->dirroot.'/local/vmoodle/plugins/courses/backup/restore_automation.class.php');

use core\task\adhoc_task;
use context_system;
use context_coursecat;
use local_vmoodle\restore_automation;
use StdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * Class that deployes a portfolio.
 *
 * @package     local_vmoodle
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_course_task extends adhoc_task {

    /**
     * Run the task deploy courses. the course deployment process invokes a moodlescript
     * engine and runs the moodlescript scenario setup in the deployportfolio tool settings.
     */
    public function execute() {
        global $CFG, $DB;

        // Get what was recorded in customdata when registered.
        // cohortid the cohort concerned
        // templateid the course template to deploy
        $customdata = $this->get_custom_data();
        $report = '';
        $traceable = 0;
        if (function_exists('debug_trace')) {
            $traceable = 1;
        }

        // Recheck all conditions at runtime. they have been checked at setup time, but 
        // something might have changed in thje meanwhile.

        if (!file_exists($customdata->location)) {
            mtrace(get_string('errornolocation', 'vmoodleadminset_courses')."\n ".$customdata->location);
            return false;
        }

        if (!preg_match('/\.mbz/', $customdata->location)) {
            mtrace(get_string('errornotamoodlearchive', 'vmoodleadminset_courses')."\n ".$customdata->location);
            return false;
        }

        if (!$coursecat = $DB->get_record('course_categories', array('id' => $customdata->coursecatid))) {
            mtrace(get_string('errornocategory', 'vmoodleadminset_courses'));
            return false;
        }

        if ($DB->get_record('course', array('shortname' => $customdata->shortname))) {
            mtrace(get_string('errorcoursealreadyexists', 'vmoodleadminset_courses'));
            return false;
        }

        if (!empty($customdata->idnumber) && $DB->get_record('course', array('idnumber' => $customdata->idnumber))) {
            mtrace(get_string('errorcourseidnumberexists', 'vmoodleadminset_courses'));
            return false;
        }

        $coursecatcontext = context_coursecat::instance($coursecat->id);
        if (!has_capability('moodle/course:create', $coursecatcontext)) {
            // Do the task holder have capability to create in the targeted category ?
            mtrace(get_string('errornopermission', 'vmoodleadminset_courses'));
            return false;
        }

        debug_trace('VMoodle Ad Hoc : Executing restore', TRACE_DEBUG);
        try {
            $newcourseid = restore_automation::run_automated_restore(null, $customdata->location, $coursecat->id, $customdata->seed);

            if (!$newcourseid) {
                if ($traceable) debug_trace('VMoodle Ad Hoc : error after restore. No course created.', TRACE_ERRORS);
                mtrace(get_string('errorafterrestore', 'vmoodleadminset_courses'));
                return false;
            } else {
                // Restore was OK, force the explicit identifiers.
                if (!empty($customdata->idnumber)) {
                    $DB->set_field('course', 'idnumber', $customdata->idnumber, ['id' => $newcourseid]);
                }

                if (!empty($customdata->shortname)) {
                    $DB->set_field('course', 'shortname', $customdata->shortname, ['id' => $newcourseid]);
                }

                if (!empty($customdata->fullname)) {
                    $DB->set_field('course', 'fullname', $customdata->fullname, ['id' => $newcourseid]);
                }

                // Restore was OK, now check for admins enrolment.
                if (!empty($customdata->enroladmins)) {
                    if ($traceable) debug_trace('VMoodle Ad Hoc : checking users to enrol', TRACE_DEBUG);
                    if (in_array($customdata->enroladmins, ['siteadmins', 'adminsandmanagers'])) {
                        if ($traceable) debug_trace('VMoodle Ad Hoc : Seeking for site admins', TRACE_DEBUG);
                        // Enrol site admins.
                        $admins = explode(",", $CFG->siteadmins);
                        if (!empty($admins)) {
                            foreach ($admins as $uid) {
                                $userstoenrol[] = $uid;
                            }
                        }
                    }

                    if (in_array($customdata->enroladmins, ['managers', 'adminsandmanagers'])) {
                        if ($traceable) debug_trace('Vmoodle Ad hoc : Seeking for site managers', TRACE_DEBUG);
                        // Complete users to enrol array with manager ids.
                        $systemcontext = context_system::instance();
                        // This should be a workable heuristic.
                        $managers = get_users_by_capability('moodle/site:deleteanymessage', $systemcontext);
                        if (!empty($managers)) {
                            foreach (array_keys($managers) as $uid) {
                                if (!in_array($uid, $userstoenrol)) {
                                    $userstoenrol[] = $uid;
                                }
                            }
                        }
                    }

                    if (!empty($userstoenrol)) {
                        if ($traceable) debug_trace('VMoodle Ad Hoc : Have '.count($userstoenrol).' users to enrol', TRACE_DEBUG);
                        $enrolplugin = enrol_get_plugin('manual');
                        $role = $DB->get_record('role', ['shortname' => 'editingteacher']);
                        $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $newcourseid, 'status' => 0]);
                        if (!$instance) {
                            // Do create a first enabled instance for manual enrolments if missing.
                            $newcourse = $DB->get_record('course', ['id' => $newcourseid]);
                            $enrolplugin->add_default_instance($newcourse);
                            // Fetch again the default instance now we have it.
                            $instance = $DB->get_record('enrol', ['enrol' => 'manual', 'courseid' => $newcourseid, 'status' => 0]);
                        }

                        // Now enrol all pending users.
                        foreach ($userstoenrol as $uid) {
                            $enrolplugin->enrol_user($instance, $uid, $role->id);
                        }
                    }
                }
            }
            if ($traceable) debug_trace('VMoodle Ad Hoc : Restore complete '.$newcourseid, TRACE_DEBUG);
        } catch (Exception $e) {
            if ($traceable) debug_trace('VMoodle Ad Hoc : Restore exception. '.$e->getMessage(), TRACE_ERRORS);
            mtrace(get_string('errorduringrestore', 'vmoodleadminset_courses', $e->getMessage()));
            return false;
        }

        return true;
    }
}
