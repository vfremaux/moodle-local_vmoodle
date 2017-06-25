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
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

// Ensure options are blanck.
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'withmaster'       => false
    ),
    array(
        'h' => 'help',
        'm' => 'withmaster'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "
Synchronizes master vmoodle register to all subs.

This is necessary when using the primary account feature in a VMoodle to garantee local
mnet identity will be overriden by a local account.

The mdl_local_vmoodle table will be replicated in all subs to give them the reference
of the primary account of the ingoing user.

WARNING : this script only works when all databases share the same connexion and the 
master host can write into all databases.

    Options:
    -h, --help              Print out this help

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

// Start updating.
// Linux only implementation.

echo "Reading VMoodle register....\n";

local_vmoodle_sync_register();