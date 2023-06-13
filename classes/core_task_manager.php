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
 * Web cron single task
 *
 * This script runs a single scheduled task from the web UI.
 *
 * @package tool_task
 * @copyright 2016 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local\vmoodle;

defined('MOODLE_INTERNAL') || die();

class core_task_manager extends \core\task\manager {

    /**
     * Executes a cron from web invocation using PHP CLI.
     *
     * @param \core\task\task_base $task Task that be executed via CLI.
     * @return bool
     * @throws \moodle_exception
     */
    public static function run_from_cli(\core\task\task_base $task):bool {
        global $CFG;

        if (!self::is_runnable()) {
            $redirecturl = new \moodle_url('/admin/settings.php', ['section' => 'systempaths']);
            throw new \moodle_exception('cannotfindthepathtothecli', 'core_task', $redirecturl->out());
        } else {
            // Shell-escaped path to the PHP binary.
            $phpbinary = escapeshellarg(self::find_php_cli_path());

            // Shell-escaped path CLI script.
            $pathcomponents = [$CFG->dirroot, 'local', 'vmoodle', 'cli', 'schedule_task.php'];
            $scriptpath     = escapeshellarg(implode(DIRECTORY_SEPARATOR, $pathcomponents));

            // Shell-escaped task name.
            $classname = get_class($task);
            // CHANGE+.
            // Adds vmoodle routing when invoking the scheduled task.
            $wwwroot = $CFG->wwwroot;
            $taskarg   = '--host='.escapeshellarg("{$wwwroot}").' --execute='.escapeshellarg("{$classname}");
            // CHANGE-.

            // Build the CLI command.
            $command = "{$phpbinary} {$scriptpath} {$taskarg}";

            // Execute it.
            passthru($command);
        }

        return true;
    }
} 