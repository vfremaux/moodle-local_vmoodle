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
 * Install and upgrade Exemple library for the local Vmoodle.
 *
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux (valery.fremaux@club-internet.fr)
 * @version Moodle 2.2
 */
function xmldb_vmoodleadminset_generic_upgrade($oldversion = 0) {
    // The function name must match with library name.
    // Initializing.
    $result = true;

    if ($oldversion < 2018021601) {
        upgrade_plugin_savepoint(true, 2018021601, 'vmoodleadminset', 'generic');
    }
    // Moodle 2.0 breakline.

    return $result;
}