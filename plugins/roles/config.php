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
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

namespace vmoodleadminset_roles;
Use \local_vmoodle\commands\Command_Category;

// Creating category
$category = new Command_Category('roles');

// Adding commands
$category->add_command(new Command_Role_Sync());
$category->add_command(new Command_Role_Compare());
$category->add_command(new Command_Role_Capability_Sync());
$category->add_command(new Command_Role_Allow_Sync());
$category->add_command(new Command_Role_Allow_Compare());

// Returning the category
return $category;