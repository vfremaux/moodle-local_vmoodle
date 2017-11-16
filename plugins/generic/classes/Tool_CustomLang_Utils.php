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
 * Publiscizes some usefull core methods.
 *
 * @package    local_vmoodle
 * @subpackage local
 * @copyright  2010 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace vmoodleadminset_generic;

use \tool_customlang_utils;
use \StdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/admin/tool/customlang/locallib.php');

class VMoodle_CustomLang_Utils extends tool_customlang_utils {

    public static function get_localpack_location($lang) {
        return parent::get_localpack_location($lang);
    }

    public static function get_component_filename($component) {
        // Normalise name
        $component = str_replace('/', '_', $component);
        return parent::get_component_filename($component);
    }

    public static function get_installed_langs() {
        $langs = get_string_manager()->get_list_of_translations(true);
        return array_keys($langs);
    }
}