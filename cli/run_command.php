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
 * This script intends to provide a general way to invoque by CLI any VMoodle Super Administration command.
 *
 * @package    local_vmoodle
 * @category local
 * @subpackage cli
 * @author Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);

// Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_once($CFG->dirroot.'/lib/clilib.php');
require_once($CFG->dirroot.'/local/vmoodle/lib.php');
require_once($CFG->dirroot.'/local/vmoodle/classes/commands/Cli_Command_Parameter.php');

use \local_vmoodle\commands\Cli_Command_Parameter;

// Cli only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array('fromhost'         => true,
          'tohosts'          => true,
          'tohostsmatch'     => true,
          'command'          => true,
          'attributes'       => true,
          'help'             => true),
    array('f' => 'fromhost',
          't' => 'tohosts',
          'm' => 'tohostsmatch',
          'c' => 'command',
          'a' => 'attributes',
          'h' => 'help')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("This option is not recognized: $unrecognized");
}

if (empty($options['help'])) {
    $help = "
Invokes a command of the Moodle Super Administration layer. Only usable on a master moodle.

Options:
-f, --fromhost        The source host
-t, --tohosts         Remote hosts to transfer to. List of comma separated root urls.
-m, --tohostsmatch    an alternative regexp pattern for finding the destination hosts.
-c, --command         the command name, as pluginname/command
-a, --attributes      the attributes as a QUERYSTRING formatted string.
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/run_command.php --fromhost=http://source.virtual.moodle.org 
        ---tohosts=http://target1.other.moodle.org,http://target2.other.moodle.org
        --command=generic/CopyFilearea --attributes=filearea=mod_h5p/0/libraries

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (empty($options['fromhost'])) {
    // Local source.
    $options['fromhost'] = 0;
}

if (empty($options['tohosts'])) {
    // At the moment, the master host cannot be target
    die("No dest hosts\n");
}

if (empty($options['command'])) {
    die("Empty command\n");
}

// Set an admin user.
$USER = get_admin();

list($plugin, $commandname) = explode('/', $options['command']);

try {
    $commandobj = vmoodle_load_command($plugin, $commandname);
} catch (Exception $e) {
    die("Command failed to load\n");
}

if (!empty($options['attributes'])) {
    $pairs = explode('&', $options['attributes']);
    foreach ($pairs as $pair) {
        list($key, $value) = explode('=', $pair);
        if (empty($key)) {
            die("Empty attribute key error\n");
        }
        $param = new Cli_Command_Parameter($key, urldecode($value));
        $commandobj->set_parameter($key, $param);
    }
}
$param = new Cli_Command_Parameter('platform', $options['fromhost']);
$commandobj->set_parameter('platform', $param);

$tohostsarr = explode(',', $options['tohosts']);
$tohostsmap = array_combine($tohostsarr, $tohostsarr); // Make an assoc array of hosts as required by commands.

mtrace("About to run command...\n");
$commandobj->run($tohostsmap);

foreach ($tohostsarr as $targethost) {
    $result = $commandobj->get_result($targethost);
    print_object($result);
}

echo "Done.\n";