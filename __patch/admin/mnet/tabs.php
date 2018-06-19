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
 * Tabs to be included on the pages for configuring a single host
 * $mnet_peer object must be set and bootstrapped
 * $currenttab string must be set
 *
 * @package    core
 * @subpackage mnet
 * @copyright  2007 Donal McMullan
 * @copyright  2007 Martin Langhoff
 * @copyright  2010 Penny Leach
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page
}

$strmnetservices   = get_string('mnetservices', 'mnet');
$strmnetedithost   = get_string('reviewhostdetails', 'mnet');

// PATCH+ : navigate though hosts.
// $strmnetlog        = get_string('mnetlog', 'mnet'); // Removed
$next = 0;
$prev = 0;
if (!empty($mnet_peer->id)) {
    $sql = "
        SELECT
            MIN(id)
        FROM
            {mnet_host}
        WHERE 
            id > ? AND
            name <> '' AND
            name <> 'All Hosts'
        ";
    $next = $DB->get_field_sql($sql, array($mnet_peer->id));
    if ($mnet_peer->id >= 1) {
        $sql = "
            SELECT
                MAX(id)
            FROM
                {mnet_host}
            WHERE 
                id < ? AND
                name <> '' AND
            name <> 'All Hosts'
        ";
        $prev = $DB->get_field_sql($sql, array($mnet_peer->id));
    }
}

$logurl = $CFG->wwwroot.
          '/report/log/index.php?chooselog=1&amp;showusers=1&amp;showcourses=1&amp;host_course='.$mnet_peer->id.
          '%2F1&amp;user='.'0'.
          '&amp;date=0'.
          '&amp;modid=&amp;modaction=0&amp;logformat=showashtml';
$tabs = array();
if (isset($mnet_peer->id) && $mnet_peer->id > 0) {
    if (!empty($prev)) {
        $tabs[] = new tabobject('prevmnet', 'peers.php?step=update&amp;hostid='.$prev, "&lt;", "&lt;", false);
    }
    $tabs[] = new tabobject('mnetdetails', 'peers.php?step=update&amp;hostid='.$mnet_peer->id, $strmnetedithost, $strmnetedithost, false);
    $tabs[] = new tabobject('mnetservices', 'services.php?hostid='.$mnet_peer->id, $strmnetservices, $strmnetservices, false);
/*
    if ($mnet_peer->application->name == 'moodle' && $mnet_peer->id != $CFG->mnet_all_hosts_id) {
        $tabs[] = new tabobject('mnetlog', $logurl, $strmnetlog, $strmnetlog, false);
    }
*/
    $tabs[] = new tabobject('mnetprofilefields', 'profilefields.php?hostid=' . $mnet_peer->id, get_string('profilefields', 'mnet'), get_string('profilefields', 'mnet'), false);
    if (!empty($next)) {
        $tabs[] = new tabobject('nextmnet', 'peers.php?step=update&amp;hostid='.$next, "&gt;", "&gt;", false);
    }
} else {
    $tabs[] = new tabobject('mnetdetails', '#', $strmnetedithost, $strmnetedithost, false);
}
print_tabs(array($tabs), $currenttab);
// PATCH-.
