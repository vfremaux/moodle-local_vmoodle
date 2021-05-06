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
 * Remote Control objects for Moodle nework configuration.
 *
 * @package    local
 * @subpackage local_vmoodle
 * @copyright  2013 Valery Fremaux {@link http://www.mylearningfactory.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/message/lib.php');
require_once($CFG->dirroot.'/repository/lib.php');
require_once($CFG->libdir.'/questionlib.php');
require_once($CFG->libdir.'/portfoliolib.php');
require_once($CFG->dirroot.'/mod/assign/adminlib.php');
require_once($CFG->dirroot.'/mod/assign/locallib.php');

abstract class plugin_remote_control {

    // plugin type.
    protected $type;

    // plugin name.
    protected $plugin;

    /**
     * @param string $fqplugin a fully qualified plugin with type
     *
     */
    public function __construct($type, $plugin) {
        $this->type = $type;
        $this->plugin = $plugin;
    }

    public abstract function action($action);
    public abstract function is_enabled();
}

class mod_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('mod', $plugin);
    }

    public function action($action) {
        global $DB;

        if (!$module = $DB->get_record('modules', array('name' => $this->plugin))) {
            return get_string('moduledoesnotexist', 'error');
        }

        switch ($action) {

            case 'enable':
                $DB->set_field('modules', 'visible', '1', array('id' => $module->id)); // Show main module.
                // Get the previous saved visible state for the course module.
                $DB->set_field('course_modules', 'visible', '1', array('visibleold' => 1, 'module' => $module->id));
                /*
                 * clear the course modinfo cache for courses
                 * where we just made something visible
                 */
                $sql = "
                    SELECT
                        DISTINCT course,
                        course
                    FROM
                        {course_modules}
                    WHERE
                        visible = 1 AND
                        module = ?
                ";
                if ($courseids = $DB->get_records_sql($sql, array($module->id))) {
                    foreach (array_keys($courseids) as $cid) {
                        rebuild_course_cache($cid);
                    }
                }
                break;

            case 'disable':
                $DB->set_field('modules', 'visible', '0', array('id' => $module->id)); // Hide main module.
                /*
                 * Remember the visibility status in visibleold
                 * and hide...
                 */
                $sql = "
                    UPDATE
                        {course_modules}
                    SET 
                        visibleold = visible,
                        visible = 0
                    WHERE
                        module = ?
                ";
                $DB->execute($sql, array($module->id));

                /*
                 * clear the course modinfo cache for courses
                 * where we just deleted something
                 */
                $sql = "
                    SELECT
                        DISTINCT course,
                        course
                    FROM
                        {course_modules}
                    WHERE
                        visibleold = 1 AND
                        module = ?
                ";
                if ($courseids = $DB->get_records_sql($sql, array($module->id))) {
                    foreach (array_keys($courseids) as $cid) {
                        rebuild_course_cache($cid);
                    }
                }
                break;
        }
        return 0;
    }

    public function is_enabled() {
        global $DB;

        if (!$module = $DB->get_record('modules', array('name' => $this->plugin))) {
            return null;
        }

        return $DB->get_field('modules', 'visible', array('id' => $module->id)); // Hide main module.
    }
}

class block_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('block', $plugin);
    }

    public function action($action) {
        global $DB;

        if (!$block = $DB->get_record('block', array('name' => $this->plugin))) {
            return get_string('blockdoesnotexist', 'error');
        }

        switch ($action) {

            case 'enable':
                $DB->set_field('block', 'visible', '1', array('id' => $block->id)); // Show main module.
                break;

            case 'disable':
                $DB->set_field('block', 'visible', '0', array('id' => $block->id)); // Hide main module.
                break;
        }
        return 0;
    }

    public function is_enabled() {
        global $DB;

        if (!$block = $DB->get_record('block', array('name' => $this->plugin))) {
            return null;
        }

        return $DB->get_field('block', 'visible', array('id' => $block->id)); // Hide main module.
    }
}

class local_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('local', $plugin);
    }

    public function action($action) {
        global $CFG;

        if (file_exists($CFG->dirroot.'/local/'.$this->plugin.'/lib.php')) {
            require_once($CFG->dirroot.'/local/'.$this->plugin.'/lib.php');
        }

        $enablefuncname = 'local_'.$this->plugin.'_enable';
        $disablefuncname = 'local_'.$this->plugin.'_disable';

        switch ($action) {

            case 'enable':
                if (function_exists($enablefuncname)) {
                    $enablefuncname();
                    return 0;
                }

            case 'disable':
                if (function_exists($disablefuncname)) {
                    $disablefuncname();
                    return 0;
                }
        }
        return 1; // No command available.
    }

    public function is_enabled() {
        global $CFG;

        if (file_exists($CFG->dirroot.'/local/'.$this->plugin.'/lib.php')) {
            require_once($CFG->dirroot.'/local/'.$this->plugin.'/lib.php');
        }

        $funcname = 'local_'.$plugin.'_enabled';

        if (function_exists($funcname)) {
            return $funcname();
        }
        return false;
    }
}

class message_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('message', $plugin);
    }

    public function action($action){
        global $DB;

        if (!$processor = $DB->get_record('message_processors', array('name' => $this->plugin))) {
            return get_string('outputdoesnotexist', 'message');
        }

        switch($action){
            case 'enable':
                $DB->set_field('message_processors', 'enabled', '1', array('id' => $processor->id));      // Enable output.
                break;
            case 'disable':
                $DB->set_field('message_processors', 'enabled', '0', array('id' => $processor->id));      // Disable output.
                break;
        }
        return 0;
    }

    public function is_enabled() {
        global $DB;

        if (!$processor = $DB->get_record('message_processors', array('name' => $this->plugin))) {
            return null;
        }

        return $DB->get_field('message_processors', 'enabled', array('id' => $processor->id));
    }
}

class filter_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('filter', $plugin);
    }

    public function action($action) {

        switch ($action) {
            case 'enable': {
                $newstate = TEXTFILTER_ON;
                filter_set_global_state($this->plugin, $newstate);
                break;
            }

            case 'available': {
                $newstate = TEXTFILTER_OFF;
                filter_set_global_state($this->plugin, $newstate);
                break;
            }

            case 'disable': {
                $newstate = TEXTFILTER_DISABLED;
                filter_set_global_state($this->plugin, $newstate);
                break;
            }
        }
        return 0;

    }

    /**
     * Returns the active value of the filter.
     */
    public function is_enabled() {
        global $DB;

        $context = context_system::instance();
        $params = array('contextid' => $context->id, 'filter' => $this->plugin);
        $state = $DB->get_field('filter_active', 'active', $params, 'sortorder', 'filter,active,sortorder');
        switch ($state) {
            case -1 :
                return 'available';

            case 1 :
                return 'enabled';

            default :
                return 'disabled';
        }
    }
}

class repository_remote_control extends plugin_remote_control{

    public function __construct($plugin) {
        return parent::__construct('repository', $plugin);
    }

    public function action($state){

        $repositorytype = repository::get_type_by_typename($this->plugin);
        if (empty($repositorytype)) {
            return get_string('invalidplugin', 'repository', $this->plugin);
        }

        switch($action){
            case 'enable':
                $repositorytype->update_visibility(true);
                break;
            case 'disable':
                $repositorytype->update_visibility(false);
                break;
        }
        return 0;
    }

    public function is_enabled() {
        global $DB;

        $repositorytype = repository::get_type_by_typename($this->plugin);
        if (empty($repositorytype)) {
            return null;
        }

        return $repositorytype->get_visible();
    }
}

/*
class plagiarism_remote_control extends plugin_remote_control{
    function action($state){
    }
}
*/

class qbehaviour_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('qbehaviour', $plugin);
    }

    public function action($state) {

        $behaviours = get_plugin_list('qbehaviour');

        $pm = core_plugin_manager::instance();
        $sql = "
            SELECT
                behaviour,
                COUNT(1)
            FROM 
                {question_attempts}
            GROUP BY
                behaviour
        ";
        $counts = $DB->get_records_sql_menu($sql);
        $needed = array();
        $archetypal = array();
        foreach ($behaviours as $behaviour => $foobar) {
            if (!array_key_exists($behaviour, $counts)) {
                $counts[$behaviour] = 0;
            }
            $needed[$behaviour] = ($counts[$behaviour] > 0) ||
                    $pm->other_plugins_that_require('qbehaviour_' . $behaviour);
            $archetypal[$behaviour] = question_engine::is_behaviour_archetypal($behaviour);
        }

        $config = get_config('question');
        if (!empty($config->disabledbehaviours)) {
            $disabledbehaviours = explode(',', $config->disabledbehaviours);
        } else {
            $disabledbehaviours = array();
        }

        if (!isset($behaviours[$this->plugin])) {
            return get_string('unknownbehaviour', 'question', $this->plugin);
        }

        switch ($action) {
            case 'enable': {
                if (!$archetypal[$this->plugin]) {
                    return get_string('cannotenablebehaviour', 'question', $this->plugin);
                }

                if (($key = array_search($this->plugin, $disabledbehaviours)) !== false) {
                    unset($disabledbehaviours[$key]);
                    set_config('disabledbehaviours', implode(',', $disabledbehaviours), 'question');
                }
                break;
            }

            case 'disable': {
                if (array_search($disable, $disabledbehaviours) === false) {
                    $disabledbehaviours[] = $disable;
                    set_config('disabledbehaviours', implode(',', $disabledbehaviours), 'question');
                }
                break;
            }
        }
        return 0;
    }

    public function is_enabled() {

        $config = get_config('question');
        if (!empty($config->disabledbehaviours)) {
            $disabledbehaviours = explode(',', $config->disabledbehaviours);
        } else {
            $disabledbehaviours = array();
        }

        return !in_array($this->plugin, $disabledbehaviours);
    }
}

class qtype_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('qtype', $plugin);
    }

    public function action($action) {

        $qtypes = question_bank::get_all_qtypes();

        if (!isset($qtypes[$this->plugin])) {
            return get_string('unknownquestiontype', 'question', $this->plugin);
        }

        switch ($action) {
            case 'enable':
                unset_config($this->plugin . '_disabled', 'question');
                break;
            case 'disable':
                set_config($this->plugin . '_disabled', 1, 'question');
                break;
        }

        return 0;
    }

    public function is_enabled() {
        return get_config($this->plugin . '_disabled', 'question');
    }
}

class portfolio_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('portfolio', $plugin);
    }

    public function action($action) {

        $instance = portfolio_instance($this->plugin);
        $current = $instance->get('visible');
        if (empty($current) && $instance->instance_sanity_check()) {
            return get_error('cannotsetvisible', 'portfolio');
        }

        switch ($action) {
            case 'enable': {
                $visible = 1;
                break;
            }

            case 'disable': {
                $visible = 0;
                break;
            }
        }

        $instance->set('visible', $visible);
        $instance->save();
        return 0;
    }

    function is_enabled() {
        $instance = portfolio_instance($this->plugin);
        return $instance->get('visible');
    }
}

class auth_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('auth', $plugin);
    }

    public function action($action) {
        global $CFG;

        get_enabled_auth_plugins(true); // fix the list of enabled auths

        $auth = get_config('moodle', 'auth');
        $auth = preg_replace('/,$/', '', $auth);
        $auth = preg_replace('/^,/', '', $auth);
        if (empty($auth)) {
            $auth = 'manual';
        }

        if (empty($auth)) {
            $authsenabled = array();
        } else {
            $authsenabled = explode(',', $auth);
        }

        if (!exists_auth_plugin($this->plugin)) {
            return get_string('pluginnotinstalled', 'auth', $this->plugin);
        }

        switch ($action) {
            case 'enable':
                // Add to enabled list.
                if (!in_array($this->plugin, $authsenabled)) {
                    $authsenabled[] = $this->plugin;
                    $authsenabled = array_unique($authsenabled);
                    $newauth = implode(',', $authsenabled);
                    set_config('auth', $newauth);
                }
                break;

            case 'disable':
                // Remove from enabled list.
                $key = array_search($this->plugin, $authsenabled);
                if ($key !== false) {
                    unset($authsenabled[$key]);
                    set_config('auth', implode(',', $authsenabled));
                }

                if ($this->plugin == $CFG->registerauth) {
                    set_config('registerauth', '');
                }
                break;
        }

        \core\session\manager::gc(); // remove stale sessions
        return 0;
    }

    public function is_enabled() {
        global $CFG;

        get_enabled_auth_plugins(true); // Fix the list of enabled auths.
        if (empty($CFG->auth)) {
            $authsenabled = array();
        } else {
            $authsenabled = explode(',', $CFG->auth);
        }

        return in_array($this->plugin, $authsenabled);
    }
}

class format_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('format', $plugin);
    }

    public function action($action) {

        $allplugins = \core_plugin_manager::instance()->get_plugins();
        $formatplugins = $allplugins['format'];

        if (!isset($formatplugins[$this->plugin])) {
            return get_string('courseformatnotfound', 'error', $this->plugin);
        }

        switch ($action) {
            case 'enable': {
                if (!$this->is_enabled()) {
                    unset_config('disabled', 'format_'. $this->plugin);
                }
                break;
            }

            case 'disable': {
                if ($this->is_enabled()) {
                    if (get_config('moodlecourse', 'format') === $this->plugin) {
                        debug_trace("Cannot disable course format $this->plugin");
                        return get_string('cannotdisableformat', 'error');
                    }
                    debug_trace('disabling');
                    set_config('disabled', 1, 'format_'. $this->plugin);
                }
                break;
            }
        }

        return 0;
    }

    public function is_enabled() {
        return !get_config('format_'. $this->plugin, 'disabled');
    }
}

class editor_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('editor', $plugin);
    }

    public function action($action) {

        // Get currently installed and enabled auth plugins.
        $available_editors = editors_get_available();
        if (empty($available_editors[$this->plugin])) {
            return get_string('unavailableeditor');
        }

        $active_editors = explode(',', $CFG->texteditors);
        foreach ($active_editors as $key => $active) {
            if (empty($available_editors[$active])) {
                unset($active_editors[$key]);
            }
        }

        switch ($action) {
            case 'enable': {
                // Add to enabled list.
                if (!in_array($this->plugin, $active_editors)) {
                    $active_editors[] = $this->plugin;
                    $active_editors = array_unique($active_editors);
                }
                break;
            }

            case 'disable': {
                // Remove from enabled list.
                $key = array_search($this->plugin, $active_editors);
                unset($active_editors[$key]);
                break;
            }
        }

        return 0;
    }

    public function is_enabled() {
        $active_editors = explode(',', $CFG->texteditors);
        return in_array($this->plugin, $active_editors);
    }
}

class enrol_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('enrol', $plugin);
    }

    public function action($action) {

        $enabled = enrol_get_plugins(true);
        $all     = enrol_get_plugins(false);
        $syscontext = context_system::instance();

        switch ($action) {
            case 'enable': {
                if (!isset($all[$this->plugin])) {
                    break;
                }
                $enabled = array_keys($enabled);
                $enabled[] = $this->plugin;
                set_config('enrol_plugins_enabled', implode(',', $enabled));
                // Resets all enrol caches.
                $syscontext->mark_dirty();
                break;
            }

            case 'disable': {
                unset($enabled[$this->plugin]);
                set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));
                $syscontext->mark_dirty(); // resets all enrol caches
                break;
            }
        }

        return 0;
    }

    public function is_enabled() {
        $active_enrols = explode(',', get_config('enrol_plugins_enabled'));
        return in_array($this->plugin, $active_enrols);
    }
}

class assignsubmission_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('assignsubmission', $plugin);
    }

    public function action($action) {

        $pluginmanager = new assign_plugin_manager('assignsubmission');
        switch ($action) {
            case 'enable': {
                $pluginmanager->show_plugin($this->plugin);
                break;
            }

            case 'disable': {
                $pluginmanager->hide_plugin($this->plugin);
                break;
            }
        }

        return 0;
    }

    public function is_enabled() {
        return !get_config('submission_' . $this->plugin, 'disabled');
    }
}


class assignfeedback_remote_control extends plugin_remote_control {

    public function __construct($plugin) {
        return parent::__construct('assignfeedback', $plugin);
    }

    public function action($action) {

        $pluginmanager = new assign_plugin_manager('assignfeedback');

        switch ($action) {
            case 'enable':
                $pluginmanager->show_plugin($this->plugin);
                break;
            case 'disable':
                $pluginmanager->hide_plugin($this->plugin);
                break;
        }

        return 0;
    }

    public function is_enabled() {
        return !get_config('feedback_' . $this->plugin, 'disabled');
    }
}
