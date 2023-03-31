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
 * Build and store theme CSS.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2017 Ryan Wyllie <ryan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true;

// Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_once("{$CFG->dirroot}/lib/clilib.php");

$longparams = [
    'themes'    => null,
    'direction' => null,
    'help'      => false,
    'host'      => false,
    'debug'      => false,
    'verbose'   => false
];

$shortmappings = [
    't' => 'themes',
    'd' => 'direction',
    'h' => 'help',
    'H' => 'host',
    'd' => 'debug',
    'v' => 'verbose'
];

list($options, $unrecognized) = cli_get_params($longparams, $shortmappings);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo
"Compile the CSS for one or more installed themes.
Existing CSS caches will replaced.
By default all themes will be recompiled unless otherwise specified.

Options:
-t, --themes    A comma separated list of themes to be compiled
-d, --direction Only compile a single direction (either ltr or rtl)
-v, --verbose   Print info comments to stdout
-H, --host      Virtual host to proceed
-h, --help      Print out this help

Example:
\$ sudo -u www-data /usr/bin/php local/vmoodle/cli/build_theme_css.php --host=https://mymoodle.mydomain.org --themes=boost --direction=ltr
";
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

if (!empty($options['debug'])) {
    $CFG->debug = E_ALL;
}

require_once("$CFG->libdir/csslib.php");
require_once("$CFG->libdir/outputlib.php");
require_once("{$CFG->dirroot}/local/vmoodle/cli/clilib.php");

// Get CLI params.

global $trace;
global $vmoodletrace;
if (empty($options['verbose'])) {
    $trace = new null_progress_trace();
} else {
    $trace = new text_progress_trace();
}
$vmoodletrace = $trace;

cli_heading('Build theme css');

// Determine which themes we need to build.
$themenames = [];
if (is_null($options['themes'])) {
    $trace->output('No themes specified. Finding all installed themes.');
    $themenames = array_keys(core_component::get_plugin_list('theme'));
} else {
    if (is_string($options['themes'])) {
        $themenames = explode(',', $options['themes']);
    } else {
        cli_error('--themes must be a comma separated list of theme names');
    }
}

$trace->output('Checking that each theme is correctly installed...');
$themeconfigs = [];
foreach ($themenames as $themename) {
    if (is_null(theme_get_config_file_path($themename))) {
        cli_error("Unable to find theme config for {$themename}");
    }

    // Load the config for the theme.
    $themeconfigs[] = theme_config::load($themename);
}

$directions = ['ltr', 'rtl'];

if (!is_null($options['direction'])) {
    if (!in_array($options['direction'], $directions)) {
         cli_error("--direction must be either ltr or rtl");
    }

    $directions = [$options['direction']];
}

$trace->output('Building CSS for themes: ' . implode(', ', $themenames));
theme_build_css_for_themes($themeconfigs, $directions);

exit(0);
