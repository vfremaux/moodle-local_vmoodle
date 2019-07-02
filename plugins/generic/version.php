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
 * @package     local_vmoodle
 * @category    local
 * @author      Valery Fremaux (valery.fremaux@gmail.com)
 */

$plugin = new StdClass();
$plugin->version = 2018021601;
$plugin->requires = 2016051900;
$plugin->component = 'vmoodleadminset_generic';
$plugin->release = '3.5.0 (build 2018021601)';
$plugin->dependencies = array('local_vmoodle' => 2017090100);
