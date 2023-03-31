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
 * This is a minimalist command parameter class that allows giving commands parameters in CLI
 * situations... It has only a name and a value.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace local_vmoodle\commands;

defined('MOODLE_INTERNAL') || die;

class Cli_Command_Parameter {

    /**
     * Parameter's name
     */
    protected $name;

    /**
     * Parameter's value
     */
    protected $value = null;

    /**
     * Constructor.
     * @param $name string Parameter's name.
     * @param $type string Parameter's type.
     * @param $description mixed Parameter's description.
     * @param $default string Parameter's defaut value (optional).
     * @param $choices array Parameter's choices (in case of enum).
     */
    public function __construct($name, $value) {
        $this->name = $name;
        $this->value = $value;
    }

    /**
     * Get parameter's name.
     * @return string Parameter's name.
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get parameter's value.
     * @return mixed Parameter's value.
     * @throws Command_Exception
     */
    public function get_value() {
        return $this->value;
    }

    /**
     * Set parameter's value.
     * @param $value mixed Parameter's value.
     */
    public function set_value($value) {
        $this->value = $value;
    }
}