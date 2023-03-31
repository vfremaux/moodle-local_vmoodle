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
 * Created on 18 nov. 2010
 *
 */

if (!defined('MOODLE_INTERNAL')) {
    // It must be included from a Moodle page.
    die('Direct access to this script is forbidden.');
}

require_once($CFG->dirroot.'/local/vmoodle/rpclib.php');
require_once($CFG->dirroot.'/local/vmoodle/plugins/courses/backup/restore_automation.class.php');
require_once($CFG->dirroot.'/local/vmoodle/plugins/courses/classes/task/restore_course_task.php');
require_once($CFG->dirroot.'/local/vmoodle/plugins/courses/classes/task/delete_course_task.php');
require_once($CFG->dirroot.'/local/vmoodle/plugins/courses/classes/task/delete_course_category_task.php');
require_once($CFG->dirroot.'/local/vmoodle/plugins/courses/classes/task/empty_course_category_task.php');

if (!defined('RPC_SUCCESS')) {
    define('RPC_TEST', 100);
    define('RPC_SUCCESS', 200);
    define('RPC_FAILURE', 500);
    define('RPC_FAILURE_USER', 501);
    define('RPC_FAILURE_CONFIG', 502);
    define('RPC_FAILURE_DATA', 503); 
    define('RPC_FAILURE_CAPABILITY', 510);
    define('MNET_FAILURE', 511);
    define('RPC_FAILURE_RECORD', 520);
    define('RPC_FAILURE_RUN', 521);
}

use local_vmoodle\restore_automation;
use vmoodleadminset_courses\task\restore_course_task;
use vmoodleadminset_courses\task\delete_course_task;
use vmoodleadminset_courses\task\delete_course_category_task;
use vmoodleadminset_courses\task\empty_course_category_task;

/**
 * Creates (or updates a category having some absolute path in the categroy tree.
 * If exists, may change idnumber if provided and different of the actual one.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $catpath a string slash separated path of the category (by names). The path elements will be trimmed before applying.
 * @param string $idnumber the IDNumber of the last path element (target category to create).
 * @param boolean $jsonrequired Asks for json return
 * @return rpc status as object or json string.
 */
function mnetadmin_rpc_create_category($user, $catpath, $idnumber = null, $visible = true, $jsonrequired = true) {
    global $DB;

    $traceable = function_exists('debug_trace');

    if ($traceable) debug_trace("VMOODLE: Starting Create Category", TRACE_DEBUG);

    // Invoke local user and check his rights.
    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        } else {
            return json_decode($auth_response);
        }
    }

    // Create category.
    // the IDNumber applies only to the last created leaf category.
    $pathelms = explode('/', $catpath);
    while ($element = array_shift($pathelms)) {
        $element = trim($element);
        $catid = $DB->get_field('course_categories', 'id', array('name' => $element));
        if ($catid) {
            $cat = \core_course_category::get($catid);
            if (count($pathelms) == 0) {
                // If last one in the path (the category we wanted to create exists, just update $idnumber
                if (!is_null($idnumber)) {
                    $elementdata = new StdClass;
                    $elementdata->idnumber = $idnumber;
                    $elementdata->visible = $visible;
                    $cat->update($elementdata);
                }
            }
        } else {
            if ($traceable) debug_trace("VMOODLE: Starting Create Category path ", TRACE_DEBUG);
            $elementdata = new StdClass;
            $elementdata->name = $element;
            $elementdata->parent = $cat->id;
            $elementdata->visible = $visible;
            if (count($pathelms) == 0) {
                // This was the last one.
                $elementdata->idnumber = $idnumber;
            }
            $cat = \core_course_category::create($elementdata);
        }
    }

    // Creating response.
    $response = new StdClass();
    $response->status = RPC_SUCCESS;

    if ($jsonrequired) {
        return json_encode($response);
    } else {
        return $response;
    }
}

/**
 * Locally places a delayed course restore task in a given category (by idnumber) from a locally filesystem accessible archive.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $shortname the target course shortname. It must not exist already.
 * @param string $fullname the target course fullname
 * @param string $idnumber the target course idnumber. It must not be used already.
 * @param string $catidnumber the idnumber of the course category to restore in. It must exist.
 * @param string $location an absolute path in the file system where to find an .mbz archive file.
 * @param string $enroladmins some enrolment options. Empty if no enrol, or managers (site level) or site admins, or both.
 * @param int $delay do not start effective retore before this delay (minutes) from task setup.
 * @param int $spread a period (minutes) in which the restore will occur at random time offset.
 * @param int $seed an integer that marks the same deployment operation. Can be explicitely fixed when building command.
 * @param boolean $jsonrequired Asks for json return
 */
function mnetadmin_rpc_restore_course($user, $shortname, $fullname, $idnumber, $catidnumber, $location, $enroladmins = '', $delay = 60, $spread = 60, $seed = '', $jsonrequired = true) {
    global $CFG, $USER, $DB;

    $traceable = function_exists('debug_trace');

    if ($traceable) debug_trace("VMOODLE : Starting Restore course");
    if ($traceable) debug_trace('RPC '.json_encode($user));

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        } else {
            return json_decode($auth_response);
        }
    }

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    // Pre Check of restorability conditions.

    if (!file_exists($location)) {
        $response->status = RPC_FAILURE_DATA;
        $response->error = get_string('errornolocation', 'vmoodleadminset_courses')."\n ".$location;
        $response->errors[] = get_string('errornolocation', 'vmoodleadminset_courses')."\n ".$location;
    }

    if (!preg_match('/\.mbz/', $location)) {
        $response->status = RPC_FAILURE_DATA;
        $response->error = get_string('errornotamoodlearchive', 'vmoodleadminset_courses')."\n ".$location;
        $response->errors[] = get_string('errornotamoodlearchive', 'vmoodleadminset_courses')."\n ".$location;
    }

    if (!$coursecat = $DB->get_record('course_categories', array('idnumber' => $catidnumber))) {
        $response->status = RPC_FAILURE_DATA;
        $response->error = get_string('errornocategory', 'vmoodleadminset_courses');
        $response->errors[] = get_string('errornocategory', 'vmoodleadminset_courses');
    }

    if ($DB->get_record('course', array('shortname' => $shortname))) {
        $response->status = RPC_FAILURE_DATA;
        $response->error = get_string('errorcoursealreadyexists', 'vmoodleadminset_courses');
        $response->errors[] = get_string('errorcoursealreadyexists', 'vmoodleadminset_courses');
    }

    if (!empty($idnumber) && $DB->get_record('course', array('idnumber' => $idnumber))) {
        $response->status = RPC_FAILURE_DATA;
        $response->error = get_string('errorcourseidnumberexists', 'vmoodleadminset_courses');
        $response->errors[] = get_string('errorcourseidnumberexists', 'vmoodleadminset_courses');
    }

    $coursecatcontext = context_coursecat::instance($coursecat->id);
    if (!has_capability('moodle/course:create', $coursecatcontext)) {
        $response->status = RPC_FAILURE_CAPABILITY;
        $response->error = get_string('errornopermission', 'vmoodleadminset_courses');
        $response->errors[] = get_string('errornopermission', 'vmoodleadminset_courses');
    }

    if ($response->status != RPC_SUCCESS) {
        // Trap any previously detected errors.
        if ($jsonrequired) {
            return json_encode($response);
        } else {
            return $response;
        }
    }

    // Now setup an ad hoc task passing incoming data.
    if ($traceable) debug_trace('RPC Bind : Placing restore task', TRACE_DEBUG);

    $task = new restore_course_task();

    $customdata = new StdClass;
    $customdata->location = $location;
    $customdata->coursecatid = $coursecat->id;
    $customdata->enroladmins = $enroladmins;
    $customdata->shortname = $shortname;
    $customdata->fullname = $fullname;

    $task->set_custom_data($customdata);

    $task->set_component('vmoodleadminset_courses');
    $task->set_userid($user->id);
    // Program a time randomly spread beteen delay and delay + spread. This is usefull on VMoodle large arrays.
    $tasktime = time() + (rand($delay, $delay + $spread) * MINSECS);

    $task->set_next_run_time($tasktime);

    \core\task\manager::queue_adhoc_task($task);
    $response->message = "Restore scheduled at ".userdate($tasktime);

    if ($traceable) debug_trace('RPC Bind : Sending response', TRACE_DEBUG);

    // Returns response (success or failure).
    if ($jsonrequired) {
        return json_encode($response);
    } else {
        return $response;
    }
}

/**
 * Remotely deletes a course given a shortname or an idnumber.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $fullname the target course fullname with potential LIKE wildcards
 * @param string $shortname the target course shortname
 * @param string $idnumber the target course idnumber
 * @param int $delay do not start effective retore before this delay (minutes) from task setup.
 * @param int $spread a period (minutes) in which the restore will occur at random time offset.
 * @param boolean $jsonrequired Asks for json return
 */
function mnetadmin_rpc_delete_course($user, $fullname = null, $shortname = null, $idnumber = null, $delay = 60, $spread = 60, $jsonrequired = true) {
    global $DB;

    $traceable = function_exists('debug_trace');

    if ($traceable) debug_trace("VMOODLE : Starting Delete course");
    if ($traceable) debug_trace('RPC '.json_encode($user));

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        } else {
            return json_decode($auth_response);
        }
    }

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    if (!empty($fullname)) {
        $courses = $DB->get_records_select('course', ' fullname LIKE ? ', [$fullname]);
    } else if (!empty($shortname)) {
        $course = $DB->get_record('course', array('shortname' => $shortname));
    } else  {
        if (!empty($idnumber)) {
            $course = $DB->get_record('course', array('shortname' => $shortname));
        }
    }

    if (empty($courses) && empty($course)) {
        $response->status = RPC_FAILURE_RUN;
        $response->error = get_string('errornocourse', 'vmoodleadminset_courses');
        $response->errors[] = get_string('errornocourse', 'vmoodleadminset_courses');
    } else {
        // Now setup an ad hoc task passing incoming data.
        if ($traceable) debug_trace('RPC Bind : Placing delete task', TRACE_DEBUG);

        $task = new delete_course_task();

        $customdata = new StdClass;
        $customdata->fullname = $fullname;
        $customdata->shortname = $shortname;
        $customdata->idnumber = $idnumber;

        $task->set_custom_data($customdata);

        $task->set_component('vmoodleadminset_courses');
        $task->set_userid($user->id);
        // Program a time randomly spread beteen delay and delay + spread. This is usefull on VMoodle large arrays.
        $tasktime = time() + (rand($delay, $delay + $spread) * MINSECS);

        $task->set_next_run_time($tasktime);

        \core\task\manager::queue_adhoc_task($task);
        $response->message = "Course Deletion scheduled at ".userdate($tasktime);

    }

    if ($traceable) debug_trace('RPC Bind : Sending response', TRACE_DEBUG);

    // Returns response (success or failure).
    if ($jsonrequired) {
        return json_encode($response);
    } else {
        return $response;
    }
}

/**
 * Remotely deletes a course given a shortname or an idnumber.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $shortname the target course shortname
 * @param string $idnumber the target course idnumber
 * @param int $delay do not start effective retore before this delay (minutes) from task setup.
 * @param int $spread a period (minutes) in which the restore will occur at random time offset.
 * @param boolean $jsonrequired Asks for json return
 */
function mnetadmin_rpc_delete_course_category($user, $idnumber = null, $delay = 60, $spread = 60, $jsonrequired = true) {
    global $DB;

    $traceable = function_exists('debug_trace');

    if ($traceable) debug_trace("VMOODLE : Starting Delete course category");
    if ($traceable) debug_trace('RPC '.json_encode($user));

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        } else {
            return json_decode($auth_response);
        }
    }

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    $coursecat = $DB->get_record('course_categories', array('idnumber' => $idnumber));

    if (empty($coursecat)) {
        $response->status = RPC_FAILURE_RUN;
        $response->error = get_string('errornocategory', 'vmoodleadminset_courses');
        $response->errors[] = get_string('errornocategory', 'vmoodleadminset_courses');
    } else {
        // Now setup an ad hoc task passing incoming data.
        if ($traceable) debug_trace('RPC Bind : Placing category delete task', TRACE_DEBUG);

        $task = new delete_course_category_task();

        $customdata = new StdClass;
        $customdata->catidnumber = $idnumber;

        $task->set_custom_data($customdata);

        $task->set_component('vmoodleadminset_courses');
        $task->set_userid($user->id);
        // Program a time randomly spread beteen delay and delay + spread. This is usefull on VMoodle large arrays.
        $tasktime = time() + (rand($delay, $delay + $spread) * MINSECS);

        $task->set_next_run_time($tasktime);

        \core\task\manager::queue_adhoc_task($task);
        $response->message = "Course Category Deletion scheduled at ".userdate($tasktime);

    }

    if ($traceable) debug_trace('RPC Bind : Sending response', TRACE_DEBUG);

    // Returns response (success or failure).
    if ($jsonrequired) {
        return json_encode($response);
    } else {
        return $response;
    }
}

/**
 * Remotely deletes a course given a shortname or an idnumber.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $shortname the target course shortname
 * @param string $idnumber the target course idnumber
 * @param int $delay do not start effective retore before this delay (minutes) from task setup.
 * @param int $spread a period (minutes) in which the restore will occur at random time offset.
 * @param boolean $jsonrequired Asks for json return
 */
function mnetadmin_rpc_empty_course_category($user, $idnumber = null, $delay = 60, $spread = 60, $jsonrequired = true) {
    global $DB;

    $traceable = function_exists('debug_trace');

    if ($traceable) debug_trace("VMOODLE : Starting Empty course category");
    if ($traceable) debug_trace('RPC '.json_encode($user));

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        } else {
            return json_decode($auth_response);
        }
    }

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    $coursecat = $DB->get_record('course_categories', array('idnumber' => $idnumber));

    if (empty($coursecat)) {
        $response->status = RPC_FAILURE_RUN;
        $response->error = get_string('errornocategory', 'vmoodleadminset_courses');
        $response->errors[] = get_string('errornocategory', 'vmoodleadminset_courses');
    } else {
        // Now setup an ad hoc task passing incoming data.
        if ($traceable) debug_trace('RPC Bind : Placing empty category task', TRACE_DEBUG);

        $task = new empty_course_category_task();

        $customdata = new StdClass;
        $customdata->catidnumber = $idnumber;

        $task->set_custom_data($customdata);

        $task->set_component('vmoodleadminset_courses');
        $task->set_userid($user->id);
        // Program a time randomly spread beteen delay and delay + spread. This is usefull on VMoodle large arrays.
        $tasktime = time() + (rand($delay, $delay + $spread) * MINSECS);

        $task->set_next_run_time($tasktime);

        \core\task\manager::queue_adhoc_task($task);
        $response->message = "Course Category Purge scheduled at ".userdate($tasktime);

    }

    if ($traceable) debug_trace('RPC Bind : Sending response', TRACE_DEBUG);

    // Returns response (success or failure).
    if ($jsonrequired) {
        return json_encode($response);
    } else {
        return $response;
    }
}

/**
 * Remotely checks for courses given a shortname, idnumber or fullname.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $shortname the target course shortname
 * @param string $fullname the target course fullname
 * @param string $idnumber the target course idnumber
 * @param boolean $jsonrequired Asks for json return
 */
function mnetadmin_rpc_check_course($user, $shortname = null, $fullname = null, $idnumber = null, $jsonrequired = true) {
    global $DB;

    $traceable = function_exists('debug_trace');

    if ($traceable) debug_trace("VMOODLE : Starting Check course");
    if ($traceable) debug_trace('RPC '.json_encode($user));

    if ($auth_response = invoke_local_user((array)$user)) {
        if ($jsonrequired) {
            return $auth_response;
        } else {
            return json_decode($auth_response);
        }
    }

    // Creating response.
    $response = new stdClass;
    $response->status = RPC_SUCCESS;

    $candidates = [];
    if (!empty($shortname)) {
        $sqllike = $DB->sql_like('shortname', ':shortname');
        $candidates = $DB->get_records_select('course', $sqllike, ['shortname' => $shortname], 'fullname', 'id,shortname,idnumber,fullname');
    }

    else if (!empty($idnumber)) {
        $sqllike = $DB->sql_like('idnumber', ':idnumber');
        $candidates = $DB->get_records_select('course', $sqllike, ['idnumber' => $idnumber], 'fullname', 'id,shortname,idnumber,fullname');
    }

    else if (!empty($fullname)) {
        $sqllike = $DB->sql_like('fullname', ':fullname');
        $candidates = $DB->get_records_select('course', $sqllike, ['fullname' => $fullname], 'fullname', 'id,shortname,idnumber,fullname');
    }

    $courses = [];
    foreach ($candidates as $c) {
        $courses[] = "[{$c->shortname}] $c->fullname ({$c->idnumber})";
    }

    if (empty($courses)) {
        $response->message = get_string('nocourses', 'vmoodleadminset_courses');
    } else {
        $response->message = implode("\n<br>", $courses);
    }

    // Returns response (success or failure).
    if ($jsonrequired) {
        return json_encode($response);
    } else {
        return $response;
    }
}