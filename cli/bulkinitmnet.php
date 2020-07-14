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
        'help'             => false,
        'withmaster'       => false,
        'fullstop'         => false,
        'debug'            => false,
        'verbose'          => false,
    ),
    array(
        'h' => 'help',
        'm' => 'withmaster',
        'd' => 'debug',
        'v' => 'verbose',
        's' => 'fullstop',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Command line for initializing MNET bindings on nodes.

Note : Mnet must be started on all nodes for this script being able to proceed.

    Options:
    -m, --withmaster        Init mnet also on main host. If set to 'mainonly', will only init master host.
    -h, --help              Print out this help
    -d, --debug             Turns on debug mode on workers.
    -v, --verbose           Outputs workers output.
    -s, --fullstop          If present stops on first error.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

$debug = '';
if (!empty($options['debug'])) {
    $debug = ' --debug ';
}

// Start updating.
// Linux only implementation.

echo "Starting binding mnet {$options['withmaster']}....\n";

if (!empty($options['withmaster'])) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/init_mnet_node.php {$debug} --bindhost=subs";

    mtrace("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            die("Worker ended with error\n");
        } else {
            mtrace("Worker ended with error:\n");
            echo implode("\n", $output)."\n";
        }
    }
    if (!empty($options['verbose'])) {
        echo implode("\n", $output)."\n";
    }
}

if (!empty($options['withmaster']) && ($options['withmaster'] === 'mainonly')) {
    die("Requested only for master moodle. Exiting\n");
}

$i = 1;
if ($allhosts) {
    foreach ($allhosts as $h) {
        $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/init_mnet_node.php {$debug} --host=\"{$h->vhostname}\" --bindhost=\"{$CFG->mainwwwroot}\" ";

        mtrace("Executing $workercmd\n######################################################\n");
        $output = array();
        exec($workercmd, $output, $return);
        if ($return) {
            if (!empty($options['fullstop'])) {
                echo implode("\n", $output);
                echo "\n";
                die("Worker ended with error\n");
            }
            echo "Worker ended with error\n";
            echo implode("\n", $output);
            echo "\n";
        } else {
            if (!empty($options['verbose'])) {
                echo implode("\n", $output)."\n";
                echo "\n";
            }
        }
    }
}

echo "Done.\n";
