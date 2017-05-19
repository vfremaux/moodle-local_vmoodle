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
        'help' => false,
        'collation' => false,
        'list' => false,
        'available' => false,
        'logroot' => false,
    ),
    array(
        'h' => 'help',
        'c' => 'collation',
        'l' => 'list',
        'a' => 'available',
        'L' => 'logroot',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Command line for Mysql engine update.

    Options:
    -h, --help              Print out this help
    -e, --engine            the target engine
    -l, --logroot           Root directory for logs.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (!empty($options['logroot'])) {
    $logroot = $options['logroot'];
} else {
    $logroot = $CFG->dataroot;
}

$collation = '';
if (!empty($options['collation'])) {
    $collation = '--collation='.$options['collation'];
}

$list = '';
if (!empty($options['list'])) {
    $list = '--list';
}

$available = '';
if (!empty($options['available'])) {
    $available = '--available';
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Start updating.
// Linux only implementation.

echo "Starting updating database collation....\n";

$i = 1;
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/mysql_collation.php {$collation} {$list} {$available} --host=\"{$h->vhostname}\" ";

    mtrace("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        die("Worker ended with error");
    }
}

echo "done.\n";
