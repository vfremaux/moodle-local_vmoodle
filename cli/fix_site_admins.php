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
 * This script allows you to reset any local user password.
 *
 * @package    local_vmoodle
 * @subpackage cli
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @copyright   Valery fremaux (http://www.mylearningfactory.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

if (!isset($CFG->dirroot)) {
    die ('$CFG->dirroot must be explicitely defined in moodle config.php for this script to be used');
}

require_once($CFG->dirroot.'/lib/clilib.php');         // Cli only functions.

// now get cli options
list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'host' => false,
        'debug' => false,
        'withmnetadmins' => false,
        'userscheme' => false,
    ),
    array(
        'h' => 'help',
        'H' => 'host',
        'd' => 'debug',
        'u' => 'userscheme',
        'm' => 'withmnetadmins',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Not recognized options ".$unrecognized);
}

if ($options['help']) {
    $help = "
Ensure some global and local admins are correctly registered in siteadmin list.

Options:
-h, --help           Print out this help
-H, --host           the virtual host you are working for
-u, --userscheme     A SQL LIKE expression (eg : nx%) to mach additional admins
-m, --withmnetadmins Also regisqter non deleted mnetadmins

Example:
\$ /usr/bin/php admin/cli/fix_site_admins.php [--withmnetadmins] --host=http://myvmoodle.moodlearray.com
"; //TODO: localize - to be translated later when everything is finished

    echo $help;
    exit(0);
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
}
echo 'Config check : playing for '.$CFG->wwwroot."\n";

if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

function add_to_siteadmins($userid) {
    $siteadminstr = get_config('siteadmins');
    $siteadmins = explode(',', $siteadminstr);
    if (!in_array($userid, $siteadmins)) {
        echo "Adding user $userid to admins\n";
        $siteadmins[] = $userid;
        // This clears config cache also.
    }
    set_config('siteadmins', rtrim(implode(',', $siteadmins), ','));
}

// Process local admin.

$params = array('auth' => 'manual',
                'username'=> 'admin',
                'mnethostid' => $CFG->mnet_localhost_id,
                'deleted' => 0);

$deletedparams = array('auth' => 'manual',
                'username'=> 'admin',
                'mnethostid' => $CFG->mnet_localhost_id,
                'deleted' => 1);

if (!$user = $DB->get_record('user', $params)) {
    if ($deletedadmin = $DB->get_record('user', $deletedparams)) {
        // Revive and old admin account while resetting its password.
        $deletedadmin->deleted = 0;
        $deletedadmin->confirmed = 1;
        $deletedadmin->suspended = 0;
        $DB->update_record('user', $deletedadmin);
        echo "Reviving deleted admin record\n";
        add_to_siteadmins($deletedadmin->id);
    }
} else {
    echo "Adding local admin to admins\n";
    add_to_siteadmins($user->id);
}

// Process mnet admin.

if (!empty($options['withmnetadmins'])) {

    if (!empty($CFG->mainhostprefix)) {

        $mainhost = $DB->get_record_select('mnet_hosts', 'wwwroot LIKE ?', [$CFG->mainhostprefix.'%']);

        if ($mainhost) {

            $params = array('auth' => 'mnet',
                            'username'=> 'admin',
                            'mnethostid' => $mainhost->id,
                            'deleted' => 0);

            if ($user = $DB->get_record('user', $params)) {
                echo "Adding mnet admin to admins\n";
                add_to_siteadmins($user->id);
            }
        }
    }
}

// Additional admins.

if (!empty($options['userscheme'])) {
    $expectedadmins = $DB->get_records_select('user', ' username LIKE ? AND deleted = 0 ', [$options['userscheme']]);
    if (count($expectedadmins) > 20) {
        die("Seems there are really many admins, probably too many... check your userscheme");
    }
    if (!empty($expectedadmins)) {
        foreach ($expectedadmins as $expectedadmin) {
            echo "Adding {$expectedadmin->username} to admins\n";
            add_to_siteadmins($expectedadmin->id);
        }
    }
}


exit(0); // 0 means success.