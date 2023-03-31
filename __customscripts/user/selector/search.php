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
 * Code to search for users in response to an ajax call from a user selector.
 *
 * @package core_user
 * @copyright 1999 Martin Dougiamas  http://dougiamas.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Hooks the original search script adding an extra custom class for user selectors
if (file_exists($CFG->dirroot.'/customscripts/admin/roles/user_selector.class.php')) {
    include_once $CFG->dirroot.'/customscripts/admin/roles/lib.php';
    include_once $CFG->dirroot.'/customscripts/admin/roles/user_selector.class.php';
    include_once $CFG->dirroot.'/customscripts/enrol/manual/locallib.php';
    include_once $CFG->dirroot.'/customscripts/cohort/cohort_selector.class.php';
}

if (file_exists($CFG->dirroot.'/customscripts/admin/roles/classes/admins_potential_selector.php')) {
    include_once $CFG->dirroot.'/customscripts/admin/roles/classes/admins_potential_selector.php';
}
