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
 * Exception about Command.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

namespace local_vmoodle\commands;

defined('MOODLE_INTERNAL') || die;

class Command_Exception extends \Exception {

    /**
     * Constructor with localized message.
     * @param $identifier string The key identifier for the localized string.
     * @param $a mixed An object, string or number that can be used (optional).
     */
    public function __construct($identifier, $a = null) {
        global $CFG;

        $message = '';
        if ($CFG->debug == DEBUG_DEVELOPER) {
            $message = '<br/>';
            $trace = debug_backtrace();
            // array_shift($trace);
            if ($tracepoint = array_shift($trace)) {
                $f = @$tracepoint['file'];
                $l = @$tracepoint['line'];
                $func = @$tracepoint['function'];
                $message .= "\nAt : {$f} line {$l} calling to {$func}";
            } else {
                $message .= "\nAt : <unknown file> line <unknown line> calling to <unknown function> ";
            }

            $i = 1;
            while ($tracepoint = array_shift($trace)) {
                $f = @$tracepoint['file'];
                $l = @$tracepoint['line'];
                $func = @$tracepoint['function'];
                $message .= "<br/>\n$i) : {$f} line {$l} calling to {$func}";
                $i++;
            }
        }

        parent::__construct(get_string($identifier, 'local_vmoodle', $a).$message);
    }
}