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
 * Describes meta-administration plugin's command for Maintenance setup.
 * 
 * @package local_vmoodle
 * @category local
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
namespace vmoodleadminset_generic;

use \local_vmoodle\commands\Command;
use \local_vmoodle\commands\Command_Parameter;
use \StdClass;
use \context_system;

class Command_CopyFileArea extends Command {

    /**
     * If command's result should be returned.
     */
    private $returned;

    /**
     * Constructor.
<<<<<<< HEAD
     * @param string $name Command's name.
     * @param string $description Command's description.
     * @param string $sql SQL command.
     * @param string $parameters Command's parameters (optional / could be null, Command_Parameter object or Command_Parameter array).
=======
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
     * @param Command $rpcommand Retrieve platforms command (optional / could be null or Command object).
     * @throws Command_Exception
     */
    public function __construct($rpcommand = null) {
        global $vmcommandconstants, $DB;

        // Getting command description.
        $cmdname = vmoodle_get_string('cmdcopyfilearea', 'vmoodleadminset_generic');
        $cmddesc = vmoodle_get_string('cmdcopyfilearea_desc', 'vmoodleadminset_generic');

        $platforms = get_available_platforms();

        $platforms = array_merge(array('0' => get_string('localfilearea', 'vmoodleadminset_generic')), $platforms);

        // Creating platform parameter.
        $label = get_string('platformparamfilearea_desc', 'vmoodleadminset_generic');
        $platformparam = new Command_Parameter('platform', 'enum', $label, null, $platforms);

        // Get all system level files that are true files.
        $params = array('contextid' => context_system::instance()->id);
        $sql = "
            SELECT DISTINCT
                CONCAT(component, filearea, itemid) as pkey,
                component,
                filearea,
                itemid
            FROM
                {files}
            WHERE
                contextid = :contextid
            GROUP BY
                component, filearea, itemid
        ";
        $fileareas = $DB->get_records_sql($sql, $params);

        $fileareasmenu = array();
        if (!empty($fileareas)) {
            foreach ($fileareas as $fa) {
                if ($fa->filearea == 'preview' || $fa->filearea == 'draft') {
                    continue;
                }
                $fileareasmenu["{$fa->component}/{$fa->filearea}/{$fa->itemid}"] = "{$fa->component}@{$fa->filearea} § {$fa->itemid}";
                $fileareasmenu["{$fa->component}/{$fa->filearea}/*"] = "{$fa->component} @ {$fa->filearea} § * (all items)";
            }
        }

        // Creating platform parameter. This is the source platform.
        $label = get_string('filearea_desc', 'vmoodleadminset_generic');
        $fileareaparam = new Command_Parameter('fileareaid', 'enum', $label, null, $fileareasmenu);

<<<<<<< HEAD
        // Creating Command.
        parent::__construct($cmdname, $cmddesc, array($platformparam, $fileareaparam), $rpcommand);
=======
        // Creating platform parameter. This is the source platform.
        $label = get_string('skipfiles_desc', 'vmoodleadminset_generic');
        $skipfilesparam = new Command_Parameter('skipfiles', 'text', $label, null);

        // Creating Command.
        parent::__construct($cmdname, $cmddesc, array($platformparam, $fileareaparam, $skipfilesparam), $rpcommand);
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
    }

    /**
     * Execute the command.
     * @param mixed $host The hosts where run the command (may be wwwroot or an array).
     * @throws Command_SetConfig_Exception
     */
    public function run($hosts) {
        global $CFG, $USER;

<<<<<<< HEAD
        // Set Config. Adding constants.
        require_once $CFG->dirroot.'/local/vmoodle/rpclib.php';

        $systemcontextid = \context_system::instance()->id;
=======
        ini_set('max_execution_time', 600);

        debug_trace('Start running  CopyFileArea');

        // Set Config. Adding constants.
        require_once $CFG->dirroot.'/local/vmoodle/rpclib.php';

        $systemcontext = \context_system::instance();
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31

        // Set Config. Checking host.
        if (!is_array($hosts)) {
            $hosts = array($hosts => 'Unnamed host');
        }

<<<<<<< HEAD
        // Set Config. Checking capabilities.
        if (!has_capability('local/vmoodle:execute', $systemcontextid)) {
=======
        debug_trace('Running CopyFileArea : check caps');
        // Set Config. Checking capabilities.
        if (!has_capability('local/vmoodle:execute', $systemcontext)) {
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
            throw new Command_CopyFile_Exception('insuffisantcapabilities');
        }

        // Set Config. Initializing responses.
        $responses = array();

<<<<<<< HEAD
        // Creating peers.
        $mnet_hosts = array();
=======
        debug_trace('Running CopyFileArea : get hosts');

        // Creating peers for all target hosts.
        $mnethosts = array();
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
        foreach ($hosts as $host => $name) {

            debug_trace('Running CopyFileArea for '.$name);

<<<<<<< HEAD
            $mnet_host = new \mnet_peer();
            if ($mnet_host->bootstrap($host, null, 'moodle')) {
                $mnet_hosts[] = $mnet_host;
=======
            $mnethost = new \mnet_peer();
            if ($mnethost->bootstrap($host, null, 'moodle')) {
                $mnethosts[] = $mnethost;
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
            } else {
                $responses[$host] = (object) array('status' => MNET_FAILURE, 'error' => get_string('couldnotcreateclient', 'local_vmoodle', $host));
            }
        }

        // Set Config. Getting command.
        $command = $this->is_returned();

        // Get file descriptor from locally defined files.
        // We cannot transfer or copy a file the master does not knwow about.
        $fileareaid = $this->get_parameter('fileareaid')->get_value();

<<<<<<< HEAD
        $fs = get_file_storage();

        list($component, $filearea, $itemid) = explode('/', $fileareaid);
        // Get only true files. 
        if ($itemid == '*') {
            $files = $fs->get_area_files($systemcontextid, $component, $filearea, false, "itemid, filepath, filename", false);
        } else {
            $files = $fs->get_area_files($systemcontextid, $component, $filearea, $itemid, "itemid, filepath, filename", false);
        }

        // Resolve file source and get a remote file if remote.
        $source = $this->get_parameter('platform')->get_value();

        foreach ($files as $file) {
            debug_trace('Running CopyFileArea for '.$file->get_filepath().'/'.$file->get_filename());
            if ($source) {
                // Creating peer to read files from the designated peer.
                $mnethost = new mnet_peer();
                if (!$mnethost->bootstrap($this->get_parameter('platform')->get_value(), null, 'moodle')) {
                    $response = (object) array(
                        'status' => MNET_FAILURE,
                        'error' => get_string('couldnotcreateclient', 'local_vmoodle', $platform)
=======
        // Resolve file source and get a remote file if remote.
        $source = $this->get_parameter('platform')->get_value();

        // Skip n files if required
        $skipfiles = 0 + $this->get_parameter('skipfiles')->get_value(true);

        if (empty($source) || $source === 0) {
            $fs = get_file_storage();

            list($component, $filearea, $itemid) = explode('/', $fileareaid);
            // Get only true files. 
            if ($itemid == '*') {
                $files = $fs->get_area_files($systemcontext->id, $component, $filearea, false, "itemid, filepath, filename", false);
            } else {
                $files = $fs->get_area_files($systemcontext->id, $component, $filearea, $itemid, "itemid, filepath, filename", false);
            }

            if (!empty($files)) {
                foreach ($files as $f) {
                    $filedesc = new Stdclass;
                    $filedesc->component = $f->get_component();
                    $filedesc->filearea = $f->get_filearea();
                    $filedesc->itemid = $f->get_itemid();
                    $filedesc->filepath = $f->get_filepath();
                    $filedesc->filename = $f->get_filename();
                    $filedesc->localfile = $f;
                    $filedescs[] = $filedesc;
                }
            }
        } else {
            // Creating XMLRPC client to get the remote filearea content.
            list($component, $filearea, $itemid) = explode('/', $fileareaid);
            $rpcfaclient = new \local_vmoodle\XmlRpc_Client();
            $rpcfaclient->set_method('local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_get_remote_filearea');
            $rpcfaclient->add_param($component, 'string'); // filearea desc.
            $rpcfaclient->add_param($filearea, 'string'); // languages.
            $rpcfaclient->add_param($itemid, 'string'); // languages.
            $rpcfaclient->add_param(true, 'string'); // Not jsonrequired.

            $sourcemnethost = new \mnet_peer();
            $bootstrap = $sourcemnethost->bootstrap($source, null, 'moodle');
            if (!$bootstrap) {
                $response = (object) array(
                    'status' => MNET_FAILURE,
                    'error' => get_string('couldnotcreateclient', 'local_vmoodle', $source)
                );

                // If we fail, we fail for all.
                foreach ($hosts as $host => $name) {
                    $this->results[$host] = $response;
                }
                return;
            }
            if (!$rpcfaclient->send($sourcemnethost)) {
                // General failure querying the source for filearea full directory.
                print_object($rpcfaclient);
                $response = (object) array(
                    'status' => MNET_FAILURE,
                    'error' => get_string('sendfailure', 'local_vmoodle', $source)
                );

                // If we fail, we fail for all.
                foreach ($hosts as $host => $name) {
                    $this->results[$host] = $response;
                }
                return;
            } else {
                $response = json_decode($rpcfaclient->response);
                if (empty($response) || ($response->status != RPC_SUCCESS)) {
                    $response = (object) array(
                        'status' => MNET_FAILURE,
                        'error' => get_string('responseerror', 'local_vmoodle', $source)
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
                    );

                    // If we fail, we fail for all.
                    foreach ($hosts as $host => $name) {
                        $this->results[$host] = $response;
                    }
                    return;
                }
<<<<<<< HEAD

                debug_trace('Launching mnetadmin_rpc_get_remote_file on source ');
                // Creating XMLRPC client to get the remote customisation language pack.
                $rpcclient = new \local_vmoodle\XmlRpc_Client();
                $rpcclient->set_method('local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_get_remote_file');
                $rpcclient->add_param($file->get_component(), 'string'); // plugins.
                $rpcclient->add_param($file->get_filearea(), 'string'); // languages.
                $rpcclient->add_param($file->get_itemid(), 'string'); // languages.
                $rpcclient->add_param($file->get_filepath().$file->get_filename(), 'string'); // languages.
                $rpcclient->add_param(true, 'string'); // Not jsonrequired.

                // Checking result.
                if (!($rpcclient->send($mnethost) && ($response = json_decode($rpcclient->response)) && $response->status == RPC_SUCCESS)) {
                    // Creating response.
                    if (!isset($response)) {
                        $response = new Stdclass();
                        $response->status = MNET_FAILURE;
                        $response->errors[] = implode('<br/>', $rpcclient->get_errors($mnethost));
                        $response->error = implode('<br/>', $rpcclient->get_errors($mnethost));
                    }

                    $responses = array();
                    // Sending requests.
                    foreach ($hosts as $host => $name) {
                        $responses[$host] = $response;
                    }

                    $this->results = $responses + $this->results;

                    // Don't go futher.
                    return;
                } else {
                    // We have a remote file.
                    $filecontent = $response->filecontent;
                }
            } else {
                // If file is local use the local content of the file.
                $filecontent = $file->get_content();
            }

            // Creating XMLRPC client.
            $rpc_client = new \local_vmoodle\XmlRpc_Client();
            $rpc_client->set_method('local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_import_file');
            $rpc_client->add_param($file->get_component(), 'string');
            $rpc_client->add_param($file->get_filearea(), 'string');
            $rpc_client->add_param($file->get_itemid(), 'string');
            $rpc_client->add_param($file->get_filepath().$file->get_filename(), 'string');
            $rpc_client->add_param(base64_encode($filecontent), 'string');
            $rpc_client->add_param(true, 'boolean');

            // Set Config. Sending requests.
            foreach($mnet_hosts as $mnet_host) {
                // Sending request.
                if (!$rpc_client->send($mnet_host)) {
                    $response = new StdClass();
                    $response->status = MNET_FAILURE;
                    $response->errors[] = implode('<br/>', $rpc_client->get_errors($mnet_host));
                } else {
                    $response = json_decode($rpc_client->response);
                }
                // Recording response.
                $responses[$mnet_host->wwwroot] = $response;
=======
            }

            $filedescs = $response->fileareacontent;
        }

        if (!empty($filedescs)) {
            $filestosend = count($filedescs);
            $sendstatus = array();
            if (function_exists('debug_trace')) {
                debug_trace('Running CopyFileArea : Copying '.$filestosend.' files from source '.$source);
            }
            mtrace('Running CopyFileArea : Copying '.$filestosend.' files from source '.$source);
            $i = 0;
            $j = 0;
            foreach ($filedescs as $fileordesc) {

                if (defined('CLI_SCRIPT')) {
                    if ($j < $skipfiles) {
                        echo '-';
                    } else {
                        echo '.';
                    }
                    $i++;
                    $j++;
                    if ($i > 99) {
                        $i = 0;
                        echo " $j \n";
                    }
                }

                if ($j < $skipfiles) {
                    continue;
                }

                if ($source) {
                    // Creating XMLRPC client to get the remote customisation language pack.
                    $rpcclient = new \local_vmoodle\XmlRpc_Client();
                    $rpcclient->set_method('local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_get_remote_file');
                    $rpcclient->add_param($fileordesc->component, 'string'); // component.
                    $rpcclient->add_param($fileordesc->filearea, 'string'); // filearea.
                    $rpcclient->add_param($fileordesc->itemid, 'string'); // itemid.
                    $rpcclient->add_param($fileordesc->filepath, 'string'); // filepath.
                    $rpcclient->add_param($fileordesc->filename, 'string'); // filename.
                    $rpcclient->add_param(true, 'string'); // Not jsonrequired.

                    // Checking result.
                    if (!($rpcclient->send($sourcemnethost) && ($response = json_decode($rpcclient->response)) && $response->status == RPC_SUCCESS)) {
                        // Creating response.
                        if (!isset($response)) {
                            $response = new Stdclass();
                            $response->status = MNET_FAILURE;
                            $response->errors[] = implode('<br/>', $rpcclient->get_errors($sourcemnethost));
                            $response->error = implode('<br/>', $rpcclient->get_errors($sourcemnethost));
                        }

                        $responses = array();
                        // Sending requests.
                        foreach ($hosts as $host => $name) {
                            $responses[$host] = $response;
                        }

                        $this->results = $responses + $this->results;

                        // Don't go futher.
                        return;
                    } else {
                        // We have a remote file.
                        $filecontent = $response->filecontent;
                    }
                } else {
                    // If file is local use the local content of the file.
                    $filecontent = $filedesc->localfile->get_content();
                }

                // Creating XMLRPC client.
                $rpc_client = new \local_vmoodle\XmlRpc_Client();
                $rpc_client->set_method('local/vmoodle/plugins/generic/rpclib.php/mnetadmin_rpc_import_file');
                $rpc_client->add_param($fileordesc->component, 'string');
                $rpc_client->add_param($fileordesc->filearea, 'string');
                $rpc_client->add_param($fileordesc->itemid, 'string');
                $rpc_client->add_param($fileordesc->filepath, 'string');
                $rpc_client->add_param($fileordesc->filename, 'string');
                $rpc_client->add_param(base64_encode($filecontent), 'string');
                $rpc_client->add_param(true, 'boolean');

                // Import file. Sending requests.
                foreach ($mnethosts as $mnethost) {

                    if (empty($sendstatus[$mnethost->wwwroot]['mneterrors'])) {
                        $sendstatus[$mnethost->wwwroot]['mneterrors'] = 0;
                    }

                    if (empty($sendstatus[$mnethost->wwwroot]['success'])) {
                        $sendstatus[$mnethost->wwwroot]['success'] = 0;
                    }

                    if (empty($sendstatus[$mnethost->wwwroot]['failures'])) {
                        $sendstatus[$mnethost->wwwroot]['failures'] = 0;
                    }

                    // Sending request.
                    if (!$rpc_client->send($mnethost)) {
                        $sendstatus[$mnethost->wwwroot]['mneterrors']++;
                    } else {
                        $response = json_decode($rpc_client->response);
                    }

                    // Recording response and check remote status.
                    if ($response->status == RPC_SUCCESS) {
                        @$sendstatus[$mnethost->wwwroot]['success']++;
                    } else {
                        @$sendstatus[$mnethost->wwwroot]['failures']++;
                    }
                    $responses[$mnethost->wwwroot] = $response;
                }
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
            }
        }

        // Set Config. Saving results.
<<<<<<< HEAD
        $this->results = $responses + $this->results;
=======

        foreach ($sendstatus as $hostroot => $status) {
            $this->results[$hostroot] = "Files sent : OK {$status['success']}, Failed {$status['failures']}, Mnet errors {$status['mneterrors']}\n";
        }

        debug_trace('Running CopyFileArea : Finished.');
>>>>>>> f0e8ce055c5d6b1708c2f90d0e41c0191910aa31
    }

    /**
     * Get the result of command execution for one host.
     * @param string $host The host to retrieve result (optional, if null, returns general result).
     * @param string $key The information to retrieve (ie status, error / optional).
     * @throws Command_Sql_Exception
     */
    public function get_result($host = null, $key = null) {
        // Checking if command has been runned.
        if (is_null($this->results)) {
            throw new Command_Exception('commandnotrun');
        }

        // Set Config. Checking host (general result isn't provide in this kind of command).
        if (is_null($host) || !array_key_exists($host, $this->results)) {
            return null;
        }
        $result = $this->results[$host];

        // Set Config. Checking key.
        if (is_null($key)) {
            return $result;
        } else if (property_exists($result, $key)) {
            return $result->$key;
        } else {
            return null;
        }
    }

    /**
     * Get if the command's result is returned.
     * @return bool True if the command's result should be returned, false otherwise.
     */
    public function is_returned() {
        return $this->returned;
    }

    /**
     * Set if the command's result is returned.
     * @param bool $returned True if the command's result should be returned, false otherwise.
     */
    public function set_returned($returned) {
        $this->returned = $returned;
    }
}