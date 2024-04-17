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
        'help'      => false,
        'reset-hub-infos'  => false,
        'fullstop'   => false,
    ),
    array(
        'h' => 'help',
        'p' => 'reset-hub-infos',
        's' => 'fullstop',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("$unrecognized is not a recognized option. Use --help for information.");
}

if ($options['help']) {
    $help = "
Resets primary local admin account 'admin'.

    Options:
    -h, --help              Print out this help
    -r, --reset-hubb-infos  Only reset hub and registration infos
    -s, --fullstop          If set, stops on first failure, otherwise attempt all instances.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$resethubinfos = '';
if (!empty($options['reset-hub-infos'])) {
    $resethubinfos = ' --reset-hub-infos ';
}

$allhosts = $DB->get_records('local_vmoodle', $params);

// Start updating.
// Linux only implementation.

echo "Starting processing registration command....\n";

$i = 1;
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/register.php {$resethubinfos} --host=\"{$h->vhostname}\" ";

    mtrace("Executing $workercmd\n######################################################\n");

    $output = array();
    exec($workercmd, $output, $return);
    echo implode("\n", $output)."\n";

    if ($return) {
        if (!empty($options['fullstop'])) {
            die("Worker ended with error");
        } else {
            mtrace("Worker failed for {$h->hostname}");
        }
    }

}

echo "Done.\n";
