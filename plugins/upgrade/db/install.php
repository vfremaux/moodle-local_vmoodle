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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/vmoodle/db/install.php');

/**
 * We must capture the old block_vmoodle table records and remove the old table
 *
 */
function xmldb_vmoodleadminset_upgrade_install() {
    global $DB;

    set_config('late_install', 1, 'vmoodleadminset_upgrade');
    set_config('late_install', 1, 'local_vmoodle');
    return true;
}

/**
 * this function is called when viewing the vmoodle register to 
 * fix some mispositionned rpc registration (Moodle bug in upgradelib.php)
 */
function xmldb_vmoodleadminset_upgrade_late_install() {
    global $USER, $DB;

    xmldb_local_vmoodle_late_install();

    set_config('late_install', null, 'vmoodleadminset_upgrade');
    return true;
}