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
 */

// Privacy.
$string['privacy:metadata'] = 'The local plugin vmoodeladminset Courses does not directly store any personal data about any user.';

$string['cmdcreatecategory'] = 'Course Category Creation';
$string['cmdcreatecategory_desc'] = 'Create a course category given a path';
$string['cmdrestorecourse'] = 'Course restore';
$string['cmdrestorecourse_desc'] = 'Restores a course from an absolute file location';
$string['cmddeletecourse'] = 'Course deletion';
$string['cmddeletecourse_desc'] = 'Deletes a course on base of its local shortname or idnumber';
$string['mnetadmin_description'] = 'When published, you allow the local platform to be remotely administrated by the peer moodle.<br/><br/>When subscribing, you will access to remote administration of the peer moodle.<br/><br/>';
$string['mnetadmin_name'] = 'Meta-administration service';
$string['pluginname'] = 'Course  related features';
$string['errornolocation'] = 'The given location has no file or is not readable.';
$string['errornotamoodlearchive'] = 'The given location is not a moodle archive.';
$string['errornocategory'] = 'The target category does not exist (by idnumber).';
$string['errorcoursealreadyexists'] = 'A course with same shortname exists already.';
$string['errorcourseidnumberexists'] = 'A course with same idnumber exists. Resuming.';
$string['errornopermission'] = 'You have no permissions with your remote user to restore.';
$string['errorduringrestore'] = 'An error raised while restoring. Exception : {$a}';
$string['errorafterrestore'] = 'Restore terminated without failure but no course was created.';
$string['path'] = 'Name path of the category';
$string['catidnumber'] = 'Category ID number';
$string['catvisible'] = 'Category initially visible';
$string['coursevisible'] = 'Course initially visible';
$string['setcategoryvisibility'] = 'Change course category visibility';
$string['setcategoryvisibility_desc'] = 'Given a category idnumber, set or removes remote visibility on a course category.';
$string['setcoursevisibility'] = 'Change course visibility';
$string['setcoursevisibility_desc'] = 'Given a valid idnumber, set or removes remote visibility on a course.';
$string['location'] = 'Moodle backup archive location on remote server';

