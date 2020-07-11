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
 * Install and upgrade SQL library for the local Vmoodle.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 */
namespace vmoodleadminset_test;

defined('MOODLE_INTERNAL') || die;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Category;
use \local_vmoodle\commands\Command_Parameter;
use \local_vmoodle\commands\Command_Parameter_Internal;
use \local_vmoodle\commands\Command_Exception;
use \vmoodleadminset_roles\Command_Role_Sync;
use \vmoodleadminset_roles\Command_Role_Compare;
use \vmoodleadminset_roles\Command_Role_Capability_Sync;
use \vmoodleadminset_upgrade\Command_Upgrade;
use \vmoodleadminset_sql\Command_Sql;
use \vmoodleadminset_sql\Command_MultiSql;
use \vmoodleadminset_test\CommandWrapper;
use \Exception;

/**
 * Description of assisted commands for testing generic oommands.
 */

// Creating category.
$category = new Command_Category('test');

// Adding commands.
$cmd = new Command_Sql(
    'Command 1',
    'Command without parameter.',
    'SELECT aa FROM bb'
);
$category->add_command($cmd);

/* ****************************** */

$cmd = new Command_Sql(
    'Command 2',
    'Command with a boolean parameter.',
    'SELECT [[?parameter1]] FROM bb',
    new Command_Parameter(
        'parameter1',
        'boolean',
        'The boolean'
    )
);
$category->add_command($cmd);

/* ****************************** */

$cmd = new Command_Sql(
    'Command 3',
    'Command with a boolean parameter selected.',
    'SELECT [[?parameter1]] FROM bb',
    new Command_Parameter(
        'parameter1',
        'boolean',
        'The boolean selected by default',
        true
    )
);
$category->add_command($cmd);

/* ****************************** */

$cmd = new Command_Sql(
    'Command 4',
    'Command with a boolean parameter unselected.',
    'SELECT [[?parameter1]] FROM bb',
    new Command_Parameter(
        'parameter1',
        'boolean',
        'The boolean unselected by default',
        false
    )
);
$category->add_command($cmd);

/* ****************************** */

$param1 = new Command_Parameter(
    'parameter1',
    'enum',
    'The enum choice',
    null,
    array(
        'value1' => get_string('value1', 'vmoodleadminset_test'),
        'value2' => get_string('value2', 'vmoodleadminset_test'),
        'value3' => get_string('value3', 'vmoodleadminset_test'),
    )
);
$cmd = new Command_Sql(
    'Command 5',
    'Command with an enum choice without default value.',
    'SELECT [[?parameter1]] FROM bb',
    $param1
);
$category->add_command($cmd);

/* ****************************** */

$param1 = new Command_Parameter(
    'parameter1',
    'enum',
    'The enum choice values 2 by default',
    'value2',
    array(
        'value1' => vmoodle_get_string('value1', 'vmoodleadminset_test'),
        'value2' => vmoodle_get_string('value2', 'vmoodleadminset_test'),
        'value3' => vmoodle_get_string('value3', 'vmoodleadminset_test'),
    )
);
$cmd = new Command_Sql(
    'Command 6',
    'Command with an enum choice with default value.',
    'SELECT [[?parameter1]] FROM bb',
    $param1
);
$category->add_command($cmd);

/*********************************/

$param1 = new Command_Parameter(
    'parameter1',
    'text',
    'The free text without default value'
);
$cmd = new Command_Sql(
    'Command 7',
    'Command with free text without default value.',
    'SELECT [[?parameter1]] FROM bb',
    $param1
);
$category->add_command($cmd);

/************************************/

$param1 = new Command_Parameter(
    'parameter1',
    'text',
    'The free text with default value',
    'the default value'
);
$cmd = new Command_Sql(
    'Command 8',
    'Command with free text with default value.',
    'SELECT [[?parameter1]] FROM bb',
    $param1
);
$category->add_command($cmd);

/**************************************/

$param1 = new Command_Parameter(
    'parameter1',
    'ltext',
    'The free long text without default value'
);
$cmd = new Command_Sql(
    'Command 9',
    'Command with free long text without default value.',
    'SELECT [[?parameter1]] FROM bb',
    $param1
);
$category->add_command($cmd);

/*****************************************/

$param1 = new Command_Parameter(
    'parameter1',
    'ltext',
    'The free long text with default value',
    'default value'
);
$cmd = new Command_Sql(
    'Command 10',
    'Command with free long text with default value.',
    'SELECT [[?parameter1]] FROM bb',
    $param1
);
$category->add_command($cmd);

/*****************************/

$param1 = new Command_Parameter(
    'parameter1',
    'boolean',
    'A boolean selected by default',
    true
);
$param2 = new Command_Parameter(
    'parameter2',
    'enum',
    'The enum choice values 2 by default',
    'value2',
    array(
        'value1' => get_string('value1', 'vmoodleadminset_test'),
        'value2' => get_string('value2', 'vmoodleadminset_test'),
        'value3' => get_string('value3', 'vmoodleadminset_test'),
    )
);
$param3 = new Command_Parameter(
    'parameter3',
    'text',
    'The free text with default value',
    'the default value'
);
$cmd = new Command_Sql(
    'Command 11',
    'Command which combine different fields.',
    'SELECT [[?parameter1]], [[?parameter2]], [[?parameter3]] FROM bb',
    array( $param1,
           $param2,
           $param3
    )
);
$category->add_command($cmd);

/* ******************************* */

$param1 = new Command_Parameter(
    'parameter1',
    'boolean',
    'The boolean selected by default',
    true
);
$param2 = new Command_Parameter(
    'parameter2',
    'enum',
    'The enum choice values 2 by default',
    'value2',
    array(
        'value1' => get_string('value1', 'vmoodleadminset_test'),
        'value2' => get_string('value2', 'vmoodleadminset_test'),
        'value3' => get_string('value3', 'vmoodleadminset_test'),
    )
);
$param3 = new Command_Parameter_Internal(
    'parameter3',
    'explode',
    array('[[?parameter1]]', '[[?parameter2]]', '[[bibi]]', 'ba[[?parameter1:tot]]be[[prefix]]bi', true, 'aa')
);
$cmd = new Command_Sql(
    'Command 12',
    'Command with a boolean parameter selected and an internal parameter.',
    'SELECT [[?parameter1]],[[?parameter2]],[[?parameter3]] FROM bb',
    array(
        $param1,
        $param2,
        $param3
    )
);
$category->add_command($cmd);

$cmd = new Command_Sql(
    'Command 13',
    'Command to  try error handling.',
    'SELECT [[?parameter1]] FROM bb',
    new Command_Parameter_Internal(
        'parameter1',
        'vmoodleadminset_test\\CommandWrapper::myTestFunction'
    )
);
$category->add_command($cmd);

$testrpcommand = new Command_Sql(
    'Command 14',
    'Command with a retrieve platforms command.',
    'UPDATE bb SET value = \'aa\' WHERE name = \'cc\'',
    null
);

$param1 = new Command_Parameter(
    'param',
    'text',
    'Name of config directive',
    'local_vmoodle_host_source'
);
$param2 = new Command_Parameter(
    'value',
    'text',
    'Value of config directive',
    'vmoodle'
);
$cmd = new Command_Sql(
    'Retrieve platforms command',
    'Command used to retrieve platforms from their original value.',
    'SELECT id FROM {config} WHERE name = [[?param]] AND value = [[?value]] ',
    array(
        $param1,
        $param2
    )
);

$testrpcommand->attach_rpc_command($cmd);
$category->add_command($testrpcommand);

$category->add_command(new Command_Role_Sync());
$category->add_command(new Command_Role_Capability_Sync());
$category->add_command(new Command_Role_Compare());
$category->add_command(new Command_Upgrade());

// Returning the category.
return $category;