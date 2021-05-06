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
 * Tests database connection.
 *
 * @package local_vmoodle
 * @category local
 * @author Moheissen Fabien (fabien.moheissen@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
require('../../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/bootlib.php');

// Retrieve parameters for database connection test.
$vmaster = new StdClass();
$vmaster->vdbtype = $CFG->vmasterdbtype;
$vmaster->vdbhost = $CFG->vmasterdbhost;
$vmaster->vdblogin = $CFG->vmasterdblogin;
$vmaster->vdbpass = $CFG->vmasterdbpass;
$vmaster->vdbname = $CFG->vmasterdbname;

// Works, but need to improve the style...
if (vmoodle_make_connection($vmaster, false)) {
    echo(get_string('connectionok', 'local_vmoodle'));
} else {
    echo(get_string('badconnection', 'local_vmoodle'));
}
