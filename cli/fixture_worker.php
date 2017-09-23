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

/**
 * A fixture worker will play a script
 *
 * @package     local_vmoodle
 * @category    local
 * @copyright   2016 Valery Fremaux
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
define('ENT_INSTALLER_SYNC_INTERHOST', 1);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'              => false,
        'fixture'           => false,
        'nodes'             => false,
        'logfile'           => false,
        'logmode'           => false,
        'verbose'           => false,
    ),
    array(
        'h' => 'help',
        'f' => 'fixture',
        'n' => 'nodes',
        'l' => 'logfile',
        'm' => 'logmode',
        'v' => 'verbose'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help'] || empty($options['nodes'])) {
    $help = "
Command Line Fixture Worker.

    Options:
    -h, --help          Print out this help
    -f, --fixture       The fixture to run.
    -n, --nodes         Node ids to work with.
    -l, --logfile       the log file to use. No log if not defined
    -m, --logmode       'append' or 'overwrite'
    -v, --verbose       Verbose output
    -s, --fullstop      Stops on first error

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (empty($options['logmode'])) {
    $options['logmode'] = 'w';
}

if (!empty($options['logfile'])) {
    $log = fopen($options['logfile'], $options['logmode']);
}

// Fire sequential synchronisation.
mtrace("Starting worker");
if (isset($log)) {
    fputs($log, "Starting worker\n");
};

$nodes = explode(',', $options['nodes']);
foreach ($nodes as $nodeid) {
    $host = $DB->get_record('local_vmoodle', array('id' => $nodeid));
    $cmd = "php {$CFG->dirroot}/local/vmoodle/cli/fixtures/{$options['fixture']}.php --host={$host->vhostname} ";
    $return = 0;
    $output = array();
    mtrace($cmd);
    exec($cmd, $output, $return);
    if ($return) {
        if (!empty($options['fullstop'])) {
            echo implode("\n", $output)."\n";
            die ("Worker failed\n");
        }
        echo "Worker failed:\n";
        echo implode("\n", $output)."\n";
    }
    if (!empty($options['verbose'])) {
        echo implode("\n", $output)."\n";
    }
    if (isset($log)) {
        fputs($log, "$cmd\n#-------------------\n");
        fputs($log, implode("\n", $output)."\n");
    };
    sleep(ENT_INSTALLER_SYNC_INTERHOST);
}

if (isset($log)) {
    fclose($log);
}

return 0;