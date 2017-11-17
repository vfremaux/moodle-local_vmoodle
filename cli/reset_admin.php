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
 * @package    block_vmoodle
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
        'password' => false,
        'firstname' => false,
        'lastname' => false,
        'email' => false,
    ),
    array(
        'h' => 'help',
        'H' => 'host',
        'd' => 'debug',
        'p' => 'password',
        'f' => 'firstname',
        'l' => 'lastname',
        'm' => 'email',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Not recognized options ".$unrecognized);
}

if ($options['help']) {
    $help = "
Reset local admin user, creating it if it w as deleted or renamed.

There are no security checks here because anybody who is able to
execute this file may execute any PHP too.

Options:
-h, --help          Print out this help
-H, --host          the virtual host you are working for
-p, --password      the admin password to set.
-f, --firstname         the firstname (mandatory in case it needs to be created).
-l, --lastname          the lastname (mandatory in case it needs to be created).
-m, --email             the email (mandatory in case it needs to be created).

Example:
\$ /usr/bin/php admin/cli/reset_admin.php -pXXXXXXX -H http://myvmoodle.moodlearray.com
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

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
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

if (empty($options['password'])) {
    echo "Empty passwords are not allowed for admins\n";
    exit(1);
}

$hashedpassword = hash_internal_user_password($options['password']);

$errmsg = ''; // prevent eclipse warning.
if (!check_password_policy($options['password'], $errmsg)) {
    echo $errmsg."\n";
    exit(1);
}

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
        $deletedadmin->password = $hashedpassword;
        $DB->update_record('user', $deletedadmin);
        echo "Reviving deleted admin record\n";
        add_to_siteadmins($deletedadmin->id);
    } else {
        // Create new admin account from scratch.
        $newadmin = new StdClass();
        $newadmin->password = $hashedpassword;
        $newadmin->username = 'admin';
        $newadmin->mnethostid = $CFG->mnet_localhost_id;
        $newadmin->deleted = 0;
        $newadmin->confirmed = 1;
        $newadmin->suspended = 0;
        $newadmin->firstname = 'Local';
        if (!empty($options['firstname'])) {
            $newadmin->firstname = $options['firstname'];
        }

        $newadmin->lastname = 'Administrator';
        if (!empty($options['lastname'])) {
            $newadmin->firstname = $options['lastname'];
        }

        $newadmin->email = $options['email'];
        if (empty($options['email'])) {
            echo "New admin record needs email\n";
            return 1;
        }
        $newadmin->lang = $CFG->lang;
        $newadmin->city = $CFG->city;
        if (is_null($newadmin->city)) {
            $newadmin->city = '';
        }
        $newadmin->country = $CFG->country;

        echo "Creating admin record\n";
        $adminid = $DB->insert_record('user', $newadmin);

        add_to_siteadmins($adminid);
    }
} else {
    // Just pin password and ensure it is a site admin.
    $user->password = $hashedpassword;

    if (!empty($options['firstname'])) {
        $user->firstname = $options['firstname'];
    }
    if (!empty($options['lastname'])) {
        $user->lastname = $options['lastname'];
    }
    if (!empty($options['email'])) {
        $user->email = $options['email'];
    }

    $user->deleted = 0;
    $user->confirmed = 1;
    $user->suspended = 0;

    echo "Updating admin infos\n";
    add_to_siteadmins($user->id);
    $DB->update_record('user', $user);
}

exit(0); // 0 means success.