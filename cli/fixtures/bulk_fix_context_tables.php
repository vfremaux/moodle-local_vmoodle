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
 * @package     local_vmoodle
 * @category    local
 * @copyright   2016 Valery Fremaux
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
global $CLI_VMOODLE_PRECHECK;
global $isfixture;

$isfixture = true;

$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.
require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array('verbose' => false,
          'help'    => false,
          'host'    => false,
          'debug'   => false
    ),
    array('h' => 'help',
          'v' => 'verbose',
          'H' => 'host',
          'd' => 'debug'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error($unrecognized.' are not recognized options ');
}

if ($options['help']) {
    $help = "
Command line: runs context_cleanup_task for all nodes, forcing recalculating paths.

    Options:
    --verbose           Provides lot of output
    -h, --help          Print out this help
    -H, --host          Set the host (physical or virtual) to operate on
    -d, --debug         Turns debug mode on.
"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/config.php'); // Global moodle config file.
echo('Config check : playing for '.$CFG->wwwroot);

require_once($CFG->dirroot.'/local/vmoodle/cli/clilib.php'); // CLI more functions.

$debug = '';
if (!empty($options['debugging'])) {
    $debug = ' --debugging ';
}

$allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

$i = 0;
$numhosts = count($allhosts);
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/schedule_task.php {$debug} --isfixture --host=\"{$h->vhostname}\" --execute='\\core\\task\\context_cleanup_task' ";

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

    $i++;
    vmoodle_send_cli_progress($numhosts, $i, 'bulkfixcontexttables');
}

vmoodle_cli_notify_admin("[$SITE->shortname] Bulk_fix_contexts_tables done.");
echo "Done.\n";
exit(0);
