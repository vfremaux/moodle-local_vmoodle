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
 * Describes meta-administration plugin's command parameter.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace local_vmoodle\commands;

defined('MOODLE_INTERNAL') || die;

class Command_Parameter {

    /**
     * Types of parameter allowed
     */
    const PARAMETER_TYPES_ALLOWED = 'boolean|enum|menum|mhenum|senum|text|ltext|internal';

    /**
     * Parameter's name
     */
    protected $name;

    /**
     * Parameter's type
     */
    protected $type; // Types : boolean | enum | menum | mhenum | senum | text | ltext | internal.

    /**
     * Parameter's description : uses for label or choices of enum parameter
     */
    protected $description;

    /**
     * Parameter's description : used to add attributes to the form input
     */
    protected $attributes;

    /**
     * Parameter's default value (optional)
     */
    protected $default;

    /**
     * Parameter's choices (in case of enum)
     */
    protected $choices;

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
    public function __construct($name, $type, $description, $default = null, $choices = null, $attrs = null) {
        global $SESSION;

        // Checking parameter's name.
        if (empty($name)) {
            unset($SESSION->vmoodle_sa['command']);
            throw new Command_Exception('parameteremptyname');
        } else {
            $this->name = $name;
        }

        // Checking parameter's type.
        if (!in_array($type, explode('|', self::PARAMETER_TYPES_ALLOWED))) {
            unset($SESSION->vmoodle_sa['command']);
            throw new Command_Exception('parameterforbiddentype', $this->name);
        } else {
            $this->type = $type;
        }

        // Checking parameter's description.
        if ($this->type != 'internal' && empty($description)) {
            unset($SESSION->vmoodle_sa['command']);
            throw new Command_Exception('parameteremptydescription', $this->name);
        } else {
            $this->description = $description;
        }

        // Checking parameter's values.
        if (($this->type == 'enum' || $this->type == 'menum' || $this->type == 'mhenum') && !is_array($choices)) {
            unset($SESSION->vmoodle_sa['command']);
            throw new Command_Exception('parameterallowedvaluesnotgiven', $this->name);
        } else {
            $this->choices = $choices;
        }

        $this->attributes = $attrs;

        // Checking parameter's default value.
        if (!is_null($default) && $this->type == 'enum' && (!is_string($default) || !array_key_exists($default, $this->choices))) {
            unset($SESSION->vmoodle_sa['command']);
            throw new Command_Exception('parameterwrongdefaultvalue', $this->name);
        } else {
            $this->default = $default;
        }
    }

    /**
     * Get parameter's name.
     * @return string Parameter's name.
     */
    public function get_name() {
        return $this->name;
    }

    /**
     * Get parameter's type.
     * @return string Parameter's type.
     */
    public function get_type() {
        return $this->type;
    }

    /**
     * Get parameter's type.
     * @return string Parameter's attributes.
     */
    public function get_attributes() {
        return $this->attributes;
    }

    /**
     * Get parameter's description.
     * @return mixed Parameter's description.
     */
    public function get_description() {
        return $this->description;
    }

    /**
     * Get parameter's default value.
     * @return mixed Parameter's default.
     */
    public function get_default() {
        return $this->default;
    }

    /**
     * Get parameter's choices (in case of enum).
     * @return array Parameter's choices.
     */
    public function get_choices() {
        return $this->choices;
    }

    /**
     * Get parameter's value.
     * @return mixed Parameter's value.
     * @throws Command_Exception
     */
    public function get_value($readonly = false) {
        global $SESSION;

        if (is_null($this->value)) {
            if (!$readonly) {
                unset($SESSION->vmoodle_sa['command']);
                throw new Command_Exception('parametervaluenotdefined', $this->name);
            }
        }
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