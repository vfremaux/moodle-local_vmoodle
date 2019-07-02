<?php

/**
 * Scheduled background task for sending automated appointment reminders
 *
 * @package    local_vmoodle
 * @copyright  2019 Valery Fremaux <valery.fremaux@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_vmoodle\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/vmoodle/mnetcronlib.php');

class check_mnet_keys extends \core\task\scheduled_task {

    public function get_name() {
        return get_string('checkmnetkeys', 'local_vmoodle');
    }

    public function execute() {
        cron_check_mnet_keys();
    }

}