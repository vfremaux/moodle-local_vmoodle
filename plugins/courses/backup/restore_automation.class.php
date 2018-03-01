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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');

class restore_automation {

    /**
     * given a stored backup file, this function creates course from
     * this backup automatically . 
     * @param mixed $backup_file_id backup file id
     * @param mixed $course_category_id  destination restore category.
     */
    public static function run_automated_restore($backupfileid = null, $filepath = null, $coursecategoryid) {
        global $CFG, $DB, $USER;

        $fs = get_file_storage();

        if (!$backupfileid && !$filepath) {
            print_error("invalid backup file");
        }

        if ($filepath != null) {
            $array = split('/', $filepath);
            $file_name= array_pop($array);

            $file_rec = new stdClass();
            $file_rec->contextid = 1;
            $file_rec->component = 'backup';
            $file_rec->filearea = 'publishflow';
            $file_rec->itemid = 0;
            $file_rec->filename = $file_name;
            $file_rec->filepath = '/';

            // Try load the file.
            $file = $fs->get_file($file_rec->contextid,
                                  $file_rec->component,
                                  $file_rec->filearea,
                                  $file_rec->itemid,
                                  $file_rec->filepath,
                                  $file_rec->filename);
            if ($file) {
                $file->delete();
            }

            $file  = $fs->create_file_from_pathname($file_rec, $filepath);
        } else {
            $file = $fs->get_file_by_id($backupfileid);

            if (empty($file)) {
                print_error("backup file does not exist.");
            }
        }

        // Copy file to temp place.
        $tempfile = $CFG->tempdir.'/backup/'.$file->get_filename();
        $result = $file->copy_content_to($tempfile);

        // Start by extracting the file to temp dir.
        $tempdir = $CFG->tempdir.'/backup/'.$file->get_contenthash();

        // Create temp directory.
        if (!file_exists($tempdir)) {
            if (!mkdir($tempdir)) {
                print_error("Could'nt create backup temp directory. operation faild.");
            }
        }

        $fp = get_file_packer('application/vnd.moodle.backup');
        $unzipresult = $fp->extract_to_pathname($CFG->tempdir.'/backup/'.$file->get_filename(), $tempdir);

        // Check category exists.
        if (!$cat = $DB->get_record('course_categories',array('id' => $coursecategoryid))) {
            print_error("Invalid destination category");
        }

        // Create the base course.
        $data = new StdClass();
        $data->fullname = "Course restore in progress...";
        $data->shortname= "course_shortname".(rand(0, 293736));
        $data->category = $coursecategoryid;

        $course = create_course($data);

        $rc = new restore_controller($file->get_contenthash(),
                                     $course->id,
                                     backup::INTERACTIVE_NO,
                                     backup::MODE_GENERAL,
                                     $USER->id,
                                     backup::TARGET_NEW_COURSE);

        $rc->set_status(backup::STATUS_AWAITING);
        $rc->execute_plan();
        $results = $rc->get_results();

         // Cleanup.
         unlink($tempfile);
         return $course->id;
    }
}
