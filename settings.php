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

if (!defined('MOODLE_INTERNAL')) {
    die ("You cannot use this script this way");
}

$systemcontext = context_system::instance();
$hasadmin = false;
if (is_dir($CFG->dirroot.'/local/adminsettings')) {
    // This is AdminSettings Edunao driven administration 
    if (has_capability('local/adminsettings:nobody', $systemcontext)) {
        $hasadmin = true;
    }
} else {
    // this is Moodle Standard
    if ($ADIN->fulltree) {
        $hasadmin = true;
    }
}

if ($hasadmin) {
    if (@$CFG->mainwwwroot == $CFG->wwwroot) {
        // Only master moodle can have this menu.
        $ADMIN->add('server', new admin_externalpage('vmoodle', get_string('vmoodleadministration', 'local_vmoodle'), $CFG->wwwroot . '/local/vmoodle/view.php', 'local/vmoodle:managevmoodles'));

        $settings = new admin_settingpage('local_vmoodle', get_string('pluginname', 'local_vmoodle'));
        $ADMIN->add('localplugins', $settings);

        $yesnoopts[0] = get_string('no');
        $yesnoopts[1] = get_string('yes');
    
        $settings->add(new admin_setting_configselect('local_vmoodle/automatedschema', get_string('automateschema', 'local_vmoodle'), get_string('automateschema_desc', 'local_vmoodle'), 1, $yesnoopts));
    
        $settings->add(new admin_setting_heading('siteschema', get_string('siteschema', 'local_vmoodle'), ''));
        $settings->add(new admin_setting_configtext('local_vmoodle/vmoodlehost', get_string('vmoodlehost', 'local_vmoodle'), get_string('vmoodlehost_desc', 'local_vmoodle'), 'http://<%%INSTANCE%%>'));
        $settings->add(new admin_setting_configtext('local_vmoodle/vmoodleip', get_string('vmoodleip', 'local_vmoodle'), get_string('vmoodleip_desc', 'local_vmoodle'), ''));
    
        $dbopts['mysqli'] = 'MySQLi';
        $dbopts['postgres'] = 'Postgres';
        $settings->add(new admin_setting_heading('dbschema', get_string('dbschema', 'local_vmoodle'), ''));
        $settings->add(new admin_setting_configselect('local_vmoodle/dbtype', get_string('vdbtype', 'local_vmoodle'), get_string('vdbtype_desc', 'local_vmoodle'), 'mysqli', $dbopts));
        $settings->add(new admin_setting_configtext('local_vmoodle/vdbhost', get_string('vdbhost', 'local_vmoodle'), get_string('vdbhost_desc', 'local_vmoodle'), 'localhost'));
        $settings->add(new admin_setting_configtext('local_vmoodle/vdblogin', get_string('vdblogin', 'local_vmoodle'), get_string('vdblogin_desc', 'local_vmoodle'), 'root'));
        $settings->add(new admin_setting_configpasswordunmask('local_vmoodle/vdbpass', get_string('vdbpass', 'local_vmoodle'), get_string('vdbpass_desc', 'local_vmoodle'), ''));
        $settings->add(new admin_setting_configtext('local_vmoodle/vdbbasename', get_string('vdbname', 'local_vmoodle'), get_string('vdbname_desc', 'local_vmoodle'), 'vmdl_<%%INSTANCE%%>'));
        $settings->add(new admin_setting_configtext('local_vmoodle/vdbprefix', get_string('vdbprefix', 'local_vmoodle'), get_string('vdbprefix_desc', 'local_vmoodle'), 'mdl_'));
        $settings->add(new admin_setting_configselect('local_vmoodle/dbpersist', get_string('vdbpersist', 'local_vmoodle'), get_string('vdbpersist_desc', 'local_vmoodle'), 0, $yesnoopts));
    
        $settings->add(new admin_setting_heading('fileschema', get_string('fileschema', 'local_vmoodle'), ''));
        $settings->add(new admin_setting_configtext('local_vmoodle/vdatapathbase', get_string('vdatapath', 'local_vmoodle'), get_string('vdatapath_desc', 'local_vmoodle'), '/var/moodledata/<%%INSTANCE%%>'));
    
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
        $settings->add(new admin_setting_configselect('local_vmoodle/mnet', get_string('multimnet', 'local_vmoodle'), get_string('multimnet_desc', 'local_vmoodle'), 0, $subnetworks));
    
        // Services strategy.
        $services_strategies = array(
            'default' => get_string('servicesstrategydefault', 'local_vmoodle'), 
            'subnetwork' => get_string('servicesstrategysubnetwork', 'local_vmoodle')
        );
        $settings->add(new admin_setting_configselect('local_vmoodle/services', get_string('servicesstrategy', 'local_vmoodle'), get_string('servicesstrategy_desc', 'local_vmoodle'), 0, $services_strategies));
    
        $settings->add(new admin_setting_heading('key_autorenew_parms', get_string('tools', 'local_vmoodle'), ''));
    
        $onoffopts[0] = get_string('off', 'local_vmoodle');
        $onoffopts[1] = get_string('on', 'local_vmoodle');
        $settings->add(new admin_setting_configselect('local_vmoodle/mnet_key_autorenew', get_string('mnetkeyautorenew', 'local_vmoodle'), get_string('mnetkeyautorenew_desc', 'local_vmoodle'), 1, $onoffopts));
        $settings->add(new admin_setting_configtext('local_vmoodle/mnet_key_autorenew_gap', get_string('mnetkeyautorenewgap', 'local_vmoodle'), get_string('mnetkeyautorenewgap_desc', 'local_vmoodle'), 24 * 3));
        $settings->add(new admin_setting_configtime('local_vmoodle/mnet_key_autorenew_time_hour', 'mnet_key_autorenew_time_min', get_string('mnetkeyautorenewtime', 'local_vmoodle'), '', array('h' => 0, 'm' => 0)));
    
        $settings->add(new admin_setting_heading('tools', get_string('tools', 'local_vmoodle'), ''));
        $settings->add(new admin_setting_configtext('local_vmoodle/cmd_mysql', get_string('mysqlcmd', 'local_vmoodle'), get_string('systempath_desc', 'local_vmoodle'), '/usr/bin/mysql'));
        $settings->add(new admin_setting_configtext('local_vmoodle/cmd_mysqldump', get_string('mysqldumpcmd', 'local_vmoodle'), get_string('systempath_desc', 'local_vmoodle'), '/usr/bin/mysqldump'));
        $settings->add(new admin_setting_configtext('local_vmoodle/cmd_pgsql', get_string('pgsqlcmd', 'local_vmoodle'), get_string('systempath_desc', 'local_vmoodle'), '/usr/bin/psql'));
        $settings->add(new admin_setting_configtext('local_vmoodle/cmd_pgsqldump', get_string('pgsqldumpcmd', 'local_vmoodle'), get_string('systempath_desc', 'local_vmoodle'), '/usr/bin/pg_dump'));
    
        $settings->add(new admin_setting_heading('massdeployment', get_string('massdeployment', 'local_vmoodle'), ''));
    
        $encodingopts[0] = 'UTF-8';
        $encodingopts[1] = 'ISO-5889-1';
        $settings->add(new admin_setting_configselect('local_vmoodle/encoding', get_string('csvencoding', 'local_vmoodle'), get_string('csvencoding_desc', 'local_vmoodle'), 1, $encodingopts));
    
        $settings->add(new admin_setting_heading('tools', get_string('tools', 'local_vmoodle'), ''));
        $yesno = array(0 => get_string('no'), 1 => get_string('yes'));
        $settings->add(new admin_setting_configselect('local_vmoodle/force_https_proto', get_string('forcehttpsproto', 'local_vmoodle'), get_string('multimnet_desc', 'local_vmoodle'), 0, $yesno));
        $settings->add(new admin_setting_configselect('local_vmoodle/allow_mnet_user_system_admin', get_string('allowmentusersasadmin', 'local_vmoodle'), get_string('multimnet_desc', 'local_vmoodle'), 0, $yesno));
    }
}
