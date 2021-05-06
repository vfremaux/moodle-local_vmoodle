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
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
require_once('../../../../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

header('Content-Type: application/x-javascript');

echo 'var vmoodle_pluginlib_notinstalled = "'.get_string('notinstalled', 'vmoodleadminset_plugins').'";';
echo 'var vmoodle_pluginlib_nosrcpltfrm = "'.get_string('nosrcpltfrm', 'vmoodleadminset_plugins').'";';
echo 'var vmoodle_pluginlib_nosyncpltfrm = "'.get_string('nosyncpltfrm', 'vmoodleadminset_plugins').'";';
echo 'var vmoodle_pluginlib_confirmpluginvisibilitysync = "'.get_string('confirmpluginvisibilitysync', 'vmoodleadminset_plugins').'";';