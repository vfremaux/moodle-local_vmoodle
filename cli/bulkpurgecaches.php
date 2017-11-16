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

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Ensure options are blanck.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'verbose'          => false,
        'fullstop'         => false,
        'debug'            => false,
    ),
    array(
        'h' => 'help',
        'v' => 'verbose',
        'f' => 'fullstop',
        'd' => 'debug',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Command line Global Cache clearance

    Options:
    -h, --help              Print out this help
    -v, --verbose           Print out workers output.
    -f, --fullstop          Stops on first error.
    -d, --debug             Turns on debug mode.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Start updating.
// Linux only implementation.

echo "Starting bulk cache purging....\n";

$debug = '';
if (!empty($options['debug'])) {
    $debug = ' --debug ';
}

$i = 1;
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/purge_caches.php --host=\"{$h->vhostname}\" {$debug} ";

    mtrace("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);

    if ($return) {
        if (empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            die("Worker ended with error\n");
        }
        echo "Worker ended with error:\n";
        echo implode("\n", $output)."\n";
        echo "Pursuing anyway\n";
    } else {
        if (!empty($options['verbose'])) {
            echo implode("\n", $output)."\n";
        }
    }
}

echo "All done.\n";
