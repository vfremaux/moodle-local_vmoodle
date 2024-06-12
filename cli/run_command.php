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
          'tohosts'          => false,
          'tohostsmatch'     => false,
          'exclude'          => false,
          'command'          => true,
          'attributes'       => true,
          'test'             => false,
          'help'             => true),
    array('f' => 'fromhost',
          't' => 'tohosts',
          'm' => 'tohostsmatch',
          'e' => 'exclude',
          'c' => 'command',
          'a' => 'attributes',
          'T' => 'test',
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
-e, --exclude         a regexp pattern to exclude specifically some hosts in the candidate set.
-c, --command         the command name, as pluginname/command
-a, --attributes      the attributes as a QUERYSTRING formatted string.
-T, --test            Test attributes decoding.
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
    if (empty($options['tohostsmatch'])) {
        die("No dest hosts\n");
    }
}

if (empty($options['command'])) {
    die("Empty command\n");
}

// Set an admin user.
$USER = get_admin();

if ($options['command'] != 'showtargets') {
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
            mtrace("Registering attribute $key as $value\n");
            $param = new Cli_Command_Parameter($key, urldecode($value));
            $commandobj->set_parameter($key, $param);
        }
    }
    $param = new Cli_Command_Parameter('platform', $options['fromhost']);
    $commandobj->set_parameter('platform', $param);
}

$tohostsmap = array();
if (!empty($options['tohosts'])) {
    // Hostlist wins.
    if ($options['tohosts'] != '*') {
        mtrace("Using explicit HostList...\n");
        $tohostsarr = explode(',', $options['tohosts']);
        foreach ($tohostsarr as $vhostname) {
            if ($vhostname !== $options['fromhost']) {
                // Avoid on ourself.
                $tohostsmap[$vhostname] = $vhostname; // Make an assoc array of hosts as required by commands.
            }
        }
    } else {
        mtrace("Using all Hosts...\n");
        $vhosts = $DB->get_records('local_vmoodle', array());
        if (!empty($vhosts)) {
            foreach ($vhosts as $vhost) {
                if ($vhost != $options['fromhost']) {
                    // Avoid on ourself.
                    if (preg_match('/'.$options['tohostsmatch'].'/', $vhost->vhostname)) {
                        $tohostsmap[$vhost->vhostname] = $vhost->name;
                    }
                }
            }
        }
    }
} else if (!empty($options['tohostsmatch'])) {
    mtrace("Using Regexp Pattern {$options['tohostsmatch']}...\n");
    $vhosts = $DB->get_records('local_vmoodle', array());

    // Shift to Regexp PCRE
    $options['tohostsmatch'] = str_replace('*', '.*', $options['tohostsmatch']);
    $options['tohostsmatch'] = str_replace('?', '.', $options['tohostsmatch']);

    foreach ($vhosts as $vhost) {
        if ($vhost->vhostname != $options['fromhost']) {
            // Avoid on ourself.
            if (preg_match('/'.$options['tohostsmatch'].'/', $vhost->vhostname)) {
                $tohostsmap[$vhost->vhostname] = $vhost->name;
            }
        }
    }
}

// Post processes tohostsmap for exclusions.
if (!empty($options['exclude'])) {
    $excludepatterns = explode(',', $options['exclude']);
    foreach ($excludepatterns as $expattern) {
        $expattern = str_replace('*', '.*', $expattern);
        $expattern = str_replace('?', '.', $expattern);
        foreach (array_keys($tohostsmap) as $vhostname) {
            if (preg_match('/'.$expattern.'/', $vhostname)) {
                // Exclude some matching hosts.
                unset($tohostsmap[$vhostname]);
            }
        }
    }
}

if ($options['command'] == 'showtargets') {
    echo "VMoodle command targets:\n";
    print_r($tohostsmap);
    echo "Done.\n";
    die;
}

mtrace("About to run command on...\n");
throw new moodle_exception($tohostsmap);

if (!empty($options['test'])) {
    mtrace("Test mode. Not executing.\n");
    die;
}

$commandobj->run($tohostsmap);

foreach (array_keys($tohostsmap) as $targethost) {
    $result = $commandobj->get_result($targethost);
    throw new moodle_exception($result);
}

echo "Done.\n";