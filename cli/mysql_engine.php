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
 * MySQL engine conversion tool.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

global $CLI_VMOODLE_PRECHECK;

define('CLI_SCRIPT', true);
define('CACHE_DISABLE_ALL', true);
$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/lib/clilib.php'); // Cli only functions.

// Now get cli options.
list($options, $unrecognized) = cli_get_params(
    array('help' => false,
          'list' => false,
          'engine' => false,
          'available' => false,
          'host' => false),
    array('h'=>'help',
          'l'=>'list',
          'a'=>'available',
          'H' => 'host')
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error("Invalid option $unrecognized\n");
}

$help = "
MySQL engine conversions script.

It is recommended to stop the web server before the conversion.
Do not use MyISAM if possible, because it is not ACID compliant
and does not support transactions.

Options:
--engine=ENGINE       Convert MySQL tables to different engine
-l, --list            Show table information
-a, --available       Show list of available engines
-H,   Host
-h, --help            Print out this help

Example:
\$sudo -u www-data /usr/bin/php admin/cli/mysql_engine.php --engine=InnoDB --host=http://mysubmoodle.mymoodle.com
";

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']."\n"); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

if (!defined('MOODLE_INTERNAL')) {
    // If we are still in precheck, this means this is NOT a VMoodle install and full setup has already run.
    // Otherwise we only have a tiny config at this location, sso run full config again forcing playing host if required.
    require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
}
echo('Config check : playing for '.$CFG->wwwroot."\n");

if ($DB->get_dbfamily() !== 'mysql') {
    cli_error('This function is designed for MySQL databases only!');
}

if (!empty($options['engine'])) {
    $engines = mysql_get_engines();
    $engine = clean_param($options['engine'], PARAM_ALPHA);
    if (!isset($engines[strtoupper($engine)])) {
        cli_error("Error: engine '$engine' is not available on this server!");
    }

    echo "Converting tables to '$engine' for $CFG->wwwroot:\n";
    $prefix = $DB->get_prefix();
    $prefix = str_replace('_', '\\_', $prefix);
    $sql = "SHOW TABLE STATUS WHERE Name LIKE BINARY '$prefix%'";
    $rs = $DB->get_recordset_sql($sql);
    $converted = 0;
    $skipped   = 0;
    $errors    = 0;
    foreach ($rs as $table) {
        if (strtoupper($table->engine) === strtoupper($engine)) {
            $newengine = mysql_get_table_engine($table->name);
            echo str_pad($table->name, 40). " - NO CONVERSION NEEDED ($newengine)\n";
            $skipped++;
            continue;
        }
        echo str_pad($table->name, 40). " - ";

        try {
            $DB->change_database_structure("ALTER TABLE {$table->name} ENGINE = $engine");
            $newengine = mysql_get_table_engine($table->name);
            if (strtoupper($newengine) !== strtoupper($engine)) {
                echo "ERROR ($newengine)\n";
                $errors++;
                continue;
            }
            echo "DONE ($newengine)\n";
            $converted++;
        } catch (moodle_exception $e) {
            echo $e->getMessage()."\n";
            $errors++;
            continue;
        }
    }
    $rs->close();
    echo "Converted: $converted, skipped: $skipped, errors: $errors\n";
    exit(0); // success

} else if (!empty($options['list'])) {
    echo "List of tables for $CFG->wwwroot:\n";
    $prefix = $DB->get_prefix();
    $prefix = str_replace('_', '\\_', $prefix);
    $sql = "SHOW TABLE STATUS WHERE Name LIKE BINARY '$prefix%'";
    $rs = $DB->get_recordset_sql($sql);
    $counts = array();
    foreach ($rs as $table) {
        if (isset($counts[$table->engine])) {
            $counts[$table->engine]++;
        } else {
            $counts[$table->engine] = 1;
        }
        echo str_pad($table->engine, 10);
        echo $table->name .  "\n";
    }
    $rs->close();

    echo "\n";
    echo "Table engines summary for $CFG->wwwroot:\n";
    foreach ($counts as $engine => $count) {
        echo "$engine: $count\n";
    }
    exit(0); // success

} else if (!empty($options['available'])) {
    echo "List of available MySQL engines for $CFG->wwwroot:\n";
    $engines = mysql_get_engines();
    foreach ($engines as $engine) {
        echo " $engine\n";
    }
    die;

} else {
    echo $help;
    die;
}



// ========== Some functions ==============

function mysql_get_engines() {
    global $DB;

    $sql = "SHOW Engines";
    $rs = $DB->get_recordset_sql($sql);
    $engines = array();
    foreach ($rs as $engine) {
        if (strtoupper($engine->support) !== 'YES' and strtoupper($engine->support) !== 'DEFAULT') {
            continue;
        }
        $engines[strtoupper($engine->engine)] = $engine->engine;
        if (strtoupper($engine->support) === 'DEFAULT') {
            $engines[strtoupper($engine->engine)] .= ' (default)';
        }
    }
    $rs->close();

    return $engines;
}

function mysql_get_table_engine($tablename) {
    global $DB;

    $engine = null;
    $sql = "SHOW TABLE STATUS WHERE Name = '$tablename'"; // no special chars expected here
    $rs = $DB->get_recordset_sql($sql);
    if ($rs->valid()) {
        $record = $rs->current();
        $engine = $record->engine;
    }
    $rs->close();
    return $engine;
}
