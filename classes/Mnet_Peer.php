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
 * An object to represent lots of information about an RPC-peer machine
 * This is a special implementation override for vmoodle MNET admin operations
 *
 * @author  Valery fremaux valery.fremaux@gmail.com
 * @version 0.0.1
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package mnet
 * @version Moodle 2.2
 */
namespace local_vmoodle;

defined('MOODLE_INTERNAL') || die();

use \StdClass;

require_once($CFG->libdir.'/filelib.php'); // Download_file_content() used here.

class Mnet_Peer {

    public $id                 = 0;
    public $wwwroot            = '';
    public $ipaddress          = '';
    public $name               = '';
    public $publickey          = '';
    public $publickeyexpires   = 0;
    public $deleted            = 0;
    public $lastconnecttime    = 0;
    public $lastlogid          = 0;
    public $forcetheme         = 0;
    public $theme              = '';
    public $applicationid      = 1; // Default of 1 == Moodle.
    public $keypair            = array();
    public $error              = array();
    public $bootstrapped       = false; // Set when the object is populated.
    public $sslverification    = 0; // No ssl check.

    public function __construct() {
        $this->updateparams = new StdClass();
        return true;
    }

    /**
     * Fetch information about a peer identified by wwwroot
     * If information does not preexist in db, collect it together based on
     * supplied information
     *
     * @param string $wwwroot - address of peer whose details we want
     * @param string $pubkey - to use if we add a record to db for new peer
     * @param int $application - table id - what kind of peer are we talking to
     * @param bool $force - force renewing key when the calling host (us) is known to the remote.
     * @return bool - indication of success or failure
     */
    public function bootstrap($wwwroot, $pubkey = null, $application, $force = false, $localname = '') {
        global $DB;

        if (substr($wwwroot, -1, 1) == '/') {
            $wwwroot = substr($wwwroot, 0, -1);
        }

        if (!$this->set_wwwroot($wwwroot)) {
            $hostname = mnet_get_hostname_from_uri($wwwroot);

            /*
             * Get the IP address for that host - if this fails, it will
             * return the hostname string
             */
            $ipaddress = gethostbyname($hostname);

            // Couldn't find the IP address?
            if ($ipaddress === $hostname && !preg_match('/^\d+\.\d+\.\d+.\d+$/', $hostname)) {
                $this->errors[] = 'ErrCode 2 - Host has no valid IP address.';
                return false;
            }

            if (empty($localname)) {
                $this->name = stripslashes($wwwroot);
                $this->updateparams->name = $wwwroot;
            } else {
                $this->name = $localname;
                $this->updateparams->name = $localname;
            }

            /*
             * TODO: In reality, this will be prohibitively slow... need another
             * default - maybe blank string
             */
            // PATCH+ : Add Skipcertverify ON and filter additional label after site name.
            $homepage = download_file_content($wwwroot, null, null, false, 300, 20, true);
            if (!empty($homepage)) {
                $count = preg_match("@<title>(.*?)(\:?.*)</title>@siU", $homepage, $matches);
            // PATCH-.
                if ($count > 0) {
                    $this->name = $matches[1];
                    $this->updateparams->name = str_replace("'", "''", $matches[1]);
                }
            }

            $this->wwwroot = stripslashes($wwwroot);
            $this->updateparams->wwwroot = $wwwroot;
            $this->ipaddress = $ipaddress;
            $this->updateparams->ipaddress = $ipaddress;
            $this->deleted = 0;
            $this->updateparams->deleted = 0;

            $this->application = $DB->get_record('mnet_application', array('name' => $application));
            if (empty($this->application)) {
                $this->application = $DB->get_record('mnet_application', array('name' => 'moodle'));
            }

            $this->applicationid = $this->application->id;
            $this->updateparams->applicationid = $this->application->id;

            if (empty($pubkey)) {
                // Start bootstraping as usual through the system command.
                $pubkeytemp = clean_param(mnet_get_public_key($this->wwwroot, $this->application), PARAM_PEM);
                if (function_exists('debug_trace')) {
                    debug_trace("bootstrap $this->wwwroot from null key");
                }
                // This is the key difference : force the exchange using vmoodle RPC keyswap !!
                if (empty($pubkeytemp)) {
                    $pubkeytemp = clean_param(mnet_get_public_key($this->wwwroot, $this->application, $force), PARAM_PEM);
                    if (empty($pubkeytemp)) {
                        // We definitely failed.
                        $this->errors[] = 'ErrCode 3 - Empty key received from peer.';
                        return false;
                    }
                }
            } else {
                $pubkeytemp = clean_param($pubkey, PARAM_PEM);
            }
            $this->publickeyexpires = $this->check_common_name($pubkeytemp);

            if ($this->publickeyexpires == false) {
                $this->errors[] = 'ErrCode 4 - Missing expiration date.';
                return false;
            }
            $this->updateparams->publickeyexpires = $this->publickeyexpires;

            $this->updateparams->publickey = $pubkeytemp;
            $this->publickey = $pubkeytemp;

            $this->lastconnecttime = 0;
            $this->updateparams->lastconnecttime = 0;
            $this->lastlogid = 0;
            $this->updateparams->lastlogid = 0;
        }

        return true;
    }

    /**
     * Delete mnet peer
     * the peer is marked as deleted in the database
     * we delete current sessions.
     * @return bool - success
     */
    public function delete() {
        global $DB;

        if ($this->deleted) {
            return true;
        }

        $this->delete_all_sessions();

        $this->deleted = 1;
        return $this->commit();
    }

    public function count_live_sessions() {
        global $DB;

        $obj = $this->delete_expired_sessions();
        return $DB->count_records('mnet_session', array('mnethostid' => $this->id));
    }

    public function delete_expired_sessions() {
        global $DB;

        $now = time();
        return $DB->delete_records_select('mnet_session', " mnethostid = ? AND expires < ? ", array($this->id, $now));
    }

    public function delete_all_sessions() {
        global $CFG, $DB;

        // TODO: Expires each PHP session individually.
        $sessions = $DB->get_records('mnet_session', array('mnethostid' => $this->id));

        if (count($sessions) > 0 && file_exists($CFG->dirroot.'/auth/mnet/auth.php')) {
            require_once($CFG->dirroot.'/auth/mnet/auth.php');
            $auth = new \auth_plugin_mnet();
            $auth->end_local_sessions($sessions);
        }

        $deletereturn = $DB->delete_records('mnet_session', array('mnethostid' => $this->id));
        return true;
    }

    public function check_common_name($key) {
        $credentials = $this->check_credentials($key);
        return $credentials['validTo_time_t'];
    }

    public function check_credentials($key) {
        $credentials = openssl_x509_parse($key);
        if ($credentials == false) {
            $params = array('subject' => '', 'host' => '');
            $this->error[] = array('code' => 3, 'text' => get_string("nonmatchingcert", 'mnet', $params));
            return false;
        } else if (array_key_exists('subjectAltName', $credentials['subject']) &&
                $credentials['subject']['subjectAltName'] != $this->wwwroot) {
            $a['subject'] = $credentials['subject']['subjectAltName'];
            $a['host'] = $this->wwwroot;
            $this->error[] = array('code' => 5, 'text' => get_string("nonmatchingcert", 'mnet', $a));
            return false;
        } else if ($credentials['subject']['CN'] != substr($this->wwwroot, 0, 64)) {
            // Here we accept partial certificates.
            $a['subject'] = $credentials['subject']['CN'];
            $a['host'] = $this->wwwroot;
            $this->error[] = array('code' => 4, 'text' => get_string('nonmatchingcert', 'mnet', $a));
            return false;
        } else {
            if (array_key_exists('subjectAltName', $credentials['subject'])) {
                $credentials['wwwroot'] = $credentials['subject']['subjectAltName'];
            } else {
                $credentials['wwwroot'] = $credentials['subject']['CN'];
            }
            return $credentials;
        }
    }

    public function commit() {
        global $DB;

        $obj = new StdClass();

        $obj->wwwroot                 = $this->wwwroot;
        $obj->ip_address              = $this->ipaddress;
        $obj->name                    = $this->name;
        $obj->public_key              = $this->publickey;
        $obj->public_key_expires      = $this->publickeyexpires;
        $obj->deleted                 = $this->deleted;
        $obj->last_connect_time       = $this->lastconnecttime;
        $obj->last_log_id             = $this->lastlogid;
        $obj->force_theme             = $this->forcetheme;
        $obj->theme                   = $this->theme;
        $obj->applicationid           = $this->applicationid;

        if (isset($this->id) && $this->id > 0) {
            $obj->id = $this->id;
            return $DB->update_record('mnet_host', $obj);
        } else {
            $this->id = $DB->insert_record('mnet_host', $obj);
            return $this->id > 0;
        }
    }

    public function touch() {
        $this->lastconnecttime = time();
        $this->commit();
    }

    public function set_name($newname) {
        if (is_string($newname) && strlen($newname <= 120)) {
            $this->name = $newname;
            return true;
        }
        return false;
    }

    public function set_applicationid($applicationid) {
        if (is_numeric($applicationid) && $applicationid == intval($applicationid)) {
            $this->applicationid = $applicationid;
            return true;
        }
        return false;
    }

    /**
     * Load information from db about an mnet peer into this object's properties
     *
     * @param string $wwwroot - address of peer whose details we want to load
     * @return bool - indication of success or failure
     */
    public function set_wwwroot($wwwroot) {
        global $DB;

        $hostinfo = $DB->get_record('mnet_host', array('wwwroot' => $wwwroot));

        if ($hostinfo != false) {
            $this->populate($hostinfo);
            return true;
        }
        return false;
    }

    public function set_id($id) {
        global $DB;

        if (clean_param($id, PARAM_INT) != $id) {
            $this->errno[]  = 1;
            $this->errmsg[] = 'Your id ('.$id.') is not legal';
            return false;
        }

        $sql = "
            SELECT
                h.*
            FROM
                {mnet_host} h
            WHERE
                h.id = ?
        ";

        if ($hostinfo = $DB->get_record_sql($sql, array($id))) {
            $this->populate($hostinfo);
            return true;
        }
        return false;
    }

    /**
     * Several methods can be used to get an 'mnet_host' record. They all then
     * send it to this private method to populate this object's attributes.
     *
     * @param object $hostinfo   A database record from the mnet_host table
     * @return  void
     */
    public function populate($hostinfo) {
        global $DB;

        $this->id                   = $hostinfo->id;
        $this->wwwroot              = $hostinfo->wwwroot;
        $this->ipaddress            = $hostinfo->ip_address;
        $this->name                 = $hostinfo->name;
        $this->deleted              = $hostinfo->deleted;
        $this->publickey            = $hostinfo->public_key;
        $this->publickeyexpires     = $hostinfo->public_key_expires;
        $this->lastconnecttime      = $hostinfo->last_connect_time;
        $this->lastlogid            = $hostinfo->last_log_id;
        $this->forcetheme           = $hostinfo->force_theme;
        $this->theme                = $hostinfo->theme;
        $this->applicationid        = $hostinfo->applicationid;
        $this->application = $DB->get_record('mnet_application', array('id' => $this->applicationid));
        $this->bootstrapped = true;
        $this->visible = @$hostinfo->visible; // Let it flexible if not using the host visibility hack.
    }

    public function get_public_key() {
        if (isset($this->publickeyref)) {
            return $this->publickeyref;
        }
        $this->publickeyref = openssl_pkey_get_public($this->publickey);

        return $this->publickeyref;
    }
}
