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
 * This script creates config.php file and prepares database.
 *
 * This script is not intended for beginners!
 * Potential problems:
 * - su to apache account or sudo before execution
 * - not compatible with Windows platform
 *
 * @package    local_vmoodle
 * @category local
 * @subpackage cli
 * @revised by Valery Fremaux for VMoodle upgrades
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Force OPcache reset if used, we do not want any stale caches
// When detecting if upgrade necessary or when running upgrade.
if (function_exists('opcache_reset') && !isset($_SERVER['REMOTE_ADDR'])) {
    opcache_reset();
}

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true;

// Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

if (!isset($CFG->dirroot)) {
    die ('$CFG->dirroot must be explicitely defined in moodle config.php for this script to be used');
}

require_once($CFG->dirroot.'/lib/clilib.php');

// Cli only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array('non-interactive'   => false,
          'allow-unstable'    => false,
          'host'              => false,
          'test'              => false,
          'help'              => false),
    array('h' => 'help',
          'H' => 'host',
          'u' => 'allow-unstable',
          't' => 'test')
);

$interactive = empty($options['non-interactive']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Command line Moodle upgrade.
Please note you must execute this script with the same uid as apache!

Site defaults may be changed via local/defaults.php.

Options:
--non-interactive     No interactive questions or confirmations
-u, --allow-unstable      Upgrade even if the version is not marked as stable yet,
                      required in non-interactive mode.
-H, --host                Switches to this host virtual configuration before processing
--test                Stops after host resolution, telling the actual config that will be used
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/upgrade.php --host=http://my.virtual.moodle.org
"; // TODO: localize - to be translated later when everything is finished.

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

if (!empty($options['test'])) {
    echo "Upgrade test mode : Using configuration: \n";
    echo "wwwroot : $CFG->wwwroot\n";
    echo "dirroot : $CFG->dirroot\n";
    echo "dataroot : $CFG->dataroot\n";
    echo "dbhost : $CFG->dbhost\n";
    echo "dbname : $CFG->dbname\n";
    echo "dbuser : $CFG->dbuser\n";
    die;
}

require_once($CFG->libdir.'/adminlib.php');       // Various admin-only functions.
require_once($CFG->libdir.'/upgradelib.php');     // General upgrade/install related functions.
require_once($CFG->libdir.'/environmentlib.php');

if (empty($CFG->version)) {
    cli_error(get_string('missingconfigversion', 'debug'));
}

require("$CFG->dirroot/version.php");       // Defines version, release, branch and maturity.
$CFG->target_release = $release;            // Used during installation and upgrades.

if ($version < $CFG->version) {
    cli_error(get_string('downgradedcore', 'error'));
}

$oldversion = "$CFG->release ($CFG->version)";
$newversion = "$release ($version)";

if (!moodle_needs_upgrading()) {
    cli_error(get_string('cliupgradenoneed', 'core_admin', $newversion), 0);
}

// Test environment first.
list($envstatus, $environmentresults) = check_moodle_environment(normalize_version($release), ENV_SELECT_RELEASE);
if (!$envstatus) {
    $errors = environment_get_errors($environmentresults);
    cli_heading(get_string('environment', 'admin'));
    foreach ($errors as $error) {
        list($info, $report) = $error;
        echo "!! $info !!\n$report\n\n";
    }
    exit(1);
}

// Test plugin dependencies.
$failed = array();
if (!core_plugin_manager::instance()->all_plugins_ok($version, $failed)) {
    cli_problem(get_string('pluginscheckfailed', 'admin', array('pluginslist' => implode(', ', array_unique($failed)))));
    cli_error(get_string('pluginschecktodo', 'admin'));
}

if ($interactive) {
    $a = new stdClass();
    $a->oldversion = $oldversion;
    $a->newversion = $newversion;
    echo cli_heading(get_string('databasechecking', '', $a)) . PHP_EOL;
}

// Make sure we are upgrading to a stable release or display a warning.
if (isset($maturity)) {
    if (($maturity < MATURITY_STABLE) && !$options['allow-unstable']) {
        $maturitylevel = get_string('maturity'.$maturity, 'admin');

        if ($interactive) {
            cli_separator();
            cli_heading(get_string('notice'));
            echo get_string('maturitycorewarning', 'admin', $maturitylevel) . PHP_EOL;
            echo get_string('morehelp') . ': ' . get_docs_url('admin/versions') . PHP_EOL;
            cli_separator();
        } else {
            cli_problem(get_string('maturitycorewarning', 'admin', $maturitylevel));
            cli_error(get_string('maturityallowunstable', 'admin'));
        }
    }
}

if ($interactive) {
    echo html_to_text(get_string('upgradesure', 'admin', $newversion))."\n";
    $prompt = get_string('cliyesnoprompt', 'admin');
    $input = cli_input($prompt, '', array(get_string('clianswerno', 'admin'), get_string('cliansweryes', 'admin')));
    if ($input == get_string('clianswerno', 'admin')) {
        exit(1);
    }
}

if ($version > $CFG->version) {
    /*
     * We purge all of MUC's caches here.
     * Caches are disabled for upgrade by CACHE_DISABLE_ALL so we must set the first arg to true.
     * This ensures a real config object is loaded and the stores will be purged.
     * This is the only way we can purge custom caches such as memcache or APC.
     * Note: all other calls to caches will still used the disabled API.
     */
    cache_helper::purge_all(true);
    upgrade_core($version, true);
}
set_config('release', $release);
set_config('branch', $branch);

// Unconditionally upgrade.
upgrade_noncore(true);

// Log in as admin - we need doanything permission when applying defaults.
if ($admin = get_admin()) {
    \core\session\manager::set_user($admin);
}

// Apply all default settings, just in case do it twice to fill all defaults.
admin_apply_default_settings(null, false);
admin_apply_default_settings(null, false);

echo get_string('cliupgradefinished', 'admin')."\n";
exit(0); // 0 means success.
