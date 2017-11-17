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
        'help'              => false,
        'pre28'             => false,
        'all'               => false,
        'fix'             => false,
        'list'             => false,
    ),
    array(
        'h' => 'help',
        'a' => 'all',
        'l' => 'list',
        'f' => 'fix',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Command line ENT Global Updater.

    Options:
    -h, --help              Print out this help
    -a, --all               Processes all vmoodles including disabled.
        --pre28             Before vmoodle version 2.8, seeks vmoodles in block register.
    -l, --list              List tables to convert.
    -f, --fix               Fix table format.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$list = '';
if (!empty($options['list'])) {
    $list = '--list';
    $mode = 'listing';
}

$fix = '';
if (!empty($options['fix'])) {
    $fix = '--fix';
    $list = '';
    $mode = 'fixing';
}

if (!empty($options['pre28'])) {
    $tablebase = 'block';
    $pre28 = '--pre28';
} else {
    $tablebase = 'local';
    $pre28 = '';
}

$choice = array();
if (!empty($options['all'])) {
    $choice = array('enabled' => 1);
}
$allhosts = $DB->get_records($tablebase.'_vmoodle', $choice);

// Start updating.
// Linux only implementation.

echo "Starting $mode InnoDB format....\n";

$i = 1;
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/mysql_compressed_rows.php --host=\"{$h->vhostname}\" {$fix} {$list} {$pre28}";

    mtrace("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        die("Worker ended with error");
    }
}

echo "Done.";
