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
        'with-master'      => false,
        'source'          => false,
        'mode'          => 'all',
        'lang'          => false,
        'checkin'          => false,
        'fullstop'         => false,
        'verbose'          => false,
        'debug'            => false,
    ),
    array(
        'h' => 'help',
        'M' => 'with-master',
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
    --lang                  The target language (will get from filename if not provided)
    --source=path           File or folder of the custom lang files (zip or php files)
    --mode                  What string should be imported. Options are:
                            - all: all string will be imported (default)
                            - new: only string with no previous customisation
                            - update: only strings already modified
    --checkin               Save strings to the language pack
    -v, --verbose           More verbose output.
    -s, --fullstop          Stops on first error.
    -d, --debug             Set debug mode to develpper in individual task.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (empty($options['lang'])) {
    die("No lang given\n");
}
$lang = "--lang=".$options['lang'];

if (empty($options['source'])) {
    die("No source given\n");
}
$source = "--source=".$options['source'];

$mode = '';
if (!empty($options['mode'])) {
    $mode = "--mode=".$options['mode'];
}

$checkin = '';
if (!empty($options['checkin'])) {
    $checkin = "--checkin=".$options['checkin'];
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

    echo "Starting importing in master....\n";

    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/customlang_import.php ";
    $workercmd .= "{$lang} {$source} {$mode} {$checkin} {$debug}";

    echo("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            vmoodle_cli_notify_admin("Lang import : {$CFG->wwwroot} (master) ended with error");
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
    vmoodle_cli_notify_admin("[$SITE->shortname] Custom Lang import Success : {$CFG->wwwroot} (master) upgraded");
}

echo "Starting importing in nodes....\n";

$i = 0;
$numhosts = count($allhosts);
$fails = 0;
$failed = [];
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/customlang_import.php --host=\"{$h->vhostname}\" ";
    $workercmd .= "{$lang} {$source} {$mode} {$checkin}";

    echo("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            vmoodle_cli_notify_admin("Lang import Error : {$h->vhostname} ended with error. Fatal.");
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
    vmoodle_send_cli_progress($numhosts, $i, 'customlangimport');
}

vmoodle_cli_notify_admin("[$SITE->shortname] Custom Lang import done. See logs for detailed result. $fails failures.", implode("\n", $failed));
echo "Done with $fails failures.\n";
exit(0);
