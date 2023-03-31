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
 * Search and replace strings throughout all texts in the whole database.
 *
 * @package    tool_replace
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
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

require_once($CFG->dirroot.'/lib/clilib.php'); // Cli only functions.

$help =
    "Search and replace text throughout the whole database.

Options:
--search=STRING       String to search for.
--replace=STRING      String to replace with.
--shorten             Shorten result if necessary.
--host                Host to play on.
<<<<<<< HEAD
=======
--debug               Set debug on.
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
--non-interactive     Perform the replacement without confirming.
-h, --help            Print out this help.

Example:
\$ sudo -u www-data /usr/bin/php admin/tool/replace/cli/replace.php --search=//oldsitehost --replace=//newsitehost
";

list($options, $unrecognized) = cli_get_params(
    array(
        'search'  => null,
        'replace' => null,
        'shorten' => false,
        'host' => false,
<<<<<<< HEAD
=======
        'debug' => false,
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
        'non-interactive' => false,
        'help'    => false,
    ),
    array(
        'h' => 'help',
        'H' => 'host',
<<<<<<< HEAD
=======
        's' => 'search',
        'r' => 'replace',
        'S' => 'shorten',
        'I' => 'non-interactive'
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
    )
);

if ($options['help'] || $options['search'] === null || $options['replace'] === null) {
    echo $help;
    exit(0);
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
echo('Config check : playing for '.$CFG->wwwroot."\n");

require_once($CFG->libdir.'/adminlib.php');

if (!$DB->replace_all_text_supported()) {
    cli_error(get_string('notimplemented', 'tool_replace'));
}

if (empty($options['shorten']) && core_text::strlen($options['search']) < core_text::strlen($options['replace'])) {
    cli_error(get_string('cannotfit', 'tool_replace'));
}

try {
    $search = validate_param($options['search'], PARAM_RAW);
    $replace = validate_param($options['replace'], PARAM_RAW);
} catch (invalid_parameter_exception $e) {
    cli_error(get_string('invalidcharacter', 'tool_replace'));
}

<<<<<<< HEAD
if (!$options['non-interactive']) {
=======
if (empty($options['non-interactive'])) {
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
    echo get_string('excludedtables', 'tool_replace') . "\n\n";
    echo get_string('notsupported', 'tool_replace') . "\n\n";
    $prompt = get_string('cliyesnoprompt', 'admin');
    $input = cli_input($prompt, '', array(get_string('clianswerno', 'admin'), get_string('cliansweryes', 'admin')));
    if ($input == get_string('clianswerno', 'admin')) {
        exit(1);
    }
}

if (!db_replace($search, $replace)) {
    cli_heading(get_string('error'));
    exit(1);
}

cli_heading(get_string('success'));
exit(0);
