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
        'help'              => false,
        'withmaster'        => false,
        'fullstop'          => false,
        'debug'             => false,
        'verbose'           => false,
    ),
    array(
        'h' => 'help',
        'm' => 'withmaster',
        'v' => 'verbose',
        'd' => 'debug',
        's' => 'fullstop',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(" $unrecognized is not a recognized option \n");
}

if ($options['help']) {
    $help = "
Command line for updating language packs.
This needs an outgoing HTTP request is possible or a suitable proxy is setup in config.

Please note you must execute this script with the same uid as apache!

    Options:
    -m, --withmaster        Init mnet also on main host.
    -h, --help              Print out this help
    -v, --verbose           Print out the output of each worker
    -d, --debug             Turn on debug mode of workers
    -s, --fullstop          If true, stops on first error

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/bulkupdatelangpacks.php --withmaster

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Start updating.
// Linux only implementation.

echo "Starting updating lang packs...\n";

$debug = '';
if (!empty($options['debug'])) {
    $debug = '  --debug ';
}

if (!empty($options['withmaster'])) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/update_langpacks.php {$debug}";

    mtrace("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            die("Worker ended with error\n");
        } else {
            echo "Worker ended with error\n";
            echo implode("\n", $output)."\n";
        }
    } else {
        if (!empty($options['verbose'])) {
            echo implode("\n", $output)."\n";
        }
    }
}

if (!empty($allhosts)) {

    $i = 0;
    $numhosts = count($allhosts);
    $fails = 0;

    foreach ($allhosts as $h) {
        $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/update_langpacks.php {$debug} --host=\"{$h->vhostname}\" ";

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
                $fails++;
            }
        } else {
            if (!empty($options['verbose'])) {
                echo implode("\n", $output)."\n";
            }
        }
        $i++;
        vmoodle_send_cli_progress($numhosts, $i, 'bulkupdatelangpacks');
    }
    vmoodle_cli_notify_admin("[$SITE->shortname] Bulkupdatelangpacks done with $fails failures.");
    echo "Done with $fails failures.\n";
    exit(0);
}

echo "No vhosts.\n";
