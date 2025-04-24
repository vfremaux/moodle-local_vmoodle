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
 * at the load balancer entry and not on physical hosts
 *
 * @package local_vmoodle
 * @category local
 * @author Valery fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/local/vmoodle/cronlib.php');
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

define('ROUND_ROBIN', 0);
define('LOWEST_POSSIBLE_GAP', 1);
define('RUN_PER_TURN', 16);

global $vcron;

$vcron = new StdClass;
$vcron->activation = 'cli';                         // Choose how individual cron are launched.
$vcron->strategy = ROUND_ROBIN;                     // Choose vcron rotation mode.
$vcron->period = 15 * MINSECS;                      // Used if LOWEST_POSSIBLE_GAP to setup the max gap.
$vcron->timeout = 300;                              // Time out for CURL call to effective cron.
$vcron->trace = $CFG->dataroot.'/vcrontrace.log';   // Trace file where to collect cron outputs.
$vcron->trace_enable = false;                       // Enables tracing.

$config = get_config('local_vmoodle');

$cronlockfactory = \core\lock\lock_config::get_lock_factory('cron');
if (!$vcronlock = $cronlockfactory->get_lock("vcron_loop", 0)) {
    die("Vcron is already locked and running\n");
}

if (local_vmoodle_supports_feature('vcron/clustering')) {
    include_once($CFG->dirroot.'/local/vmoodle/pro/localprolib.php');
    $vmoodles = \local_vmoodle\local_pro_manager::vmoodle_get_vmoodleset();
} else {
    $vmoodles = vmoodle_get_vmoodleset();
}

if (empty($vmoodles)) {
    $vcronlock->release();
    die("Nothing to do. No Vhosts\n");
}

$allvhosts = array_values($vmoodles);

echo "Moodle VCron... start in {$vcron->activation} mode \n";
if (!empty($config->cron_lasthost)) {
    echo "Previous croned : ".$config->cron_lasthost." ".$vmoodles[$config->cron_lasthost]->vhostname."\n";
}

if ($vcron->strategy == ROUND_ROBIN) {
    if (!empty($config->cron_lasthost)) {
        $vhost = array_shift($allvhosts);
        while ($vhost->id <= $config->cron_lasthost) {
            if (empty($allvhosts)) {
                echo "Round Robin : Reloading hostlist.\n";
                $allvhosts = array_values($vmoodles);
                break;
            }
            $vhost = array_shift($allvhosts);
        }
    }
    for ($rr = 0; $rr < RUN_PER_TURN; $rr++) {

        // If $allhosts has been consumed, reload it.
        if (empty($allvhosts)) {
            echo "Round Robin : Reloading hostlist.\n";
            $allvhosts = array_values($vmoodles);
        }

        // Start consuming an processing hosts.
        $vhost = array_shift($allvhosts);
        echo "Round Robin : ".$vhost->vhostname."\n";
        if ($vcron->activation == 'cli') {
            exec_vhost_cron($vhost);
        } else {
            fire_vhost_cron($vhost);
        }
        // Mark the host as visited.
        set_config('cron_lasthost', $vhost->id, 'local_vmoodle');
        $config->cron_lasthost = $vhost->id;
    }

} else if ($vcron->strategy == LOWEST_POSSIBLE_GAP) {
    // First make measurement of cron period.
    if (empty($config->vcrontickperiod)) {
        set_config('vcrontime', time(), 'local_vmoodle');
        $vcronlock->release();
        die();
    }
    set_config('vcrontickperiod', time() - $config->vcrontime, 'local_vmoodle');
    $hostsperturn = max(1, $vcron->period / $config->vcrontickperiod * count($allvhosts));
    $i = 0;
    foreach ($allvhosts as $vhost) {
        if ((time() - $vhost->lastcron) > $vcron->period) {
            if ($vcron->activation == 'cli') {
                exec_vhost_cron($vhost);
            } else {
                fire_vhost_cron($vhost);
            }
            $i++;
            if ($i >= $hostsperturn) {
                return;
            }
        }
    }
}

$vcronlock->release();
echo "VCron Done. \n";