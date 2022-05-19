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

// Ensure options are blanck.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'      => false,
        'userscheme'   => false,
        'withmnetadmins'   => false,
        'fixguest' => false,
        'fullstop'   => false,
    ),
    array(
        'h' => 'help',
        'u' => 'userscheme',
        'm' => 'withmnetadmins',
        'g' => 'fixguest',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("$unrecognized is not a recognized option. Use --help for information.");
}

if ($options['help']) {
    $help = "
Fix site admin registrations.

    Options:
    -h, --help              Print out this help
    -u, --userscheme        SQK LIKE pattern for finding users to add to admins by username
    -s, --fullstop          If set, stops on first failure, otherwise attempt all instances.
    -m, --withmnetadmins    If set, fetch mnet 'admin' account and restore it.
    -g, --fixguest          If set, also fix guest record registration in config.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

$withmnetadmins = '';
if (!empty($options['withmnetadmins'])) {
    $withmnetadmins = ' --withmnetadmins ';
}

$userscheme = '';
if (!empty($options['userscheme'])) {
    $userscheme = '--userscheme='.$options['userscheme'];
}

$fixguest = '';
if (!empty($options['fixguest'])) {
    $fixguest = ' --fixguest ';
}

$allhosts = $DB->get_records('local_vmoodle', $params);

// Start updating.
// Linux only implementation.

echo "Starting resetting admins....\n";

$i = 1;
foreach ($allhosts as $h) {
    $workercmd = "php {$CFG->dirroot}/local/vmoodle/cli/fix_site_admins.php {$withmnetadmins} {$userscheme} {$fixguest} ";
    $workercmd .= " --host=\"{$h->vhostname}\" ";

    mtrace("Executing $workercmd\n######################################################\n");

    $output = array();
    exec($workercmd, $output, $return);
    echo implode("\n", $output)."\n";

    if ($return) {
        if (!empty($options['fullstop'])) {
            die("Worker ended with error");
        } else {
            mtrace("Worker failed for {$h->hostname}");
        }
    }

}

echo "Done.\n";
