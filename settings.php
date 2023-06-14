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
 * @package     local_vmoodle
 * @category    local
 * @author      Bruce Bujon (bruce.bujon@gmail.com)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

if (get_config('local_vmoodle', 'late_install')) {
    // Need performing some corrections on some db recordings, specially subplugins mnet function records.
    require_once $CFG->dirroot.'/local/vmoodle/db/install.php';
    xmldb_local_vmoodle_late_install();
}

if ($hassiteconfig) {

    $settings = new admin_settingpage('localsettingvmoodle', get_string('pluginname', 'local_vmoodle'));
    $ADMIN->add('localplugins', $settings);

    if (@$CFG->mainwwwroot == $CFG->wwwroot) {
        // Only master moodle can have this menu.
        $label = get_string('vmoodleadministration', 'local_vmoodle');
        $viewurl = new moodle_url('/local/vmoodle/view.php');
        $ADMIN->add('server', new admin_externalpage('vmoodle', $label, $viewurl, 'local/vmoodle:managevmoodles'));

        $yesnoopts[0] = get_string('no');
        $yesnoopts[1] = get_string('yes');

        $key = 'local_vmoodle/automatedschema';
        $label = get_string('automateschema', 'local_vmoodle');
        $desc = get_string('automateschema_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configselect($key, $label, $desc, 1, $yesnoopts));

        $settings->add(new admin_setting_heading('siteschema', get_string('siteschema', 'local_vmoodle'), ''));

        $key = 'local_vmoodle/vmoodleinstancepattern';
        $label = get_string('vmoodleinstancepattern', 'local_vmoodle');
        $desc = get_string('vmoodleinstancepattern_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, '^.*$'));

        $key = 'local_vmoodle/vmoodlehost';
        $label = get_string('vmoodlehost', 'local_vmoodle');
        $desc = get_string('vmoodlehost_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, 'https://<%%INSTANCE%%>'));

        $key = 'local_vmoodle/vmoodleip';
        $label = get_string('vmoodleip', 'local_vmoodle');
        $desc = get_string('vmoodleip_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, ''));

        $dbopts['mariadb'] = 'MariaDB';
        $dbopts['mysqli'] = 'MySQLi';
        $dbopts['postgres'] = 'Postgres';
        $settings->add(new admin_setting_heading('dbschema', get_string('dbschema', 'local_vmoodle'), ''));

        $key = 'local_vmoodle/dbtype';
        $label = get_string('vdbtype', 'local_vmoodle');
        $desc = get_string('vdbtype_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configselect($key, $label, $desc, 'mariadb', $dbopts));

        $key = 'local_vmoodle/vdbhost';
        $label = get_string('vdbhost', 'local_vmoodle');
        $desc = get_string('vdbhost_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, 'localhost'));

        $key = 'local_vmoodle/vdblogin';
        $label = get_string('vdblogin', 'local_vmoodle');
        $desc = get_string('vdblogin_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, 'root'));

        $key = 'local_vmoodle/vdbpass';
        $label = get_string('vdbpass', 'local_vmoodle');
        $desc = get_string('vdbpass_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configpasswordunmask($key, $label, $desc, ''));

        $key = 'local_vmoodle/vdbbasename';
        $label = get_string('vdbname', 'local_vmoodle');
        $desc = get_string('vdbname_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, 'vmdl_<%%INSTANCE%%>'));

        $key = 'local_vmoodle/vdbprefix';
        $label = get_string('vdbprefix', 'local_vmoodle');
        $desc = get_string('vdbprefix_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, 'mdl_'));

        $key = 'local_vmoodle/dbpersist';
        $label = get_string('vdbpersist', 'local_vmoodle');
        $desc = get_string('vdbpersist_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesnoopts));

        $settings->add(new admin_setting_heading('fileschema', get_string('fileschema', 'local_vmoodle'), ''));

        $key = 'local_vmoodle/vdatapathbase';
        $label = get_string('vdatapath', 'local_vmoodle');
        $desc = get_string('vdatapath_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, '/var/moodledata/<%%INSTANCE%%>'));

        $settings->add(new admin_setting_heading('mnetschema', get_string('mnetschema', 'local_vmoodle'), ''));

        $subnetworks = array('-1' => get_string('nomnet', 'local_vmoodle'));
        $subnetworks['0'] = get_string('mnetfree', 'local_vmoodle');
        $subnetworksrecords = $DB->get_records_sql('SELECT * from {local_vmoodle} WHERE mnet > 0 ORDER BY mnet');
        $newsubnetwork = 1;
        if (!empty($subnetworksrecords)) {
            foreach ($subnetworksrecords as $subnetworksrecord) {
                $subnetworks[$subnetworksrecord->mnet] = $subnetworksrecord->mnet;
            }
        }
        $subnetworks['NEW'] = get_string('mnetnew', 'local_vmoodle');

        $key = 'local_vmoodle/mnet';
        $label = get_string('multimnet', 'local_vmoodle');
        $desc = get_string('multimnet_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $subnetworks));

        // Services strategy.
        $services_strategies = array('default' => get_string('servicesstrategydefault', 'local_vmoodle'), 
                                     'subnetwork' => get_string('servicesstrategysubnetwork', 'local_vmoodle'));

        $key = 'local_vmoodle/services';
        $label = get_string('servicesstrategy', 'local_vmoodle');
        $desc = get_string('servicesstrategy_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $services_strategies));

        $settings->add(new admin_setting_heading('key_autorenew_parms', get_string('mnetkeyautorenew', 'local_vmoodle'), ''));

        $onoffopts[0] = get_string('off', 'local_vmoodle');
        $onoffopts[1] = get_string('on', 'local_vmoodle');

        $key = 'local_vmoodle/mnet_key_autorenew';
        $label = get_string('mnetkeyautorenewenable', 'local_vmoodle');
        $desc = get_string('mnetkeyautorenew_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configselect($key, $label, $desc, 1, $onoffopts));

        $key = 'local_vmoodle/mnet_key_autorenew_gap';
        $label = get_string('mnetkeyautorenewgap', 'local_vmoodle');
        $desc = get_string('mnetkeyautorenewgap_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, 24 * 3));

        $key = 'local_vmoodle/mnet_key_autorenew_time_hour';
        $keymin = 'mnet_key_autorenew_time_min';
        $label = get_string('mnetkeyautorenewtime', 'local_vmoodle');
        $settings->add(new admin_setting_configtime($key, $keymin, $label, '', array('h' => 0, 'm' => 0)));

        $key = 'local_vmoodle/vlogfilepattern';
        $label = get_string('vlogfilepattern', 'local_vmoodle');
        $desc = get_string('vlogfilepattern_desc', 'local_vmoodle');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $settings->add(new admin_setting_heading('tools', get_string('tools', 'local_vmoodle'), ''));

        $key = 'local_vmoodle/cmd_mysql';
        $label = get_string('mysqlcmd', 'local_vmoodle');
        $desc = get_string('systempath_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, '/usr/bin/mysql'));

        $key = 'local_vmoodle/cmd_mysqldump';
        $label = get_string('mysqldumpcmd', 'local_vmoodle');
        $desc = get_string('systempath_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, '/usr/bin/mysqldump'));

        $key = 'local_vmoodle/cmd_pgsql';
        $label = get_string('pgsqlcmd', 'local_vmoodle');
        $desc = get_string('systempath_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, '/usr/bin/psql'));

        $key = 'local_vmoodle/cmd_pgsqldump';
        $label = get_string('pgsqldumpcmd', 'local_vmoodle');
        $desc = get_string('systempath_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, '/usr/bin/pg_dump'));

        $key = 'local_vmoodle/sudoer';
        $label = get_string('sudoer', 'local_vmoodle');
        $desc = get_string('sudoer_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configtext($key, $label, $desc, ''));

        $key = 'local_vmoodle/oldversionsuffix';
        $label = get_string('oldversionsuffix', 'local_vmoodle');
        $desc = get_string('oldversionsuffix_desc', 'local_vmoodle');
        $default = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, $default));

        $settings->add(new admin_setting_heading('massdeployment', get_string('massdeployment', 'local_vmoodle'), ''));

        $encodingopts[0] = 'UTF-8';
        $encodingopts[1] = 'ISO-5889-1';

        $key = 'local_vmoodle/encoding';
        $label = get_string('csvencoding', 'local_vmoodle');
        $desc = get_string('csvencoding_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configselect($key, $label, $desc, 1, $encodingopts));

        $settings->add(new admin_setting_heading('behaviour', get_string('behaviour', 'local_vmoodle'), ''));
        $yesno = array(0 => get_string('no'), 1 => get_string('yes'));

        $key = 'local_vmoodle/force_https_proto';
        $label = get_string('forcehttpsproto', 'local_vmoodle');
        $desc = get_string('multimnet_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesno));

        $key = 'local_vmoodle/allow_mnet_user_system_admin';
        $label = get_string('allowmnetusersasadmin', 'local_vmoodle');
        $desc = get_string('multimnet_desc', 'local_vmoodle');
        $settings->add(new admin_setting_configselect($key, $label, $desc, 0, $yesno));

        $key = 'local_vmoodle/web_server_user';
        $label = get_string('webserveruser', 'local_vmoodle');
        $desc = '';
        $settings->add(new admin_setting_configtext($key, $label, $desc, 'www-data', PARAM_TEXT));

        if (local_vmoodle_supports_feature('emulate/community') == 'pro') {
            include_once($CFG->dirroot.'/local/vmoodle/pro/prolib.php');
            $promanager = local_vmoodle\pro_manager::instance();
            $promanager->add_settings($ADMIN, $settings);
        } else {
            $label = get_string('plugindist', 'local_vmoodle');
            $desc = get_string('plugindist_desc', 'local_vmoodle');
            $settings->add(new admin_setting_heading('plugindisthdr', $label, $desc));
        }
    }

    // Minimal settings for vmoodle instances.
    $settings->add(new admin_setting_heading('key_autorenew_parms', get_string('mnetkeyautorenew', 'local_vmoodle'), ''));

    $onoffopts[0] = get_string('off', 'local_vmoodle');
    $onoffopts[1] = get_string('on', 'local_vmoodle');

    $key = 'local_vmoodle/mnet_key_autorenew';
    $label = get_string('mnetkeyautorenewenable', 'local_vmoodle');
    $desc = get_string('mnetkeyautorenew_desc', 'local_vmoodle');
    $settings->add(new admin_setting_configselect($key, $label, $desc, 1, $onoffopts));

    $key = 'local_vmoodle/mnet_key_autorenew_gap';
    $label = get_string('mnetkeyautorenewgap', 'local_vmoodle');
    $desc = get_string('mnetkeyautorenewgap_desc', 'local_vmoodle');
    $settings->add(new admin_setting_configtext($key, $label, $desc, 24 * 3));

    $key = 'local_vmoodle/mnet_key_autorenew_time_hour';
    $keymin = 'mnet_key_autorenew_time_min';
    $label = get_string('mnetkeyautorenewtime', 'local_vmoodle');
    $settings->add(new admin_setting_configtime($key, $keymin, $label, '', array('h' => 0, 'm' => 0)));
}
