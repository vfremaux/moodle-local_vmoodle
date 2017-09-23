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
 * This script copies a file from one host to another host.
 *
 * @package    local_vmoodle
 * @category local
 * @subpackage cli
 * @author Valery Fremaux
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);

// Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');

require_once($CFG->dirroot.'/lib/clilib.php');

// Cli only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array('tohost'          => true,
          'component'            => true,
          'filearea'             => true,
          'itemid'                 => true,
          'filepath'             => true,
          'filename'             => true),
    array('t' => 'tohost',
          'c' => 'component',
          'F' => 'filearea',
          'i' => 'itemid',
          'n' => 'filename',
          'h' => 'help',
          'f' => 'from')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("This option is not recognized: $unrecognized");
}

if ($options['help']) {
    $help = "
Tranfers (copies) a file from a moodle file system to another.
Keeps component and filearea safe. At the moment only works for context system
and is dedicated to sync settings attachements between moodles.

This script operates from the master install using the vmoodle register to find
assets locations.

Options:
-f, --from            The source host
-t, --tohost          Remote host to transfer to.
-c, --component       the component name.
-F, --filearea        the filearea.
-i, --itemid          the item id.
-n, --filepath        the file's fullpath.
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/copy_file.php --from=http://my.virtual.moodle.org ---to=http://my.other.moodle.org
        --component=theme_essential --filearea=files --itemid=0 --filepath=/logo.jpg

Binding master to subs :
\$sudo -u www-data /usr/bin/php local/vmoodle/cli/init_mnet_node.php --host=http://my.virtual.moodle.org ---bind=subs

"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (empty($options['from'])) {
    die("No source host\n");
}

if (empty($options['to'])) {
    die("No dest host\n");
}



echo "Done.\n";