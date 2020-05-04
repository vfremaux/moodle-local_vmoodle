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
 * CLI for plugins management
 *
 * @package    local_vmoodle
 * @category   local
 * @subpackage cli
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
$CLI_VMOODLE_PRECHECK = true; // force first config to be minimal

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

if (!isset($CFG->dirroot)) {
    die ('$CFG->dirroot must be explicitely defined in moodle config.php for this script to be used');
}
require_once($CFG->dirroot.'/lib/clilib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(
    array(
        'non-interactive'   => false,
        'plugin'            => false,
        'host'              => false,
        'test'              => false,
        'help'              => false
    ),
    array(
        'n' => 'non-interactive',
        'p' => 'plugin',
        'H' => 'host',
        't' => 'test',
        'h' => 'help'
    )
);

$interactive = empty($options['non-interactive']);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help =
"Command line Moodle plugin.
Site defaults may be changed via local/defaults.php.

Options:
-n, --non-interactive     No interactive questions or confirmations
-p, --plugin              Complete plugin name (ex : local_vmoodle)
-H, --host                Switches to this host virtual configuration before processing
-t, --test                Stops after host resolution, telling the actual config that will be used
-h, --help                Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/plugin.php --host=http://my.virtual.moodle.org
"; 
    echo $help;
    die;
}

if (!$options['plugin']) {
    cli_error('You must give a plugin name !');
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

echo('Config check : playing for '.$CFG->wwwroot."\n");

if (!empty($options['test'])) {
    echo("Upgrade test mode : Using configuration: \n");
    echo "wwwroot : $CFG->wwwroot\n";
    echo "dirroot : $CFG->dirroot\n";
    echo "dataroot : $CFG->dataroot\n";
    echo "dbhost : $CFG->dbhost\n";
    echo "dbname : $CFG->dbname\n";
    echo "dbuser : $CFG->dbuser\n";
    die;
}

require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/filelib.php');

// NOTE: do not use admin_externalpage_setup() here because it loads
//       full admin tree which is not possible during uninstallation.

$syscontext = context_system::instance();
$pluginman = core_plugin_manager::instance();

$pluginfo = $pluginman->get_plugin_info($options['plugin']);

// Make sure we know the plugin.
if (is_null($pluginfo)) {
    cli_error("Error : unknown plugin {$options['plugin']} into {$options['host']}");
}

$pluginname = $pluginman->plugin_name($pluginfo->component);

if (!$pluginman->can_uninstall_plugin($pluginfo->component)) {
    cli_error("Error : cannot uninstall plugin {$options['plugin']}");
}

if ($interactive) {
    mtrace("Are you sure to completely remove plugin {$options['plugin']} from {$options['host']} ?");
    $prompt = get_string('cliyesnoprompt', 'admin');
    $input = cli_input($prompt, '', array(get_string('clianswerno', 'admin'), get_string('cliansweryes', 'admin')));
    if ($input == get_string('clianswerno', 'admin')) {
        exit(1);
    }
}
$progress = new progress_trace_buffer(new text_progress_trace(), false);
$pluginman->uninstall_plugin($pluginfo->component, $progress);
$progress->finished();

if (function_exists('opcache_reset')) {
    opcache_reset();
}
mtrace("Plugin {$options['plugin']} has been correctly uninstalled from {$options['host']}");
if ($interactive) {
    mtrace("To finish removing it (and prevent auto reinstallation) its folder must be removed manualy");
    mtrace("/!\\ but you MUST check that NO VIRTUAL HOST is still using it before /!\\");
}
