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
        'search'           => false,
        'replace'          => false,
        'shorten'          => false,
        'fullstop'         => false,
        'debug'            => false,
    ),
    array(
        'h' => 'help',
        'v' => 'verbose',
        's' => 'search',
        'r' => 'replace',
        'S' => 'shorten',
        'f' => 'fullstop',
        'd' => 'debug',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['search'] || empty($options['replace']))) {
    $help = "
Command line global DB replacement

    Options:
    -h, --help              Print out this help
    -s, --search            Search pattern.
    -r, --replace           Replace pattern.
    -v, --verbose           Print out workers output.
    -S, --shorten           Shorten data if not fits in field after replacement.
    -f, --fullstop          Stops on first error.
    -d, --debug             Turns on debug mode.

\$ sudo -u www-data /usr/bin/php local/vmoodle/cli/bulkreplace.php --search=//oldsitehost --replace=//newsitehost

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Start updating.
// Linux only implementation.

echo "Starting DB replacement....\n";

$debug = '';
if (!empty($options['debug'])) {
    // At the moment does nothing.
    $debug = ' --debug ';
}

$shorten = '';
if (!empty($options['shorten'])) {
    $shorten = ' --shorten ';
}

$search = '';
if (!empty($options['search'])) {
    $shsearch = escapeshellarg($options['search']);
    $shorten = ' --search=$shsearch" ';
}

$replace = '';
if (!empty($options['replace'])) {
    $shreplace = escapeshellarg($options['replace']);
    $replace = ' --replace="$shreplace" ';
}

$i = 1;
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/replace.php --host=\"{$h->vhostname}\" {$shorten} ";
    $workercmd .= "{$search} {$replace} --non-interactive ";

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
