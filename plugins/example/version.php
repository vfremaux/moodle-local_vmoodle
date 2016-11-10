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
 * Description of SyncRole plugin library.
 *
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 */

defined('MOODLE_INTERNAL') || die;

$plugin = new stdclass;
$plugin->version = 2015062000;
$plugin->component = 'vmoodleadminset_example';
$plugin->requires = 2014111001;
$plugin->dependencies = array('local_vmoodle' => 2015062000);
