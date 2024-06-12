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
 * CLI script for deleting courses, single and en masse. Use at your own risk.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2015 Daniel Parker (Black River Technical College)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true;

// Location is in local/vmoodle/cli. change for using this script elsewhere.
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_once($CFG->dirroot.'/lib/clilib.php');

// CLI Options
list($options, $unrecognized) = cli_get_params(array(
    'help' => false,
    'action' => '',
    'rules' => '',
    'ignoreusers' => '',
    'hours' => '',
    'run' => '',
    'verbose' => '',
    'debugging' => '',
    'host' => '',
    ),
    array(
    'h' => 'help',
    'a' => 'action',
    'R' => 'rules',
    'i' => 'ignoreusers',
    'H' => 'hours',
    'r' => 'run',
    'v' => 'verbose',
    'D' => 'debugging',
    'h' => 'host'
    ));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("$unrecognized is NOT a valid command line option\n");
}

if ($options['help']) {
    $help =
        "
        Delete, suspend, unsuspend a brunch of users choosed by rules

        You may ask the script to stop after some hours of processing.

        Options:
        -h, --help              Print out this help
        -a, --action            'delete', 'suspend', 'unsuspend'
        -R, --rules             Rules : rules are type:fieldname:value e.g. : equals:profile_field_usertype:student
                                        several rules can be combined in an implicit AND as :
                                        equals:profile_field_usertype:student|match:idnumber:^2023.*
        -i, --ignoreusers       If given, a list of userids to be skipped, f.e. exceptions to rules or blocking. 
        -r, --run               Needs to be set to actually do the job.  Else outputs what will be done.
        -d, --hours             Number of hours the purge process is allowed to run.
        -v, --verbose           Make the script verbose, specially on eviction choices.
        -D, --debugging         Turns debug mode on.
        -H, --host              Only in use with VMoodle virtualization if available. 

        Example:
        \$php moodle_folder/local/vmoodle/cli/delete_users.php --host=<moodlewwwroot> --rules=<rules> --action=suspend
        ";

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
}
echo 'Config check : playing for '.$CFG->wwwroot."\n";

if (!empty($options['debugging'])) {
    $CFG->debug = E_ALL;
}

// Mandatory cheks
if (empty($options['rules'])) {
    die("No rules given. We cannot process all users, would we ?\n");
}

if (empty($options['action'])) {
    die("No action given. Should be delete, suspend or unsuspend.\n");
}

if (!in_array($options['action'], ['delete', 'suspend', 'unsuspend'])) {
    die("Invalid action. Should be delete, suspend or unsuspend.\n");
}

$ignoredusers = [];
if (!empty($optiosn['ignoredusers'])) {
    $ignoredusers = explode(',', $optiosn['ignoredusers']);
}

$run = false;
$run = !empty($options['run']);

if (!empty($options['hours'])) {
    $now = new DateTime();
    $interval = new DateInterval($options['hours'].' hours');
    $stopdate = $now->add($interval);
}

// Getting all (undeleted) users (protecting main admin and guest)

$users = $DB->get_records_select('user', ' username <> "admin" AND username <> "guest" AND deleted = 0 ');

// Retrieve shortly user extra attributes.
$sql = "
    SELECT
        u.id as userid,
        uif.shortname as field,
        uid.data as data
    FROM
        {user} u,
        {user_info_data} uid,
        {user_info_field} uif
    WHERE
        u.username <> 'admin' AND u.username <> 'guest' AND
        u.deleted = 0 AND
        u.id = uid.userid AND
        uid.fieldid = uif.id
";

// Aggregate extra data to users

$userdatars = $DB->get_recordset_sql($sql);

if ($userdatars->valid()) {
    foreach ($userdatars as $data) {
        $fieldname = 'profile_field_'.$data->field;
        $users[$data->userid]->$fieldname = $data->data;
    }
}

$userdatars->close();

// Apply rules.

apply_rules($users, $options['rules'], $options);

// process users.
$u = 0;
$total = 0;
$pc = 0;

if (!empty($users)) {
    echo(($run) ? "Run in Run mode \n" : "Run in DryRun mode \n");
    echo('Found matching users : '.count($users)."\n");
    if (!empty($options['verbose'])) {
        print_object(array_keys($users));
    }
    die("Secure debug");
    $total = count($users);
    $ucount = 0;
    foreach ($users as $u) {
        if (in_array($u->id, $ignoredusers)) {
            echo "Skipping $u->id as in ignored\n";
            continue;
        }

        switch ($options['action']) {
            case 'delete': {
                if ($run) {
                    delete_user($u);
                    echo "Deleted :{$u->id}: ({$u->username}) ({$u->idnumber}) {$u->firstname} {$u->lastname} \n";
                } else {
                    echo "Dryrun : To delete :{$u->id}: ({$u->username}) ({$u->idnumber}) {$u->firstname} {$u->lastname} \n";
                }
                break;
            }

            case 'suspend': {
                if ($run) {
                    $DB->set_field('user', 'suspended', 1, ['id' => $u->id]);
                    echo "Processed :{$u->id}: ({$u->username}) ({$u->idnumber}) {$u->firstname} {$u->lastname} : suspended\n";
                } else {
                    echo "Dryrun : To suspend :{$u->id}: ({$u->username}) ({$u->idnumber}) {$u->firstname} {$u->lastname} \n";
                }
                break;
            }

            case 'unsuspend': {
                if ($run) {
                    $DB->set_field('user', 'suspended', 0, ['id' => $u->id]);
                    echo "Processed :{$u->id}: ({$u->username}) ({$u->idnumber}) {$u->firstname} {$u->lastname} : revived\n";
                } else {
                    echo "Dryrun : To revive :{$u->id}: ({$u->username}) ({$u->idnumber}) {$u->firstname} {$u->lastname} \n";
                }
                break;
            }
        }

        $ucount++;
        $pc = sprintf('%0.2f', $ucount / $total * 100);

        // Stop control point.
        if (!empty($options['hours'])) {
            $now = new DateTime();
            if ($now > $stopdate) {
                exit("Processing stopped after required {$options['hours']} of run. Processed $ucount / $total ($pc %) users.");
            }
        }
    }
} else {
    echo "No users to process.\n";
}

echo "Done. Processed $u /$total ($pc %) users .\n";
exit(0);

/**
 * applies rules. Note that rules CANNOT be empty.
 * Rules will discard users NOT TO DELETE.
 */
function apply_rules(&$users, $rules, $options) {

    $allrules = explode('|', $rules);

    foreach ($allrules as $rule) {
        apply_rule($users, $rule, $options);
    }
}

/**
 * A single rule is : type:fieldname:value
 */
function apply_rule(&$users, $rule, $options) {
    global $DB;

    if (preg_match('/^([\\w]+)\\:([\\w]+)\\:(.*)?/', $rule, $matches)) {
        $type = $matches[1];
        $fieldname = $matches[2];
        $value = $matches[3];
    } else {
        echo "Malformed rule \"$rule\"\n";
        return;
    }

    foreach ($users as $uid => $u) {
        switch ($type) {
            case "equals" : {
                if (property_exists($u, $fieldname)) {
                    if ($u->$fieldname !=  $value) {
                        if (!empty($options['verbose'])) {
                            echo("-- Ignoring ({$u->id}) [{$u->username}] {$u->firstname} {$u->lastname} not equals\n");
                        }
                        unset($users[$uid]);
                    }
                } else {
                    // A specific field name has been required but is not present.
                    if (!empty($options['verbose'])) {
                        echo("-- Ignoring ({$u->id}) [{$u->username}] {$u->firstname} {$u->lastname} not equals (field $fieldname not present)\n");
                    }
                    unset($users[$uid]);
                }
                break;
            }

            case "matchs" : {
                if (property_exists($u, $fieldname)) {
                    if (!preg_match('/'.$value.'/', $u->$fieldname)) {
                        if (!empty($options['verbose'])) {
                            echo("-- Ignoring ({$u->id}) [{$u->username}] {$u->firstname} {$u->lastname} not matchs $value \n");
                        }
                        unset($users[$uid]);
                    }
                } else {
                    // A specific field name has been required but is not present.
                    if (!empty($options['verbose'])) {
                        echo("-- Ignoring ({$u->id}) [{$u->username}] {$u->firstname} {$u->lastname} not match (field not present)\n");
                    }
                    unset($users[$uid]);
                }
                break;
            }

            case "isset" : {
            	// Need "isset" here to catch NULL values.
                if (!isset($u, $fieldname)) {
                    if (!empty($options['verbose'])) {
                        echo("-- Ignoring ({$u->id}) [{$u->username}] {$u->firstname} {$u->lastname} $fieldname is not set but should for deletion\n");
                    }
                    unset($users[$uid]);
                }
                break;
            }

            case "notisset" : {
            	// Need "isset" here to catch NULL values.
                if (isset($u, $fieldname)) {
                    if (!empty($options['verbose'])) {
                        echo("-- Ignoring ({$u->id}) [{$u->username}] {$u->firstname} {$u->lastname} $fieldname is set and should not for deletion \n");
                    }
                    unset($users[$uid]);
                }
                break;
            }

            case "hasrole" : {
                if ($fieldname == 'site') {
                    $context = context_system::instance();
                    if ($value == '*') {
                        // Cach all.
                        if (!$DB->record_exists('role_assignments', ['userid' => $u->id, 'contextid' => $context->id])) {
                            if (!empty($options['verbose'])) {
                                echo("-- Ignoring ({$u->id}) [{$u->username}] {$u->firstname} {$u->lastname} has not role (any) in context but required\n");
                            }
                            unset($users[$u->id]);
                        }
                    } else {
                        $role = $DB->get_record('role', ['shortname' => $value]);
                        if (!$role) {
                            die ("Unknown role name $value in hasrole rule... Aborting\n");
                        }
                        if (!$DB->record_exists('role_assignments', ['userid' => $u->id, 'contextid' => $context->id, 'roleid' => $role->id])) {
                            if (!empty($options['verbose'])) {
                                echo("-- Ignoring ({$u->id}) [{$u->username}] {$u->firstname} {$u->lastname} has not role $value in context but required\n");
                            }
                            unset($users[$u->id]);
                        }
                    }
                } else {
                    die("Unupported value for \"hasrole\" rule at the moment (only \"site\" level supported)\n");
                }
                break;
            }

            case "nothasrole" : {
                if ($fieldname == 'site') {
                    $context = context_system::instance();
                    if ($value == '*') {
                        // Cach all.
                        if ($DB->record_exists('role_assignments', ['userid' => $u->id, 'contextid' => $context->id])) {
                            if (!empty($options['verbose'])) {
                                echo("-- Ignoring ({$u->id}) [{$u->username}] {$u->firstname} {$u->lastname} has not role (any) in context but required\n");
                            }
                            unset($users[$u->id]);
                        }
                    } else {
                        $role = $DB->get_record('role', ['shortname' => $value]);
                        if (!$role) {
                            die ("Unknown role name $value in hasrole rule... Aborting\n");
                        }
                        if ($DB->record_exists('role_assignments', ['userid' => $u->id, 'contextid' => $context->id, 'roleid' => $role->id])) {
                            if (!empty($options['verbose'])) {
                                echo("-- Ignoring ({$u->id}) [{$u->username}] {$u->firstname} {$u->lastname} has not role $value in context but required\n");
                            }
                            unset($users[$u->id]);
                        }
                    }
                } else {
                    die("Unupported value for \"nothasrole\" rule at the moment (only \"site\" level supported)\n");
                }
                break;
            }

            // Implement local rules if needed.
            // This is a "negative rule" : means if rules match, will delete the user, unsetting from $users if not.
            //
            // case 'skema1': {
            //     whatever reason to confirm users from deletion. 
            //     break;
            // }

            default: {
                die("Invalid rule type $type... Aborting\n");
            }
        }
    }
}
