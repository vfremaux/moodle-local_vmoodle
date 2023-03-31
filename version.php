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
 * Version details.
 *
 * @package     local_vmoodle
 * @category    local
 * @author      Valery Fremaux <valery.fremaux@gmail.com>
 * @copyright   2008 onwards Valery Fremaux (valery.fremaux@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

<<<<<<< HEAD
$plugin->version = 2019012700; // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2018112800; // Requires this Moodle version.
$plugin->component = 'local_vmoodle'; // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_RC;
$plugin->release = '3.6.0 (Build 2019012700)';

// Non moodle attributes.
$plugin->codeincrement = '3.6.0003';
=======
$plugin->version = 2020110901; // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires  = 2019051100; // Requires this Moodle version.
$plugin->component = 'local_vmoodle'; // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_RC;
$plugin->release = '3.7.0 (Build 2020110901)';

// Non moodle attributes.
$plugin->codeincrement = '3.7.0006';
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
$plugin->privacy = 'dualrelease';
$plugin->devprotectedfiles = array('vconfig.php');