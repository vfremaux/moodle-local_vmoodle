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
 * @author Valery Fremaux valery.fremaux@gmail.com
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package report_zabbix
 * @category report
 */
namespace report_zabbix\indicators;

use moodle_exception;
use coding_exception;
use StdClass;

require_once($CFG->dirroot.'/report/zabbix/classes/indicator.class.php');

class weekly_vmoodle_indicator extends zabbix_indicator {

    static $submodes = 'isvhost,enabledvhosts,disabledvhosts,submnet,submnets,mnetkeyisvalid,mnetkeyisinvalid,invalidremotemnetkeys';

    public function __construct() {
        parent::__construct();
        $this->key = 'moodle.vmoodle';
    }

    /**
     * Return all available submodes
     * return array of strings
     */
    public function get_submodes() {
        return explode(',', self::$submodes);
    }

    /**
     * the function that contains the logic to acquire the indicator instant value.
     * @param string $submode to target an aquisition to an explicit submode, elsewhere 
     */
    public function acquire_submode($submode) {
        global $DB, $CFG;

        if (!is_object($this->value)) {
            $this->value = new Stdclass;
        }

        if (is_null($submode)) {
            $submode = $this->submode;
        }

        $now = time();
        $horizon = $now - WEEKSECS;
        $me = $DB->get_record('local_vmoodle', ['vhostname' => $CFG->wwwroot]);
        $isvhost = !preg_match('#'.$CFG->mainhostprefix.'#', $CFG->wwwroot) && $me;

        switch ($submode) {

            case 'isvhost': {
                if (empty($CFG->mainhostprefix)) {
                    $this->value = 0;
                    break;
                }
                $this->value->$submode = $isvhost;
                break;
            }
            case 'enabledvhosts': {
                if ($isvhost) {
                    $this->value->$submode = 0;
                    break;
                }
                $select = ' enabled = 1 ';
                $this->value->$submode = $DB->count_records_select('local_vmoodle', $select, []);
                break;
            }

            case 'disabledvhosts': {
                if ($isvhost) {
                    $this->value->$submode = 0;
                    break;
                }
                $select = ' enabled = 0 ';
                $this->value->$submode = $DB->count_records_select('local_vmoodle', $select, []);
                break;
            }

            case 'submnet': {
                if ($isvhost) {
                    $this->value->$submode = $me->mnet;
                    break;
                }
                $this->value->$submode = 0;
                break;
            }

            case 'submnets': {
                if ($isvhost) {
                    $this->value->$submode = 0;
                    break;
                }
                $mnets = $DB->get_records('local_vmoodle', ['enabled' => 1], '', 'DISTINCT(mnet)');
                $this->value->$submode = count($mnets);
                break;
            }

            case 'mnetmode': {
                $this->value->$submode = $CFG->mnet_dispatcher_mode;
                break;
            }

            case 'mnetkeyisvalid': {
                if ($CFG->mnet_dispatcher_mode == 'strict') {
                    $mnet = get_mnet_environment();
                    if (empty($mnet->publick_key)) {
                        $this->value->$submode = -2;  // Unset
                        break;
                    }
                    if ($mnet->publick_key_expires < time()) {
                        $this->value->$submode = -1; // Expired
                        break;
                    }
                    $this->value->$submode = 0;
                    break;
                }
                $this->value->$submode = -9999; // Off
                break;
            }

            case 'mnetkeyisinvalid': {
                if ($CFG->mnet_dispatcher_mode == 'strict') {
                    $mnet = get_mnet_environment();
                    if (empty($mnet->publick_key)) {
                        $this->value->$submode = 1;  // Unset
                        break;
                    }
                    if ($mnet->publick_key_expires < time()) {
                        $this->value->$submode = 1; // Expired
                        break;
                    }
                    $this->value->$submode = 0;
                    break;
                }
                $this->value->$submode = 1; // Off
                break;
            }

            case 'invalidremotemnetkeys': {
                $peerrecs = $DB->get_records('mnet_host', ['deleted' => 0]);
                $obsoletekeys = 0;
                foreach ($peerrecs as $peer) {
                    if ($peer->name == '' || $peer->name == "All Hosts") {
                        continue;
                    }
                    if ($peer->public_key_expires < time()) {
                        $obsoletekeys++;
                    }
                }
                $this->value->$submode = $obsoletekeys;
                break;
            }

            default: {
                if ($CFG->debug == DEBUG_DEVELOPER) {
                    throw new coding_exception("Indicator has a submode that is not handled in aquire_submode().");
                }
            }
        }
    }
}