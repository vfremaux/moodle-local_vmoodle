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
        'help'            => false,
        'shortname'       => false,
        'subdir'          => false,
        'enabled'         => false,
        'debug'         => false,
    ),
    array(
        'h' => 'help',
        's' => 'shortname',
        'D' => 'subdir',
        'e' => 'enabled',
        'd' => 'enabled',
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Command line Virtual Subdirs Generator.

    Options:
    -h, --help              Print out this help
    -s, --shortname         If present (default), generates on virtual moodle shortname basis
    -d, --subdir            If present, generates on virtual moodle url picking first subdir token
    -e, --enabled           If present, only process enabled instances.

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (!empty($options['debug'])) {
    $CFG->Wdbeug = E_ALL;
}

if (!empty($options['enabled'])) {
    $params = array('enabled' => 1);
} else {
    $params = array();
}

$allhosts = $DB->get_records('local_vmoodle', $params);

// Start updating
// Linux only implementation.

echo "Starting generating virtual subdirs....";

$i = 1;
foreach ($allhosts as $h) {
    $dir = dirname(dirname(dirname(dirname(__FILE__))));

    if ($options['subdir']) {
        if (!preg_match('#https?://[^\\/]*?\\/([^\\/]*)#', $h->vhostname, $matches)) {
            echo "Subdir not found in instance wwwroot {$h->vhostname} ";
            continue;
        }
        $subdir = $matches[1];
    }

    $cmd = "ln -s $dir {$dir}/{$subdir} ";
    echo "#### $cmd\n";
    $result = exec($cmd, $output, $return);
    echo (implode("\n", $output));
}

echo "done.";
