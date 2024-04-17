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
 * CLI script for deleting courses, single and en masse. Use at your own risk.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2015 Daniel Parker (Black River Technical College)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
// define('CACHE_DISABLE_ALL', true); => this leads to errors in course deletion. (mod_quiz)
$CLI_VMOODLE_PRECHECK = true;

// Location is in local/vmoodle/cli. change for using this script elsewhere.
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_once($CFG->dirroot.'/lib/clilib.php');

// CLI Options
list($options, $unrecognized) = cli_get_params(array(
    'help' => false,
    'category' => '',
    'categories' => '',
    'courseid' => '',
    'ignorecourses' => false,
    'hours' => '',
    'dryrun' => false,
    'host' => '',
    'debugging' => false,
    ),
    array(
    'h' => 'help',
    'C' => 'category',
    'CC' => 'categories',
    'c' => 'courseid',
    'i' => 'ignorecourses',
    'H' => 'host',
    't' => 'hours',
    'd' => 'dryrun',
    'D' => 'debugging',
    ));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
        "
        Delete one or multiple courses.
        Script used to delete courses in bulk by category or delete a specific course by ID.
        If by category, you must enter the category id.
        
        You may ask the script to stop after some hours of processing.

        Options:
        -h, --help              Print out this help
        -C, --category          Deletes courses by category
        -CC, --categories       Deletes several categories
        -c, --courseid          Deletes course by id2
        -i, --ignorecourses     If given, a list of courseids to be skipped, f.e. because blocking the purge process. 
        -t, --hours             Number of hours the purge process is allowed to run. 
        -H, --host              The host to play with. 
        -D, --debugging         Debug mode. 
        -d, --dryrun            Dry run mode. 

        Example:
        \$php moodle_folder/admin/cli/RemoveCourses.php
        ";

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
}
echo 'Config check : playing for '.$CFG->wwwroot."\n";

if (!empty($options['debugging'])) {
    error_reporting(E_ERROR | E_WARNING | E_PARSE);
}

require_once($CFG->dirroot . '/course/lib.php');
require_once( $CFG->dirroot .'/course/classes/category.php');

if (!empty($options['courseid'])) {

    $id = cli_input('Enter the course id');
    $id = clean_param($id, PARAM_INT);

    if ($id < 1) {
        cli_error("Invalid course number. Aborting..\n");
    }

    try {
        $course = get_course($id);
    } catch (Exception $e) {
        throw cli_error('The course cannot be found. Ensure you are using the correct ID number');
    }

    $prompt = "Delete :{$course->id}: [{$course->shortname}] ({$course->idnumber}) {$course->fullname} ? (Y/N)";
    $input = cli_input($prompt);
    $input = clean_param($input, PARAM_ALPHA);

    if (strtolower($input) == 'y') {
        try {
            if (empty($options['dryrun'])) {
                delete_this_course($course);
                echo 'Course deleted FOREVER!' . "\n";
                exit(0);
            } else {
                echo "Dry run : Will delete course :{$course->id}: [{$course->shortname}] ({$course->idnumber}) {$course->fullname} ";
            }
        } catch (Exception $e) {
            throw cli_error('Hmmm. Something went wrong.' . "\n");
        }
    } else {
        die();
    }
} elseif ($options['category']) {

    // This is a single category deletion request.

    $id = cli_input('Enter the category ID');
    $id = clean_param($id, PARAM_INT);

    if ($id < 1) {
        cli_error("You must specify a valid Category ID. Aborting...\n");
    }

    if (!is_numeric($id)) {
        cli_error("You must specify a valid Category ID as a number. Aborting...\n");
    }

    if (!$DB->record_exists('course_categories', ['id' => $id])) {
        cli_error("The category with ID $id does'nt exist. Aborting...\n");
    }

    $cat = $DB->get_record('course_categories', ['id' => $id]);
    clean_category($cat, $options);

} elseif ($options['categories']) {

    $catids = explode(',', $options['categories']);

    // Precheck and clean the list.
    foreach ($catids as &$cid) {
        if (!$DB->record_exists('course_categories', ['id' => $cid])) {
            $cid = trim($cid);
            cli_error("The category of ID $cid does not exist. Remove it from the list of categories to delete\n");
        }
    }

    foreach ($catids as $cid) {
        $cat = $DB->get_record('course_categories', ['id' => $cid]);
        $coulddeleteallcontent = clean_category($cat, $options);
        if ($coulddeleteallcontent) {
            $catobj = \core_course_category::get($cid, \MUST_EXIST, true);
            $catobj->delete_full(true);
            cli_write('--- Deleting Root category ['.$cat->id."] {$cid} \n");
        }
    }
}

function clean_category($cat, $options) {
    global $DB;

    $couldremoveall = true;
    $coulddeletesubcats = true;

    $cat = \core_course_category::get($cat->id, \MUST_EXIST, true);

    cli_write("--- deleting category :  {$cat->id} " . $cat->name . " \n");

    if (empty($options['dryrun'])) {

        $children = $DB->get_records('course_categories', ['parent' => $cat->id]);
        if ($children) {
            cli_write('Deleting children... '."\n");
            $j = 0;
            $f = 0;
            foreach ($children as $subcat) {
                cli_write('Cleaning sub category '.$subcat->id."\n");
                $subcatobj = \core_course_category::get($subcat->id, \MUST_EXIST, true);
                if ($coulddeletesubcat = clean_category($subcat, $options)) {
                    $coulddeletesubcats = $coulddeletesubcats && $coulddeletesubcat;
                    // could empty all content.
                    // Remove the emptied category.
                    if (empty($options['dryrun'])) {
                        $subcatobj->delete_full(true);
                    } else {
                        cli_write('Dryrun : Cleaning sub category '.$subcat->id." \n");
                    }
                    $j++;
                } else {
                    $coulddeletesubcats = false;
                    $f++;
                    cli_write('Error : Cleaning sub category '.$subcat->id." was not complete \n");
                }
            }
            cli_write("Deleting children... Success : $j : Failed : $f \n");
        }

        $catcourses = $DB->get_records('course', ['category' => $cat->id]);
        if ($catcourses) {
            $start = count($catcourses);
            $i = 0;
            foreach ($catcourses as $course) {

                if (!empty($options['ignorecourses'])) {
                    $ignored = explode(',', $options['ignorecourses']);
                    if (in_array($course->id, $ignored)) {
                        $couldremoveall = false;
                        continue;
                    }
                }

                try {
                    cli_write("--- Deleting ({$course->shortname}) ".$course->fullname." ");
                    if (empty($options['dryrun'])) {
                        delete_course($course);
                    } else {
                        cli_write('Dryrun : Deleting course '.$course->id." \n");
                    }
                    cli_write(" ...deleted\n");
                    $i++;
                } catch (Exception $e) {
                    $couldremoveall = false;
                    cli_write("--- Error: Cannot remove course [$course->id] ({$course->shortname}) ".$course->shortname." but try to continue \n");
                }
            }
            cli_write('--- ' . $i . " courses deleted \n");
        } else {
            cli_write('--- ' . " no courses to delete \n");
        }
    }

    return $couldremoveall && $coulddeletesubcats;
}

/**
 * Deletes courses in a CLI friendly way
 * @param stdClass course - Moodle course object
 * @return void
 */
function delete_this_course($course) {
    $courseObj =  (!is_object($course)) ? get_course($course) : $courseObj = $course;

    echo 'Deleting ' . $courseObj->shortname . ' (' . $courseObj->fullname . ")\n";

    try {
        // Output buffer because I don't want it spitting tons of HTML at me
        ob_start();
        delete_course($courseObj);
        fix_course_sortorder();
        // End output buffer
        ob_end_clean();
    } catch (Exception $e) {
        echo "Error deleting [{$courseObj->id}] (" . $courseObj->shortname . ') ' . $courseObj->fullname . "\n";
        echo "Exception Message: " . $e . "\n";
    }
}