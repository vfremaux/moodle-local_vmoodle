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

use tool_migratehvp2h5p\api;

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.

if (!is_dir($CFG->dirroot.'/admin/tool/migratehvp2h5p')) {
    die ("Migrating tool for HVP is Not installed. Abporting\n");
}

require_once($CFG->dirroot.'/admin/tool/migratehvp2h5p/classes/api.php');
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.
require_once($CFG->dirroot.'/local/vmoodle/cli/clilib.php'); // CLI only functions.

// Ensure options are blanck.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'execute' => false,
        'limit' => 100,
        'keeporiginal' => 1,
        'copy2cb' => api::COPY2CBYESWITHLINK,
        'contenttypes' => [],
        'with-master'      => false,
        'logroot'          => false,
        'fullstop'         => false,
        'verbose'          => false,
        'debug'            => false,
    ),
    array(
        'h' => 'help',
        'e' => 'execute',
        'l' => 'limit',
        'k' => 'keeporiginal',
        'c' => 'copy2cb',
        't' => 'contenttypes',
        'M' => 'with-master',
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
    -M, --with-master       Apply also on master moodle.
    -e, --execute           Run the migration tool
    -k, --keeporiginal=N    After migration 0 will remove the original activity, 1 will keep it and 2 will hide it
    -c, --copy2cb=N         Whether H5P files should be added to the content bank with a link (1), as a copy (2) or not added (0)
    -t, --contenttypes=N    The library ids, separated by commas, for the mod_hvp contents to migrate.
                            Only contents having these libraries defined as main library will be migrated.
    -l  --limit=N           The maximmum number of activities per execution (default 100).
                            Already migrated activities will be ignored.
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

if (!empty($options['execute'])) {
    $execute = '--execute';
} else {
    $execute = '';
}

if (!empty($options['keeporiginal'])) {
    $keeporiginal = '--keeporiginal='.$options['keeporiginal'];
} else {
    $keeporiginal = '';
}

if (!empty($options['copy2cb'])) {
    $copy2cb = '--copy2cb='.$options['copy2cb'];
} else {
    $copy2cb = '';
}

if (!empty($options['contenttypes'])) {
    $contenttypes = '--contenttypes='.$options['contenttypes'];
} else {
    $contenttypes = '';
}

if (!empty($options['limit'])) {
    $limit = '--limit='.$options['limit'];
} else {
    $limit = '';
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

    echo "Starting migrating master....\n";

    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/migratehvp2h5p.php ";
    if (empty($options['verbose']) || !empty($options['logroot'])) {
        $workercmd .= " {$execute} {$copy2cp} {$keeporiginal} {$contenttypes} {$limit} {$debug} > {$logroot}/migratehvp_{$SITE->shortname}.log";
    } else {
        $workercmd .= " {$execute} {$copy2cp} {$keeporiginal} {$contenttypes} {$limit} {$debug} ";
    }

    echo("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            vmoodle_cli_notify_admin("Bulkmigratehvp2h5p : {$CFG->wwwroot} (master) ended with error");
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
    vmoodle_cli_notify_admin("[$SITE->shortname] Bulkmigratehvp2h5p Success : {$CFG->wwwroot} (master) migrated");
}

echo "Starting migrating nodes....\n";

$i = 0;
$numhosts = count($allhosts);
$fails = 0;
$failed = [];
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/migratehvp2h5p.php --host=\"{$h->vhostname}\" ";
    if (empty($options['verbose']) || !empty($options['logroot'])) {
        $workercmd .= " {$execute} {$copy2cp} {$keeporiginal} {$contenttypes} {$limit} {$debug} > {$logroot}/migratehvp_{$h->shortname}.log";
    } else {
        $workercmd .= " {$execute} {$copy2cp} {$keeporiginal} {$contenttypes} {$limit} {$debug} ";
    }

    echo("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            vmoodle_cli_notify_admin("Bulkmigratehvp2h5p Error : {$h->vhostname} ended with error. Fatal.");
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
    vmoodle_send_cli_progress($numhosts, $i, 'bulkmigratehvp2h5p');
}

vmoodle_cli_notify_admin("[$SITE->shortname] Bulkmigratehvp2h5p done. See logs for detailed result. $fails failures.", implode("\n", $failed));
echo "Done with $fails failures.\n";
exit(0);
