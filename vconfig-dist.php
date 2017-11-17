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
 * This is a fake alternative virtual configuration that must be included before calling to
 * lib/setup.php in master configuration.
 *
 * The VMASTER host must point to a Moodle setup that holds the effective vmoodle block
 * holding the virtual configs. The basic configuration uses the same configuration
 * values as the original one (the configuration from config.php). Say, the physical
 * moodle is also the master of the virtual system.
 *
 * @package local_vmoodle
 * @category local
 * @author Moheissen Fabien (fabien.moheissen@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

/**
 * VMoodle configuration
 * Must point to a VMaster server
 *
 * Information in the README file.
 *
 */

if ((defined('CLI_SCRIPT') && CLI_SCRIPT) && !defined('WEB_CRON_EMULATED_CLI') && !defined('CLI_VMOODLE_OVERRIDE')) {
    return;
}
require_once($CFG->dirroot.'/local/vmoodle/bootlib.php');

// EDIT A CONFIGURATION FOR MASTER MOODLE.

$CFG->vmasterdbhost = 'localhost';
$CFG->vmasterdbtype = 'mysqli';
$CFG->vmasterdbname = '';
$CFG->vmasterdblogin = '';
$CFG->vmasterdbpass = '';
$CFG->vmasterdbpersist =  false;
$CFG->vmasterprefix    = 'mdl_';
$CFG->vmoodlenodefault    = 0; // If set, will block the use of the master install vmoodle.
$CFG->vlogfilepattern  = '/var/log/%%VHOSTNAME%%.log';

/*
 * Define here the childs DB access in order NOT to expose individual DB access in the local_vmoodle database.
 * If empty, each vmoodle needs to provide login and/or password from the database register.
 */
// $CFG->vchildsdblogin = '';
// $CFG->vchildsdbpass = '';

/*
 * Use subpath will handle the virtual moodles as path extensions of a master domain, such as
 * - http://main.domain.edu/moodle1
 * - http://main.domain.edu/moodle2
 * ...
 *
 * Subpathing is achieved with symlinks in the moodle installation directory as obtained by :
 * ln -s . moodle1
 *
 * VMoodle will extract virtual instance identity from the first subpath part following the root domain
 */
// $CFG->vmoodleusesubpath = false;

/*
 * Forcing HTTPS proto. This is in case the Web server do not provide the environment variable
 * HTTP_X_FORWARDED_PROTO but yet operates with an external https front protocol.
 *
 */
// $CFG->vmoodle_force_https_proto = false;

/*
 * Setting forced default for master moodle or for all childs allows to include a forced setting
 * additional configuration, located in the /local root. The file must be named :
 * defaults_<defaultname>.php
 *
 */
// $CFG->vmoodlehardmasterdefaults = 'defaultname';
// $CFG->vmoodlehardchildsdefaults = 'defaultname';

vmoodle_get_hostname();

// TODO : insert customized additional code here if required.

vmoodle_boot_configuration();
