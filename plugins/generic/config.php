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
 * Description of assisted commands for administrating configs.
 * 
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @copyright valeisti (http://www.valeisti.fr)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

namespace vmoodleadminset_generic;

use \local_vmoodle\commands\Command_Category;
use \local_vmoodle\commands\Command_Parameter;
use \vmoodleadminset_sql\Command_MultiSql;
use \vmoodleadminset_sql\Command_Sql;

function vmoodle_config_get_plugins_params() {
    global $CFG, $DB;

    $sql = '
        SELECT DISTINCT
            id,
            CONCAT(plugin,'/',name)
        FROM
            {config_plugins}
    ';
    $paramslist = $DB->get_records_sql_menu($sql);
    $paramlist = array_combine(array_values($paramslist), array_values($paramslist));
    return $paramlist;
}

function vmoodle_config_get_params() {
    global $CFG, $DB;

    $sql = '
        SELECT DISTINCT
            id,
            name
        FROM
            {config}
    ';
    $paramslist = $DB->get_records_sql_menu($sql);
    $paramlist = array_combine(array_values($paramslist), array_values($paramslist));
    return $paramlist;
}


$category = new Command_Category('generic');

// Set on/off the maintenance mode.
$param1 = new Command_Parameter(
    'source1',
    'boolean',
    'Maintenance mode',
    null,
    null);

$param2 = new Command_Parameter(
    'source2',
    'ltext',
    'Maintenance message',
    null,
    null);

$sql = 'UPDATE {config} SET value = [[?source1]] WHERE name = \'maintenance_enabled\' '.";\n";
$sql .= ' UPDATE {config} SET value = [[?source2]] WHERE name = \'maintenance_message\'';

$cmd = new Command_MultiSql(
    'Vmoodle Maintenance',
    'Setting on/off the maintenance mode',
    $sql,
    array($param1,$param2));

$category->addCommand($cmd);

$cmd = new Command_PurgeCaches(
    'Vmoodle Purge Caches',
    'Purge remote caches'
);

$category->addCommand($cmd);

// Distribute a config value to all nodes (Using SetConfig).

$param1 = new Command_Parameter(
    'key',
    'enum',
    'Config Key',
    null,
    vmoodle_config_get_params());

$param2 = new Command_Parameter(
    'value',
    'text',
    'Config Value',
    null,
    null);

$cmd = new Command_SetConfig(
    'Vmoodle Config Value',
    'Distributing a configuration value',
    array($param1,$param2));

$category->addCommand($cmd);


$param1 = new Command_Parameter(
    'key',
    'enum',
    'Config Key',
    null,
    vmoodle_config_get_plugins_params());

$param2 = new Command_Parameter(
    'value',
    'text',
    'Config Value',
    null,
    null);

$cmd = new Command_SetPluginConfig(
    'Vmoodle Plugin Config Value',
    'Distributing a configuration value in Config Plugin',
    array($param1,$param2));
$category->addCommand($cmd);

return $category;