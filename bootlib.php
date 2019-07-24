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
 * @category        local
 * @package         local_vmoodle
 * @author          valery.fremaux (valery.fremaux@gmail.com)
 */

function vmoodle_get_hostname() {
    global $CFG;

    if ((!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') ||
                    !empty($CFG->vmoodle_force_https_proto)) {
        $protocol = 'https';
    } else {
        $protocol = 'http';
    }

    define('VMOODLE_BOOT', true);

    /*
     * This happens when a cli script needs to force one Vmoodle execution.
     * This will force vmoodle switch using a hard defined constant.
     */
    if (defined('CLI_VMOODLE_OVERRIDE')) {
        $CFG->vmoodleroot = CLI_VMOODLE_OVERRIDE;
        $CFG->vmoodlename = preg_replace('/https?:\/\//', '', CLI_VMOODLE_OVERRIDE);
        $parts = explode('.', $CFG->vmoodlename);
        $CFG->vhost = array_shift($parts);
        return;
    }

    /*
     * This is the standard case when each vmoodle runs on his own masgter single domain.
     */
    $CFG->vmoodleroot = "{$protocol}://".@$_SERVER['HTTP_HOST'];
    $CFG->vmoodlename = @$_SERVER['HTTP_HOST'];
    if (empty($CFG->vmoodlename)) {
        // Try again.
        $CFG->vmoodleroot = "{$protocol}://".$_SERVER['SERVER_NAME'];
        if ($_SERVER['SERVER_PORT'] != 80) {
            $CFG->vmoodleroot .= ':'.$_SERVER['SERVER_PORT'];
        }
        $CFG->vmoodlename = $_SERVER['SERVER_NAME'];
    }

    /*
     * When using a single domain with subpaths for instances, we need
     * catch the first path element in host identity reference.
     */
    if (!empty($CFG->vmoodleusesubpaths)) {
        if (preg_match('#^/#', $CFG->dirroot)) {
            // We are on a linux, (but not having full $CFG to know it).
            $uri = preg_replace('#^/#', '', $_SERVER['REQUEST_URI']);
            if (!preg_match('#/$#', $uri)) {
                $path = dirname($uri);
            } else {
                $path = $uri;
            }
            $pathparts = explode('/', $path);
            $firstpath = array_shift($pathparts);
            if (($firstpath != '') && ($firstpath != '/') && ($firstpath != '.')) {
                // If request uri goes into a subdir.
                if (is_link($CFG->dirroot.'/'.$firstpath)) {
                    // Symbolic links in dirroot are characteristic to submoodledirs.
                    $CFG->vmoodleroot .= '/'.$firstpath;
                    $CFG->vmoodlename .= '/'.$firstpath;
                }
            }
        } else {
            echo "VMoodle sub paths are not supported on Windows systems. Change configuration. \n";
        }
        // echo "Baseroot : ".$CFG->vmoodleroot.'<br/>';
    }
}

/**
 * This is a boot lib that is required BEFORE we can have access
 * to $CFG defines. Mysql type has been removed (php 5.5 ahead)
 */
function vmoodle_boot_configuration() {
    global $CFG;

    if (empty($CFG->dirroot)) {
        die('VMoodle installs need $CFG->dirroot be defined explicitely in config.php.'."\n");
    }

    /*
     * vconfig provides an bypassed configuration using vmoodle host definition
     * from the vmoodle master instance
     */

    $CFG->mainwwwroot = $CFG->wwwroot;

    if ($CFG->vmoodleroot != $CFG->wwwroot) {
        if (($CFG->vmasterdbtype == 'mysqli') || ($CFG->vmasterdbtype == 'mariadb')) {
            $vmaster = new StdClass();
            $vmaster->vdbtype = $CFG->vmasterdbtype;
            $vmaster->vdbhost = $CFG->vmasterdbhost;
            $vmaster->vdblogin = $CFG->vmasterdblogin;
            $vmaster->vdbpass = $CFG->vmasterdbpass;
            $vmaster->vdbname = $CFG->vmasterdbname;

            if (!$sidecnx = vmoodle_make_connection($vmaster, true)) {
                // If vmoodle cnx not valid.
                die ('VMoodle master server ('.$vmaster->vdbtype.':'.$vmaster->vdbname.'@'.$vmaster->vdbhost.') unreachable from '.$CFG->wwwroot."\n");
            }

            $sql = "
               SELECT
               *
               FROM
                  {$CFG->vmasterprefix}local_vmoodle
               WHERE
                  vhostname = '$CFG->vmoodleroot'
            ";

            $res = mysqli_query($sidecnx, $sql);
            if ($res) {
                if (mysqli_num_rows($res)) {
                    $vmoodle = mysqli_fetch_object($res);
                    vmoodle_feed_config($vmoodle);
                } else {
                    die ("VMoodling : No configuration for this host : $CFG->vmoodleroot. May be faked.\n");
                }
            } else {
                die ("VMoodling : Could not fetch virtual moodle configuration\n");
            }
        } else if ($CFG->vmasterdbtype == 'postgres' || $CFG->vmasterdbtype == 'postgres7') {
            $vmaster = new StdClass();
            $vmaster->vdbtype = $CFG->vmasterdbtype;
            $vmaster->vdbhost = $CFG->vmasterdbhost;
            $vmaster->vdblogin = $CFG->vmasterdblogin;
            $vmaster->vdbpass = $CFG->vmasterdbpass;
            $sidecnx = vmoodle_make_connection($vmaster);

            $sql = "
               SELECT
               *
               FROM
                  {$CFG->vmasterprefix}local_vmoodle
               WHERE
                  vhostname = '$CFG->vmoodleroot'
            ";
            $res = pg_query($sidecnx, $sql);

            if ($res) {
                if (pg_num_rows($res)) {
                    $vmoodle = pg_fetch_object($res);
                    vmoodle_feed_config($vmoodle);
                } else {
                    die ("VMoodling : No configuration for this host. May be faked.\n");
                }
                pg_close($sidecnx);
            } else {
                die ("VMoodling : Could not fetch virtual moodle configuration\n");
            }
        } else {
            die("VMoodling : Unsupported Database for VMoodleMaster\n");
        }

        // Apply child default config if any.
        /**
         * Note that hard config cannot be anymore overriden by administration.
         *
         * Setup will additionnaly apply a local/defaults.php file if exists.
         */
        if (!empty($CFG->vmoodlehardchildsdefaults)) {
            $default = $CFG->dirroot.'/local/defaults_'.$CFG->vmoodlehardchildsdefaults.'.php';
            if (file_exists($default)) {
                include($default);
            }
        }

    } else if (empty($CFG->vmoodlenodefault)) {

        // Apply master config hard defaults if any.
        /**
         * Note that hard config cannot be anymore overriden by administration.
         *
         * Setup will additionnaly apply a local/defaults.php file if exists.
         */
        if (!empty($CFG->vmoodlehardmasterdefaults)) {
            $default = $CFG->dirroot.'/local/defaults_'.$CFG->vmoodlehardmasterdefaults.'.php';
            if (file_exists($default)) {
                include($default);
            }
        }

        // Do nothing, just bypass.
        assert(true);
    } else {
        die ("real moodle instance cannot be used in this VMoodle implementation");
    }
}

/**
 * provides a side connection to a vmoodle database
 * mysql type has been removed.
 * @param object $vmoodle a vmoodle database description
 * @param boolean $binddb if true, the database is bound after connection.
 * @return a connection
 */
function vmoodle_make_connection(&$vmoodle, $binddb = false) {

    if (($vmoodle->vdbtype == 'mysqli') || ($vmoodle->vdbtype == 'mariadb')) {
        // Important : force new link here.

        $sidecnx = @mysqli_connect($vmoodle->vdbhost, $vmoodle->vdblogin, $vmoodle->vdbpass, $vmoodle->vdbname, 3306);
        if (!$sidecnx) {
            die ("VMoodle_make_connection : Server {$vmoodle->vdblogin}@{$vmoodle->vdbhost} unreachable");
        }
        if ($binddb) {
            if (!mysqli_select_db($sidecnx, $vmoodle->vdbname)) {
                die ("VMoodle_make_connection : Database not found");
            }
        }
        return $sidecnx;
    } else if ($vmoodle->vdbtype == 'postgres') {

        if (preg_match("/:/", $vmoodle->vdbhost)) {
            list($host, $port) = explode(":", $vmoodle->vdbhost);
            $port = "port=$port";
        } else {
            $host = $vmoodle->vdbhost;
            $port = '';
        }

        $dbname = ($binddb) ? "dbname={$vmoodle->vdbname} " : '';

        $cnxstr = "host={$host} {$port} user={$vmoodle->vdblogin} password={$vmoodle->vdbpass} {$dbname}";
        $sidecnx = @pg_connect($cnxstr);
        return $sidecnx;
    } else {
        echo "vmoodle_make_connection : Database not supported<br/>";
    }
}

/**
 * Get values from a virtual configuration and feed the apparent running config with them.
 * @para object $vmoodle $vmoodle descriptor
 */
function vmoodle_feed_config($vmoodle) {
    global $CFG;

    $CFG->dbtype    = $vmoodle->vdbtype;
    $CFG->dbhost    = $vmoodle->vdbhost;
    $CFG->dbname    = $vmoodle->vdbname;

    // Security enhancement.
    /*
     * When using config forced logins and passwords for childs, the database
     * register corresponding attributes can be cleared.
     */
    if (empty($CFG->vchildsdblogin)) {
        $CFG->dbuser = $vmoodle->vdblogin;
    } else {
        $CFG->dbuser = $CFG->vchildsdblogin;
    }

    if (empty($CFG->vchildsdbpass)) {
        $CFG->dbpass = $vmoodle->vdbpass;
    } else {
        $CFG->dbpass = $CFG->vchildsdbpass;
    }

    $CFG->dboptions['dbpersist'] = $vmoodle->vdbpersist;
    $CFG->prefix    = $vmoodle->vdbprefix;

    $CFG->wwwroot   = $CFG->vmoodleroot;
    $CFG->dataroot  = $vmoodle->vdatapath;
}