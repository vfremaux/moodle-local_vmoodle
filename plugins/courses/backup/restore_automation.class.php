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
 * This file contains a class used for full course restore automation.
 *
 * @Author Wafa Adham ,wafa@adham.ps
 */
namespace local_vmoodle;

defined('MOODLE_INTERNAL') || die();

use StdClass;

require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');

class restore_automation {

    /**
     * given a stored backup file, this function creates course from
     * this backup automatically.
     * @param mixed $backup_file_id backup file id
     * @param mixed $course_category_id  destination restore category.
     */
    public static function run_automated_restore($backupfileid = null, $filepath = null, $coursecategoryid, $seed = '') {
        global $CFG, $DB, $USER;

        debug_trace("Starting automated restore...");

        $fs = get_file_storage();

        if (!$backupfileid && empty($filepath)) {
            debug_trace("Invalid or empty backup file source.");
            throw new Exception("Invalid or empty backup file source.");
        }

        if (!empty($filepath)) {
            if (!is_readable($filepath)) {
                debug_trace("Not readable path");
                throw new Exception("Not readable path");
            }

            if (!is_file($filepath)) {
                debug_trace("Not a file");
                throw new Exception("Not a file");
            }
        }

        debug_trace("Registering a file.");
        if (!empty($filepath)) {
            debug_trace("By filepath");
            debug_trace($filepath);
            $array = explode('/', $filepath);
            $filename = array_pop($array);

            $filerec = new stdClass();
            $filerec->contextid = 1;
            $filerec->component = 'backup';
            $filerec->filearea = 'publishflow';
            $filerec->itemid = 0;
            $filerec->filename = $filename;
            $filerec->filepath = '/';

            // Try load the file.
            $file = $fs->get_file($filerec->contextid,
                                  $filerec->component,
                                  $filerec->filearea,
                                  $filerec->itemid,
                                  $filerec->filepath,
                                  $filerec->filename);
            if ($file) {
                $file->delete();
            }

            debug_trace("Attempting with $filepath");
            $file = $fs->create_file_from_pathname($filerec, $filepath);
            debug_trace("File created from path $filepath");
        } else {
            $file = $fs->get_file_by_id($backupfileid);

            if (empty($file)) {
                debug_trace("backup file does not exist (by id).");
                throw new Exception("backup file does not exist (by id).");
            }
            debug_trace("File from id $backupfileid");
        }

        // Copy file to temp place.
        $tempfile = $CFG->tempdir.'/backup/'.$file->get_filename();
        $result = $file->copy_content_to($tempfile);

        // Start by extracting the file to temp dir.
        $tempdir = $CFG->tempdir.'/backup/'.$file->get_contenthash();

        // Create temp directory.
        if (!file_exists($tempdir)) {
            if (!mkdir($tempdir, 0775, true)) {
                debug_trace("Could'nt create backup temp directory $tempdir. operation failed.");
                throw new Exception("Could'nt create backup temp directory $tempdir. operation failed.");
            }
        }

        debug_trace("VMoodle : Start extraction");
        $fp = get_file_packer('application/vnd.moodle.backup');
        $unzipresult = $fp->extract_to_pathname($CFG->tempdir.'/backup/'.$file->get_filename(), $tempdir);
        debug_trace("VMoodle : Backup File extracted");

        // Check category exists.
        if (!$cat = $DB->get_record('course_categories', array('id' => $coursecategoryid))) {
            debug_trace("Error : Invalid destination category");
            throw new Exception("Invalid destination category");
        }

        // Create the base course.
        $data = new \StdClass();
        $data->fullname = "Course restore in progress...";
        if (empty($seed)) {
            $seed = rand(0, 293736);
        }
        $data->shortname= "course_shortname".$seed;
        $data->category = $coursecategoryid;

        // $course = create_course($data);
        $courseid = \restore_dbops::create_new_course($data->fullname, $data->shortname, $coursecategoryid);

        if (!$courseid) {
            print_object("Could not create course in automation process");
        }

        $rc = new \restore_controller($file->get_contenthash(),
                                     $courseid,
                                     \backup::INTERACTIVE_NO,
                                     \backup::MODE_SAMESITE,
                                     $USER->id,
                                     \backup::TARGET_NEW_COURSE);

        $rc->set_status(\backup::STATUS_AWAITING);
        $rc->execute_plan();
        $results = $rc->get_results();
        debug_trace("VMoodle : Course restored.");

         // Cleanup.
         unlink($tempfile);
         return $courseid;
    }
}
