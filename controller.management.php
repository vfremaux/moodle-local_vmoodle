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
 * This file catches an action and do the corresponding usecase.
 * Called by 'view.php'.
 *
 * @usecase add (form)
 * @usecase doadd
 * @usecase edit (form)
 * @usecase doedit
 * @usecase enable
 * @usecase disable
 * @usecase snapshot
 * @usecase delete
 * @usecase fulldelete
 * @usecase renewall
 * @usecase generateconfigs
 *
 * @package local_vmoodle
 * @category local
 * @author Moheissen Fabien (fabien.moheissen@gmail.com)
 * @copyright valeisti (http://www.valeisti.fr)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
defined('MOODLE_INTERNAL') || die();

Use \local_vmoodle\Mnet_Peer;

// Includes the MNET library.
require_once($CFG->dirroot.'/mnet/lib.php');
require_once($CFG->dirroot.'/local/vmoodle/lib.php');

// Add needed javascript here (because addonload() is needed before).

$PAGE->requires->js('/local/vmoodle/js/host_form.js');

$config = get_config('local_vmoodle');

// It must be included from 'view.php' in local/vmoodle.

// Confirmation message.
$messageobject = new StdClass();
$messageobject->message = '';
$messageobject->style = 'notifyproblem';

// Execution time can take more than 30 sec (PHP default value).
$initialmaxexectime = ini_get('max_execution_time');
if ($initialmaxexectime > 0) {
    set_time_limit(0);
}

/* *************************** Make the ADD form *********** */
if ($action == 'add') {

    // Test the number of templates.
    $templates = vmoodle_get_available_templates();
    if (!empty($templates)) {

        // Default configuration (automated schema).
        if (@$config->automatedschema) {
            $platformform = new StdClass();
            $platformform->vhostname = (@$config->vmoodlehost) ? $config->vmoodlehost : 'localhost';
            $platformform->vdbtype = (@$config->vdbtype) ? $config->vdbtype : 'mariadb';
            $platformform->vdbhost = (@$config->vdbhost) ? $config->vdbhost : 'localhost';
            $platformform->vdblogin = $config->vdblogin;
            $platformform->vdbpass = $config->vdbpass;
            $platformform->vdbname = $config->vdbbasename;
            $platformform->vdbprefix = (@$config->vdbprefix) ? $config->vdbprefix : 'mdl_';
            $platformform->vdbpersist = (@$config->vdbpersist) ? 1 : 0;
            $platformform->vdatapath = stripslashes($config->vdatapathbase);

            if ($config->mnet == 'NEW') {
                $lastsubnetwork = $DB->get_field('local_vmoodle', 'MAX(mnet)', array());
                $platformform->mnet = $lastsubnetwork + 1;
            } else {
                $platformform->mnet = 0 + @$config->mnet;
            }

            $platformform->services = $config->services;

            // Try to get crontab (Linux).
            if ($CFG->ostype != 'WINDOWS') {
                $crontabcmd = escapeshellcmd('crontab -l');
                $platformform->crontab = passthru($crontabcmd);
            }

            // Data are placed in session for displaying.
            unset($SESSION->vmoodledata);
            echo $OUTPUT->header();
            $form = new \local_vmoodle\Host_Form('add');
            $form->set_data($platformform);
            $form->display();
            echo $OUTPUT->footer();
            die;
        }
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->box(get_string('notemplates', 'local_vmoodle'));
        echo $OUTPUT->continue_button(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
        echo $OUTPUT->footer();
        die;
    }
}
/* *************************** Do ADD actions *********** */
if ($action == 'doadd') {

    $interactive = empty($automation);

    if (function_exists('debug_trace')) {
        debug_trace('Start vnode addition');
    }

    if ($interactive) {
        $vmoodlestep = optional_param('step', 0, PARAM_INT);

        // Retrieve submitted data, from the add form.
        unset($SESSION->vmoodle_mg['dataform']);
        $platformform = new \local_vmoodle\Host_Form('add', null);

        // Check if form is cancelled.
        if ($platformform->is_cancelled()) {
            if (function_exists('debug_trace')) {
                debug_trace('Vnode addition cancelled');
            }
            echo $OUTPUT->notification(get_string('cancelled', 'local_vmoodle'));
            redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management', 'what' => 'list')));
        }
    }

    // If there is submitted data from form or in session (no errors).
    if (!isset($SESSION->vmoodledata)) {
        $submitteddata = $SESSION->vmoodledata = $platformform->get_data();
    } else {
        $submitteddata = $SESSION->vmoodledata;
    }

    if (!$submitteddata) {
        if (function_exists('debug_trace')) {
            debug_trace('Vnode No submitted data. Exiting.');
        }

        // Rollback to form.
        unset($SESSION->vmoodledata);
        echo $OUTPUT->header();
        $platformform->display();
        echo $OUTPUT->footer();
        die;
    }

    if (function_exists('debug_trace')) {
        debug_trace('Vnode addition processing input data');
    }

    if (empty($submitteddata->vtemplate)) {

        // Update potentially existing record.
        $sqlrequest = 'UPDATE
                            {mnet_host}
                       SET
                            deleted = 0
                       WHERE
                            wwwroot = "'.$submitteddata->vhostname.'"';
        $DB->execute($sqlrequest);

        // Check if ever was existing or not.
        $sqlrequest = 'SELECT
                        *
                       FROM
                            {local_vmoodle}
                       WHERE
                            vhostname = "'.$submitteddata->vhostname.'"';
        $record = $DB->get_record_sql($sqlrequest);

        // In case not, create a new record and exit.
        $new = false;
        if (empty($record)) {
            $new = true;
            $record = (object) array('name' => $submitteddata->name,
                                     'shortname' => $submitteddata->shortname,
                                     'description' => $submitteddata->description,
                                     'vhostname' => $submitteddata->vhostname,
                                     'vdbtype' => $submitteddata->vdbtype,
                                     'vdbhost' => $submitteddata->vdbhost,
                                     'vdblogin' => $submitteddata->vdblogin,
                                     'vdbpass' => $submitteddata->vdbpass,
                                     'vdbname' => $submitteddata->vdbname,
                                     'vdbpersist' => $submitteddata->vdbpersist,
                                     'vdbprefix' => $submitteddata->vdbprefix,
                                     'vdbpersist' => $submitteddata->vdbpersist,
                                     'vdatapath' => $submitteddata->vdatapath,
                                     'mnet' => $submitteddata->mnet);
            $DB->insert_record('local_vmoodle', $record);
        }

        if (!is_dir($submitteddata->vdatapath)) {
            // Make a datapath if not existing.
            mkdir($submitteddata->vdatapath, 0755, true);
        }

        // If using domain subpath, add the subpath symlink (Linux only).
        if (!empty($CFG->vmoodleusesubpaths)) {
            vmoodle_add_subpath($submitteddata);
        }

        // Ensure a database is created. Let the existing database play if already exists.
        vmoodle_create_database($submitteddata);

        if (function_exists('debug_trace')) {
            debug_trace('Vnode simple reactivation');
        }

        if ($interactive) {
            if (!$new) {
                $messageobject->message = get_string('platformreactivate', 'local_vmoodle');
            } else {
                $messageobject->message = get_string('newplatformregistered', 'local_vmoodle');
            }
            $messageobject->style = 'notifysuccess';
            $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
            redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
        }
        return 0;
    } else {

        // Checks if the chosen template still exists.
        $templates = vmoodle_get_available_templates();
        if (empty($templates) || !vmoodle_exist_template($submitteddata->vtemplate)) {

            if (function_exists('debug_trace')) {
                debug_trace('Vnode no template. Exiting.');
            }

            // If the snapshot has been deleted between loading the add form and submitting it.
            $messageobject->message = get_string('notemplates', 'local_vmoodle');
            if ($interactive) {
                $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
                redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
            }
            mtrace(get_string('notemplates', 'local_vmoodle'));
            return -1;
        }

        // Check if the required hostname has DNS resolution.
        $domainname = preg_replace('/https?:\/\//', '', $submitteddata->vhostname); // Remove protocol.
        $domainname = preg_replace('/\/.*/', '', $domainname); // Remove all trailing path.
        if (!gethostbynamel($domainname)) {
            if (!empty($submitteddata->forcedns)) {
                if (!$interactive) {
                    mtrace('unknownhostforced', 'local_vmoodle');
                }
            } else {

                if (function_exists('debug_trace')) {
                    debug_trace('Vnode unkown host non force. Exiting.');
                }

                $messageobject->message = get_string('unknownhost', 'local_vmoodle'). ' : '.$domainname;
                $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
                if ($interactive) {
                    redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
                }
                mtrace($SESSION->vmoodle_ma['confirm_message']->message);
                return -1;
            }
        }

        // Do we have a "self" host record ?
        if (!$thisashost = $DB->get_record('mnet_host', array('wwwroot' => $CFG->wwwroot))) {

            if (function_exists('debug_trace')) {
                debug_trace('Vnode self mnet host is missing. Exiting.');
            }

            // If loading this host's data has failed.
            $messageobject->message = get_string('badthishostdata', 'local_vmoodle');
            $messageobject->style = 'notifyproblem';

            if ($interactive) {
                $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
                redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
            }
            mtrace(get_string('badthishostdata', 'local_vmoodle'));
            return -1;
        }

        // Creates database from template.

        if ($vmoodlestep == 0) {

            if (function_exists('debug_trace')) {
                debug_trace('Vnode STEP 0.');
            }

            if (!vmoodle_load_database_from_template($submitteddata, $CFG->dataroot.'/vmoodle')) {

                if (function_exists('debug_trace')) {
                    debug_trace('Vnode could not load DB. Exiting.');
                }

                // If loading database from template has failed.
                unset($SESSION->vmoodledata);
                $messageobject->message = get_string('badtemplatation', 'local_vmoodle');
                $messageobject->style = 'notifyproblem';

                if ($interactive) {
                    $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
                    redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
                }

                mtrace(get_string('badtemplatation', 'local_vmoodle'));
                return -1;
            }

            if ($interactive) {
                echo $OUTPUT->header();
                echo $OUTPUT->notification(get_string('vmoodledoadd1', 'local_vmoodle'), 'notifysuccess');
                $params = array('view' => 'management', 'what' => 'doadd', 'step' => 1);
                echo $OUTPUT->continue_button(new moodle_url('/local/vmoodle/view.php', $params));
                echo $OUTPUT->footer();
                die;
            }
            return 0;
        }

        // Fix remote database for Mnet operations.

        /*
         * Fixing database will rewrite and prepare the remote mnet_host table for having
         * consistant identity of the VMoodle Master node.
         * Additionnaly, some data from instance addition form should be forced into
         * the SQL template, whatever the configuration of the original Moodle was.
         *
         * A script backup is available in vmoodle data directory as
         *
         * vmoodle_setup_template.temp.sql
         *
         * with all fixing SQL instructions processed.
         */

        if ($vmoodlestep == 1) {

            if (function_exists('debug_trace')) {
                debug_trace('Vnode STEP 1.');
            }

            if (!vmoodle_fix_database($submitteddata, $thisashost, $CFG->dataroot.'/vmoodle')) {

                if (function_exists('debug_trace')) {
                    debug_trace('Fixing database error. Exiting.');
                }

                // If fixing database has failed.
                unset($SESSION->vmoodledata);
                $messageobject->message = get_string('couldnotfixdatabase', 'local_vmoodle');
                $messageobject->style = 'notifyproblem';
                $SESSION->vmoodle_ma['confirm_message'] = $messageobject;

                if ($interactive) {
                    redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
                    die;
                }

                mtrace($SESSION->vmoodle_ma['confirm_message']->message);
                return -1; 
            }

            if ($interactive) {
                echo $OUTPUT->header();
                echo $OUTPUT->notification(get_string('vmoodledoadd2', 'local_vmoodle'), 'notifysuccess');
                if (debugging()) {
                    $params = array();
                    $params['view'] = 'management';
                    $params['what'] = 'doadd';
                    $params['step'] = 2;
                    echo '<center>';
                    $label = get_string('skip', 'local_vmoodle');
                    echo $OUTPUT->single_button(new moodle_url('/local/vmoodle/view.php', $params), $label, 'get');
                    echo '</center>';
                }
                $params = array('view' => 'management', 'what' => 'doadd', 'step' => 2);
                echo $OUTPUT->continue_button(new moodle_url('/local/vmoodle/view.php', $params));
                echo $OUTPUT->footer();
                die;
            }
            return 0;
        }

        // Get fileset for moodledata.

        if ($vmoodlestep == 2) {

            if (function_exists('debug_trace')) {
                debug_trace('Vnode STEP 2.');
            }

            vmoodle_dump_files_from_template($submitteddata->vtemplate, $submitteddata->vdatapath);
            if ($interactive) {
                echo $OUTPUT->header();
                echo $OUTPUT->notification(get_string('vmoodledoadd3', 'local_vmoodle'), 'notifysuccess');
                $params = array('view' => 'management', 'what' => 'doadd', 'step' => 3);
                echo $OUTPUT->continue_button(new moodle_url('/local/vmoodle/view.php', $params));
                echo $OUTPUT->footer();
                die;
            }
            return 0;
        }

        // Insert proper vmoodle record.

        if ($vmoodlestep == 3) {

            if (function_exists('debug_trace')) {
                debug_trace('Vnode STEP 3.');
            }

            // Adds the new virtual instance record, with all data if everything is done.
            $submitteddata->timecreated = time();
            $submitteddata->vhostname = preg_replace("/\/$/", '', $submitteddata->vhostname); // Fix possible misslashing.

            if ($submitteddata->mnet == 'NEW') {
                $maxmnet = vmoodle_get_last_subnetwork_number();
                $submitteddata->mnet = $maxmnet + 1;
            }

            if (!$oldrec = $DB->get_record('local_vmoodle', array('vhostname' => $submitteddata->vhostname))) {
                $DB->insert_record('local_vmoodle', $submitteddata);
            } else {
                $submitteddata->id = $oldrec->id;
                $DB->update_record('local_vmoodle', $submitteddata);
            }

            // If using domain subpath, add the subpath symlink (Linux only).
            if (!empty($CFG->vmoodleusesubpaths)) {
                vmoodle_add_subpath($submitteddata);
            }

            // Finish the step.
            if ($interactive) {
                echo $OUTPUT->header();
                echo $OUTPUT->notification(get_string('vmoodledoadd4', 'local_vmoodle'), 'notifysuccess');
                $params = array('view' => 'management', 'what' => 'doadd', 'step' => 4);
                echo $OUTPUT->continue_button(new moodle_url('/local/vmoodle/view.php', $params));
                echo $OUTPUT->footer();
                die;
            }

            return 0;
        }

        // Mnet bind from master side.
        if ($vmoodlestep == 4) {

            if (function_exists('debug_trace')) {
                debug_trace('Vnode STEP 4.');
            }

            $newmnethost = new \local_vmoodle\Mnet_Peer();
            $newmnethost->set_wwwroot($submitteddata->vhostname);
            $newmnethost->set_name($submitteddata->name);

            // If the new host is not using MNET, we discard it from us. There will be no more MNET contact with this host.
            // vmoodle_fix_database should have disabled all mnet operations in the remote moodle.
            if ($submitteddata->mnet == -1) {
                $newmnethost->updateparams->deleted = 1;
                $newmnethost->commit();
                $messageobject->message = get_string('successaddnewhostwithoutmnet', 'local_vmoodle');
                $message = new StdClass();
                $message->style = 'notifysuccess';
                $SESSION->vmoodle_ma['confirm_message'] = $messageobject;

                if ($interactive) {
                    redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
                }

                mtrace('Finished without MNET.');
                return 1;
            }

            // Force renew using remote keyboot.php access.
            $uri = $submitteddata->vhostname.'/local/vmoodle/keyboot.php';

            $rq = 'pk='.urlencode($thisashost->public_key);
            $ch = curl_init("$uri");
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Moodle');
            curl_setopt($ch, CURLOPT_POSTFIELDS, $rq);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml charset=UTF-8"));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            // Try remote key booting.
            if (!$res = curl_exec($ch)) {

                if (function_exists('debug_trace')) {
                    debug_trace('Vnode remote key booting failure. CURL Error. Exiting.');
                }

                // If remote keybooting has failed.
                $messageobject->message = get_string('couldnotkeyboot', 'local_vmoodle', 'CURL Error');
                $messageobject->style = 'notifyproblem';

                if ($interactive) {
                    $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
                    redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
                }

                mtrace(get_string('couldnotkeyboot', 'local_vmoodle', 'CURL Error'));
                return -1;
            }

            if (preg_match('/ERROR/', $res)) {

                if (function_exists('debug_trace')) {
                    debug_trace('Vnode remote key booting failure. Other. Exiting.');
                }

                // If remote keybooting has failed.
                $messageobject->message = get_string('couldnotkeyboot', 'local_vmoodle', $res);
                $messageobject->style = 'notifyproblem';

                if ($interactive) {
                    $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
                    redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
                }

                mtrace(get_string('couldnotkeyboot', 'local_vmoodle', $res));
                return -1;
            }
            curl_close($ch);

            // Force new virtual host to renew our key and send his own to us.

            if (!$newmnethost->bootstrap($submitteddata->vhostname, null, 'moodle', 1, $submitteddata->name)) {
                // If bootstraping the new host has failed.
                $messageobject->message = 'bootstrap failure :<br/> '.implode("\n", $newmnethost->errors);
                $messageobject->style = 'notifyproblem';
                $SESSION->vmoodle_ma['confirm_message'] = $messageobject;

                if ($interactive) {
                    if (debugging()) {
                        echo $OUTPUT->header();
                        echo $OUTPUT->continue_button(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
                        echo $OUTPUT->footer();
                        die;
                    } else {
                        redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
                    }
                }

                mtrace('bootstrap failure');
                return -1;
            }
            $newmnethost->updateparams->deleted = 0; // In case already there and needs revive.
            $newmnethost->commit();

            // We need to start output here in case of exceptions.

            // Service 'mnetadmin' is needed to speak with new host. Set it our side.
            $slavehost = $DB->get_record('mnet_host', array('wwwroot' => $submitteddata->vhostname));

            // Cleanup any previous records.
            $DB->delete_records('mnet_host2service', array('hostid' => $slavehost->id));
            $mnetadminservice = $DB->get_record('mnet_service', array('name' => 'mnetadmin'));
            $host2service = new stdclass();
            $host2service->hostid = $slavehost->id;
            $host2service->serviceid = $mnetadminservice->id;
            $host2service->publish = 0;
            $host2service->subscribe = 1;
            $DB->insert_record('mnet_host2service', $host2service);

            $ssoservice = $DB->get_record('mnet_service', array('name' => 'sso_idp'));
            $host2service = new stdclass();
            $host2service->hostid = $slavehost->id;
            $host2service->serviceid = $ssoservice->id;
            $host2service->publish = 1;
            $host2service->subscribe = 0;
            $DB->insert_record('mnet_host2service', $host2service);

            $ssoservice = $DB->get_record('mnet_service', array('name' => 'sso_sp'));
            $host2service = new stdclass();
            $host2service->hostid = $slavehost->id;
            $host2service->serviceid = $ssoservice->id;
            $host2service->publish = 0;
            $host2service->subscribe = 1;
            $DB->insert_record('mnet_host2service', $host2service);

            // MNET subnetworking, unless completely isolated.
            if ($submitteddata->mnet > 0) {

                if (function_exists('debug_trace')) {
                    debug_trace('Vnode mnet bindings.');
                }

                vmoodle_bind_to_network($submitteddata, $newmnethost);
            }
        }

        // Every step was SUCCESS.
        $messageobject->message = get_string('successaddnewhost', 'local_vmoodle');
        $messageobject->style = 'notifysuccess';

        // Save confirm message before redirection.
        unset($SESSION->vmoodledata);
        $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
        if ($interactive) {
            redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management'))); // Finished.
        }

        mtrace('Finished.');
        return 0; // Finished.
    }
}

/* *************************** Make the EDIT form *********** */
if ($action == 'edit') {

    // Retrieve the vmoodle platform data.
    $id = required_param('id', PARAM_INT);
    if ($platformform = $DB->get_record('local_vmoodle', array('id' => $id))) {

        // Print title (heading).
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('editvmoodle', 'local_vmoodle'));
        // Print beginning of a box.
        echo $OUTPUT->box_start();
        // Displays the form with data (and errors).
        $form = new \local_vmoodle\Host_Form('edit');
        $form->set_data($platformform);
        $form->display();

        // Print ending of a box.
        echo $OUTPUT->box_end();
        echo $OUTPUT->footer();
        die;
    }
}

/* *************************** Do EDIT actions *********** */

if ($action == 'doedit') {
    // Retrieves data from the edit form.
    $platformform = new \local_vmoodle\Host_Form('edit');

    // Checks if form is cancelled.
    if ($platformform->is_cancelled()) {
        redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
    }

    // If there is submitted data (no errors).
    if ($submitteddata = $platformform->get_data()) {

        // Updates the host, with all data.
        $olddata = $DB->get_record('local_vmoodle', array('id' => $submitteddata->id));
        $success = false;

        if (!$DB->update_record('local_vmoodle', $submitteddata)) {
            // If updating data in 'local_vmoodle' table has failed.
            $messageobject->message = get_string('badblockupdate', 'local_vmoodle');
            $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
            redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
        }

        // Updates MNET state, if required.
        if ($olddata->mnet != $submitteddata->mnet) {

            // Creating the needed mnet_peer object, to do actions.
            $editedhost = new \local_vmoodle\Mnet_Peer();
            if (!$editedhost->bootstrap($olddata->vhostname, null, 'moodle', 1)) {
                // If bootstraping the host has failed.
                $badstr = get_string('badbootstraphost', 'local_vmoodle', $olddata->vhostname);
                $messageobject->message = $badstr.' = '.$submitteddata->mnet;
                $manageurl = new moodle_url('/local/moodle/view.php', array('view' => 'management'));
                if (debugging()) {
                    echo $OUTPUT->header();
                    echo implode('<br/>', $editedhost->errors);
                    echo $OUTPUT->continue_button($manageurl);
                    echo $OUTPUT->footer();
                } else {
                    $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
                    redirect($manageurl);
                }
                die;
            }

            // Commit the updated key in DB.
            $editedhost->commit();

            // Retrieves last subnetwork members.
            if ($olddata->mnet > 0) {
                $lastsubnetworkhosts = array();
                $sql = '
                    SELECT
                        *
                    FROM
                        {local_vmoodle}
                    WHERE
                        id != ? AND
                        mnet = ? AND
                        enabled = 1
                ';
                $lastsubnetworkmembers = $DB->get_records_sql($sql, array($olddata->id, $olddata->mnet));
                if (!empty($lastsubnetworkmembers)) {
                    foreach ($lastsubnetworkmembers as $lastsubnetworkmember) {
                        $temphost = new stdClass();
                        $temphost->wwwroot = $lastsubnetworkmember->vhostname;
                        $temphost->name = utf8_decode($lastsubnetworkmember->name);
                        $lastsubnetworkhosts[] = $temphost;
                    }
                }
            }

            // Prepares future subnetwork members.
            if ($submitteddata->mnet > 0) {
                $subnetworkhosts = array();
                $sql = '
                    SELECT
                        *
                    FROM
                        {local_vmoodle}
                    WHERE
                        id != '.$submitteddata->id.' AND
                        mnet = '.$submitteddata->mnet.' AND
                        enabled = 1
                ';
                $subnetworkmembers = $DB->get_records_sql($sql);
                if (!empty($subnetworkmembers)) {
                    foreach ($subnetworkmembers as $subnetworkmember) {
                        $temphost = new stdClass();
                        $temphost->wwwroot = $subnetworkmember->vhostname;
                        $temphost->name = utf8_decode($subnetworkmember->name);
                        $subnetworkhosts[] = $temphost;
                    }
                }
            }

            /*
             * Deletes peer in last subnetwork members, and disconnects
             * peer from them, if was subnetworking.
             */
            if ($olddata->mnet > 0) {

                // Call to 'unbind_peer'.
                $rpcclient = new \local_vmoodle\XmlRpc_Client();
                $rpcclient->set_method('local/vmoodle/rpclib.php/mnetadmin_rpc_unbind_peer');
                // Authentication params.
                $rpcclient->add_param($USER->username, 'string');
                $userhostroot = $DB->get_field('mnet_host', 'wwwroot', array('id' => $USER->mnethostid));
                $rpcclient->add_param($userhostroot, 'string');
                $rpcclient->add_param($CFG->wwwroot, 'string');
                // Peer to unbind from.
                $rpcclient->add_param($editedhost->wwwroot, 'string');
                foreach ($lastsubnetworkhosts as $lastsubnetworkhost) {
                    $tempmember = new \local_vmoodle\Mnet_Peer();
                    $tempmember->set_wwwroot($lastsubnetworkhost->wwwroot);
                    // RPC error.
                    if (!$rpcclient->send($tempmember)) {
                        echo $OUTPUT->notification(implode('<br />', $rpcclient->get_errors($tempmember)));
                        if (debugging()) {
                            echo '<pre>';
                            var_dump($rpcclient);
                            echo '</pre>';
                        }
                    }

                    // Unbind other from edited.
                    // Call to 'disconnect_from_subnetwork'.
                    $rpcclient2 = new \local_vmoodle\XmlRpc_Client();
                    $rpcclient2->set_method('local/vmoodle/rpclib.php/mnetadmin_rpc_unbind_peer');
                    // Authentication params.
                    $rpcclient2->add_param($USER->username, 'string');
                    $userhostroot = $DB->get_field('mnet_host', 'wwwroot', array('id' => $USER->mnethostid));
                    $rpcclient2->add_param($userhostroot, 'string');
                    $rpcclient2->add_param($CFG->wwwroot, 'string');
                    // Other to unbind from.
                    $rpcclient2->add_param($lastsubnetworkhost->wwwroot, 'string');
                    // RPC error.
                    if (!$rpcclient2->send($editedhost)) {
                        echo $OUTPUT->header();
                        echo $OUTPUT->notification(implode('<br />', $rpcclient2->get_errors($editedhost)));
                        if (debugging()) {
                            echo '<pre>';
                            var_dump($rpcclient2);
                            echo '</pre>';
                        }
                    }
                    unset($rpcclient2);
                }
            }

            /*
             * Rebind peer to the new subnetwork members, and connect
             * it to them, if it is subnetworking and not creating new subnetwork.
             */
            if (($submitteddata->mnet > 0) && ($submitteddata->mnet <= vmoodle_get_last_subnetwork_number())) {
                vmoodle_bind_to_network($submitteddata, $editedhost);
            }

            // First check for global mnet disabing/reviving.
            if ($submitteddata->mnet > -1) {
                $editedhost->updateparams->deleted = 0;
            } else {
                /*
                 * this host has been unbound from all others
                 * we should remotely disable its network
                 */
                $editedhost->updateparams->deleted = 1;
                $editedhost->commit();
            }

            // Every step was SUCCESS.
            $success = true;
        } else {
            // Every step was SUCCESS.
            $success = true;
        }

        // Every step was SUCCESS.
        if (isset($success) && $success) {
            $messageobject->message = get_string('successedithost', 'local_vmoodle').' ';
            $messageobject->style = 'notifysuccess';
        }

        // Save confirm message before redirection.
        $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
        redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
    }
}
/* *************************** Enables a Vmoodle *********** */
if ($action == 'enable') {
    $vmoodleid = required_param('id', PARAM_INT);
    $DB->set_field('local_vmoodle', 'enabled', 1, array('id' => $vmoodleid));
    redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
}
/* *************************** Disables a vmoodle *********** */
if ($action == 'disable') {
    $vmoodleid = required_param('id', PARAM_INT);
    $DB->set_field('local_vmoodle', 'enabled', 0, array('id' => $vmoodleid));
    redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
}
/* *************************** Snapshots a Vmoodle in the templates *********** */
if ($action == 'snapshot') {

    // Parsing url for building the template name.
    if (empty($automation)) {
        $wwwroot = optional_param('wwwroot', '', PARAM_URL);
        $vmoodlestep = optional_param('step', 0, PARAM_INT);
    }
    $hostname = preg_replace('/https?:\/\//', '', $wwwroot);
    $hostname = str_replace(':', '_', $hostname);
    $hostname = str_replace('.', '_', $hostname);
    $hostname = str_replace('-', '_', $hostname);
    $hostname = str_replace('/', '_', $hostname);
    $hostname = str_replace('\\', '_', $hostname);

    // Make template directory (files and SQL).
    $templatefoldername = 'vmoodle';
    $separator = DIRECTORY_SEPARATOR;
    $relativedatadir = $templatefoldername.$separator.$hostname.'_vmoodledata';
    $absolutedatadir = $CFG->dataroot.$separator.$relativedatadir;
    $relativesqldir = $templatefoldername.$separator.$hostname.'_sql';
    $absolutesqldir = $CFG->dataroot.$separator.$relativesqldir;

    if (preg_match('/ /', $absolutesqldir)) {
        print_error('errorbaddirectorylocation', 'local_vmoodle');
        if ($automation) {
            return -1;
        }
    }

    if (!filesystem_is_dir('vmoodle', $CFG->dataroot)) {
        mkdir($CFG->dataroot.'/vmoodle');
    }

    if ($vmoodlestep == 0) {
        // Create directories, if necessary.
        if (!filesystem_is_dir($relativedatadir, $CFG->dataroot)) {
            mkdir($absolutedatadir, 0777, true);
        } else {
            filesystem_clear_dir($relativedatadir, false, $CFG->dataroot);
        }
        if (!filesystem_is_dir($relativesqldir, $CFG->dataroot)) {
            mkdir($absolutesqldir, 0777, true);
        }
        if (empty($automation)) {
            echo $OUTPUT->header();
            echo $OUTPUT->notification(get_string('vmoodlesnapshot1', 'local_vmoodle'), 'notifysuccess');
            $params = array('view' => 'management', 'what' => 'snapshot', 'step' => 1, 'wwwroot' => $wwwroot);
            echo $OUTPUT->continue_button(new moodle_url('/local/vmoodle/view.php', $params));
            echo $OUTPUT->footer();
            die;
        } else {
            // Chain following steps.
            $vmoodlestep++;
        }
    }
    if ($vmoodlestep > 0) {
        if ($wwwroot == $CFG->wwwroot) {
            // Make fake Vmoodle record.
            $vmoodle = vmoodle_make_this();
            $vdatabase = '';
            $vdatapath = $CFG->dataroot;
        } else {
            // Get Vmoodle known record.
            $vmoodle = $DB->get_record('local_vmoodle', array('vhostname' => $wwwroot));
            $vdatabase = '';
            $vdatapath = $vmoodle->vdatapath;
        }

        if ($vmoodlestep == 1) {
            // Auto dump the database in a master template_folder.
            if (!vmoodle_dump_database($vmoodle, $absolutesqldir.$separator.'vmoodle_master.sql')) {
                print_error('baddumpcommandpath', 'local_vmoodle');
                if ($automation) {
                    return -1;
                }
            }
            if (empty($automation)) {
                echo $OUTPUT->header();
                echo $OUTPUT->notification(get_string('vmoodlesnapshot2', 'local_vmoodle'), 'notifysuccess');
                $params = array('view' => 'management', 'what' => 'snapshot', 'step' => 2, 'wwwroot' => $wwwroot);
                echo $OUTPUT->continue_button(new moodle_url('/local/vmoodle/view.php', $params));
                echo $OUTPUT->footer();
                die;
            }
        }

        // End of process.

        // Copy moodle data and protect against copy recursion.
        filesystem_copy_tree($vdatapath, $absolutedatadir, $vdatabase, array("^$templatefoldername\$"));
        // Remove Vmoodle clone session, temp and cache dir.
        filesystem_clear_dir($relativedatadir.$separator.'sessions', true);
        filesystem_clear_dir($relativedatadir.$separator.'temp', true);
        filesystem_clear_dir($relativedatadir.$separator.'cache', true);

        // Store original hostname for further database replacements.
        $file = fopen($absolutesqldir.$separator.'manifest.php', 'w');
        fwrite($file, "<?php\n ");
        fwrite($file, "\$templatewwwroot = '".$wwwroot."';\n");
        fwrite($file, "\$templatevdbprefix = '".$CFG->prefix."';\n ");
        fwrite($file, "?>");
        fclose($file);

        if (empty($automation)) {
            // Every step was SUCCESS.
            $messageobject->message = get_string('successfinishedcapture', 'local_vmoodle');
            $messageobject->style = 'notifysuccess';

            // Save confirm message before redirection.
            $SESSION->vmoodle_ma['confirm_message'] = $messageobject;
            echo $OUTPUT->header();
            echo $OUTPUT->notification(get_string('vmoodlesnapshot3', 'local_vmoodle'), 'notifysuccess');
            echo $OUTPUT->continue_button(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
            echo $OUTPUT->footer();
            die;
        } else {
            mtrace(get_string('successfinishedcapture', 'local_vmoodle'));
        }
    }
}
/* *************************** Delete a Vmoodle and uninstall it *********** */
if (($action == 'delete') || ($action == 'fulldelete')) {
    $id = required_param('id', PARAM_INT);

    // Unmarks the Vmoodle in everyplace (subnetwork, common).
    if ($vmoodle = $DB->get_record('local_vmoodle', array('id' => $id))) {
        if ($vmoodlehost = $DB->get_record('mnet_host', array('wwwroot' => $vmoodle->vhostname))) {

            if (($vmoodlehost->deleted == 0)) {
                $vmoodlehost->deleted = 1;
                $DB->update_record('mnet_host', $vmoodlehost);
            }

            if (($vmoodle->enabled == 1)) {

                // Deletes(unmarking) the local record and host. It could be regenerated.
                $vmoodle->enabled = 0;
                $vmoodle->vdatapath = addslashes($vmoodle->vdatapath);
                $vmoodlehost->deleted = 1;
                $DB->update_record('local_vmoodle', $vmoodle);
                $DB->update_record('mnet_host', $vmoodlehost);

                // Members of the subnetwork delete the host.
                if ($vmoodle->mnet > 0) {
                    $subnetworkhosts = array();
                    $sql = "
                        SELECT
                            *
                        FROM
                            {local_vmoodle}
                        WHERE
                            vhostname != ? AND
                            mnet = ? AND
                            enabled = 1
                        ORDER BY
                            vhostname
                    ";
                    $subnetworkmembers = $DB->get_records_sql($sql, array($vmoodle->vhostname, $vmoodle->mnet));
                    if (!empty($subnetworkmembers)) {
                        foreach ($subnetworkmembers as $subnetworkmember) {
                            $temphost = new stdClass();
                            $temphost->wwwroot = $subnetworkmember->vhostname;
                            $temphost->name = utf8_decode($subnetworkmember->name);
                            $subnetworkhosts[] = $temphost;
                        }
                    }

                    if (count($subnetworkhosts) > 0) {
                        $rpcclient = new \local_vmoodle\XmlRpc_Client();
                        $rpcclient->set_method('local/vmoodle/rpclib.php/mnetadmin_rpc_unbind_peer');
                        $rpcclient->add_param($vmoodle->vhostname, 'string');
                        foreach ($subnetworkhosts as $subnetworkhost) {
                            $tempmember = new mnet_peer();
                            $tempmember->set_wwwroot($subnetworkhost->wwwroot);
                            // RPC error.
                            if (!$rpcclient->send($tempmember)) {
                                echo $OUTPUT->notification(implode('<br />', $rpcclient->get_errors($tempmember)));
                                if (debugging()) {
                                    echo '<pre>';var_dump($rpcclient);echo '</pre>';
                                }
                            }
                        }

                        $rpcclient = new \local_vmoodle\XmlRpc_Client();
                        $rpcclient->set_method('local/vmoodle/rpclib.php/mnetadmin_rpc_disconnect_from_subnetwork');
                        $rpcclient->add_param($subnetworkhosts, 'array');
                        $deletedpeer = new mnet_peer();
                        $deletedpeer->set_wwwroot($vmoodlehost->wwwroot);
                        // RPC error.
                        if (!$rpcclient->send($deletedpeer)) {
                            echo $OUTPUT->notification(implode('<br />', $rpcclient->get_errors($deletedpeer)), 'notifyproblem');
                            if (debugging()) {
                                echo '<pre>';var_dump($rpcclient);echo '</pre>';
                            }
                        }
                    }
                    // Every step was SUCCESS.
                    $messageobject->message = get_string('successdeletehost', 'local_vmoodle');
                    $messageobject->style = 'notifysuccess';
                }
            } else {
                // If trying to delete an already deleted host.
                $messageobject->message = get_string('badhostalreadydeleted', 'local_vmoodle');
                $messageobject->style = 'notifysuccess';
            }
        } else {
            // If local_vmoodles and host are not synchronized.
            $sqlrequest = '
                DELETE FROM
                    {local_vmoodle}
                WHERE
                    id = '.$id;
            if ($DB->execute($sqlrequest)) {
                $messageobject->message = get_string('successdeletehost', 'local_vmoodle');
                $messageobject->style = 'notifysuccess';
            } else {
                $messageobject->message = get_string('badhostalreadydeleted', 'local_vmoodle');
                $messageobject->style = 'notifysuccess';
            }
        }
    } else {
        // If the Vmoodle record doesn't exist in the block, because of a manual action.
        $messageobject->message = get_string('novmoodle', 'local_vmoodle');
    }

    if ($action == 'fulldelete') {
        if (function_exists('debug_trace')) {
            debug_trace('Full deleting vmoodle host');
        }
        vmoodle_destroy($vmoodle);
    }
    if (empty($automation)) {
        redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
    }
}
/* ******************** Just rough destroy without care (for bulk cleaning) *********** */
if ($action == 'destroy') {

    // If there is submitted data from form or in session (no errors).
    if (isset($SESSION->vmoodledata)) {
        $submitteddata = $SESSION->vmoodledata;
    } else {
        $id = required_param('id', PARAM_INT);
        $submitteddata = $DB->get_record('local_vmoodle', array('id' => $id));
    }

    if ($submitteddata) {
        if (function_exists('debug_trace')) {
            debug_trace('Destroying vmoodle host');
        }
        vmoodle_destroy($submitteddata);
    }
    if (empty($automation)) {
        redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
    }
}
/* ******************** Run an interactive cronlike trigger forcing key renew on all vmoodle *********** */
if ($action == 'renewall') {

    /*
     * Important Note : Renewing relies on Web triggering of the mnetcron function
     * on the pears, asking for key change. If you are using password to protect
     * the Moodle cron by Web note that ALL sites should use the SAME cron password
     * for renewall to be performed globally.
     */

    // Self renew.
    echo $OUTPUT->header();
    echo '<pre>';
    $params = array('forcerenew' => 1);
    if ($CFG->cronremotepassword) {
        $params['password'] = $CFG->cronremotepassword;
    }
    $renewuri = new moodle_url('/local/vmoodle/mnetcron.php', $params);
    echo "Running on : $renewuri\n";

    echo "#############################\n";

    $ch = curl_init($renewuri);

    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Moodle');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml charset=UTF-8"));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $raw = curl_exec($ch);
    echo $raw."\n\n";
    echo '</pre>';

    $sql = '
        SELECT
            *
        FROM
            {local_vmoodle}
        WHERE
            mnet > -1
    ';
    $vmoodles = $DB->get_records_sql($sql);

    echo '<pre>';
    foreach ($vmoodles as $vmoodle) {
        $renewuri = $vmoodle->vhostname.'/local/vmoodle/mnetcron.php?forcerenew=1';
        if ($CFG->cronremotepassword) {
            $renewuri .= '&password='.$CFG->cronremotepassword;
        }
        echo "Running on : $renewuri\n";

        echo "#############################\n";

        $ch = curl_init($renewuri);

        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Moodle');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: text/xml charset=UTF-8"));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $raw = curl_exec($ch);
        echo $raw."\n\n";
    }
    echo '</pre>';

    if (empty($automation)) {
        echo '<center>';
        echo $OUTPUT->continue_button(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
        echo '</center>';
        echo $OUTPUT->footer();
    }
    die;
}
/* ******************** Generates physical configs *********** */
if ($action == 'generateconfigs') {

    $allvmoodles = $DB->get_records('local_vmoodle', array());

    // Prepare generation dir.

    $configpath = $CFG->dataroot.'/vmoodle_configs';

    if (!is_dir($configpath)) {
        mkdir($configpath, 0777);
    }

    // Generate.

    $configtemplate = implode('', file($CFG->dirroot.'/config.php'));

    $generated = array();

    $result = 'generating';

    foreach ($allvmoodles as $vm) {

        $configvm = $configtemplate;

        assert(preg_match("#CFG->wwwroot\s+=\s+'.*?';#", $configvm));

        $configvm = preg_replace("#CFG->wwwroot\s+=\s+['\"].*?['\"];#s", 'CFG->wwwroot = \''.$vm->vhostname."';", $configvm);
        $configvm = preg_replace("#CFG->dataroot\s+=\s+['\"].*?['\"];#s", 'CFG->dataroot = \''.$vm->vdatapath."';", $configvm);
        $configvm = preg_replace("#CFG->dbhost\s+=\s+['\"].*?['\"];#s", 'CFG->dbhost = \''.$vm->vdbhost."';", $configvm);
        $configvm = preg_replace("#CFG->dbname\s+=\s+['\"].*?['\"];#s", 'CFG->dbname = \''.$vm->vdbname."';", $configvm);
        $configvm = preg_replace("#CFG->dbuser\s+=\s+['\"].*?['\"];#s", 'CFG->dbuser = \''.$vm->vdblogin."';", $configvm);
        $configvm = preg_replace("#CFG->dbpass\s+=\s+['\"].*?['\"];#s", 'CFG->dbpass = \''.$vm->vdbpass."';", $configvm);
        $configvm = preg_replace("#CFG->prefix\s+=\s+['\"].*?['\"];#s", 'CFG->prefix = \''.$vm->vdbprefix."';", $configvm);
        if ($vm->vdbpersist) {
            $configvm = preg_replace("#'dbpersist'\s+=\s+.*?,#", "'dbpersist' = true,", $configvm);
        }

        if ($configfile = fopen($configpath.'/config-'.$vm->shortname.'.php', 'w')) {
            $generated[] = 'config-'.$vm->shortname.'.php';
            fputs($configfile, $configvm);
            fclose($configfile);
        }
    }
    if (!empty($generated)) {
        $result = implode("\n", $generated);
        $controllerresult = get_string('generatedconfigs', 'local_vmoodle', $result);
    }
}
/* ******************** Enable instances *********** */

if ($action == 'enableinstances') {
    $nodes = optional_param_array('vmoodleids', null, PARAM_INT);

    if (!empty($nodes)) {
        $nodelist = implode("','", $nodes);
        $sql = "
            UPDATE
                {local_vmoodle} bv
            SET
                enabled = 1
            WHERE
                id IN ('$nodelist')
        ";
        $DB->execute($sql);
    }
    if (empty($automation)) {
        redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
    }
}

/* ******************** Disable instances *********** */

if ($action == 'disableinstances') {
    $nodes = optional_param_array('vmoodleids', null, PARAM_INT);

    if (!empty($nodes)) {
        $nodelist = implode("','", $nodes);
        $sql = "
            UPDATE
                {local_vmoodle} bv
            SET
                enabled = 0
            WHERE
                id IN ('$nodelist')
        ";
        $DB->execute($sql);
    }
    if (empty($automation)) {
        redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
    }
}

/* ******************** Destroy instances *********** */

if ($action == 'deleteinstances') {
    $nodes = optional_param_array('vmoodleids', null, PARAM_INT);
    if (!empty($nodes)) {
        $vmoodles = $DB->get_records_list('local_vmoodle', 'id', $nodes);

        if ($vmoodles) {
            foreach ($vmoodles as $vm) {
                if ($vm->enabled == 0) {
                    // Only destroy not running moodle for security.
                    vmoodle_destroy($vm);
                }
            }
        }
    }
    if (empty($automation)) {
        redirect(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
    }
}

/* ******************** Sync vmoodle register to all active nodes *********** */

if ($action == 'syncregister') {
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string('syncvmoodleregister', 'local_vmoodle'));
    echo '<pre>';
    vmoodle_sync_register();
    echo '</pre>';
    echo '<center>';
    echo $OUTPUT->continue_button(new moodle_url('/local/vmoodle/view.php', array('view' => 'management')));
    echo '</center>';
    echo $OUTPUT->footer();
    die;
}

// Return to initial 'max_execution_time' value, in every case.
set_time_limit($initialmaxexectime);