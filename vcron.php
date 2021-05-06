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
 * @author Valery fremaux (valery.fremaux@club-internet.fr)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
require('../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/cronlib.php');

define('ROUND_ROBIN', 0);
define('LOWEST_POSSIBLE_GAP', 1);
define('RUN_PER_TURN', 1);

global $vcron;

$vcron = new StdClass;
$vcron->activation = 'cli';                         // Choose how individual cron are launched (cli or curl).
$vcron->strategy = ROUND_ROBIN ;                    // Choose vcron rotation mode.
$vcron->period = 15 * MINSECS ;                     // Used if LOWEST_POSSIBLE_GAP to setup the max gap.
$vcron->timeout = 300;                              // Time out for CURL call to effective cron.
$vcron->trace = $CFG->dataroot.'/vcrontrace.log';   // Trace file where to collect cron outputs.
$vcron->traceenable = false;                        // Enables tracing.

if (!$vmoodles = $DB->get_records('local_vmoodle', array('enabled' => 1))) {
    die("Nothing to do. No Vhosts");
}

$allvhosts = array_values($vmoodles);

$config = get_config('local_vmoodle');

mtrace("<pre>");
mtrace("Moodle VCron... start");
mtrace("Previous croned : ".@$config->cron_lasthost);

if ($vcron->strategy == ROUND_ROBIN) {
    $rr = 0;
    foreach ($allvhosts as $vhost) {
        // Seek for previous host to continue triggering.
        if ($rr == 1) {
            set_config('cron_lasthost', $vhost->id, 'local_vmoodle');
            mtrace("Round Robin : ".$vhost->vhostname);
            if ($vcron->activation == 'cli') {
                exec_vhost_cron($vhost);
            } else {
                fire_vhost_cron($vhost);
            }
            die('Done.');
        }
        if ($vhost->id == @$config->cron_lasthost) {
            $rr = 1; // Take next one.
        }
    }
    // We were at last. Loop back and take first.
    set_config('cron_lasthost', $allvhosts[0]->id, 'local_vmoodle');
    mtrace("Round Robin : ".$vhost->vhostname);
    if ($vcron->activation == 'cli') {
        exec_vhost_cron($allvhosts[0]);
    } else {
        fire_vhost_cron($allvhosts[0]);
    }

} else if ($vcron->strategy == LOWEST_POSSIBLE_GAP) {
    // First make measurement of cron period.
    if (empty($config->vcrontickperiod)) {
        set_config('vcrontime', time(), 'local_vmoodle');
        return;
    }
    set_config('vcrontickperiod', time() - $config->vcrontime);
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