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

define('CLI_SCRIPT', true);
define('CLI_VMOODLE_OVERRIDE', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Ensure options are blanck.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'          => false,
        'domain'        => false,
        'fullstop'      => false
    ),
    array(
        'h' => 'help',
        'D' => 'domain',
        's' => 'fullstop'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error($unrecognized." is not a reconized option\n");
}

if ($options['help']) {
    $help = "
Command line for changing domain name.
Detects the new domain name in the physical master config.php.
The old domain name is catched in the key $CFG->oldwwwroot of the config file.
The wwwroot must reflect the new domain.
Storage (dataroot) is NOT affected (assuming not including domain name).
Database names (dbname) are NOT affected (assuming not including domain name).

    Options:
    -h, --help              Print out this help
    -F, --fromdomain        the origin domain. If not provided, detected from the config.php file (oldwwwroot)
    -D, --domain            the target domain. If not provided, detected from the config.php file (wwwroot).

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (empty($options['domain'])) {
    $wwwroot = $CFG->wwwroot;
    $wwwroot = preg_replace('#https?://#', '', $wwwroot;
    $parts = explode('.', $wwwroot);
    array_shift($parts);
    $domain = implode('.', $parts);
} else {
    $domain = $options['domain'];
}

if (empty($options['fromdomain'])) {
    if (!empty($CFG->oldwwwroot)) {
        $oldwwwroot = $CFG->oldwwwroot;
        $oldwwwroot = preg_replace('#https?://#', '', $oldwwwroot;
        $parts = explode('.', $oldwwwroot);
        array_shift($parts);
        $olddomain = implode('.', $parts);
    } else {
        die("Old domain could not be resolved");
    }
} else {
    $olddomain = $options['fromdomain'];
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

foreach ($allhosts as $h) {
    $h->vhostname = str_replace();
}

// Start updating.
// Linux only implementation.

echo "Starting switching domain....\n";

$i = 1;
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/switch_domain.php --domain={$domain} --host=\"{$h->vhostname}\" ";

    mtrace("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            die("Worker ended with error");
            echo implode("\n", $output);
        }
        echo "Worker ended with error";
        echo implode("\n", $output);
        echo "\n";
    } else {
        if (!empty($options['verbose'])) {
            echo implode("\n", $output);
            echo "\n";
        }
    }
}

echo "done.\n";
