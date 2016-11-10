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
require('../../../config.php');
header('Content-Type: application/x-javascript');
echo 'var vmoodle_badregexp = "'.get_string('badregexp', 'local_vmoodle').'"; ';
echo 'var vmoodle_contains = "'.get_string('contains', 'local_vmoodle').'"; ';
echo 'var vmoodle_delete = "'.get_string('delete', 'local_vmoodle').'"; ';
echo 'var vmoodle_none = "'.get_string('none', 'local_vmoodle').'"; ';
echo 'var vmoodle_notcontains = "'.get_string('notcontains', 'local_vmoodle').'"; ';
echo 'var vmoodle_regexp = "'.get_string('regexp', 'local_vmoodle').'"; ';

echo 'var vmoodle_testconnection = "'.get_string('testconnection', 'local_vmoodle').'"; ';
echo 'var vmoodle_testdatapath = "'.get_string('testdatapath', 'local_vmoodle').'"; ';
echo 'var mnetactivationrequired = "'.get_string('mnetactivationrequired', 'local_vmoodle').'"; ';