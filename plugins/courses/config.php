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
 * Description of assisted commands for administrating configs.
 * 
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright Valery Fremaux (http://www.mylearningfactory.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

namespace vmoodleadminset_courses;

use \local_vmoodle\commands\Command_Category;
use \local_vmoodle\commands\Command_Parameter;
use \vmoodleadminset_courses\Command_CreateCategory;
use \vmoodleadminset_courses\Command_RestoreCourse;
use \vmoodleadminset_courses\Command_DeleteCourse;
use \vmoodleadminset_courses\Command_DeleteCourseCategory;
use \vmoodleadminset_courses\Command_EmptyCourseCategory;
use \vmoodleadminset_sql\Command_Sql;

$category = new Command_Category('courses');

$cmd = new Command_CreateCategory();
$category->add_command($cmd);

$param1 = new Command_Parameter(
    'idnumber',
    'text',
    get_string('idnumber'),
    null,
    null);

$param2 = new Command_Parameter(
    'visible',
    'boolean',
    get_string('visible'),
    1,
    null);

$name = vmoodle_get_string('setcategoryvisibility', 'vmoodleadminset_courses');
$desc = vmoodle_get_string('setcategoryvisibility_desc', 'vmoodleadminset_courses');
$sql = 'UPDATE {course_categories} SET visible = [[?visible]] WHERE idnumber = [[?idnumber]] ';
$cmd = new Command_Sql($name, $desc, $sql, array($param1, $param2));
$cmd->set_purgecaches(true);
$category->add_command($cmd);

$cmd = new Command_DeleteCourseCategory();
$category->add_command($cmd);

$cmd = new Command_EmptyCourseCategory();
$category->add_command($cmd);

$cmd = new Command_CheckCourse();
$category->add_command($cmd);

$cmd = new Command_RestoreCourse();
$category->add_command($cmd);

$name = vmoodle_get_string('setcoursevisibility', 'vmoodleadminset_courses');
$desc = vmoodle_get_string('setcoursevisibility_desc', 'vmoodleadminset_courses');
$sql = 'UPDATE {course} SET visible = [[?visible]] WHERE idnumber = [[?idnumber]] ';
$cmd = new Command_Sql($name, $desc, $sql, array($param1, $param2));
$cmd->set_purgecaches(true);
$category->add_command($cmd);

$cmd = new Command_DeleteCourse();
$category->add_command($cmd);

return $category;