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
 * CLI command to migrate mod_hvp to mod_h5pactivity.
 *
 * @package    tool_migratehvp2h5p
 * @copyright  2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use tool_migratehvp2h5p\api;

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

if (!is_dir($CFG->dirroot.'/admin/tool/migratehvp2h5p')) {
    die ("Migrating tool for HVP is Not installed. ...Aborting.\n");
}

require_once("{$CFG->dirroot}/lib/clilib.php");

list($options, $unrecognized) = cli_get_params(
    [
        'execute' => false,
        'help' => false,
        'limit' => 100,
        'keeporiginal' => 1,
        'copy2cb' => false,
        'contenttypes' => [],
        'host' => false,
    ], [
        'e' => 'execute',
        'h' => 'help',
        'l' => 'limit',
        'k' => 'keeporiginal',
        'c' => 'copy2cb',
        't' => 'contenttypes',
        'H' => 'host',
    ]
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error($unrecognized.' is not a valid command line option'."\n");
}

if ($options['help']) {
    $help = <<<EOT
Migration command from mod_hvp to mod_h5pactivity.

Options:
 -h, --help                Print out this help
 -H, --host                If given as wwwroot, wich config to this virtual moodle host.
 -e, --execute             Run the migration tool
 -k, --keeporiginal=N      After migration 0 will remove the original activity, 1 will keep it and 2 will hide it
 -c, --copy2cb=N           Whether H5P files should be added to the content bank with a link (1), as a copy (2) or not added (0)
 -t, --contenttypes=N      The library ids, separated by commas, for the mod_hvp contents to migrate.
                           Only contents having these libraries defined as main library will be migrated.
 -l  --limit=N             The maximmum number of activities per execution (default 100).
                           Already migrated activities will be ignored.

Example:
\$sudo -u www-data /usr/bin/php admin/tool/migratehvp2h5p/cli/migrate.php --execute

EOT;

    echo $help;
    die;
}

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
echo 'Config check : playing for '.$CFG->wwwroot."\n";

require_once("{$CFG->dirroot}/admin/tool/migratehvp2h5p/classes/api.php");

if (moodle_needs_upgrading()) {
    echo "Moodle upgrade pending, operation suspended. Please upgrade before proceedeing.\n";
    exit(1);
}

if (empty($options['keeporiginal'])) {
    $options['keeporiginal'] = 1;
}

if (empty($options['copy2cb'])) {
    $options['copy2cb'] = api::COPY2CBYESWITHLINK;
}

if (!empty($options['contenttypes'])) {
    $ctparam = explode(',', $options['contenttypes']);
} else {
    $ctparam = [];
}

$keeporiginal = $options['keeporiginal'];
$copy2cb = $options['copy2cb'];
$limit = $options['limit'] ?? 100;
$execute = (empty($options['execute'])) ? false : true;

if (!is_numeric($limit)) {
    echo "Limit must be an integer.\n";
    exit(1);
}

if (!is_numeric($keeporiginal)) {
    echo "keeporiginal must be an integer.\n";
    exit(1);
}

if (!is_numeric($copy2cb)) {
    echo "copy2cb must be an integer.\n";
    exit(1);
}

$contenttypes = [];
if (!empty($ctparam)) {
    foreach ($ctparam as $contenttype) {
        if (!is_numeric($contenttype)) {
            echo "contenttypes must be a list of library ids separated by commas.\n";
            exit(1);
        } else {
            $contenttypes[] = intval($contenttype);
        }
    }
}

core_php_time_limit::raise();

// Increase memory limit.
raise_memory_limit(MEMORY_EXTRA);

// Emulate normal session - we use admin account by default.
cron_setup_user();

$humantimenow = date('r', time());

mtrace("Server Time: {$humantimenow}\n");

mtrace("Search for $limit non migrated hvp activites\n");

list($sql, $params) = api::get_sql_hvp_to_migrate(false, null, $contenttypes);
$activities = $DB->get_records_sql($sql, $params, 0, $limit);

if (empty($activities)) {
    mtrace(" * No activites are found.\n");
    exit(1);
}

foreach ($activities as $hvpid => $info) {
    mtrace("Migrating ID:$hvpid\t{$info->name}\t course:{$info->courseid}\t{$info->course}");
    if (empty($execute)) {
        mtrace("\t ...Skipping\n");
        continue;
    }
    try {
        $messages = tool_migratehvp2h5p\api::migrate_hvp2h5p($hvpid, $keeporiginal, $copy2cb);
        if (empty($messages)) {
            mtrace("\t ...Successful\n");
        } else {
            foreach ($messages as $message) {
                mtrace("\t ...$message[0]\n");
            }
        }
    } catch (moodle_exception $e) {
        mtrace("\tException: ".$e->getMessage()."\n");
        mtrace("\t ...Failed!\n");
    }
}
