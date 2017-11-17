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

// We need block Web use of theis script.
define('CLI_SCRIPT', true);

// We need block evaluation of vconfig because possible not yet created !
global $CLI_VMOODLE_PRECHECK;
$CLI_VMOODLE_PRECHECK = true;

require('../../../config.php');

$configtpl = implode('', file($CFG->dirroot.'/local/vmoodle/vconfig-tpl.php'));

if (file_exists($CFG->dirroot.'/local/vmoodle/vconfig.php')) {
    copy($CFG->dirroot.'/local/vmoodle/vconfig.php', $CFG->dirroot.'/local/vmoodle/vconfig.php.back');
}

if (!$vconfig = fopen($CFG->dirroot.'/local/vmoodle/vconfig.php', 'w')) {
    die(-1);
}

$configtpl = str_replace('<%%DBHOST%%>', $CFG->dbhost, $configtpl);
$configtpl = str_replace('<%%DBTYPE%%>', $CFG->dbtype, $configtpl);
$configtpl = str_replace('<%%DBNAME%%>', $CFG->dbname, $configtpl);
$configtpl = str_replace('<%%DBLOGIN%%>', $CFG->dbuser, $configtpl);
$configtpl = str_replace('<%%DBPASS%%>', $CFG->dbpass, $configtpl);
$configtpl = str_replace('<%%DBPREFIX%%>', $CFG->prefix, $configtpl);

fputs($vconfig, $configtpl);
fclose($vconfig);

return 0;