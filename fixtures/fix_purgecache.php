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
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * this is an emergency physical cache fix (cache purge)
 * in case of severe cacheing inconsistancies
 */

require('../../../config.php');

require_login();
$systemcontext = context_system::instance();
require_capability('moodle/site:config', $systemcontext);

// This is a special fixture in cas we loose mnet auth settings.

$vmoodles = $DB->get_records_select('local_vmoodle', " mnet > 0 ");

echo '<pre>';
mtrace("Removing all cache data");

foreach ($vmoodles as $vm) {
    // Remove caches.
    mtrace("Removing physical cache data for $vm->vhostname");

    $cmd = "rm -rf {$vm->vdatapath}/cache/*";
    exec($cmd);

    $cmd = "rm -rf {$vm->vdatapath}/muc/*";
    exec($cmd);
}
echo '</pre>';
