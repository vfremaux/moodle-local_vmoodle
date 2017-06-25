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
 * @package    core
 * @subpackage cli
 * @copyright  2011 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/lib/clilib.php');         // Cli only functions.

list($options, $unrecognized) = cli_get_params(
    array(
        'help' => false,
        'nodefile' => true,
        ),
    array(
        'h' => 'help',
        'n' => 'nodefile')
);

$table = 'local_vmoodle';

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Not recognized options ".$unrecognized);
}

if ($options['help']) {
    $help = "
Integrates existing instances in VMoodle from a CSV file. The vmoodle nodes databases and moodledata
must exist before (no instance creation).

Options:
-h, --help            Print out this help
-n, --nodefile        The nodefile to open

Example:
\$sudo -u www-data /usr/bin/php admin/cli/integrate_vmoodles.php --nodefile=nodefile.csv
";

    echo $help;
    exit(0);
}

if (empty($options['nodefile'])) {
    die("No nodefile given\n");
}

if (!is_readable($options['nodefile'], 'r')) {
    die("Could not open file {$options['nodefile']}\n");
}

$nodes = file($options['nodefile']);

$fieldline = rtrim(array_shift($nodes));
$fields = explode(';', $fieldline);

if (!in_array('vhostname', $fields)) {
    die("vhostname is expected as mandatory field\n");
}

$i = 1;
while ($line = rtrim(array_shift($nodes))) {
    // Jump any cas of comments or empty.
    if (strpos($line, '#') === 0) {
        continue;
    }
    if (strpos($line, '//') === 0) {
        continue;
    }
    if (empty($line)) {
        continue;
    }

    $values = explode(';', $line);
    if (count($fields) != count($values)) {
        die("Not exact number of values at line $i. Stopping.\n");
    }

    $data = array_combine($fields, $values);

    // Process some defaults.
    if (!array_key_exists('vdbprefix', $data)) {
        $data['vdbprefix'] = 'mdl_';
    }

    if (!array_key_exists('vdbpersist', $data)) {
        $data['vdbpersist'] = 0;
    }

    if (!array_key_exists('vdbtype', $data)) {
        $data['vdbtype'] = 'mysqli';
    }

    if (!array_key_exists('enabled', $data)) {
        $data['enabled'] = 1;
    }

    if (!$oldrec = $DB->get_record($table, array('vhostname' => $data['vhostname']))) {
        // Bring updated values in record;
        $newrec = new StdClass;
        foreach ($fields as $f) {
            if ($f != 'vhostname') {
                $newrec->$f = $data[$f];
            }
        }
        $DB->insert_record($table, $newrec);

    } else {
        // Bring updated values in record;
        foreach ($fields as $f) {
            if ($f != 'vhostname') {
                $oldrec->$f = $data[$f];
            }
        }
        // Update record.
        $DB->update_record($table, $oldrec);
    }

    $i++;
}

echo "Done.\n";
exit(0);