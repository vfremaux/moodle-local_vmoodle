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

require_once('../../../../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

header('Content-Type: application/x-javascript');

echo 'var vmoodle_rolelib_nocapability = "'.get_string('nocapability', 'vmoodleadminset_roles').'"; ';
echo 'var vmoodle_rolelib_nosrcpltfrm = "'.get_string('nosrcpltfrm', 'vmoodleadminset_roles').'"; ';
echo 'var vmoodle_rolelib_nosyncpltfrm = "'.get_string('nosyncpltfrm', 'vmoodleadminset_roles').'"; ';
echo 'var vmoodle_rolelib_confirmrolecapabilitysync = "'.get_string('confirmrolecapabilitysync', 'vmoodleadminset_roles').'"; ';