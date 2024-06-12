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
 * This file is a cron microclock script.
 * It will be used as replacement of setting individual
 * cron lines for all virtual instances.
 *
 * Setup this vcron to run at the smallest period possible, as
 * it will schedule all availables vmoodle to be run as required.
 * Note that one activaton of this cron may not always run real crons
 * or may be run more than one cron.
 *
 * If used on a big system with clustering, ensure hostnames are adressed
 * at the load balancer entry and not on physical hosts. Thus the cluster
 * settings of vmoodle will NOT be applicable to the web operated vcron (this script) as
 * the load balancer itself diverts the cron http processes.
 *
 * @package local_vmoodle
 * @category local
 * @author Valery fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
require('../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/mnetcronlib.php');

// extra safety
\core\session\manager::write_close();

// check if execution allowed

// This script is being called via the web, use the global password if set.
if (!empty($CFG->cronremotepassword)) {
    $pass = optional_param('password', '', PARAM_RAW);
    if ($pass != $CFG->cronremotepassword) {
        // wrong password.
        throw new moodle_exception(get_string('cronerrorpassword', 'admin'));
        exit;
    }
}

cron_check_mnet_keys();
