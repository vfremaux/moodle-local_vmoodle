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
require_once($CFG->dirroot.'/local/vmoodle/cli/clilib.php'); // CLI only functions.

// Ensure options are blanck.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'allow-unstable'   => false,
        'with-master'      => false,
        'purge-caches'     => false,
        'logroot'          => false,
        'fullstop'         => false,
        'verbose'          => false,
        'debug'            => false,
    ),
    array(
        'h' => 'help',
        'a' => 'allow-unstable',
        'M' => 'with-master',
        'P' => 'purge-caches',
        'l' => 'logroot',
        's' => 'fullstop',
        'v' => 'verbose',
        'd' => 'debug',
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
    -M, --with-master       Apply also on master moodle.
    -P, --purge-caches      also purge caches after upgrading.
    -l, --logroot           Root directory for logs.
    -v, --verbose           More verbose output.
    -s, --fullstop          Stops on first error.
    -d, --debug             Set debug mode to develpper in individual task.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (!empty($options['logroot'])) {
    $logroot = $options['logroot'];
} else {
    $logroot = $CFG->dataroot;
}

$purge = '';
if (!empty($options['purge-caches'])) {
    $purge = ' --purge-caches ';
}

if (!empty($options['allow-unstable'])) {
    $allowunstable = '--allow-unstable';
} else {
    $allowunstable = '';
}

if (!empty($options['debug'])) {
    $debug = '--debug';
} else {
    $debug = '';
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Start updating.
// Linux only implementation.

if (!empty($options['with-master'])) {

    echo "Starting upgrading master....\n";

    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/upgrade.php ";
    if (empty($options['verbose']) || !empty($options['logroot'])) {
        $workercmd .= "--non-interactive {$allowunstable} {$debug} {$purge} > {$logroot}/upgrade_{$SITE->shortname}.log";
    } else {
        $workercmd .= "--non-interactive {$allowunstable} {$debug} {$purge} ";
    }

    echo("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            vmoodle_cli_notify_admin("Bulkupgrade : {$CFG->wwwroot} (master) ended with error");
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
    vmoodle_cli_notify_admin("[$SITE->shortname] Bulkupgrade Success : {$CFG->wwwroot} (master) upgraded");
}

echo "Starting upgrading nodes....\n";

$i = 0;
$numhosts = count($allhosts);
$fails = 0;
$failed = [];
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/upgrade.php --host=\"{$h->vhostname}\" ";
    if (empty($options['verbose']) || !empty($options['logroot'])) {
        $workercmd .= "--non-interactive {$allowunstable} {$purge} > {$logroot}/upgrade_{$h->shortname}.log";
    } else {
        $workercmd .= "--non-interactive {$allowunstable} {$purge} ";
    }

    echo("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            vmoodle_cli_notify_admin("Bulkupgrade Error : {$h->vhostname} ended with error. Fatal.");
            die("Worker ended with error\n");
        } else {
            echo "Worker ended with error:\n";
            echo implode("\n", $output)."\n";
            echo "Pursuing anyway\n";
            $fails++;
            $failed[] = $h->vhostname;
        }
    } else {
        if (!empty($options['verbose'])) {
            echo implode("\n", $output)."\n";
        }
    }

    $i++;
    vmoodle_send_cli_progress($numhosts, $i, 'bulkupgrade');
}

vmoodle_cli_notify_admin("[$SITE->shortname] Bulkupgrades done. See logs for detailed result. $fails failures.", implode("\n", $failed));
echo "Done with $fails failures.\n";
exit(0);
