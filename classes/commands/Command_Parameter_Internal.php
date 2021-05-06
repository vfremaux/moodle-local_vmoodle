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
 * This kind of parameters retrieve his value from a system function (not from an user form).
 *
 * @package     local_vmoodle
 * @category    local
 * @author      Bruce Bujon (bruce.bujon@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace local_vmoodle\commands;

defined('MOODLE_INTERNAL') || die;

class Command_Parameter_Internal extends Command_Parameter {
    /** The parameter's function */
    private $fct;
    /** The parameters of function's parameters */
    private $parameters;

    /**
     * Constructor.
     * @param $name string Parameter's name.
     * @param $function string Parameter's function.
     * @param $parameters mixed Parameters of parameter's function.
     */
    public function __construct($name, $function, $parameters = null) {

        // Calling parent constructor.
        parent::__construct($name, 'internal', null, null);

        // Checking parameter's function.
        if (strpos($function, '::') !== false) {
            list($classname, $method) = explode('::', $function);
            if (!method_exists($classname, $method)) {
                $params = (object) array('function_name' => $function, 'parameter_name' => $this->name);
                throw new Command_Exception('parameterinternalfunctionnotexists', $params);
            }
        } else {
            if (!function_exists($function)) {
                $params = (object) array('function_name' => $function, 'parameter_name' => $this->name);
                throw new Command_Exception('parameterinternalfunctionnotexists', $params);
            }
        }

        $this->fct = $function;

        // Setting parameters.
        if (!(is_array($parameters) || is_null($parameters))) {
            $parameters = array($parameters);
        }
        $this->parameters = $parameters;
    }

    /**
     * Retrieve the parameter's value.
     * @param mixed $datas Values of Command's parameters (optional).
     * @throws Command_Exception
     */
    public function retrieve_value($datas = null) {
        global $vmcommandconstants;

        // Looking for parameters to replace.
        if (!is_null($this->parameters)) {
            foreach ($this->parameters as $parameter) {
                preg_match_all(Command::PLACEHOLDER, $parameter, $vars);
                // Check if parameters and constants are given.
                foreach ($vars[2] as $key => $var) {
                    if (empty($vars[1][$key]) && !array_key_exists($var, $vmcommandconstants)) {
                        $params = (object)array('constant_name' => $var, 'parameter_name' => $this->name);
                        throw new Command_Exception('parameterinternalconstantnotgiven', $params);
                    } else if (!empty($vars[1][$key]) && !array_key_exists($var, $datas)) {
                        $params = (object)array('parameter_need' => $var, 'parameter_name' => $this->name);
                        throw new Command_Exception('parameterinternalparameternotgiven', $params);
                    }
                }
            }
            // Replace placeholders by theirs values.
            $this->datas = $datas;
            $func = array($this, '_replace_parameters_values');
            $this->parameters = preg_replace_callback(Command::PLACEHOLDER, $func, $this->parameters);
            unset($this->datas);
        }

        // Call parameter's function with parameters.
        try {
            $this->value = call_user_func_array($this->fct, $this->parameters);
        } catch (Exception $exception) {
            $message = $exception->getMessage();

            $wom = get_string('withoutmessage', 'local_vmoodle');
            $wm = get_string('withmessage', 'local_vmoodle', $message);
            $params = (object) array('function_name' => $this->fct,
                                     'message' => empty($message) ? $wom : $wm);
            throw new Command_Exception('parameterinternalfunctionfailed', $params);
        }
    }

    /**
     * Bind the replace_parameters_values function to create a callback.
     * Indirect use off the function
     * @param array $matches The placeholders found.
     * @return string|array The parameters' values.
     */
    private function _replace_parameters_values($matches) {
        return replace_parameters_values($matches, $this->datas);
    }
}