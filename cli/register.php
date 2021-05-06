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
 * A cli tool to register a moodle to moodle.org.
 *
 * @package    local_vmoodle
 * @subpackage cli
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/lib/clilib.php'); // Cli only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array('help' => false,
          'protected' => false,
          'host' => false,
          'debug' => false,
    ),
    array('h'=>'help',
          'p'=>'protected',
          'H' => 'host',
          'd' => 'debug'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    echo "Invalid option $unrecognized\n";
    exit(1);
}

$help = "
Moodle registration worker.

This CLI registrates the moodle instance to moodle.org worldwide register.

Options:
    -p, --protected       If set, will only publish te name of the moodle and will give no link to access it.
    -H,   Host            A VMoodle hostname to register.
    -h, --help            Print out this help.
    -d, --debug           Turns on debug mode.

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/register.php [--private] --host=http://mysubmoodle.mymoodle.com
";

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
}
echo('Config check : playing for '.$CFG->wwwroot."\n");

if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

// Display statistic that are going to be retrieve by the hub.
$coursecount = $DB->count_records('course') - 1;
$usercount = $DB->count_records('user', array('deleted' => 0));
$roleassigncount = $DB->count_records('role_assignments');
$postcount = $DB->count_records('forum_posts');
$questioncount = $DB->count_records('question');
$resourcecount = $DB->count_records('resource');
require_once($CFG->dirroot . "/course/lib.php");
$participantnumberaverage = number_format(average_number_of_participants(), 2);
$modulenumberaverage = number_format(average_number_of_courses_modules(), 2);
require_once($CFG->libdir . '/badgeslib.php');
$badges = $DB->count_records_select('badge', 'status <> ' . BADGE_STATUS_ARCHIVED);
$issuedbadges = $DB->count_records('badge_issued');

$register['name'] = format_string($site->fullname, true, array('context' => context_course::instance(SITEID)));
$register['description'] = $site->summary;
$register['contactname'] = fullname($admin, true);
$register['contactemail'] = $admin->email;
$register['contactphone'] = $admin->phone1;

$register['imageurl'] = '';
$register['privacy'] = get_config('hub', 'site_privacy_moodle');
$register['address'] = get_config('hub', 'site_address_moodle');
$register['region'] = get_config('hub', 'site_region_moodle');
$register['country'] = $admin->country;
$register['language'] = current_language();
$register['geolocation'] = get_config('hub', 'site_geolocation_moodle');
$register['contactable'] = get_config('hub', 'site_contactable_moodle');
$register['emailalert'] = get_config('hub', 'site_emailalert_moodle');

$register['siteurl'] = $CFG->wwwroot;
$register['moodleversion'] = $CFG->version;
$register['moodlerelease'] = $CFG->release;
$register['coursesnumber'] = $coursecount;
$register['usersnumber'] = $usercount;
$register['roleassignmentsnumber'] = $roleassigncount;
$register['questionsnumber'] = $questioncount;
$register['resourcesnumber'] = $resourcecount;
$register['badgesnumber'] = $badges;
$register['issuedbadgesnumber'] = $issuedbadges;
$register['participantnumberaveragecfg'] = $participantnumberaverage;
$register['modulenumberaveragecfg'] = $modulenumberaverage;

