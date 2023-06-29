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
require_once($CFG->dirroot.'/local/vmoodle/cli/clilib.php'); // CLI only functions.

// Ensure options are blanck.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'with-master'       => false,
        'is-fixture'       => false,
        'execute'       => false,
        'verbose'       => false,
        'debugging'       => false,
        'fullstop'       => false,
    ),
    array(
        'h' => 'help',
        'm' => 'with-master',
        'e' => 'execute',
        'f' => 'is-fixture',
        'd' => 'debugging',
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
Command line for starting MNET on nodes.

    Options:
    -m, --with-master       Schedule also on on main host.
    --execute=\\\\some\\\\task  Execute scheduled task manually
    -h, --help              Print out this help
    -d, --debugging         Turns on debug mode on workers
    -f, --is-fixture        Tells to run in fixture mode, i.e. skips the needs upgrade control.
    -v, --verbose           Print out the workers output
    -s, --fullstop          Stops on first error if set

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$debug = '';
if (!empty($options['debugging'])) {
    $debug = ' --debugging ';
}

if (empty($options['execute'])) {
    die("Task was missing\n");
}
$task = $options['execute'];

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Start updating.
// Linux only implementation.

echo "Starting installing mnet....\n";

if (!empty($options['withmaster'])) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/schedule_task.php  {$debug} --execute={$task} ";

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
        }
    } else {
        if (!empty($options['verbose'])) {
            echo implode("\n", $output);
            echo "\n";
        }
    }
}

$numhosts = count($allhosts);
$i = 0;
$fails = 0;
$failed = [];
if ($allhosts) {
    foreach ($allhosts as $h) {
        $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/schedule_task.php  {$debug} --execute={$task}  --host=\"{$h->vhostname}\"";

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
            }
        } else {
            if (!empty($options['verbose'])) {
                echo implode("\n", $output);
                echo "\n";
            }
        }

        $i++;
        vmoodle_send_cli_progress($numhosts, $i, 'bulkscheduletask');
    }
}

vmoodle_cli_notify_admin("[$SITE->shortname] Bulkscheduletask done. See logs for detailed result. $fails failures.", implode("\n", $failed));
echo "Done with $fails failures.\n";
exit(0);
