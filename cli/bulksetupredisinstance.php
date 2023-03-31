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
Bulk setup redis instance on a VMoodle array.

    Options:
    -h, --help              Print out this help
    -N, --redisname         Redis name
    -R, --redishost         Redis host
    -i, --redisdbid         Redis db id
    -p, --redispwd          Redis password
    -P, --redisprefix       Redis prefix
    -a, --activate          Activate
    -M, --with-master       Apply also on master moodle.
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

if (!empty($options['redisname'])) {
    $redisname = ' --redisname='.$options['redisname'];
} else {
    die("Error : a name must be given for the repo");
}

if (!empty($options['redishost'])) {
    $redishost = '--redishost='.$options['redishost'];
} else {
    $redishost = '--redishost=localhost';
}

$redispwd = '';
if (!empty($options['redispwd'])) {
    $redispwd = '--redispwd='.$options['redispwd'];
}

$redisprefix = '';
if (!empty($options['redisprefix'])) {
    $redisprefix = '--redisprefix='.$options['redisprefix'];
}

$redisdbid = '';
if (!empty($options['redisdbid'])) {
    $dbidval = $options['redisdbid'];
} else {
    $dbidval = 0;
}

$activate = '';
if (!empty($options['activate'])) {
    $activate = '--activate='.$options['activate'];
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

    $dbid = '--redisdbid='.$dbidval;

    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/setup_redis_instance.php ";
    if (empty($options['verbose']) || !empty($options['logroot'])) {
        $workercmd .= "{$redishost} {$redisprefix} {$redispwd} {$redisname} {$activate} {$debug} {$dbid} > {$logroot}/setupredis_{$SITE->shortname}.log";
    } else {
        $workercmd .= "{$redishost} {$redisprefix} {$redispwd} {$redisname} {$activate} {$debug} {$dbid} ";
    }

    echo("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            vmoodle_cli_notify_admin("BulkSetupRedis : {$CFG->wwwroot} (master) ended with error");
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
    vmoodle_cli_notify_admin("[$SITE->shortname] BulkSetupRedis Success : {$CFG->wwwroot} (master) upgraded");
}

echo "Starting setting redis nodes....\n";

$i = 0;
$numhosts = count($allhosts);
foreach ($allhosts as $h) {

    $dbidval++;
    $dbid = '--redisdbid='.$dbidval;

    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/setup_redis_instance.php --host=\"{$h->vhostname}\" ";
    if (empty($options['verbose']) || !empty($options['logroot'])) {
        $workercmd .= "{$redishost} {$redisprefix} {$redispwd} {$redisname} {$activate} {$debug} {$dbid} > {$logroot}/setupredis_{$SITE->shortname}.log";
    } else {
        $workercmd .= "{$redishost} {$redisprefix} {$redispwd} {$redisname} {$activate} {$debug} {$dbid}";
    }

    echo("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            vmoodle_cli_notify_admin("BulkSetupRedis Error : {$h->vhostname} ended with error. Fatal.");
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

    $i++;
    vmoodle_send_cli_progress($numhosts, $i, 'bulksetuprediscache');
}

vmoodle_cli_notify_admin("[$SITE->shortname] BulkSetupRedis done. See logs for detailed result.");
echo "Done.\n";
exit(0);
