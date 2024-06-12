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
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux <valery.fremaux@gmail.com>, <valery@edunao.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2014 onwards Microsoft Open Technologies, Inc. (http://msopentech.com/)
 */
require('../../../config.php');
require_once($CFG->dirroot.'/local/vmoodle/tools/generatecopyscripts_form.php');
require_once($CFG->dirroot.'/local/vmoodle/tools/lib.php');

$url = new moodle_url('/local/vmoodle/tools/generatecopyscripts.php');
$context = context_system::instance();
$PAGE->set_context($context);
$config = get_config('local_vmoodle');

require_login();
require_capability('moodle/site:config', $context);

if (@$CFG->mainwwwroot != $CFG->wwwroot) {
    throw new moodle_exception(get_string('onlymainsitecangenerate', 'local_vmoodle'));
}

if ($USER->mnethostid != $CFG->mnet_localhost_id) {
    throw new moodle_exception(get_string('onlymainsiteadminscangenerate', 'local_vmoodle'));
}

$PAGE->set_heading(get_string('scriptgenerator', 'local_vmoodle'));
$PAGE->set_url($url);
$PAGE->set_pagelayout('admin');

$mform = new CopyScriptsParams_Form();

$datastr = '';
$dbstr = '';
$cronstr = '';
$configstr = '';
$preupgradestr = '';
$upgradestr = '';
$postupgradestr = '';
$backupdbstr = '';
$backupdbtransfer = '';
$restorebackupdbstr = '';
$restorebackupdbtransfer = '';
$dropbackupdbstr = '';
$sudostr = '';
$vhostnginxstr = '';

$fs = get_file_storage();
$contextid = \context_system::instance()->id;

if ($data = $mform->get_data()) {

    $fs->delete_area_files($contextid, 'local_vmoodle', 'migrationscripts', 0);

    $vhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

    $vmoodlelocation = 'local';
    $vmoodletolocation = 'local';
    $vmoodlefromlocation = 'local';

    $main = new StdClass;

    // Processing all roots
    $main->olddbname = $CFG->dbname;
    $main->newdbname = str_replace($data->fromversion, $data->toversion, $main->olddbname);

    $main->originwwwroot = $CFG->wwwroot;
    $main->currentwwwroot = $CFG->wwwroot;
    $oldsuffix = $data->fromversion;
    if (!empty($config->oldsuffixversion)) {
        $oldsuffix = $config->oldsuffixversion;
    }
    $main->archivewwwroot = change_version($data->fromversion, $oldsuffix, $CFG->wwwroot, 'from');
    $main->currentwwwrootsed = remove_moodle_version($main->currentwwwroot);
    $main->currentwwwrootsed = str_replace("/", "\\/", $CFG->wwwroot);
    $main->originwwwrootsed = str_replace("/", "\\/", $main->originwwwroot);
    $main->archivewwwrootsed = str_replace("/", "\\/", $main->archivewwwroot);

    $main->olddataroot = $CFG->dataroot;
    $main->newdataroot = str_replace($data->fromversion, $data->toversion, $CFG->dataroot);
    $main->tomoodledatacontainer = dirname($main->newdataroot);

    if (empty($CFG->configdirroot)) {
        $CFG->configdirroot = $CFG->dirroot;
    }

    $main->olddirroot = $CFG->configdirroot;
    $main->newdirroot = str_replace($data->fromversion, $data->toversion, $CFG->configdirroot);
    $main->olddirrootsed = str_replace("/", "\\/", $CFG->configdirroot);
    $main->newdirrootsed = str_replace("/", "\\/", $main->newdirroot);
    $main->oldmoodledatased = str_replace("/", "\\/", $main->olddataroot);
    $main->newmoodledatased = str_replace("/", "\\/", $main->newdataroot);

    $hostreps = array();
    if ($vhosts) {
        foreach ($vhosts as $vhost) {
            $hostreps[$vhost->name] = new StdClass;

            $hostreps[$vhost->name]->olddbname = $vhost->vdbname;
            $hostreps[$vhost->name]->newdbname = str_replace($data->fromversion, $data->toversion, $hostreps[$vhost->name]->olddbname);

            // this is a bit tricky, but we need to manage soem special cases due to production apparent domains.
            $hostreps[$vhost->name]->originwwwroot = $vhost->vhostname;
            $hostreps[$vhost->name]->currentwwwroot = $vhost->vhostname;
            // Explicits the next version.
            $hostreps[$vhost->name]->currentwwwroot = change_version($data->fromversion, $data->toversion, $hostreps[$vhost->name]->currentwwwroot, 'to');
            // Revert to explicit archive.
            $oldsuffix = $data->fromversion;
            if (!empty($config->oldsuffixversion)) {
                $oldsuffix = $config->oldsuffixversion;
            }
            $hostreps[$vhost->name]->archivewwwroot = change_version($data->toversion, $oldsuffix, $hostreps[$vhost->name]->currentwwwroot);
            // Finally locally remove the moodle version marker for production exposed domains.
            // Note that current version may NOT have changed from original in most cases.
            $hostreps[$vhost->name]->currentwwwroot = remove_moodle_version($hostreps[$vhost->name]->currentwwwroot);
            $hostreps[$vhost->name]->originwwwrootsed = str_replace("/", "\\/", $hostreps[$vhost->name]->originwwwroot);
            $hostreps[$vhost->name]->currentwwwrootsed = str_replace("/", "\\/", $hostreps[$vhost->name]->currentwwwroot);
            $hostreps[$vhost->name]->archivewwwrootsed = str_replace("/", "\\/", $hostreps[$vhost->name]->archivewwwroot);

            $hostreps[$vhost->name]->olddataroot = $vhost->vdatapath;
            $hostreps[$vhost->name]->newdataroot = str_replace($data->fromversion, $data->toversion, $hostreps[$vhost->name]->olddataroot);
            $hostreps[$vhost->name]->olddatarootsed = str_replace("/", "\\/", $hostreps[$vhost->name]->olddataroot);
            $hostreps[$vhost->name]->newdatarootsed = str_replace("/", "\\/", $hostreps[$vhost->name]->newdataroot);

            $hostreps[$vhost->name]->olddbname = $vhost->vdbname;
            $hostreps[$vhost->name]->newdbname = str_replace($data->fromversion, $data->toversion, $hostreps[$vhost->name]->olddbname);
            $hostreps[$vhost->name]->olddbnamesed = str_replace("/", "\\/", $hostreps[$vhost->name]->olddbname);
            $hostreps[$vhost->name]->newdbnamesed = str_replace("/", "\\/", $hostreps[$vhost->name]->newdbname);

        }
    }

    // Pre save databases for backup.

    // Main host DB copy.
    $backupdbstr = '# Backup DB creation for '.$SITE->fullname."\n";
    $backupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'DROP DATABASE IF EXISTS {$main->olddbname}_bak;' \n";
    $backupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'CREATE DATABASE {$main->olddbname}_bak;' \n";

    $backupdbtransfer = '# Backup Data transfer for '.$SITE->fullname."\n";
    $backupdbtransfer .= 'mysqldump '.$main->olddbname.' -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' > temp.sql'."\n";
    $backupdbtransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' '.$main->olddbname.'_bak < temp.sql'."\n";

    // Active Vhosts DB copy.
    if ($vhosts) {
        $i = 1;
        $count = count($vhosts);
        foreach ($vhosts as $vhost) {

            $backupdbstr .= "\n";
            $backupdbstr .= '# Backup DB creation for '.$vhost->name."\n";
            $backupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'DROP DATABASE IF EXISTS {$hostreps[$vhost->name]->olddbname}_bak ;' \n";
            $backupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'CREATE DATABASE {$hostreps[$vhost->name]->olddbname}_bak;' \n";

            $backupdbtransfer .= "\n";
            $backupdbtransfer .= '# Backup Data transfer for '.$SITE->fullname."\n";
            $backupdbtransfer .= 'mysqldump '.$hostreps[$vhost->name]->olddbname.' -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' > temp.sql'."\n";
            $backupdbtransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' '.$hostreps[$vhost->name]->olddbname.'_bak < temp.sql'."\n";

            $ratio = sprintf("%.2f", $i/$count * 100);
            $backupdbtransfer .=  'echo -e "\r'.$ratio.' %         "'."\n";
            $i++;
        }
    }

    // Drop backup set.

    // Main host DB copy.
    $dropbackupdbstr = '# Drop Backup DB for '.$SITE->fullname."\n";
    $dropbackupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'DROP DATABASE IF EXISTS {$main->olddbname}_bak;' \n";

    // Active Vhosts DB copy.
    if ($vhosts) {
        $i = 1;
        $count = count($vhosts);
        foreach ($vhosts as $vhost) {

            $dropbackupdbstr .= "\n";
            $dropbackupdbstr .= '# Drop Backup DB for '.$vhost->name."\n";
            $dropbackupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'DROP DATABASE IF EXISTS {$hostreps[$vhost->name]->olddbname}_bak ;' \n";

            $ratio = sprintf("%.2f", $i/$count * 100);
            $dropbackupdbstr .=  'echo -e "\r'.$ratio.' %         "'."\n";
            $i++;
        }
    }

    // Backup Vhosts DB restore

    $restorebackupdbstr = '# Drop new DB for '.$SITE->fullname."\n";
    $restorebackupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'DROP DATABASE IF EXISTS {$main->newdbname};' \n";
    $restorebackupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'DROP DATABASE IF EXISTS {$main->olddbname};' \n";
    $restorebackupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'CREATE DATABASE {$main->olddbname};' \n";

    $restorebackupdbtransfer = '# Backup Data transfer for '.$SITE->fullname."\n";
    $restorebackupdbtransfer .= 'mysqldump '.$main->olddbname.'_bak -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' > temp.sql'."\n";
    $restorebackupdbtransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' '.$main->olddbname.' < temp.sql'."\n";

    if ($vhosts) {
        $i = 1;
        $count = count($vhosts);
        foreach ($vhosts as $vhost) {

            $restorebackupdbstr .= "\n";
            $restorebackupdbstr .= '# Backup DB creation for '.$vhost->name."\n";
            $restorebackupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'DROP DATABASE IF EXISTS {$hostreps[$vhost->name]->newdbname};' \n";

            $restorebackupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'DROP DATABASE IF EXISTS {$hostreps[$vhost->name]->olddbname};' \n";
            $restorebackupdbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'CREATE DATABASE {$hostreps[$vhost->name]->olddbname};' \n";
            $ratio = sprintf("%.2f", $i/$count * 100);
            $restorebackupdbstr .=  'echo -e "\r'.$ratio.' %         "'."\n";

            $restorebackupdbtransfer .= "\n";
            $restorebackupdbtransfer .= '# Backup Data transfer for '.$SITE->fullname."\n";
            $restorebackupdbtransfer .= 'mysqldump '.$hostreps[$vhost->name]->olddbname.'_bak -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' > temp.sql'."\n";
            $restorebackupdbtransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' '.$hostreps[$vhost->name]->olddbname.' < temp.sql'."\n";
            $restorebackupdbtransfer .=  'echo -e "\r'.$ratio.' %         "'."\n";
            $i++;
        }
    }

    // Database generator.

    // Main host DB copy.
    $dbstr = '# DB copy for '.$SITE->fullname."\n";
    $dbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'DROP DATABASE IF EXISTS {$main->newdbname};' \n";
    $dbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'CREATE DATABASE {$main->newdbname};' \n";


    $datatransfer = '# Data transfer for '.$SITE->fullname."\n";
    $datatransfer .= 'mysqldump '.$main->olddbname.' -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' > temp'.$data->fromversion.'.sql'."\n";
    $datatransfer .= '/bin/cp -f temp'.$data->fromversion.'.sql temp'.$data->toversion.'.sql'."\n";
    $datatransfer .= 'sed \'s/'.$main->currentwwwrootsed.'/'.$main->archivewwwrootsed.'/g\' -i temp'.$data->fromversion.'.sql'."\n";
    $datatransfer .= 'sed \'s/'.$main->originwwwrootsed.'/'.$main->currentwwwrootsed.'/g\' -i temp'.$data->toversion.'.sql'."\n";

    // Process main database for all peer host names, paths and references.
    if ($vhosts) {
        foreach ($vhosts as $vhost) {
            // This is a special copy that works the opposite way : newwwwroot is the older version to patch into the older database.
            $datatransfer .= 'sed \'s/'.$hostreps[$vhost->name]->currentwwwrootsed.'/'.$hostreps[$vhost->name]->archivewwwrootsed.'/g\' -i temp'.$data->fromversion.'.sql'."\n";
            $datatransfer .= 'sed \'s/'.$hostreps[$vhost->name]->originwwwrootsed.'/'.$hostreps[$vhost->name]->currentwwwrootsed.'/g\' -i temp'.$data->toversion.'.sql'."\n";

            $datatransfer .= 'sed \'s/'.$hostreps[$vhost->name]->olddatarootsed.'/'.$hostreps[$vhost->name]->newdatarootsed.'/g\' -i temp'.$data->toversion.'.sql'."\n";
            $datatransfer .= 'sed \'s/'.$hostreps[$vhost->name]->olddbnamesed.'/'.$hostreps[$vhost->name]->newdbnamesed.'/g\' -i temp'.$data->toversion.'.sql'."\n";
        }
    }

    $datatransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' '.$main->newdbname.' < temp'.$data->toversion.'.sql'."\n";

    // Old DB replacement.
    $datatransfer .= '# Old DB adjustements for '.$SITE->fullname."\n";
    $datatransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.    $datatransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' '.$main->olddbname.' < temp'.$data->fromversion.'.sql'."\n";

    $datatransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'CREATE DATABASE {$main->olddbname};' \n";
    $datatransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' '.$main->olddbname.' < temp'.$data->fromversion.'.sql'."\n";
    $datatransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' '.$main->olddbname."' -e 'UPDATE {$CFG->prefix}local_vmoodle SET vdbtype='mariadb' WHERE 1;' \n";

    // Main host replacements.

    // Active Vhosts DB copy.
    if ($vhosts) {
        $i = 1;
        $count = count($vhosts);
        foreach ($vhosts as $vhost) {

            $dbstr .= "\n";
            $dbstr .= '# DB copy for '.$vhost->name."\n";
            $dbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'DROP DATABASE IF EXISTS {$hostreps[$vhost->name]->newdbname};' \n";
            $dbstr .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'CREATE DATABASE {$hostreps[$vhost->name]->newdbname};' \n";

            $datatransfer .= "\n";
            $datatransfer .= '# Data transfer for '.$vhost->name."\n";
            $datatransfer .= 'mysqldump '.$hostreps[$vhost->name]->olddbname.' -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' > temp'.$data->fromversion.'.sql'."\n";
            $datatransfer .= '/bin/cp -f temp'.$data->fromversion.'.sql temp'.$data->toversion.'.sql'."\n";
            $datatransfer .= 'sed \'s/'.$hostreps[$vhost->name]->originwwwrootsed.'/'.$hostreps[$vhost->name]->archivewwwrootsed.'/g\' -i temp'.$data->fromversion.'.sql'."\n";
            $datatransfer .= 'sed \'s/'.$main->currentwwwrootsed.'/'.$main->archivewwwrootsed.'/g\' -i temp'.$data->fromversion.'.sql'."\n";
            $datatransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' '.$hostreps[$vhost->name]->newdbname.' < temp'.$data->toversion.'.sql'."\n";

            // Old DB replacement.
            $datatransfer .= '# Old DB adjustements for '.$vhost->name."\n";
            $datatransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'DROP DATABASE IF EXISTS {$hostreps[$vhost->name]->olddbname};' \n";
            $datatransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass."' -e 'CREATE DATABASE {$hostreps[$vhost->name]->olddbname};' \n";
            $datatransfer .= 'mysql -h'.$CFG->dbhost.' -u'.$CFG->dbuser.' -p\''.$CFG->dbpass.'\' '.$hostreps[$vhost->name]->olddbname.' < temp'.$data->fromversion.'.sql'."\n";
            $datatransfer .= 'rm temp'.$data->fromversion.'.sql'."\n";
            $datatransfer .= 'rm temp'.$data->toversion.'.sql'."\n";

            $ratio = sprintf("%.2f", $i/$count * 100);
            $datatransfer .=  'echo -e "\r'.$ratio.' %         "'."\n";
            $i++;
        }
    }

    // Main host data copy.
    $datastr = '# Data copy for '.$SITE->fullname."\n";
    $datastr .= "sudo -u{$data->webserveruser} rm -rf {$main->newdataroot}\n";
    $datastr .= "sudo -u{$data->webserveruser} rsync -r {$main->olddataroot} {$main->tomoodledatacontainer}\n";

    // Active Vhosts data copy.
    if ($vhosts) {
        $i = 1;
        $count = count($vhosts);
        foreach ($vhosts as $vhost) {

            $datarootbasename = basename($hostreps[$vhost->name]->olddataroot);
            $datastr .= "\n";
            $datastr .= '# Data copy for '.$vhost->name."\n";
            // $datastr .= "sudo -u{$data->webserveruser} rm -rf {$hostreps[$vhost->name]->newdataroot}\n";
            $datastr .= "sudo -u{$data->webserveruser} mkdir -p {$hostreps[$vhost->name]->newdataroot}\n";
            $datastr .= "sudo -u{$data->webserveruser} rsync -r -o -p -g --del {$hostreps[$vhost->name]->olddataroot} {$main->tomoodledatacontainer}\n";
            $datastr .= '# Purge eventual caches of '.$vhost->name."\n";
            $datastr .= "sudo -u{$data->webserveruser} rm -rf {$main->tomoodledatacontainer}/{$datarootbasename}/cache\n";
            $datastr .= "sudo -u{$data->webserveruser} rm -rf {$main->tomoodledatacontainer}/{$datarootbasename}/localcache\n";
            $datastr .= "sudo -u{$data->webserveruser} rm -rf {$main->tomoodledatacontainer}/{$datarootbasename}/muc\n";
            $datastr .= "sudo -u{$data->webserveruser} rm -rf {$main->tomoodledatacontainer}/{$datarootbasename}/lock\n";
            $datastr .= "sudo -u{$data->webserveruser} rm -rf {$main->tomoodledatacontainer}/{$datarootbasename}/sessions\n";

            $ratio = sprintf("%.2f", $i/$count * 100);
            $datastr .=  'echo -e "\r'.$ratio.' %         "'."\n";
            // Vhost replacements.
            $i++;
        }
    }

    /*
     * Moodle cronlines generator
     * Moodle data generator
     */

    // Main host DB copy.
    $cronstr = '# Cronlines '.$SITE->fullname."\n";
    if ($data->cronmode == 'cli') {
        $cronstr .= '*/10 * * * *  php '.$main->newdirroot.'/admin/cli/cron.php'."\n";
        $cronstr .= '*/1 * * * *  php '.$main->newdirroot.'/blocks/vmoodle/cli/vcron.php'."\n";
    } else {
        $cronstr .= '*/10 * * * *  wget -q -O /dev/null '.$main->newwwwroot.'/admin/cron.php'."\n";
        $cronstr .= '*/1 * * * *  wget -q -O /dev/null '.$main->newwwwroot.'/blocks/vmoodle/vcron.php'."\n";
    }

    /*
     * Moodle tools sudo generator
     * Moodle data generator
     */
    if (is_dir($CFG->dirroot.'/admin/tool/delivery')) {
        $sudostr = '# Sudo file processing (must be root)'."\n";
        $sudostr .= '/bin/cp -f /etc/sudoers.d/moodle'.$data->fromversion.'_sudos /etc/sudoers.d/moodle'.$data->toversion."_sudos\n";
        $sudostr .= 'chmod u+w /etc/sudoers.d/moodle'.$data->toversion."_sudos\n";
        $sudostr .= 'sed \'s/'.$data->fromversion.'/'.$data->toversion.'/g\' -i /etc/sudoers.d/moodle'.$data->toversion."_sudos\n";
        $sudostr .= 'chmod u-w /etc/sudoers.d/moodle'.$data->toversion."_sudos\n";
    }

    // Main host config change.
    $configstr = "/bin/cp -f {$main->newdirroot}/config.php {$main->newdirroot}/config.php.bak\n";
    $configstr .= "/bin/cp -f {$main->olddirroot}/config.php {$main->newdirroot}/config.php\n";
    $configstr .= "/bin/cp -f {$main->newdirroot}/{$vmoodletolocation}/vmoodle/vconfig.php {$main->newdirroot}/{$vmoodletolocation}/vmoodle/vconfig.php.bak\n";
    $configstr .= "/bin/cp -f {$main->olddirroot}/{$vmoodlefromlocation}/vmoodle/vconfig.php {$main->newdirroot}/{$vmoodletolocation}/vmoodle/vconfig.php\n";

    $configstr .= 'sed \'s/'.$main->currentwwwrootsed.'/'.$main->archivewwwrootsed.'/g\' -i '."{$main->olddirroot}/config.php\n";
    $configstr .= 'sed \'s/'.$main->olddirrootsed.'/'.$main->newdirrootsed.'/g\' -i '."{$main->newdirroot}/config.php\n";
    $configstr .= 'sed \'s/'.$main->oldmoodledatased.'/'.$main->newmoodledatased.'/g\' -i '."{$main->newdirroot}/config.php\n";
    $configstr .= 'sed \'s/'.$main->olddbname.'/'.$main->newdbname.'/g\' -i '."{$main->newdirroot}/config.php\n";
    if ($data->fromversion <= 31 && $data->toversion >= 34) {
        $configstr .= 'sed \'s/mysqli/mariadb/g\' -i '."{$main->newdirroot}/config.php\n";
    }
    $configstr .= 'sed \'s/'.$main->currentwwwrootsed.'/'.$main->archivewwwrootsed.'/g\' -i '."{$main->olddirroot}/config.php\n";

    $configstr .= 'sed \'s/'.$main->olddbname.'/'.$main->newdbname.'/g\' -i '."{$main->newdirroot}/{$vmoodletolocation}/vmoodle/vconfig.php\n";
    if ($data->fromversion <= 31 && $data->toversion >= 34) {
        $configstr .= 'sed \'s/mysqli/mariadb/g\' -i '."{$main->newdirroot}/{$vmoodletolocation}/vmoodle/vconfig.php\n";
    }

    // Main host upgrade.
    $preupgradestr = '# Pre upgrade for '.$SITE->fullname."\n";
    $preupgradestr .= "sudo -u{$data->webserveruser} php {$main->newdirroot}/admin/cli/mysql_compressed_rows.php --fix\n";
    if ($data->toversion >= 35) {
        $preupgradestr .= "sudo -u{$data->webserveruser} php {$main->newdirroot}/admin/cli/mysql_collation.php --collation=utf8mb4_general_ci\n";
    }

    $upgradestr = '# Full upgrade for '.$SITE->fullname."\n";
    $upgradestr .= "sudo -u{$data->webserveruser} php {$main->newdirroot}/admin/cli/upgrade.php  --non-interactive --allow-unstable\n";

    $postupgradestr = '# Post upgrade for '.$SITE->fullname."\n";
    $postupgradestr .= "sudo -u{$data->webserveruser} php {$main->newdirroot}/admin/cli/purge_caches.php\n";
    $postupgradestr .= "sudo -u{$data->webserveruser} php {$main->olddirroot}/blocks/user_mnet_hosts/cli/resync.php --host={$main->archivewwwroot}\n";
    $postupgradestr .= "wget {$main->archivewwwroot}/admin/cron.php?forcerenew=1\n";

    // Active Vhosts upgrades.
    if ($vhosts) {
        $i = 1;
        $count = count($vhosts);
        foreach ($vhosts as $vhost) {

            $upgradestr .= "\n";
            $upgradestr .= '# Full upgrade for ['.$vhost->name.'] '.$vhost->vhostname."\n";
            $upgradestr .= "sudo -u{$data->webserveruser} php {$main->newdirroot}/{$vmoodletolocation}/vmoodle/cli/upgrade.php --host={$hostreps[$vhost->name]->currentwwwroot} --non-interactive --allow-unstable\n";
            $ratio = sprintf("%.2f", $i/$count * 100);
            $upgradestr .=  'echo -e "\r'.$ratio.' %         "'."\n";

            $preupgradestr .= "\n";
            $preupgradestr .= '# Pre upgrade for ['.$vhost->name.'] '.$vhost->vhostname."\n";
            $preupgradestr .= "sudo -u{$data->webserveruser} php {$main->newdirroot}/{$vmoodletolocation}/vmoodle/cli/mysql_compressed_rows.php --fix --host={$hostreps[$vhost->name]->currentwwwroot}\n";
            if ($data->toversion >= 35) {
                $preupgradestr .= "sudo -u{$data->webserveruser} php {$main->newdirroot}/{$vmoodletolocation}/vmoodle/cli/mysql_collation.php --collation=utf8mb4_general_ci --host={$hostreps[$vhost->name]->currentwwwroot}\n";
            }
            $preupgradestr .=  'echo -e "\r'.$ratio.' %         "'."\n";

            $postupgradestr .= "\n";
            $postupgradestr .= '# Post upgrade for ['.$vhost->name.'] '.$vhost->vhostname."\n";
            $postupgradestr .= "sudo -u{$data->webserveruser} php {$main->newdirroot}/{$vmoodletolocation}/vmoodle/cli/purge_caches.php --host={$hostreps[$vhost->name]->currentwwwroot}\n";
            $postupgradestr .= "sudo -u{$data->webserveruser} php {$main->newdirroot}/{$vmoodletolocation}/vmoodle/cli/update_langpacks.php --host={$hostreps[$vhost->name]->currentwwwroot}\n";
            if (is_dir($CFG->dirroot.'/blocks/user_mnet_hosts')) {
                $postupgradestr .= "sudo -u{$data->webserveruser} php {$main->olddirroot}/blocks/user_mnet_hosts/cli/resync.php --host={$hostreps[$vhost->name]->archivewwwroot}\n";
            }
            if (is_dir($CFG->dirroot.'/blocks/vmoodle')) {
                $postupgradestr .= "wget {$hostreps[$vhost->name]->archivewwwroot}/admin/cron.php?forcerenew=1\n";
            }
            $postupgradestr .=  'echo -e "\r'.$ratio.' %         "'."\n";
            $i++;
        }
    }

    // Compute the nginx vhost file.
    $vhostnginxstr = implode('', file($CFG->dirroot.'/local/vmoodle/tools/templates/config-nginx.tpl'));
    $archivevhostnginxstr = implode('' , file($CFG->dirroot.'/local/vmoodle/tools/templates/config-nginx.tpl'));
    $newphpversion = '5.6';
    if ($data->toversion >= 35) {
        $newphpversion = '7.2';
    }
    if ($data->toversion >= 37) {
        $newphpversion = '7.3';
    }

    $oldphpversion = '5.6';
    if ($data->fromversion >= 35) {
        $oldphpversion = '7.2';
    }
    if ($data->fromversion >= 37) {
        $oldphpversion = '7.3';
    }

    $servernamesstr = '';
    $archiveservernamesstr = '';
    if ($vhosts) {
        foreach ($vhosts as $vhost) {
            $servernames[] = "    server_name {$hostreps[$vhost->name]->currentwwwroot};";
            $archiveservernames[] = "    server_name {$hostreps[$vhost->name]->archivewwwroot};";
        }
        $servernamesstr = implode("\n", $servernames);
        $archiveservernamesstr = implode("\n", $archiveservernames);
    }

    $vhostnginxstr = str_replace('{$subservernames}', $servernamesstr, $vhostnginxstr);
    $vhostnginxstr = str_replace('{$mainhostwwwroot}', $main->currentwwwroot, $vhostnginxstr);
    $vhostnginxstr = str_replace('{$mainhost}', str_replace('_', '-', $main->newdbname), $vhostnginxstr);
    $vhostnginxstr = str_replace('{$dirroot}', $main->newdirroot, $vhostnginxstr);
    $vhostnginxstr = str_replace('{$phpversion}', $newphpversion, $vhostnginxstr);

    $archivevhostnginxstr = str_replace('{$subservernames}', $archiveservernamesstr, $archivevhostnginxstr);
    $archivevhostnginxstr = str_replace('{$mainhostwwwroot}', $main->archivewwwroot, $archivevhostnginxstr);
    $archivevhostnginxstr = str_replace('{$mainhost}', str_replace('_', '-', $main->olddbname), $archivevhostnginxstr);
    $archivevhostnginxstr = str_replace('{$dirroot}', $main->olddirroot, $archivevhostnginxstr);
    $archivevhostnginxstr = str_replace('{$phpversion}', $oldphpversion, $archivevhostnginxstr);

}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('copyscripts', 'local_vmoodle'), 2);

$blockid = 1;

$fileareaurl = new moodle_url('/local/vmoodle/tools/filearea.php');
echo '<div class="generatedscripts-filearea-access"><a href="'.$fileareaurl.'">'.get_string('retrievefiles', 'local_vmoodle').'</a></div>';

if ($backupdbstr) {
    echo $OUTPUT->heading(get_string('backupdbcopyscript', 'local_vmoodle'), 3);
    echo $OUTPUT->heading(get_string('makebackup', 'local_vmoodle'), 4);
    echo '<div>Block: '.$blockid.'</div>';

    $blockid++;

    echo $OUTPUT->box('<pre>'.$backupdbstr.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'step1-create-backup-databases.sh';

    $fs->create_file_from_string($filedesc, $backupdbstr);

    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$backupdbtransfer.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'step2-copy-backup-databases.sh';

    $fs->create_file_from_string($filedesc, $backupdbtransfer);

    echo $OUTPUT->heading(get_string('restorebackup', 'local_vmoodle'), 4);
    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$restorebackupdbstr.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'tool-recreate-old-bases.sh';

    $fs->create_file_from_string($filedesc, $restorebackupdbstr);

    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$restorebackupdbtransfer.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'tool-recover-old-bases.sh';

    $fs->create_file_from_string($filedesc, $restorebackupdbtransfer);

    echo $OUTPUT->heading(get_string('dropbackup', 'local_vmoodle'), 4);
    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$dropbackupdbstr.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'tool-cleanout-old-backups.sh';

    $fs->create_file_from_string($filedesc, $dropbackupdbstr);
}

if ($dbstr) {
    echo $OUTPUT->heading(get_string('dbcopyscript', 'local_vmoodle'), 3);
    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$dbstr.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'step3-create-new-databases.sh';

    $fs->create_file_from_string($filedesc, $dbstr);

    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$datatransfer.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'step4-copy-transform-databases.sh';

    $fs->create_file_from_string($filedesc, $datatransfer);
}

if ($datastr) {
    echo $OUTPUT->heading(get_string('datacopyscript', 'local_vmoodle'), 3);
    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$datastr.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'step5-copy-moodledatas.sh';

    $fs->create_file_from_string($filedesc, $datastr);
}

if ($configstr) {
    echo $OUTPUT->heading(get_string('adjustconfig', 'local_vmoodle'), 3);
    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$configstr.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'step6-adjust-config-files.sh';

    $fs->create_file_from_string($filedesc, $configstr);
}

if ($preupgradestr) {
    echo $OUTPUT->heading(get_string('preupgrade', 'local_vmoodle'), 3);
    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$preupgradestr.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'step7-pre-upgrade-ops.sh';

    $fs->create_file_from_string($filedesc, $preupgradestr);
}

if ($upgradestr) {
    echo $OUTPUT->heading(get_string('upgrade', 'local_vmoodle'), 3);
    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$upgradestr.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'step8-upgrade.sh';

    $fs->create_file_from_string($filedesc, $upgradestr);
}

if ($postupgradestr) {
    echo $OUTPUT->heading(get_string('postupgrade', 'local_vmoodle'), 3);
    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$postupgradestr.'</pre>');

    // Make a file
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'step9-post-upgrade.sh';

    $fs->create_file_from_string($filedesc, $postupgradestr);
}

if ($cronstr) {
    echo $OUTPUT->heading(get_string('cronlines', 'local_vmoodle'), 3);
    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$cronstr.'</pre>');
}

if ($sudostr) {
    echo $OUTPUT->heading(get_string('sudos', 'local_vmoodle'), 3);
    echo '<div>Block: '.$blockid.'</div>';
    $blockid++;
    echo $OUTPUT->box('<pre>'.$sudostr.'</pre>');
}

if ($vhostnginxstr) {
    // Make a nginx config files
    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'new-nginx-config.vhost';

    $fs->create_file_from_string($filedesc, $vhostnginxstr);

    $filedesc = new StdClass;
    $filedesc->contextid = $contextid;
    $filedesc->component = 'local_vmoodle';
    $filedesc->filearea = 'migrationscripts';
    $filedesc->itemid = 0;
    $filedesc->filepath = '/';
    $filedesc->filename = 'old-nginx-config.vhost';

    $fs->create_file_from_string($filedesc, $archivevhostnginxstr);
}

$mform->display();

echo $OUTPUT->footer();
