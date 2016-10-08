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
 * Describes a category of commands.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @version 2.x
 */
namespace local_vmoodle\commands;

defined('MOODLE_INTERNAL') || die;

class Command_Category {

    /**
     * Category's name
     */
    private $name;

    /**
     * Category's plugin name
     */
    private $pluginname;

    /**
     * Category's commands
     */
    private $commands = array();

    /**
     * Constructor.
     * @param $name string The category's name.
     * @param $plugin_name string The category's file.
     */
    public function __construct($pluginname) {
        global $CFG;

        // Checking category's name.
        $this->name = vmoodle_get_string('pluginname', 'vmoodleadminset_'.$pluginname);
        // Checking category's plugin name.
        if (!is_string($pluginname) || empty($pluginname)) {
            throw new Command_Exception('categorywrongpluginname', $name);
        } else {
            $this->pluginname = $pluginname;
        }
    }

    /**
     * Get category's name.
     * @return string The category's name.
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get category's file.
     * @return string The category's plugin name.
     */
    public function get_plugin_name() {
        return $this->pluginname;
    }

    /**
     * Add a command to the category.
     * @param $command Command Command to add to the category.
     */
    public function add_command(Command $command) {
        $this->commands[] = $command;
        $command->set_category($this);
    }

    /**
     * Get commands.
     * @param $index Index of a command (optional).
     * @return mixed Array of Command or the requested Command.
     */
    public function get_commands($index = null) {
        if (!is_null($index)) {
            if (!array_key_exists($index, $this->commands)) {
                throw new Command_Exception('commandnotexits');
            } else {
                return $this->commands[$index];
            }
        } else {
            return $this->commands;
        }
    }

    /**
     * Get the index of a command.
     * @param $command Command Command.
     * @return mixed Index of the command if is contained by the catogory or false otherwise.
     */
    public function get_command_index(Command $command) {
        $nbrcommands = count($this->commands);
        for ($index = 0; $index < $nbrcommands; $index++) {
            if ($command->equals($this->commands[$index])) {
                return $index;
            }
        }
        return null;
    }

    /**
     * Get ammount of commands
     * @return int The ammont of commands.
     */
    public function count() {
        return count($this->commands);
    }
}