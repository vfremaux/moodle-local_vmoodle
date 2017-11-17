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
        'allow-unstable'   => false,
        'logroot'          => false,
        'fullstop'         => false,
        'verbose'         => false,
    ),
    array(
        'h' => 'help',
        'a' => 'allow-unstable',
        'l' => 'logroot',
        's' => 'fullstop',
        'v' => 'verbose',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("$unrecognized is not a recognized option\n");
}

if ($options['help']) {
    $help = "
Command line ENT Global Updater.

    Options:
    -h, --help              Print out this help
    -a, --allow-unstable    Print out this help
    -l, --logroot           Root directory for logs.
    -v, --verbose           Root directory for logs.
    -s, --fullstop          Stops on first error.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (!empty($options['logroot'])) {
    $logroot = $options['logroot'];
} else {
    $logroot = $CFG->dataroot;
}

if (!empty($options['allow-unstable'])) {
    $allowunstable = '--allow-unstable';
} else {
    $allowunstable = '';
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Start updating.
// Linux only implementation.

echo "Starting upgrading....\n";

$i = 1;
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/upgrade.php --host=\"{$h->vhostname}\" ";
    if (empty($options['verbose']) || !empty($options['logroot'])) {
        $workercmd .= "--non-interactive {$allowunstable} > {$logroot}/upgrade_{$h->shortname}.log";
    } else {
        $workercmd .= "--non-interactive {$allowunstable} ";
    }

    mtrace("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            die("Worker ended with error\n");
        } else {
            echo "Worker ended with error:\n";
            echo implode("\n", $output)."\n";
            echo "Pursuing anyway\n";
        }
    } else {
        if (!empty($options['verbose'])) {
            echo implode("\n", $output)."\n";
        }
    }
}

echo "Done.\n";
exit(0);
