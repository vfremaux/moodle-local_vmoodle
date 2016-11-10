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

defined('MOODLE_INTERNAL') || die;

/**
 * fire a cron URL using CURL.
 *
 *
 */
function fire_vhost_cron($vhost) {

    $ch = curl_init($vhost->vhostname.'/admin/cron.php');

    curl_setopt($ch, CURLOPT_TIMEOUT, $vcron->TIMEOUT);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Moodle');
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml charset=UTF-8"));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $timestampsend = time();
    $rawresponse = curl_exec($ch);
    $timestampreceive = time();

    if ($rawresponse === false) {
        $error = curl_errno($ch) .':'. curl_error($ch);
        echo "VCron started on $vhost->vhostname : $timestampsend\n";
        echo "VCron Error : $error \n";
        echo "VCron stop on $vhost->vhostname : $timestampreceive\n#################\n\n";
        return false;
    }

    vcron_process_result($vhost, $rawresponse);
}

/**
 * fire a cron URL using cli exec
 *
 *
 */
function exec_vhost_cron($vhost) {
    global $CFG;

    $cmd = 'php "'.$CFG->dirroot.'/local/vmoodle/cli/cron.php" --host='.$vhost->vhostname;

    exec($cmd, $rawresponse);

    vcron_process_result($vhost, $rawresponse);
}

/**
 * Common post processing return of a serverside or web cron evocation.
 */
function vcron_process_result($vhost, $rawresponse) {
    global $vcron, $CFG;

    if ($vcron->TRACE_ENABLE) {
        $crontrace = fopen($vcron->TRACE, 'a');
    }

    // A centralised per host trace for vcron monitoring.
    if (!empty($CFG->vlogfilepattern)) {
        $logfile = str_replace('%%VHOSTNAME%%', $vhost->vhostname, $CFG->vlogfilepattern);
        $logfile = preg_replace('#https?://#', '', $logfile);
        if ($log = fopen($logfile, 'w')) {
            fputs($log, $rawresponse);
            fclose($log);
        }
    }

    // A debug trace for developers.
    if ($vcron->TRACE_ENABLE) {
        if ($crontrace) {
            fputs($crontrace, "VCron start on $vhost->vhostname : $timestamp_send\n" );
            fputs($crontrace, $rawresponse."\n");
            fputs($crontrace, "VCron stop on $vhost->vhostname : $timestamp_receive\n#################\n\n" );
            fclose($crontrace);
        }
    }
    echo "VCron start on $vhost->vhostname : $timestamp_send\n";
    echo $rawresponse."\n";
    echo "VCron stop on $vhost->vhostname : $timestamp_receive\n#################\n\n";
    $vhost->lastcrongap = time() - $vhost->lastcron;
    $vhost->lastcron = $timestamp_send;
    $vhost->croncount++;

    $DB->update_record('local_vmoodle', $vhost);
}
