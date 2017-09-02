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
 * This script update language packs from Moodle.org.
 *
 * @package    local_vmoodle
 * @category local
 * @subpackage cli
 * @author Valery Fremaux for VMoodle upgrades
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true;

// Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_once($CFG->dirroot.'/lib/clilib.php');

// Cli only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array('host'              => false,
          'help'              => false,
          'debug'             => false,
          ),
    array('h' => 'help',
          'H' => 'host',
          'd' => 'debug',
          )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("This option is not recognized: $unrecognized");
}

if ($options['help']) {
    $help = "
Command line Moodle Language packs update.
Please note you must execute this script with the same uid as apache!

Options:
--host                Switches to this host virtual configuration before processing
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/update_langpacks.php --host=http://my.virtual.moodle.org

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

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
echo 'Config check : playing for '.$CFG->wwwroot."\n";

if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

require_once($CFG->libdir.'/adminlib.php');

echo "Resetting lang caches\n";

get_string_manager()->reset_caches();

$controller = new tool_langimport\controller();

echo "Updating lang packs\n";

core_php_time_limit::raise();
$controller->update_all_installed_languages();
get_string_manager()->reset_caches();

echo "Done.\n";
exit(0);