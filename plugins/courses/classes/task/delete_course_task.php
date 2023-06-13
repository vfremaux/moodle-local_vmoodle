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
 * Class that delayed deletes a course.
 *
 * @package     local_vmoodle
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_course_task extends adhoc_task {

    /**
     * Run the task deploy courses. the course deployment process invokes a moodlescript
     * engine and runs the moodlescript scenario setup in the deployportfolio tool settings.
     */
    public function execute() {
        global $CFG, $DB;

        // Get what was recorded in customdata when registered.
        // templateid the course template to deploy
        $customdata = $this->get_custom_data();

        // Recheck all conditions at runtime. they have been checked at setup time, but 
        // something might have changed in thje meanwhile.

        $courses = [];
        if (!empty($customdata->fullname) && !$courses = $DB->get_records_select('course', "fullname LIKE ? ", [$customdata->fullname])) {
            mtrace(get_string('errornocourse', 'vmoodleadminset_courses'));
            return false;
        } else if (!empty($customdata->shortname) && !$course = $DB->get_record('course', ['shortname' => $customdata->shortname])) {
            mtrace(get_string('errornocourse', 'vmoodleadminset_courses'));
            return false;
        } else if (!empty($customdata->idnumber) && !$course = $DB->get_record('course', ['idnumber' => $customdata->idnumber])) {
            mtrace(get_string('errornocourse', 'vmoodleadminset_courses'));
            return false;
        }

        if (!empty($course)) {
            $courses[] = $course;
        }

        foreach ($courses as $course) {
            delete_course($course);
        }

        return true;
    }
}
