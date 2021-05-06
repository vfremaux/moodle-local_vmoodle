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
 * Description of assisted commands for role purpose.
 *
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
defined('MOODLE_INTERNAL') || die;

use \local_vmoodle\commands\Command_Category;
use \vmoodleadminset_plugins\Command_Plugin_Set_State;
use \vmoodleadminset_plugins\Command_Plugin_Set_Filter_State;
use \vmoodleadminset_plugins\Command_Plugins_Sync;
use \vmoodleadminset_plugins\Command_Plugins_Compare;

// Creating category.
$category = new Command_Category('plugins');

// Adding commands.
$category->add_command(new Command_Plugin_Set_State());
$category->add_command(new Command_Plugin_Set_Filter_State());
$category->add_command(new Command_Plugins_Sync());
$category->add_command(new Command_Plugins_Compare());

// Returning the category.
return $category;