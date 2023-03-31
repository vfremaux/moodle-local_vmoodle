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
 * @package    core
 * @subpackage cli
 * @copyright  2020 Valery Fremaux <valery@activeprolearn.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

if (!isset($CFG->dirroot)) {
    die ('$CFG->dirroot must be explicitely defined in moodle config.php for this script to be used');
}

require_once($CFG->dirroot.'/lib/clilib.php'); // Cli only functions.

list($options, $unrecognized) = cli_get_params(array('help' => false,
                                                     'userid' => false,
                                                     'courseid' => false,
                                                     'verbose' => false,
                                                     'host' => true),
                                               array('h' => 'help',
                                                     'u' => 'userid',
                                                     'c' => 'courseid',
                                                     'v' => 'verbose',
                                                     'H' => 'host'));

if ($unrecognized) {
    $unrecognized = implode("\n", $unrecognized);
    cli_error("Not recognized options ".$unrecognized);
}

if ($options['help']) {
    $help = "
Recover history grades of users.

Options:
-h, --help            Print out this help
-u, --userid          If given, restricts to that user, elsewhere processes all enrolled users in the courses.
-c, --courseid        If given restrict to that course, elsewhere process all courses.
-v, --verbose         If not given, will only display a progress indicator.
-H, --host            the virtual host you are working for

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/recover_grades.php
";

    echo $help;
    exit(0);
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
}
echo('Config check : playing for '.$CFG->wwwroot."\n");

require_once("$CFG->libdir/gradelib.php");
require_once("$CFG->dirroot/local/vmoodle/cli/clilib.php");

// Step one : check inputs.
if (!empty($options['courseid'])) {
    $course = $DB->get_record('course', ['id' => $options['courseid']], 'id,shortname,idnumber,fullname');
    if (!$course) {
        echo "Invalid courseid. Aborting.";
        exit(1);
    }
    $context = context_course::instance($course->id);
}

if (!empty($options['userid'])) {
    $user = $DB->get_record('user', ['id' => $options['userid']], 'id,username,firstname,lastname');
    if (!$user) {
        echo "Invalid userid. Aborting.";
        exit(1);
    }
}

// Step one : identifies all targets.
$todo = 0;

$targetcourses = [];
if (!empty($options['courseid'])) {

    // Single course.
    if (!empty($options['userid'])) {
        // Single course and single user.
        if (is_enrolled($context, $user)) {
            $targetuser = new StdClass;
            $targetuser->id = $user->id;
            $targetuser->username = $user->username;
            $targetuser->firstname = $user->firstname;
            $targetuser->lastname = $user->lastname;
            $course->users[$user->id] = $targetuser;
            $targetcourses[$course->id] = $course;
            $todo++;
        } else {
            echo "Single user not enrolled in single course. Aborting.\n";
            exit(1);
        }
    } else {
        // Single course and all enrolled users.
        $enrolledusers = get_enrolled_users($context);
        if ($enrolledusers) {
            foreach ($enrolledusers as $user) {
                $targetuser = new StdClass;
                $targetuser->id = $user->id;
                $targetuser->username = $user->username;
                $targetuser->firstname = $user->firstname;
                $targetuser->lastname = $user->lastname;
                $course->users[$user->id] = $targetuser;
                $todo++;
            }
        } else {
            echo "Single course with NO not enrolled users. Aborting.\n";
            exit(1);
        }
    }
    $targetcourses[$course->id] = $course;
} else {
    // All courses.
    if (!$courses = $DB->get_records('course', [], 'idnumber,shortname', 'id,shortname,idnumber,fullname')) {
        echo "No courses. Aborting.";
        exit(1);
    }
    foreach ($courses as $course) {
        $context = context_course::instance($course->id);
        if (!empty($options['userid'])) {
            // Single user in al targetted courses, only if enrolled.
            if (is_enrolled($context, $user)) {
                $targetuser = new StdClass;
                $targetuser->id = $user->id;
                $targetuser->username = $user->username;
                $targetuser->firstname = $user->firstname;
                $targetuser->lastname = $user->lastname;
                $course->users[$user->id] = $targetuser;
                $targetcourses[$course->id] = $course;
                $todo++;
            }
        } else {
            // Pick all enrolled users.
            $enrolledusers = get_enrolled_users($context);
            if ($enrolledusers) {
                foreach ($enrolledusers as $$user) {
                    $targetuser = new StdClass;
                    $targetuser->id = $user->id;
                    $targetuser->username = $user->username;
                    $targetuser->firstname = $user->firstname;
                    $targetuser->lastname = $user->lastname;
                    $course->users[$user->id] = $targetuser;
                    $todo++;
                }
                $targetcourses[$course->id] = $course;
            }
        }
    }
}

// Step two : process all targets.

$done = 0;
$failed = 0;
foreach ($targetcourses as $courseid => $course) {
    if (!empty($options['verbose'])) {
        echo "Processing course : [{$course->shortname}] $course->fullname ({$course->idnumber})\n";
    }
    foreach ($course->users as $userid => $user) {
        if (!empty($options['verbose'])) {
            echo "\tProcessing user : [{$user->username}] {$user->firstname} {$user->lastname}... ";
        }
        $result = vmoodle_grade_recover_history_grades($userid, $courseid);
        if (!empty($options['verbose'])) {
            if ($result) {
                echo "Success\n";
            } else {
                echo "Failed\n";
            }
        } else {
            if ($result) {
                $done++;
            } else {
                $failed++;
            }
            vmoodle_print_cli_progress($done + $failed, $todo);
        }
    }
}

purge_all_caches();

echo "Processed : $done\n";
echo "Failed (unprocessed) : $failed\n";
echo "Done.\n";

exit(0);

/**
 * Recover a user's grades from grade_grades_history for those items that are missing.
 * Changes from core grade_recover_history_grades in that the existance check is much finer and
 * only checks for non existing or non NULL exisiting grades for each item.
 * @param int $userid the user ID whose grades we want to recover
 * @param int $courseid the relevant course
 * @return bool true if successful or false if there was an error or no grades could be recovered
 */
function vmoodle_grade_recover_history_grades($userid, $courseid) {
    global $CFG, $DB;

    if ($CFG->disablegradehistory) {
        debugging('Attempting to recover grades when grade history is disabled.');
        return false;
    }

    //Were grades recovered? Flag to return.
    $recoveredgrades = false;

    //Check the user is enrolled in this course
    //Dont bother checking if they have a gradeable role. They may get one later so recover
    //whatever grades they have now just in case.
    $course_context = context_course::instance($courseid);
    if (!is_enrolled($course_context, $userid)) {
        debugging('Attempting to recover the grades of a user who is deleted or not enrolled. Skipping recover.');
        return false;
    }

    // Retrieve the user's old grades
    // have history ID as first column to guarantee we a unique first column
    // We search the "lastest" grade state not erased nor set by a reset.
    $sql = "
        SELECT
            h.id, gi.itemtype, gi.itemmodule, gi.iteminstance as iteminstance, gi.itemnumber, h.source, h.itemid, h.userid, h.rawgrade, h.rawgrademax,
            h.rawgrademin, h.rawscaleid, h.usermodified, h.finalgrade, h.hidden, h.locked, h.locktime, h.exported, h.overridden, h.excluded, h.feedback,
            h.feedbackformat, h.information, h.informationformat, h.timemodified, itemcreated.tm AS timecreated
        FROM
            {grade_grades_history} h
        JOIN
            (SELECT
                itemid, MAX(id) AS id
             FROM
                {grade_grades_history}
             WHERE
                userid = :userid1 AND
                source <> 'system'
             GROUP BY
                itemid) maxquery
         ON
            h.id = maxquery.id AND
            h.itemid = maxquery.itemid
         JOIN
            {grade_items} gi
         ON
            gi.id = h.itemid
        JOIN
            (SELECT
                itemid, MAX(timemodified) AS tm
             FROM
                {grade_grades_history}
             WHERE
                userid = :userid2 AND
                action = :insertaction AND
                source <> 'system'
            GROUP BY
                itemid) itemcreated
        ON
            itemcreated.itemid = h.itemid
        WHERE
            gi.courseid = :courseid AND
            h.source <> 'system'
    ";
    $params = array('userid1' => $userid, 'userid2' => $userid , 'insertaction' => GRADE_HISTORY_INSERT, 'courseid' => $courseid);
    $historygrades = $DB->get_records_sql($sql, $params);

    // Now move the old grades to the grade_grades table.
    foreach ($historygrades as $hgrade) {
        unset($hgrade->id);

        $sql = "
            SELECT
                gg.id
            FROM
                {grade_grades} gg
            JOIN
                {grade_items} gi
            ON
                gi.id = gg.itemid
            WHERE
                gg.userid = :userid AND
                gg.finalgrade IS NOT NULL AND
                gg.itemid = :itemid
        ";
        $params = array('userid' => $userid, 'itemid' => $hgrade->itemid);
        if ($DB->get_records_sql($sql, $params)) {
            // Student has new non empty grades on this item. Do not process it.
            continue;
        }

        $params = ['userid' => $userid, 'itemid' => $hgrade->itemid];
        if ($oldgrade = $DB->get_record('grade_grades', $params)) {
            $oldgrade->finalgrade = $hgrade->finalgrade;
            $DB->update_record('grade_grades', $oldgrade);

        } else {
            $grade = new grade_grade($hgrade, false);//2nd arg false as dont want to try and retrieve a record from the DB
            $grade->insert($hgrade->source);
        }

        // Delete subsequent from history.
        /*
        $select = ' timemodified > ? AND userid = ? AND itemid = ? ';
        $params = [$hgrade->timemodified, $userid, $hgrade->itemid];
        $DB->delete_records('grade_grades_history', $select, $params)
        */

        //dont include default empty grades created when activities are created
        if (!is_null($hgrade->finalgrade) || !is_null($hgrade->feedback)) {
            $recoveredgrades = true;
        }
    }

    //Some activities require manual grade synching (moving grades from the activity into the gradebook)
    //If the student was deleted when synching was done they may have grades in the activity that haven't been moved across
    grade_grab_course_grades($courseid, null, $userid);

    return $recoveredgrades;
}
