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
 */

// Privacy.
$string['privacy:metadata'] = 'The local plugin vmoodeladminset SQL does not directly store any personal data about any user.';

$string['pluginname'] = 'Raw SQL commands';
$string['insuffisantcapabilities'] = 'Insuffisant capabilites';
$string['invalidfields'] = 'Invalid table fields';
$string['invalidhost'] = 'Invalid MNET host';
$string['invalidselect'] = 'Invalid constraint(s) of record selection';
$string['invalidtable'] = 'Invalid table name';
$string['mnetfailure'] = 'MNET failurew';
$string['sqlemtpycommand'] = 'The SQL instruction of the command "{$a}" is empty.';
$string['sqlconstantnotgiven'] = 'The constant "{$a->constant_name}" of the SQL instruction is not in the command "{$a->command_name}".';
$string['sqlparameternotgiven'] = 'The parameter "{$a->parameter_name}" of the SQL instruction is not in the command "{$a->command_name}".';
$string['mnetadmin_name'] = 'Provides remote SQL control';
$string['mnetadmin_description'] = 'Opens function for executing remote SQL on other moodles (administration commands)';
