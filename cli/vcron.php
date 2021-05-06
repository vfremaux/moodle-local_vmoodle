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
 * @author Valery fremaux (valery.fremaux@club-internet.fr)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
define('CLI_SCRIPT', true);

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot.'/local/vmoodle/cronlib.php');
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

define('ROUND_ROBIN', 0);
define('LOWEST_POSSIBLE_GAP', 1);
define('RUN_PER_TURN', 1);

global $vcron;

$vcron = new StdClass;
$vcron->activation = 'cli';                         // Choose how individual cron are launched.
$vcron->strategy = ROUND_ROBIN;                     // Choose vcron rotation mode.
$vcron->period = 15 * MINSECS;                      // Used if LOWEST_POSSIBLE_GAP to setup the max gap.
$vcron->timeout = 300;                              // Time out for CURL call to effective cron.
$vcron->trace = $CFG->dataroot.'/vcrontrace.log';   // Trace file where to collect cron outputs.
$vcron->trace_enable = false;                       // Enables tracing.

$config = get_config('local_vmoodle');

$clusters = 1;
if (!empty($config->clusters)) {
    $clusters = $config->clusters;
}

$clusterix = 1;
if (!empty($config->clusterix)) {
    $clusterix = $config->clusterix;
}

if (!$vmoodles = vmoodle_get_vmoodleset($clusters, $clusterix)) {
    die("Nothing to do. No Vhosts");
}

$allvhosts = array_values($vmoodles);

echo "Moodle VCron... start in {$vcron->activation} mode \n";
echo "Previous croned : ".@$config->cron_lasthost."\n";

if ($vcron->strategy == ROUND_ROBIN) {
    $rr = 0;
    foreach ($allvhosts as $vhost) {
        if ($rr == 1) {
            // From this host, fire a set of crons (usually one). @see RUN_PER_TURN
            // If we reach the end of the register, we'll need to wait the next run to continue.
            set_config('cron_lasthost', $vhost->id, 'local_vmoodle');
            $config->cron_lasthost = $vhost->id;
            echo "Round Robin : ".$vhost->vhostname."\n";
            if ($vcron->activation == 'cli') {
                exec_vhost_cron($vhost);
            } else {
                fire_vhost_cron($vhost);
            }
            if ($rr >= RUN_PER_TURN) {
                // This was the last vhost for this run.
                die('Done.');
            }
            $rr++;
        }
        if ($vhost->id == @$config->cron_lasthost) {
            $rr = 1; // Take next one.
        }
    }
    // We were at last. Loop back and take first.
    set_config('cron_lasthost', $allvhosts[0]->id, 'local_vmoodle');
    $config->cron_lasthost = $allvhosts[0]->id;
    echo "Round Robin : ".$vhost->vhostname."\n";
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