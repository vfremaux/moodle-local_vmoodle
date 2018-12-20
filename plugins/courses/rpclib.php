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

require_once($CFG->dirroot.'/lib/coursecatlib.php');
require_once($CFG->dirroot.'/local/vmoodle/rpclib.php');
require_once($CFG->dirroot.'/local/vmoodle/plugins/courses/backup/restore_automation.class.php');

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

    debug_trace("VMOODLE: Starting Create Category");

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
            $cat = coursecat::get($catid);
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
            debug_trace("VMOODLE: Starting Create Category path ");
            $elementdata = new StdClass;
            $elementdata->name = $element;
            $elementdata->parent = $cat->id;
            $elementdata->visible = $visible;
            if (count($pathelms) == 0) {
                // This was the last one.
                $elementdata->idnumber = $idnumber;
            }
            $cat = coursecat::create($elementdata);
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
 * Locally restores a course to a given category (by idnumber) from a locally filesystem accessible archive.
 * @param object $user The calling user, containing mnethostroot reference and hostroot reference.
 * @param string $shortname the target course shortname. It must not exist already.
 * @param string $fullname the target course fullname
 * @param string $idnumber the target course idnumber. It must not be used already.
 * @param string $catidnumber the idnumber of the course category to restore in. It must exist.
 * @param string $location an absolute pat in the file system where to find an .mbz archive file.
 * @param boolean $jsonrequired Asks for json return
 */
function mnetadmin_rpc_restore_course($user, $shortname, $fullname, $idnumber, $catidnumber, $location, $jsonrequired = true) {
    global $CFG, $USER, $DB;

    debug_trace("VMOODLE : Starting Restore course");
    debug_trace('RPC '.json_encode($user));

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

    // TODO :

    if (!file_exists($location)) {
        $response->status = RPC_FAILURE_DATA;
        $response->error = get_string('errornolocation', 'vmoodleadminset_courses');
        $response->errors[] = get_string('errornolocation', 'vmoodleadminset_courses');
    }

    if (!preg_match('/\.mbz/', $location)) {
        $response->status = RPC_FAILURE_DATA;
        $response->error = get_string('errornotamoodlearchive', 'vmoodleadminset_courses');
        $response->errors[] = get_string('errornotamoodlearchive', 'vmoodleadminset_courses');
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

    if (!empty($idnumber) && $DB->get_record('course', array('idnumber' => $shortname))) {
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

    debug_trace('RPC Bind : Executing restore');
    try {
        $newcourseid =  restore_automation::run_automated_restore(null, $location, $coursecat->id);

        if (!$newcourseid) {
            $response->status = RPC_FAILURE_RUN;
            $response->error = get_string('errorafterrestore', 'vmoodleadminset_courses');
            $response->errors[] = get_string('errorafterrestore', 'vmoodleadminset_courses');
        }
    } catch (Exception $e) {
        $response->status = RPC_FAILURE_RUN;
        $response->error = get_string('errorduringrestore', 'vmoodleadminset_courses', $e->getMessage());
        $response->errors[] = get_string('errorduringrestore', 'vmoodleadminset_courses', $e->getMessage());
    }

    debug_trace('RPC Bind : Sending response');

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
 * @param boolean $jsonrequired Asks for json return
 */
function mnetadmin_rpc_delete_course($user, $shortname = null, $idnumber = null, $jsonrequired = true) {
    global $DB;

    debug_trace("VMOODLE : Starting Delete course");
    debug_trace('RPC '.json_encode($user));

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

    if (!empty($shortname)) {
        $course = $DB->get_record('course', array('shortname' => $shortname));
    } else  {
        if (!empty($idnumber)) {
            $course = $DB->get_record('course', array('shortname' => $shortname));
        }
    }

    if (empty($course)) {
        $response->status = RPC_FAILURE_RUN;
        $response->error = get_string('errornocourse', 'vmoodleadminset_courses');
        $response->errors[] = get_string('errornocourse', 'vmoodleadminset_courses');
    } else {
        delete_course($course->id, false);
    }

    debug_trace('RPC Bind : Sending response');

    // Returns response (success or failure).
    if ($jsonrequired) {
        return json_encode($response);
    } else {
        return $response;
    }
}
