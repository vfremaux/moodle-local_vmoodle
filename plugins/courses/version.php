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
 * Description of Generic plugin library.
 *
 * @package         local_vmoodle
 * @subpackage      vmoodleadminset_courses
 * @category        local
 * @author          Valery Fremaux (valery.fremaux@gmail.com)
 */

$plugin = new Stdclass;
$plugin->version = 2017120102;
$plugin->component = 'vmoodleadminset_courses';
$plugin->requires = 2016051900;
$plugin->release = '3.1.0 (build 2017120102)';
$plugin->dependencies = array('local_vmoodle' => 2017090100);
