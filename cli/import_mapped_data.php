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
 * This script allows to do backup.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2013 Lancaster University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // force first config to be minimal

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/lib/clilib.php');
require_once(dirname(__FILE__).'/clilib.php');

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array(
    'host' => false,
    'file' => false,
    'keyfields' => '',
    'values' => '',
    'dryrun' => false,
    'help' => false,
    ), array(
        'h' => 'help',
        'f' => 'file',
        'k' => 'keyfields',
        'v' => 'values',
        'D' => 'dryrun',
        'H' => 'host'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    echo "$unrecognized is not a valid option\n";
}

if (!empty($options['help'])) {
    $help = <<<EOL
Imports data by replacement (REPLACE) using mapping columns.
Import SQL must contain only UPDATE.

Options:
--host=URL                  Host to proceeed for.
--file                      Json file to import @see phpmyadmin export json smart format.
--keyfields                 Use those fields as mapping composite primary key.
--values                    Those are expected updaed values. Defaults to "all but keyfields and 'id'"
--dryrun                    If given option, writes nothing.
-h, --help                  Print out this help.

Example:
    \$sudo -u www-data /usr/bin/php local/vmoodle/cli/import_mapped_data.php --sqlfile=/var/tmp/import.sql --keyfields=plugin,name --values=value/\n
    
    Given a json file with a json export of the table f.e. mdl_config_plugins, will reintroduce (update) data of column "value" into the tablespace,
    mapped by the unique index "plugin, name".

EOL;

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
echo('Config check : playing for '.$CFG->wwwroot."\n");

/*
$admin = get_admin();
if (!$admin) {
    mtrace("Error: No admin account was found\n");
    die;
}
*/

$keys = [];
if (!empty($options['keyfields'])) {
    $keys = explode(',', $options['keyfields']);
    echo("Warning : keys provided. Taking command line input.\n");
}

$values = [];
if (!empty($options['values'])) {
    $values = explode(',', $options['values']);
    echo("Warning : Values provided. Taking command line input.\n");
}

if (!is_readable($options['file'])) {
    die("No data file found as {$options['file']} \n");
}

$data = file($options['file']);

$data = json_decode(implode('', $data));

if (empty($data)) {
    die("Could not decode Json in given file\n");
}

$headerobj = $data[0];
if (empty($headerobj->type) || $headerobj->type != 'header') {
    die("Malformed data. Missing Json Header object. \n");
}

// Should be a database
$dbobj = $data[1];
if (empty($dbobj->type) || $dbobj->type != 'database') {
    die("Malformed data. Missing Json Database object. \n");
}

// Should be a table
$tableobj = $data[2];
if (empty($tableobj->type) || $tableobj->type != 'table') {
    die("Malformed data. Missing Json Table object. \n");
}

if (empty($tableobj->data)) {
    die("Empty data set. \n");
}

if (empty($keys) && !empty($tableobj->keyfields)) {
    $keys = explode(',', $tableobj->keyfields);
}

if (empty($keys)) {
    die("No keys found either in command line or in json file\n");
}

if (empty($values) && !empty($tableobj->values)) {
    $values = explode(',', $tableobj->values);
}

if (empty($values)) {
    die("No values found either in command line or in json file\n");
}

$idmapping = [];
// Fix full name from import to use moodle ddl prmtives.
$tableobj->name = str_replace($CFG->prefix, '', $tableobj->name);

$i = 0;

foreach ($tableobj->data as $datum) {

    $oldid = $datum->id; // Should be one in any exported moodle table.
    $params = [];
    foreach ($keys as $k) {
        if (!property_exists($datum, $k)) {
            throw new Exception("Missing part of unique index in data : $k. Cannot process.\n");
        }
        $params[$k] = $datum->$k;
    }

    $oldrec = $DB->get_record(trim($tableobj->name), $params);
    if ($oldrec) {
        $oldrecimmutable = clone($oldrec);
        foreach ($values as $v) {
            if (!property_exists($datum, $v)) {
                throw new Exception("Missing expected value in data : $v. Cannot process.\n");
            }
            $oldrec->$v = $datum->$v;
        }
        if (empty($options['dryrun'])) {
            $DB->update_record($tableobj->name, $oldrec);
        } else {
            unset($datum->id);
            echo "DryRun : Updating ".json_encode($oldrecimmutable)." as ".json_encode($datum)."\n";
        }
        $newid = $oldrec->id;
    } else {
        // Remove old primary key to insert new
        unset($datum->id);
        if (empty($options['dryrun'])) {
            $newid = $DB->insert_record($tableobj->name, $datum);
        } else {
            unset($datum->id);
            echo "DryRun : Inserting ".json_encode($datum)." as new\n";
        }
    }

    $mapping = new StdClass;
    $mapping->old = $oldid;
    $mapping->new = $newid;
    $idmapping[] = $mapping;
    $i++;
}

echo "Finished $i records\n";
echo "Id maps: \n";
echo json_encode($idmapping);

echo "\nDone\n";
exit(0);