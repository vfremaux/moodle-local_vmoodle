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
        'verbose'          => false,
        'fullstop'         => false,
        'debug'            => false,
        'with-master'      => false,
        'show-all'         => false,
        'show-contrib'     => false,
        'show-missing'     => false,
        'purge-missing'    => false,
        'plugins'          => false,
        'run'              => false
    ),
    array(
        'h' => 'help',
        'v' => 'verbose',
        'f' => 'fullstop',
        'd' => 'debug',
        'M' => 'with-master',
        'a' => 'show-all',
        'c' => 'show-contrib',
        'm' => 'show-missing',
        'p' => 'purge-missing',
        'P' => 'plugins',
        'r' => 'run'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Command line Plugin uninstallation

    Options:
    -h, --help                      Print out this help
    -v, --verbose                   Print out workers output.
    -f, --fullstop                  Stops on first error.
    -d, --debug                     Turns on debug mode.
    -M, --with-master               Uninstall on master moodle too.
    -a, --show-all                  Displays a list of all installed plugins.
    -c, --show-contrib              Displays a list of all third-party installed plugins.
    -m, --show-missing              Displays a list of plugins missing from disk.
    -p, --purge-missing             Uninstall all missing from disk plugins.-p 
    -P, --plugins=<plugin name>     A comma separated list of plugins to be uninstalled. E.g. mod_assign,mod_forum
    -r, --run                       Execute uninstall. If this option is not set, then the script will be run in a dry mode.
"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

// Start updating.
// Linux only implementation.

echo "Starting bulk uninstall....\n";

$debug = '';
if (!empty($options['debug'])) {
    $debug = ' --debug ';
}

$showall = '';
if (!empty($options['show-all'])) {
    $showall = ' --show-all ';
}

$showcontrib = '';
if (!empty($options['show-contrib'])) {
    $showcontrib = ' --show-contrib ';
}

$showmissings = '';
if (!empty($options['show-missing'])) {
    $showmissings = ' --show-missing ';
}

$purgemissings = '';
if (!empty($options['purge-missing'])) {
    $purgemissings = ' --purge-missing ';
}

$plugins = '';
if (!empty($options['plugins'])) {
    $plugins = " --plugins='".$options['plugins']."'";
}

$run = '';
if (!empty($options['run'])) {
    $run = " --run ";
}


if (!empty($options['with-master'])) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/uninstall_plugins.php {$debug} ";
    $workercmd .= " {$showall} {$showcontrib} {$showmissings} {$purgemissings} {$plugins} {$run} ";

    mtrace("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);

    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            vmoodle_cli_notify_admin("[$SITE->shortname] Bulkuninstallplugins Error : {$CFG->wwwroot} (master) ended with error");
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

$i = 1;
$numhosts = count($allhosts);
$fails = 0;
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/uninstall_plugins.php --host=\"{$h->vhostname}\" {$debug} ";
    $workercmd .= " {$showall} {$showcontrib} {$showmissings} {$purgemissings} {$plugins} {$run} ";

    mtrace("Executing $workercmd\n######################################################\n");
    $output = array();
    exec($workercmd, $output, $return);

    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            vmoodle_cli_notify_admin("[$SITE->shortname] Bulkuninstallplugins Error : {$h->vhostname} (master) ended with error. Fatal.");
            die("Worker ended with error\n");
        }
        echo "Worker ended with error:\n";
        echo implode("\n", $output)."\n";
        echo "Pursuing anyway\n";
        $fails++;
    } else {
        if (!empty($options['verbose'])) {
            echo implode("\n", $output)."\n";
        }
    }
    vmoodle_send_cli_progress($numhosts, $i, 'bulkuninstallplugins');
    $i++;
}
vmoodle_cli_notify_admin("[$SITE->shortname] Bulkpurgecaches done with $fails failures.");
echo "done with $fails failures.\n";
exit(0);