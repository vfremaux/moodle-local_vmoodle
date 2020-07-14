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
 * lib.php
 *
 * General library for vmoodle.
 *
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/vmoodle/bootlib.php');
require_once($CFG->dirroot.'/local/vmoodle/filesystemlib.php');

/* Define constants */
define('VMOODLE_LIBS_DIR', $CFG->dirroot.'/local/vmoodle/plugins/');
define('VMOODLE_PLUGINS_DIR', $CFG->dirroot.'/local/vmoodle/plugins/');

if (!defined('RPC_SUCCESS')) {
    define('RPC_TEST', 100);
    define('RPC_SUCCESS', 200);
    define('RPC_FAILURE', 500);
    define('RPC_FAILURE_USER', 501);
    define('RPC_FAILURE_CONFIG', 502);
    define('RPC_FAILURE_DATA', 503);
    define('RPC_FAILURE_CAPABILITY', 510);
    define('MNET_FAILURE', 511);
    define('RPC_FAILURE_RECORD', 520);
    define('RPC_FAILURE_RUN', 521);
}

/* Define commands' constants */
$vmcommandconstants = array('prefix' => $CFG->prefix,
                            'wwwroot' => $CFG->wwwroot);

// Loading plugin librairies.
$pluginlibs = glob($CFG->dirroot.'/local/vmoodle/plugins/*/lib.php');
foreach ($pluginlibs as $lib) {
    require_once($lib);
}

/**
 * Implements the generic community/pro packaging switch.
 * Tells wether a feature is supported or not. Gives back the
 * implementation path where to fetch resources.
 * @param string $feature a feature key to be tested.
 */
function local_vmoodle_supports_feature($feature) {
    global $CFG;
    static $supports;

    $config = get_config('local_vmoodle');

    if (!isset($supports)) {
        $supports = array(
            'pro' => array(
                'admin' => array('pro'),
            ),
            'community' => array(
            ),
        );
        $prefer = array();
    }

    // Check existance of the 'pro' dir in plugin.
    if (is_dir(__DIR__.'/pro')) {
        if ($feature == 'emulate/community') {
            return 'pro';
        }
        if (empty($config->emulatecommunity)) {
            $versionkey = 'pro';
        } else {
            $versionkey = 'community';
        }
    } else {
        $versionkey = 'community';
    }

    list($feat, $subfeat) = explode('/', $feature);

    if (!array_key_exists($feat, $supports[$versionkey])) {
        return false;
    }

    if (!in_array($subfeat, $supports[$versionkey][$feat])) {
        return false;
    }

    if (array_key_exists($feat, $supports['community'])) {
        if (in_array($subfeat, $supports['community'][$feat])) {
            // If community exists, default path points community code.
            if (isset($prefer[$feat][$subfeat])) {
                // Configuration tells which location to prefer if explicit.
                $versionkey = $prefer[$feat][$subfeat];
            } else {
                $versionkey = 'community';
            }
        }
    }

    return $versionkey;
}

/**
 * get the list of available vmoodles
 * @return an array of vmoodle objects
 */
function vmoodle_get_vmoodles() {
    global $DB;

    if ($vmoodles = $DB->get_records('local_vmoodle')) {
        return $vmoodles;
    }
    return array();
}

/**
 * This function is for multicluster repartition of the vcron duty. Usually,
 * Usually we use one single server with a single vcron task that operates all
 * vmoodle crons in a round robin or last gap strategy.
 *
 * If more than one cluster is used, the vmoodle register is spread into subsets and
 * each clusters consumes a part of the bulk load.
 *
 * Note that the cluster ix comes from an evaluation of the 'clusterix' config key of the local_vmoodle
 * plugin. there should be provision to force the clusterix from a local $CFG->forced_plugin_settings[(vmoodle']['clusterix']
 * key being distinct for each cluster.
 *
 * @param int $clusters the number of clusters
 * @param int $clusterix the cluster Id
 */
function vmoodle_get_vmoodleset($clusters = 1, $clusterix = 1) {
    global $DB;

    $allvhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));
    if ($clusters < 2) {
        return $allvhosts;
    }

    $vhostset = array();

    $i = 0;
    foreach ($allvhosts as $vh) {
        if ($i == $clusterix - 1) {
            $vhostset[$vh->id] = $vh;
        }
        $i = ($i + 1) % $clusters;
    }

    return $vhostset;
}
/**
 * setup and configure a mnet environment that describes this vmoodle
 * @uses $USER for generating keys
 * @uses $CFG
 * @param object $vmoodle
 * @param handle $cnx a connection
 */
function vmoodle_setup_mnet_environment($vmoodle, $cnx) {
    global $USER, $CFG;

    $config = get_config('local_vmoodle');

    // Make an empty mnet environment.
    $mnetenv = new mnet_environment();

    $mnetenv->wwwroot              = $vmoodle->vhostname;
    $mnetenv->ip_address           = $config->vmoodleip;
    $mnetenv->keypair              = array();
    $mnetenv->keypair              = mnet_generate_keypair(null);
    $mnetenv->public_key           = $mnetenv->keypair['certificate'];
    $details                        = openssl_x509_parse($mnetenv->public_key);
    $mnetenv->public_key_expires   = $details['validTo_time_t'];

    return $mnetenv;
}

/**
 * setup services for a given mnet environment in a database
 * @uses $CFG
 * @param object $mnet_env an environment with valid id
 * @param handle $cnx a connection to the target bdd
 * @param object $services an object that holds service setup data
 */
function vmoodle_add_services(&$vmoodle, $mnetenv, $cnx, $services) {
    if (!$mnetenv->id) {
        return false;
    }

    if ($services) {
        foreach ($services as $service => $keys) {
            $sql = "
               INSERT INTO
                  {$vmoodle->vdbprefix}mnet_host2service(
                  hostid,
                  serviceid,
                  publish,
                  subscribe)
               VALUES (
                  {$mnetenv->id},
                  $service,
                  {$keys['publish']},
                  {$keys['subscribe']}
               )
            ";
            vmoodle_execute_query($vmoodle, $sql, $cnx);
        }
    }
}

/**
 * get available services in the master
 * @return array of service descriptors.
 */
function vmoodle_get_service_desc() {
    global $DB;

    $services = $DB->get_records('mnet_service', array('offer' => 1));

    $servicedescriptor = array();

    if ($services) {
        foreach ($services as $service) {
            $servicedescriptor[$service->id]['publish'] = 1;
            $servicedescriptor[$service->id]['subscribe'] = 1;
        }
    }
    return $servicedescriptor;
}

/**
 * given a complete mnet_environment record, and a connection
 * record this mnet host in remote database. If the record is
 * a new one, gives back a completed env with valid remote id.
 * @param object $mnet_env
 * @param handle $cnx
 * @return the inserted mnet_env object
 */
function vmoodle_register_mnet_peer(&$vmoodle, $mnetenv, $cnx) {
    $mnetarray = get_object_vars($mnetenv);
    if (empty($mnetenv->id)) {
        foreach ($mnetarray as $key => $value) {
            if ($key == 'id') {
                continue;
            }
            $keylist[] = $key;
            $valuelist[] = "'$value'";
        }
        $keyset = implode(',', $keylist);
        $valueset = implode(',', $valuelist);
        $sql = "
            INSERT INTO
               {$vmoodle->vdbprefix}mnet_host(
                {$keyset}
                )
            VALUES(
                {$valueset}
            )
        ";
        $mnetenv->id = vmoodle_execute_query($vmoodle, $sql, $cnx);
    } else {
        foreach ($mnetarray as $key => $value) {
            $valuelist[] = "$key = '$value'";
        }
        unset($valuelist['id']);
        $valueset = implode(',', $valuelist);
        $sql = "
            UPDATE
               {$vmoodle->vdbprefix}mnet_host
            SET
                {$valueset}
            WHERE
                id = {$mnetarray['id']}
        ";
        vmoodle_execute_query($vmoodle, $sql, $cnx);
    }
    return $mnetenv;
}

/**
 * get the mnet_env record for an host
 * @param object $vmoodle
 * @return object a mnet_host record
 */
function vmoodle_get_mnet_env(&$vmoodle) {
    global $DB;

    $mnetenv = $DB->get_record('mnet_host', array('wwwroot' => $vmoodle->vhostname));
    return $mnetenv;
}

/**
 * unregister a vmoodle from the whole remaining network
 * @uses $CFG
 * $param object $vmoodle
 * @param handle $cnx
 * @param object $fromvmoodle
 */
function vmoodle_unregister_mnet(&$vmoodle, $fromvmoodle ) {
    global $CFG;

    if ($fromvmoodle) {
        $vdbprefix = $fromvmoodle->vdbprefix;
    } else {
        $vdbprefix = $CFG->prefix;
    }
    $cnx = vmoodle_make_connection($fromvmoodle, true);
    // Cleanup all services for the deleted host.
    $sql = "
        DELETE FROM
            {$vmoodle->vdbprefix}mnet_host2service
        WHERE
            hostid = (SELECT
                        id
                     FROM
                        {$vdbprefix}mnet_host
                     WHERE
                        wwwroot = '{$vmoodle->vhostname}')
    ";
    vmoodle_execute_query($vmoodle, $sql, $cnx);
    // Delete the host.
    $sql = "
        DELETE FROM
            {$vmoodle->vdbprefix}mnet_host
         WHERE
            wwwroot = '{$vmoodle->vhostname}'
     ";
    vmoodle_execute_query($vmoodle, $sql, $cnx);
}

/**
 * drop a vmoodle database
 * @param object $vmoodle
 * @param handle $side_cnx
 */
function vmoodle_drop_database(&$vmoodle, $cnx = null) {
    // Try to delete database.
    $localcnx = 0;
    if (!$cnx) {
        $localcnx = 1;
        $cnx = vmoodle_make_connection($vmoodle);
    }

    if (!$cnx) {
        $erroritem->message = get_string('couldnotconnecttodb', 'local_vmoodle');
        $erroritem->on = 'db';
        return $erroritem;
    } else {
        if ($vmoodle->vdbtype == 'mysql') {
            $sql = "
               DROP DATABASE `{$vmoodle->vdbname}`
            ";
        } else if ($vmoodle->vdbtype == 'postgres') {
            $sql = "
               DROP DATABASE {$vmoodle->vdbname}
            ";
        } else {
            echo "vmoodle_drop_database : Database not supported<br/>";
        }
        $res = vmoodle_execute_query($vmoodle, $sql, $cnx);
        if (!$res) {
            $erroritem->message = get_string('couldnotdropdb', 'local_vmoodle');
            $erroritem->on = 'db';
            return $erroritem;
        }
        if ($localcnx) {
            vmoodle_close_connection($vmoodle, $cnx);
        }
    }
    return false;
}

/**
 * load a bulk template in databse
 * @param object $vmoodle
 * @param string $bulfile a bulk file of queries to process on the database
 * @param handle $cnx
 * @param array $vars an array of vars to inject in the bulk file before processing
 */
function vmoodle_load_db_template(&$vmoodle, $bulkfile, $cnx = null, $vars = null, $filter = null) {
    global $CFG;

    $localcnx = 0;
    if (is_null($cnx) || $vmoodle->vdbtype == 'postgres') {
        // Postgress MUST make a new connection to ensure db is bound to handle.
        $cnx = vmoodle_make_connection($vmoodle, true);
        $localcnx = 1;
    }

    // Get dump file.

    if (file_exists($bulkfile)) {
        $sql = file($bulkfile);

        // Converts into an array of text lines.
        $dumpfile = implode("", $sql);
        if ($filter) {
            foreach ($filter as $from => $to) {
                $dumpfile = mb_ereg_replace(preg_quote($from), $to, $dumpfile);
            }
        }
        // Insert any external vars.
        if (!empty($vars)) {
            foreach ($vars as $key => $value) {
                $dumpfile = str_replace("<%%$key%%>", $value, $dumpfile);
            }
        }
        $sql = explode ("\n", $dumpfile);
        // Cleanup unuseful things.
        if ($vmoodle->vdbtype == 'mysql') {
            $sql = preg_replace("/^--.*/", "", $sql);
            $sql = preg_replace("/^\/\*.*/", "", $sql);
        }
        $dumpfile = implode("\n", $sql);
    } else {
        echo "vmoodle_load_db_template : Bulk file not found";
        return false;
    }
    // Split into single queries.
    $dumpfile = str_replace("\r\n", "\n", $dumpfile); // Translates to Unix LF.
    $queries = preg_split("/;\n/", $dumpfile);
    // Feed queries in database.
    $i = 0;
    $j = 0;
    if (!empty($queries)) {
        foreach ($queries as $query) {
            $query = trim($query); // Get rid of trailing spaces and returns.
            if ($query == '') {
                continue; // Avoid empty queries.
            }
            $query = mb_convert_encoding($query, 'iso-8859-1', 'auto');
            if (!$res = vmoodle_execute_query($vmoodle, $query, $cnx)) {
                echo "<hr/>load error on <br/>" . $cnx . "<hr/>";
                $j++;
            } else {
                $i++;
            }
        }
    }

    echo "loaded : $i queries succeeded, $j queries failed<br/>";
    if ($localcnx) {
        vmoodle_close_connection($vmoodle, $cnx);
    }
    return false;
}

/**
 * Get available platforms to send Command.
 * @return array The availables platforms based on MNET or Vmoodle table.
 */
function get_available_platforms() {
    global $CFG, $DB;

    $config = get_config('local_vmoodle');

    // Getting description of master host.
    $masterhost = $DB->get_record('course', array('id' => 1));

    // Setting available platforms.
    $aplatforms = array();
    if (@$config->host_source == 'vmoodle') {
        $id = 'vhostname';
        $records = $DB->get_records('local_vmoodle', array(), 'name', $id.', name');
    } else {
        $id = 'wwwroot';
        $moodleapplication = $DB->get_record('mnet_application', array('name' => 'moodle'));
        $params = array('deleted' => 0, 'applicationid' => $moodleapplication->id);
        $records = $DB->get_records('mnet_host', $params, 'name', $id.', name');
        foreach ($records as $key => $record) {
            if ($record->name == '' || $record->name == 'All Hosts') {
                unset($records[$key]);
            }
        }
    }
    if ($records) {
        foreach ($records as $record) {
            $aplatforms[$record->$id] = $record->name;
        }
        asort($aplatforms);
    }

    return $aplatforms;
}

/**
 * Return html help icon from library help files.
 * @param string $library The vmoodle library to display help file.
 * @param string $helpitem The help item to display.
 * @param string $title The title of help.
 * @return string Html span with help icon.
 */
function help_button_vml($library, $helpitem, $title) {
    global $OUTPUT;

    // WAFA: help icon no longer take links.
    return '';
}

/**
 * Get the parameters' values from the placeholders.
 * We return both canonic name of the variable and replacement value
 * @param array $matches The placeholders found.
 * @param array $data The parameters' values to insert.
 * @param bool $parameters_replace True if variables should be replaced (optional).
 * @param bool $contants_replace True if constants should be replaced (optional).
 * @return string The parameters' values.
 */
function replace_parameters_values($matches, $params, $parametersreplace = true, $constantsreplace = true) {
    global $vmcommandconstants;

    // Parsing constants.
    if ($constantsreplace
            && empty($matches[1])
                    && array_key_exists($matches[2], $vmcommandconstants)) {
        $value = $vmcommandconstants[$matches[2]];
        // Parsing parameter.
    } else if ($parametersreplace && !empty($matches[1]) && array_key_exists($matches[2], $params)) {
        $value = $params[$matches[2]]->get_value();
    } else {
        // Leave untouched.
        return array($matches[2], $matches[0]);
    }

    if (isset($matches[3]) && is_array($value)) {
        // Checking if member is asked.
        $value = $value[$matches[3]];
    }

    return array($matches[2], $value);
}

/**
 * Load a vmoodle plugin and cache it.
 * @param string $pluginname The plugin name.
 * @return Command_Category The category plugin.
 */
function load_vmplugin($pluginname) {
    global $CFG;
    static $plugins = array();

    if (!array_key_exists($pluginname, $plugins)) {
        $plugins[$pluginname] = include_once($CFG->dirroot.'/local/vmoodle/plugins/'.$pluginname.'/config.php');
    }
    return $plugins[$pluginname];
}

/**
 * Get available templates for defining a new virtual host.
 * @return array The availables templates, or EMPTY array.
 */
function vmoodle_get_available_templates() {
    global $CFG;

    // Scans the templates.
    if (!filesystem_file_exists('vmoodle', $CFG->dataroot)) {
        mkdir($CFG->dataroot.'/vmoodle');
    }
    $dirs = filesystem_scan_dir('vmoodle', FS_IGNORE_HIDDEN, FS_ONLY_DIRS, $CFG->dataroot);
    $vtemplates = preg_grep("/^(.*)_vmoodledata$/", $dirs);

    // Retrieves template(s) name(s).
    $templatesarray = array();
    if ($vtemplates) {
        foreach ($vtemplates as $vtemplatedir) {
            preg_match("/^(.*)_vmoodledata/", $vtemplatedir, $matches);
            $templatesarray[$matches[1]] = $matches[1];
            if (!isset($first)) {
                $first = $matches[1];
            }
        }
    }

    $templatesarray[0] = get_string('reactiveorregistertemplate', 'local_vmoodle');

    return $templatesarray;
}

/**
 * Make a fake vmoodle that represents the current host database configuration.
 * @uses $CFG
 * @return object The current host's database configuration.
 */
function vmoodle_make_this() {
    global $CFG;

    $thismoodle = new StdClass;
    $thismoodle->vdbtype = $CFG->dbtype;
    $thismoodle->vdbhost = $CFG->dbhost;
    $thismoodle->vdblogin = $CFG->dbuser;
    $thismoodle->vdbpass = $CFG->dbpass;
    $thismoodle->vdbname = $CFG->dbname;
    $thismoodle->vdbprefix = $CFG->prefix;

    return $thismoodle;
}

/**
 * Executes a query on a Vmoodle database. Query must return no results,
 * so it may be an INSERT or an UPDATE or a DELETE.
 * @param object $vmoodle The Vmoodle object.
 * @param string $sql The SQL request.
 * @param handle $cnx The connection to the Vmoodle database.
 * @return boolean true if the request is well-executed, false otherwise.
 */
function vmoodle_execute_query(&$vmoodle, $sql, $cnx) {

    // If database is MySQL typed.
    if (($vmoodle->vdbtype == 'mysqli') || ($vmoodle->vdbtype == 'mariadb')) {
        if (!($res = mysqli_query($sql, $cnx))) {
            echo "vmoodle_execute_query() : ".mysqli_error($cnx)."<br/>";
            return false;
        }
        if ($newid = mysqli_insert_id($cnx)) {
            // Get the last insert id in case of an INSERT.
            $res = $newid;
        }
    } else if ($vmoodle->vdbtype == 'postgres') {
        // If database is PostgresSQL typed.
        if (!($res = pg_query($cnx, $sql))) {
            echo "vmoodle_execute_query() : ".pg_last_error($cnx)."<br/>";
            return false;
        }
        if ($newid = pg_last_oid($res)) {
            // Get the last insert id in case of an INSERT.
            $res = $newid;
        }
    } else {
        // If database not supported.
        echo "vmoodle_execute_query() : Database not supported<br/>";
        return false;
    }

    return $res;
}

/**
 * Closes a connection to a Vmoodle database.
 * @param object $vmoodle The Vmoodle object.
 * @param handle $cnx The connection to the database.
 * @return boolean If true, closing the connection is well-executed.
 */
function vmoodle_close_connection($vmoodle, $cnx) {
    if (($vmoodle->vdbtype == 'mysqli') || ($vmoodle->vdbtype == 'mariadb')) {
        $res = mysqli_close($cnx);
    } else if ($vmoodle->vdbtype == 'postgres') {
        $res = pg_close($cnx);
    } else {
        echo "vmoodle_close_connection() : Database not supported<br/>";
        $res = false;
    }
    return $res;
}

/**
 * Dumps a SQL database for having a snapshot.
 * @param object $vmoodle The Vmoodle object.
 * @param string $outputfile The output SQL file.
 * @return bool If TRUE, dumping database was a success, otherwise FALSE.
 */
function vmoodle_dump_database($vmoodle, $outputfile) {
    global $CFG;

    $config = get_config('local_vmoodle');

    // Separating host and port, if sticked.
    if (strstr($vmoodle->vdbhost, ':') !== false) {
        list($host, $port) = explode(':', $vmoodle->vdbhost);
    } else {
        $host = $vmoodle->vdbhost;
    }

    // By default, empty password.
    $pass = '';
    $pgm = null;

    if ($vmoodle->vdbtype == 'mysql' || $vmoodle->vdbtype == 'mysqli' || $vmoodle->vdbtype == 'mariadb') {
        // Default port.
        if (empty($port)) {
            $port = 3306;
        }

        // Password.
        if (!empty($vmoodle->vdbpass) && ($CFG->ostype != 'WINDOWS')) {
            $pass = "-p".escapeshellarg($vmoodle->vdbpass);
        } else {
            $pass = "-p".$vmoodle->vdbpass;
        }

        // Making the command.
        if ($CFG->ostype == 'WINDOWS') {
            $cmd = "-h{$host} -P{$port} -u{$vmoodle->vdblogin} {$pass} {$vmoodle->vdbname}";
            $cmd .= " > " . $outputfile;
        } else {
            $cmd = "-h{$host} -P{$port} -u{$vmoodle->vdblogin} {$pass} {$vmoodle->vdbname}";
            $cmd .= " > " . escapeshellarg($outputfile);
        }

        // MySQL application (see 'vconfig.php').
        $pgm = (!empty($config->cmd_mysqldump)) ? stripslashes($config->cmd_mysqldump) : false;
    } else if ($vmoodle->vdbtype == 'postgres') {
        // PostgreSQL.
        // Default port.
        if (empty($port)) {
            $port = 5432;
        }

        // Password.
        if (!empty($vmoodle->vdbpass)) {
            $pass = '"'.$vmoodle->vdbpass.'"';
        }

        // Making the command, (if needed, a password prompt will be displayed).
        if ($CFG->ostype == 'WINDOWS') {
            $cmd = " -d -b -Fc -h {$host} -p {$port} -U {$vmoodle->vdblogin} {$vmoodle->vdbname}";
            $cmd .= " > " . $outputfile;
        } else {
            $cmd = " -d -b -Fc -h {$host} -p {$port} -U {$vmoodle->vdblogin} {$vmoodle->vdbname}";
            $cmd .= " > " . escapeshellarg($outputfile);
        }

        // PostgreSQL application (see 'vconfig.php').
        $pgm = (!empty($config->cmd_pgsqldump)) ? $config->cmd_pgsqldump : false;
    }

    if (!$pgm) {
        print_error('dbdumpnotavailable', 'local_vmoodle');
        return false;
    } else {
        $phppgm = str_replace("\\", '/', $pgm);
        $phppgm = str_replace("\"", '', $phppgm);
        $pgm = str_replace('/', DIRECTORY_SEPARATOR, $pgm);

        if (!is_executable($phppgm)) {
            print_error('dbcommanderror', 'local_vmoodle', '', $phppgm);
            return false;
        }
        // Final command.
        $cmd = $pgm.' '.$cmd;

        // Prints log messages in the page and in 'cmd.log'.
        if ($log = fopen(dirname($outputfile).'/cmd.log', 'a')) {
            fwrite($log, $cmd."\n");
        }

        // Executes the SQL command.
        exec($cmd, $execoutput, $returnvalue);
        if ($log) {
            foreach ($execoutput as $execline) {
                fwrite($log, $execline."\n");
            }
            fwrite($log, $returnvalue."\n");
            fclose($log);
        }
    }

    // End with success.
    return true;
}

/**
 * Loads a complete database dump from a template, and does some update.
 * @uses $CFG, $DB
 * @param object $vmoodledata All the Host_form data.
 * @param array $outputfile The variables to inject in setup template SQL.
 * @return bool If true, loading database from template was sucessful, otherwise false.
 */
function vmoodle_load_database_from_template($vmoodledata) {
    global $CFG, $DB;

    // Gets the HTTP adress scheme (http, https, etc...) if not specified.
    if (is_null(parse_url($vmoodledata->vhostname, PHP_URL_SCHEME))) {
        $vmoodledata->vhostname = parse_url($CFG->wwwroot, PHP_URL_SCHEME).'://'.$vmoodledata->vhostname;
    }

    $manifest = vmoodle_get_vmanifest($vmoodledata->vtemplate);
    $hostname = mnet_get_hostname_from_uri($CFG->wwwroot);
    $description = $DB->get_field('course', 'fullname', array('id' => SITEID));
    $cfgipaddress = gethostbyname($hostname);

    vmoodle_create_database($vmoodledata);

    // SQL files paths.
    $templatesqlfilepath = $CFG->dataroot.'/vmoodle/'.$vmoodledata->vtemplate.'_sql/vmoodle_master.sql';
    // Create temporaries files for replacing data.
    $temporarysqlfilepath = $CFG->dataroot.'/vmoodle/'.$vmoodledata->vtemplate.'_sql/vmoodle_master.temp.sql';

    // Retrieves files contents into strings.
    if (!($dumptxt = file_get_contents($templatesqlfilepath))) {
        print_error('nosql', 'local_vmoodle');
        return false;
    }

    // Change the tables prefix if required prefix does not match manifest's one (sql template).
    if ($manifest['templatevdbprefix'] != $vmoodledata->vdbprefix) {
        $dumptxt = str_replace($manifest['templatevdbprefix'], $vmoodledata->vdbprefix, $dumptxt);
    }

    // Fix special case on adodb_logsql table if prefix has a schema part (PostgreSQL).
    if (preg_match('/(.*)\./', $vmoodledata->vdbprefix, $matches)) {
        // We have schema, thus relocate adodb_logsql table within schema.
        $dumptxt = str_replace('adodb_logsql', $matches[1].'.adodb_logsql', $dumptxt);
    }

    // Puts strings into the temporary files.
    if (!file_put_contents($temporarysqlfilepath, $dumptxt)) {
        print_error('nooutputfortransformedsql', 'local_vmoodle');
        return false;
    }

    $sqlcmd = vmoodle_get_database_dump_cmd($vmoodledata);

    // Make final commands to execute, depending on the database type.
    $import = $sqlcmd.$temporarysqlfilepath;

    // Execute the command.

    if (!defined('CLI_SCRIPT')) {
        putenv('LANG=en_US.utf-8');
    }

    // Ensure utf8 is correctly handled by php exec().
    // @see http://stackoverflow.com/questions/10028925/call-a-program-via-shell-exec-with-utf-8-text-input.

    exec($import, $output, $return);

    // End.
    return true;
}

/**
 * Creates a database for Moodle.
 * @param object $vmoodledata
 */
function vmoodle_create_database($vmoodledata) {
    global $DB;

    // Availability of SQL commands.

    // Checks if paths commands have been properly defined in 'vconfig.php'.
    if ($vmoodledata->vdbtype == 'mysql') {
        $createstatement = 'CREATE DATABASE IF NOT EXISTS %DATABASE% DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ';
    } else if (($vmoodledata->vdbtype == 'mysqli') || ($vmoodledata->vdbtype == 'mariadb')) {
        $createstatement = 'CREATE DATABASE IF NOT EXISTS %DATABASE% DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci ';
    } else if ($vmoodledata->vdbtype == 'postgres') {
        $createstatement = 'CREATE SCHEMA IF NOT EXISTS %DATABASE% ';
    }

    // Creates the new database before importing the data.
    $sql = str_replace('%DATABASE%', $vmoodledata->vdbname, $createstatement);
    try {
        $DB->execute($sql);
    } catch (Exception $ex) {
        $e = new StdClass;
        $e->sql = $sql;
        $e->error = $DB->get_last_error();
        print_error('noexecutionfor', 'local_vmoodle', '', $e);
        die;
    }
}

/**
 * Loads a complete database dump from a template, and does some update.
 * @uses $CFG
 * @param object $vmoodledata All the Host_form data.
 * @param object $thisashost The mnet_host record that represents the master.
 * @return bool If true, fixing database from template was sucessful, otherwise false.
 */
function vmoodle_fix_database($vmoodledata, $thisashost) {
    global $CFG, $SITE, $DB;

    $manifest = vmoodle_get_vmanifest($vmoodledata->vtemplate);
    $hostname = mnet_get_hostname_from_uri($CFG->wwwroot);
    $cfgipaddress = gethostbyname($hostname);

    // SQL files paths.
    $temporarysetup_path = $CFG->dataroot.'/vmoodle/'.$vmoodledata->vtemplate.'_sql/vmoodle_setup_template.temp.sql';

    if (!$file = fopen($temporarysetup_path, 'wb')) {
        print_error('couldnotwritethesetupscript', 'local_vmoodle');
        return false;
    }
    $prefix = $vmoodledata->vdbprefix;
    $vmoodledata->description = str_replace("'", "''", $vmoodledata->description);
    // Setup moodle name and description.
    $sql = "UPDATE {$prefix}course SET fullname='{$vmoodledata->name}', shortname='{$vmoodledata->shortname}',";
    $sql .= " summary='{$vmoodledata->description}' WHERE category = 0 AND id = 1;\n";
    fwrite($file, $sql);

    // Setup a suitable cookie name.
    $cookiename = clean_param($vmoodledata->shortname, PARAM_ALPHANUM);
    fwrite($file, "UPDATE {$prefix}config SET value='{$cookiename}' WHERE name = 'sessioncookie';\n\n");

    // Delete all logs.
    fwrite($file, "DELETE FROM {$prefix}log;\n\n");
    fwrite($file, "DELETE FROM {$prefix}mnet_log;\n\n");
    fwrite($file, "DELETE FROM {$prefix}mnet_session;\n\n"); // Purge mnet logs and sessions.

    /*
     * we need :
     * clean host to service
     * clean mnet_hosts unless All Hosts and self record
     * rebind self record to new wwwroot, ip and cleaning public key
     */
    fwrite($file, "--\n-- Cleans all mnet tables but keeping service configuration in place \n--\n");

    // We first remove all services. Services will be next rebuild based on template or minimal strategy.
    // We expect all service declaraton are ok in the template DB as the template comes from homothetic installation.
    fwrite($file, "DELETE FROM {$prefix}mnet_host2service;\n\n");

    // We first remove all services. Services will be next rebuild based on template or minimal strategy.
    fwrite($file, "DELETE FROM {$prefix}mnet_host WHERE wwwroot != '' AND wwwroot != '{$manifest['templatewwwroot']}';\n\n");
    $vmoodlenodename = str_replace("'", "''", $vmoodledata->name);
    $sql = "UPDATE {$prefix}mnet_host SET id = 1, wwwroot = '{$vmoodledata->vhostname}', name = '{$vmoodlenodename}',";
    $sql .= " public_key = '', public_key_expires = 0, ip_address = '{$cfgipaddress}' ";
    $sql .= "WHERE wwwroot = '{$manifest['templatewwwroot']}';\n\n";
    fwrite($file, $sql);

    // Ensure consistance.
    fwrite($file, "UPDATE {$prefix}config SET value = 1 WHERE name = 'mnet_localhost_id';\n\n");

    // Disable all mnet users.
    fwrite($file, "UPDATE {$prefix}user SET deleted = 1 WHERE auth = 'mnet' AND username != 'admin';\n\n");

    /*
     * this is necessary when using a template from another location or deployment target as
     * the salt may have changed. We would like that all primary admins be the same techn admin.
     */
    // Get primary ID of moodle master.
    $params = array('username' => 'admin', 'mnethostid' => $CFG->mnet_localhost_id);
    $localadmin = $DB->get_record('user', $params);
    if (!$localadmin) {
        throw new moodle_exception('No local admin account');
    }
    fputs($file, "--\n-- Force physical admin with same credentials than in master.  \n--\n");
    $sql = "UPDATE {$prefix}user SET password = '{$localadmin->password}' WHERE auth = 'manual' AND username = 'admin';\n\n";
    fwrite($file, $sql);

    if ($vmoodledata->mnet == -1) { // NO MNET AT ALL.
        /*
         * we need :
         * disable mnet
         */
        fputs($file, "UPDATE {$prefix}config SET value = 'off' WHERE name = 'mnet_dispatcher_mode';\n\n");
    } else {
        // ALL OTHER CASES.
        /*
         * we need :
         * enable mnet
         * push our master identity in mnet_host table
         */
        fputs($file, "UPDATE {$prefix}config SET value = 'strict' WHERE name = 'mnet_dispatcher_mode';\n\n");
        $sql = "INSERT INTO {$prefix}mnet_host (wwwroot, ip_address, name, public_key, applicationid, public_key_expires) ";
        $sql .= "VALUES ('{$thisashost->wwwroot}', '{$thisashost->ip_address}', '{$SITE->fullname}', '{$thisashost->public_key}', ";
        $sql .= "{$thisashost->applicationid}, '{$thisashost->public_key_expires}');\n\n";
        fputs($file, $sql);

        fputs($file, "--\n-- Enable the service 'mnetadmin, sso_sp and sso_ip' with host which creates this host.  \n--\n");

        $sql = "INSERT INTO {$prefix}mnet_host2service VALUES (null, (SELECT id FROM {$prefix}mnet_host ";
        $sql .= "WHERE wwwroot LIKE '{$thisashost->wwwroot}'), ";
        $sql .= "(SELECT id FROM {$prefix}mnet_service WHERE name LIKE 'mnetadmin'), 1, 0);\n\n";
        fputs($file, $sql);

        $sql = "INSERT INTO {$prefix}mnet_host2service VALUES (null, (SELECT id FROM {$prefix}mnet_host ";
        $sql .= "WHERE wwwroot LIKE '{$thisashost->wwwroot}'), ";
        $sql .= "(SELECT id FROM {$prefix}mnet_service WHERE name LIKE 'sso_sp'), 1, 0);\n\n";
        fputs($file, $sql);

        $sql = "INSERT INTO {$prefix}mnet_host2service VALUES (null, (SELECT id FROM {$prefix}mnet_host ";
        $sql .= "WHERE wwwroot LIKE '{$thisashost->wwwroot}'), ";
        $sql .= "(SELECT id FROM {$prefix}mnet_service WHERE name LIKE 'sso_idp'), 0, 1);\n\n";
        fputs($file, $sql);

        fputs($file, "--\n-- Insert master host user admin.  \n--\n");

        $sql = "INSERT INTO {$prefix}user (auth, confirmed, policyagreed, deleted, mnethostid, username, password) ";
        $sql .= "VALUES ('mnet', 1, 0, 0, (SELECT id FROM {$prefix}mnet_host ";
        $sql .= "WHERE wwwroot LIKE '{$thisashost->wwwroot}'), 'admin', '');\n\n";
        fputs($file, $sql);

        fputs($file, "--\n-- Links role and capabilites for master host admin.  \n--\n");
        $roleid = "(SELECT id FROM {$prefix}role WHERE shortname LIKE 'manager')";
        $contextid = 1;
        $userid = "(SELECT id FROM {$prefix}user WHERE auth LIKE 'mnet' AND username = 'admin' AND ";
        $userid .= "mnethostid = (SELECT id FROM {$prefix}mnet_host WHERE wwwroot LIKE '{$thisashost->wwwroot}'))";
        $timemodified = time();
        $modifierid = $userid;
        $component = "''";
        $itemid = 0;
        $sortorder = 1;
        $sql = "INSERT INTO {$prefix}role_assignments(id,roleid,contextid,userid,timemodified,modifierid,component,itemid,sortorder)";
        $sql .= " VALUES (0, $roleid, $contextid, $userid, $timemodified, $modifierid, $component, $itemid, $sortorder);\n\n";
        fputs($file, $sql);

        fputs($file, "--\n-- Add new network admin to local siteadmins.  \n--\n");
        $adminidsql = "(SELECT id FROM {$prefix}user WHERE auth LIKE 'mnet' AND username = 'admin' AND ";
        $adminidsql .= "mnethostid = (SELECT id FROM {$prefix}mnet_host WHERE wwwroot LIKE '{$thisashost->wwwroot}'))";
        fputs($file, "UPDATE {$prefix}config SET value = CONCAT(value, ',', $adminidsql) WHERE name = 'siteadmins';\n");

        fputs($file, "--\n-- Create a disposable key for renewing new host's keys.  \n--\n");
        fputs($file, "INSERT INTO {$prefix}config (name, value) VALUES ('bootstrap_init', '{$thisashost->wwwroot}');\n");
    }
    fclose($file);

    $sqlcmd = vmoodle_get_database_dump_cmd($vmoodledata);

    // Make final commands to execute, depending on the database type.
    $import = $sqlcmd.' '.$temporarysetup_path.' 2>&1';

    /*
     * Ensure utf8 is correctly handled by php exec().
     * @see http://stackoverflow.com/questions/10028925/call-a-program-via-shell-exec-with-utf-8-text-input
     * this is required only with PHP exec through a web access.
     */
    if (!CLI_SCRIPT) {
        putenv('LANG=en_US.utf-8');
    }

    // Execute the command.
    exec($import, $output, $return);

    if ($LOG = fopen($CFG->dataroot.'/vmoodle/'.$vmoodledata->vtemplate.'_sql/cmd.log', 'a')) {
        fputs($LOG, $import."\n");
        fputs($LOG, implode("\n", $output)."\n");
        fclose($LOG);
    }

    // End.
    return true;
}

function vmoodle_destroy($vmoodledata) {
    global $DB, $OUTPUT;

    if (!$vmoodledata) {
        return;
    }

    // Checks if paths commands have been properly defined in 'vconfig.php'.
    if ($vmoodledata->vdbtype == 'mysql') {
        $dropstatement = 'DROP DATABASE IF EXISTS';
    } else if (($vmoodledata->vdbtype == 'mysqli') || ($vmoodledata->vdbtype == 'mariadb')) {
        $dropstatement = 'DROP DATABASE IF EXISTS';
    } else if ($vmoodledata->vdbtype == 'postgres') {
        $dropstatement = 'DROP SCHEMA';
    }

    // Drop the database.

    $sql = "$dropstatement $vmoodledata->vdbname";
    if (function_exists('debug_trace')) {
        debug_trace("destroy_database : executing drop sql");
    }

    try {
        $DB->execute($sql);
    } catch (Exception $e) {
        $e = new StdClass;
        $e->sql = $sql;
        $e->error = $DB->get_last_error();
        print_error('noexecutionfor', 'local_vmoodle', '', $e);
    }

    // Destroy moodledata.

    if ($CFG->ostype == 'WINDOWS') {
        $cmd = " RMDIR \"$vmoodledata->vdatapath\" ";
    } else {
        $cmd = " rm -rf \"$vmoodledata->vdatapath\" ";
    }
    exec($cmd);

    // Delete vmoodle instance.

    $DB->delete_records('local_vmoodle', array('vhostname' => $vmoodledata->vhostname));

    // Delete all related mnet_hosts info.

    if ($mnethost = $DB->get_record('mnet_host', array('wwwroot' => $vmoodledata->vhostname))) {
        $DB->delete_records('mnet_host', array('wwwroot' => $mnethost->wwwroot));
        $DB->delete_records('mnet_host2service', array('hostid' => $mnethost->id));
        $DB->delete_records('mnetservice_enrol_courses', array('hostid' => $mnethost->id));
        $DB->delete_records('mnetservice_enrol_enrolments', array('hostid' => $mnethost->id));
        $DB->delete_records('mnet_log', array('hostid' => $mnethost->id));
        $DB->delete_records('mnet_session', array('mnethostid' => $mnethost->id));
        $DB->delete_records('mnet_sso_access_control', array('mnet_host_id' => $mnethost->id));
    }

    // If using domain subpath, add the subpath symlink (Linux only).
    if (!empty($CFG->vmoodleusesubpaths)) {
        vmoodle_del_subpath($vmoodledata);
    }
}

/**
 * get the service strategy and peer mirror strategy to apply to new host, depending on
 * settings. If no settings were made, use a simple peer to peer SSO binding so that users
 * can just roam.
 * @param object $vmoodledata the new host definition
 * @param array reference $services the service scheme to apply to new host
 * @param array reference $peerservices the service scheme to apply to new host peers
 * @param array $domain
 */
function vmoodle_get_service_strategy($vmoodlerec, &$services, &$peerservices, $domain = 'peer') {
    global $DB, $CFG;

    // We will mix in order to an single array of configurated service here.
    $servicesstrategy = unserialize(get_config('local_vmoodle', 'services_strategy'));
    $servicerecs = $DB->get_records('mnet_service', array());
    $servicesstrategy = (array)$servicesstrategy;

    if (!empty($servicerecs)) {
        // Should never happen; standard services are always there.
        if (@$vmoodlerec->services == 'subnetwork' && !empty($servicesstrategy)) {
            /*
             * $vmoodledata may not exist when binding to a master host.
             */
            foreach ($servicerecs as $key => $service) {
                $services[$service->name] = new StdClass();
                $peerservices[$service->name] = new StdClass();
                $services[$service->name]->publish       = 0 + @$servicesstrategy[$domain.'_'.$service->name.'_publish'];
                $peerservices[$service->name]->subscribe = 0 + @$servicesstrategy[$domain.'_'.$service->name.'_publish'];
                $services[$service->name]->subscribe     = 0 + @$servicesstrategy[$domain.'_'.$service->name.'_subscribe'];
                $peerservices[$service->name]->publish   = 0 + @$servicesstrategy[$domain.'_'.$service->name.'_subscribe'];
            }
        } else {
            // If no strategy has been recorded, use default SSO binding.
            foreach ($servicerecs as $key => $service) {
                $services[$service->name] = new StdClass();
                $peerservices[$service->name] = new StdClass();
                $services[$service->name]->publish = 0;
                $peerservices[$service->name]->subscribe = 0;
                $services[$service->name]->subscribe = 0;
                $peerservices[$service->name]->publish = 0;
            }
            $services['sso_sp']->publish = 1;
            $services['sso_sp']->subscribe = 1;
            $services['sso_idp']->publish = 1;
            $services['sso_idp']->subscribe = 1;
            $peerservices['sso_sp']->publish = 1;
            $peerservices['sso_sp']->subscribe = 1;
            $peerservices['sso_idp']->publish = 1;
            $peerservices['sso_idp']->subscribe = 1;
        }
    }

    // With main force mnet admin whatever is said in defaults.
    if ($domain == 'main') {
        // This would be the main strategy regaring its subs.
        $services['sso_sp']->publish = 1;
        $services['sso_idp']->subscribe = 1;
        $services['mnetadmin']->subscribe = 1;
        $services['dataexchange']->publish = 1;

        // Peer is main and this is our peer strategy. We need publish mnet admin functions to it.
        $peerservices['sso_sp']->subscribe = 1;
        $peerservices['sso_idp']->publish = 1;
        $peerservices['mnetadmin']->publish = 1;
        $peerservices['dataexchange']->subscribe = 1;

        if (is_dir($CFG->dirroot.'/mod/sharedresource')) {
            // Bind sharedresource librairies.
            /*
             * We open the main site as a provider and
             * all the subs as consumers.
             */
            $services['sharedresourceservice'] = new StdClass();
            $services['sharedresourceservice']->publish = 1;

            $peerservices['sharedresourceservice'] = new StdClass();
            $peerservices['sharedresourceservice']->subscribe = 1;
        }

        if (is_dir($CFG->dirroot.'/blocks/publishflow')) {
            $services['publishflow'] = new StdClass();
            $services['publishflow']->publish = 1;
            $services['publishflow']->subscribe = 1;

            $peerservices['publishflow'] = new Stdclass();
            $peerservices['publishflow']->publish = 1;
            $peerservices['publishflow']->subscribe = 1;
        }
    }
}

/**
 * get a proper SQLDump command
 * @param object $vmoodledata the complete new host information
 * @return string the shell command 
 */
function vmoodle_get_database_dump_cmd($vmoodledata) {
    global $CFG;

    $config = get_config('local_vmoodle');

    // Checks if paths commands have been properly defined in 'vconfig.php'.
    if ($vmoodledata->vdbtype == 'mysql') {
        $pgm = (!empty($config->cmd_mysql)) ? stripslashes($config->cmd_mysql) : false;
    } else if (($vmoodledata->vdbtype == 'mysqli') || ($vmoodledata->vdbtype == 'mariadb')) {
        $pgm = (!empty($config->cmd_mysql)) ? stripslashes($config->cmd_mysql) : false;
    } else if ($vmoodledata->vdbtype == 'postgres') {
        // Needs to point the pg_restore command.
        $pgm = (!empty($config->cmd_pgsql)) ? stripslashes($config->cmd_pgsql) : false;
    }

    // Checks the needed program.
    if (!$pgm){
        print_error('dbcommandnotconfigured', 'local_vmoodle');
        return false;
    }

    $phppgm = str_replace("\\", '/', $pgm);
    $phppgm = str_replace("\"", '', $phppgm);
    $pgm = str_replace("/", DIRECTORY_SEPARATOR, $pgm);

    if (!is_executable($phppgm)) {
        print_error('dbcommanddoesnotmatchanexecutablefile', 'local_vmoodle', '', $phppgm);
        return false;
    }

    // Retrieves the host configuration (more secure).
    $thisvmoodle = vmoodle_make_this();
    if (strstr($thisvmoodle->vdbhost, ':') !== false) {
        list($thisvmoodle->vdbhost, $thisvmoodle->vdbport) = split(':', $thisvmoodle->vdbhost);
    }

    // Password.
    if (!empty($thisvmoodle->vdbpass)) {
        $thisvmoodle->vdbpass = '-p'.escapeshellarg($thisvmoodle->vdbpass).' ';
    }

    // Making the command line (see 'vconfig.php' file for defining the right paths).
    if ($vmoodledata->vdbtype == 'mysql') {
        $sqlcmd = $pgm.' -h'.$thisvmoodle->vdbhost.(isset($thisvmoodle->vdbport) ? ' -P'.$thisvmoodle->vdbport.' ' : ' ');
        $sqlcmd .= '-u'.$thisvmoodle->vdblogin.' '.$thisvmoodle->vdbpass;
        $sqlcmd .= $vmoodledata->vdbname.' < ';
    } else if (($vmoodledata->vdbtype == 'mysqli') || ($vmoodledata->vdbtype == 'mariadb')) {
        $sqlcmd = $pgm.' -h'.$thisvmoodle->vdbhost.(isset($thisvmoodle->vdbport) ? ' -P'.$thisvmoodle->vdbport.' ' : ' ');
        $sqlcmd .= '-u'.$thisvmoodle->vdblogin.' '.$thisvmoodle->vdbpass;
        $sqlcmd .= $vmoodledata->vdbname.' < ';
    } else if ($vmoodledata->vdbtype == 'postgres') {
        $sqlcmd    = $pgm.' -Fc -h '.$thisvmoodle->vdbhost.(isset($thisvmoodle->vdbport) ? ' -p '.$thisvmoodle->vdbport.' ' : ' ');
        $sqlcmd .= '-U '.$thisvmoodle->vdblogin.' ';
        $sqlcmd .= '-d '.$vmoodledata->vdbname.' -f ';
    }
    return $sqlcmd;
}

/**
 * Dump existing files of a template.
 * @uses $CFG
 * @param string $templatename The template's name.
 * @param string $destpath The destination path.
 */
function vmoodle_dump_files_from_template($templatename, $destpath) {
    global $CFG;

    // Copies files and protects against copy recursion.
    $templatefilespath = $CFG->dataroot.'/vmoodle/'.$templatename.'_vmoodledata';
    $destpath = str_replace('\\\\', '\\', $destpath);
    if (!is_dir($destpath)) {
        mkdir($destpath);
    }
    filesystem_copy_tree($templatefilespath, $destpath, '');
}

/**
 * @param object $submitteddata
 *
 */
function vmoodle_bind_to_network($submitteddata, &$newmnethost) {
    global $USER, $CFG, $DB, $OUTPUT;

    // Getting services schemes to apply.
    vmoodle_get_service_strategy($submitteddata, $services, $peerservices, 'peer');

    $idnewblock = $DB->get_field('local_vmoodle', 'id', array('vhostname' => $submitteddata->vhostname));

    // Last mnet has been raised by one at step 3 so we add to network if less.
    if ($submitteddata->mnet < vmoodle_get_last_subnetwork_number()) {
        // Retrieves the subnetwork member(s).
        $subnetworkhosts = array();
        $select = 'id != ? AND mnet = ? AND enabled = 1';
        $subnetworkmembers = $DB->get_records_select('local_vmoodle', $select, array($idnewblock, $submitteddata->mnet));

        if (!empty($subnetworkmembers)) {
            foreach ($subnetworkmembers as $subnetworkmember) {
                $temphost = new stdClass();
                $temphost->wwwroot = $subnetworkmember->vhostname;
                $temphost->name = utf8_decode($subnetworkmember->name);
                $subnetworkhosts[] = $temphost;
            }
        }

        // Member(s) of the subnetwork add the new host.
        if (!empty($subnetworkhosts)) {
            $rpcclient = new \local_vmoodle\XmlRpc_Client();
            $rpcclient->reset_method();
            $rpcclient->set_method('local/vmoodle/rpclib.php/mnetadmin_rpc_bind_peer');
            // Authentication params.
            $rpcclient->add_param($USER->username, 'string');
            $userhostroot = $DB->get_field('mnet_host', 'wwwroot', array('id' => $USER->mnethostid));
            $rpcclient->add_param($userhostroot, 'string');
            $rpcclient->add_param($CFG->wwwroot, 'string');
            // Peer to bind to.
            $rpcclient->add_param((array)$newmnethost, 'array');
            $rpcclient->add_param($peerservices, 'array');

            foreach ($subnetworkhosts as $subnetwork_host) {
                $tempmember = new \local_vmoodle\Mnet_Peer();
                $tempmember->set_wwwroot($subnetwork_host->wwwroot);
                if (!$rpcclient->send($tempmember)) {
                    echo $OUTPUT->notification(implode('<br />', $rpcclient->get_errors($tempmember)));
                }

                $rpcclient2 = new \local_vmoodle\XmlRpc_Client();
                $rpcclient2->reset_method();
                $rpcclient2->set_method('local/vmoodle/rpclib.php/mnetadmin_rpc_bind_peer');
                // Authentication params.
                $rpcclient2->add_param($USER->username, 'string');
                $userhostroot = $DB->get_field('mnet_host', 'wwwroot', array('id' => $USER->mnethostid));
                $rpcclient2->add_param($userhostroot, 'string');
                $rpcclient2->add_param($CFG->wwwroot, 'string');
                // Peer to bind to.
                $rpcclient2->add_param((array)$tempmember, 'array');
                $rpcclient2->add_param($services, 'array');

                if (!$rpcclient2->send($newmnethost)) {
                    echo $OUTPUT->notification(implode('<br />', $rpcclient2->get_errors($newmnethost)));
                }
                unset($rpcclient2); // Free some resource.
            }
        }
    }

    // Getting services schemes to apply to main.
    vmoodle_get_service_strategy($submitteddata, $services, $peerservices, 'main');

    $mainhost = new \local_vmoodle\Mnet_Peer(); // This is us.
    $mainhost->set_wwwroot($CFG->wwwroot);

    vmoodle_bind_services($newmnethost, $peerservices);

    $rpcclient = new \local_vmoodle\XmlRpc_Client();
    $rpcclient->reset_method();
    $rpcclient->set_method('local/vmoodle/rpclib.php/mnetadmin_rpc_bind_peer');
    $rpcclient->add_param($USER->username, 'string');
    $userhostroot = $DB->get_field('mnet_host', 'wwwroot', array('id' => $USER->mnethostid));
    $rpcclient->add_param($userhostroot, 'string');
    $rpcclient->add_param($CFG->wwwroot, 'string');
    // Peer to bind to : this is us.
    $rpcclient->add_param((array)$mainhost, 'array');
    $rpcclient->add_param($services, 'array');

    $rpcclient->send($newmnethost);
}

function vmoodle_bind_services($newmnethost, $services) {
    global $DB;

    // Bind the local service strategy to new host.
    if (!empty($services)) {
        // Eventually deletes something on the way.
        $DB->delete_records('mnet_host2service', array('hostid' => $newmnethost->id));
        foreach ($services as $servicename => $servicestate) {
            $service = $DB->get_record('mnet_service', array('name' => $servicename));
            if (!$service) {
                throw(new Exception("Services Binding : Uninstalled required service $servicename\n"));
            }
            $host2service = new stdclass();
            $host2service->hostid = $newmnethost->id;
            $host2service->serviceid = $service->id;
            $host2service->publish = 0 + @$servicestate->publish;
            $host2service->subscribe = 0 + @$servicestate->subscribe;
            $DB->insert_record('mnet_host2service', $host2service);
        }
    }
}

/**
 * Checks existence and consistency of a full template.
 * @uses $CFG
 * @param string $templatename The template's name.
 * @return bool Returns TRUE if the full template is consistency, FALSE otherwise.
 */
function vmoodle_exist_template($templatename) {
    global $CFG;

    // Needed paths for checking.
    $templatedirfiles = $CFG->dataroot.'/vmoodle/'.$templatename.'_vmoodledata';
    $templatedirsql = $CFG->dataroot.'/vmoodle/'.$templatename.'_sql';

    return (in_array($templatename, vmoodle_get_available_templates())
        && is_readable($templatedirfiles)
            && is_readable($templatedirsql));
}

/*
 * Read manifest values in vmoodle template.
 */

/**
 * Gets value in manifest file (in SQL folder of a template).
 * @uses $CFG
 * @param string $templatename The template's name.
 * @return array The manifest values.
 */
function vmoodle_get_vmanifest($templatename) {
    global $CFG;

    // Reads php values.
    include($CFG->dataroot.'/vmoodle/'.$templatename.'_sql/manifest.php');
    $manifest = array();
    $manifest['templatewwwroot'] = $templatewwwroot;
    $manifest['templatevdbprefix'] = $templatevdbprefix;

    return $manifest;
}

/**
 * Searches and returns the last created subnetwork number.
 * @return integer The last created subnetwork number.
 */
function vmoodle_get_last_subnetwork_number() {
    global $DB;

    $nbmaxsubnetwork = $DB->get_field('local_vmoodle', 'MAX(mnet)', array());
    return $nbmaxsubnetwork;
}

/*
 * Be careful : this library might be include BEFORE any configuration
 * or other usual Moodle libs are loaded. It cannot rely on
 * most of the Moodle API functions.
 */

/**
 * Prints an administrative status (broken, enabled, disabled) for a Vmoodle.
 *
 * @uses $CFG The global configuration.
 * @param object $vmoodle The Vmoodle object.
 * @param boolean $return If false, prints the Vmoodle state, else not.
 * @return string The Vmoodle state.
 */
function vmoodle_print_status($vmoodle, $return = false) {
    global $OUTPUT;

    if (!vmoodle_check_installed($vmoodle)) {
        $vmoodlestate = '<img src="'.$OUTPUT->image_url('broken', 'local_vmoodle').'"/>';
    } else if ($vmoodle->enabled) {
        $params = array('view' => 'management', 'what' => 'disable', 'id' => $vmoodle->id);
        $disableurl = new moodle_url('/local/vmoodle/view.php', $params);
        $pix = '<img src="'.$OUTPUT->image_url('enabled', 'local_vmoodle').'" />';
        $vmoodlestate = '<a href="'.$disableurl.'" title="'.get_string('disable').'">'.$pix.'</a>';
    } else {
        $params = array('view' => 'management', 'what' => 'enable', 'id' => $vmoodle->id);
        $enableurl = new moodle_url('/local/vmoodle/view.php', $params);
        $pix = '<img src="'.$OUTPUT->image_url('disabled', 'local_vmoodle').'" />';
        $vmoodlestate = '<a href="'.$enableurl.'" title="'.get_string('enable').'">'.$pix;
    }

    // Prints the Vmoodle state.
    if (!$return) {
        echo $vmoodlestate;
    }

    return $vmoodlestate;
}

/**
 * Checks physical availability of the Vmoodle.
 * @param object $vmoodle The Vmoodle object.
 * @return boolean If true, the Vmoodle is physically available.
 */
function vmoodle_check_installed($vmoodle) {
    return (filesystem_is_dir($vmoodle->vdatapath, ''));
}

if (!function_exists('print_error_class')) {
    /**
     * Adds an CSS marker error in case of matching error.
     * @param array $errors The current error set.
     * @param string $errorkey The error key.
     */
    function print_error_class($errors, $errorkeylist) {
        if ($errors) {
            foreach ($errors as $anerror) {
                if ($anerror->on == '') {
                    continue;
                }
                if (preg_match("/\\b{$anerror->on}\\b/", $errorkeylist)) {
                    echo " class=\"formerror\" ";
                    return;
                }
            }
        }
    }
}

function vmoodle_get_string($identifier, $subplugin, $a = '', $lang = '') {
    global $CFG;

    static $string = array();

    if (empty($lang)) {
        $lang = current_language();
    }

    list($type, $plug) = explode('_', $subplugin);

    include($CFG->dirroot.'/local/vmoodle/db/subplugins.php');

    if (!isset($plugstring[$plug])) {
        if (file_exists($CFG->dirroot.'/'.$subplugins[$type].'/'.$plug.'/lang/en/'.$subplugin.'.php')) {
            include($CFG->dirroot.'/'.$subplugins[$type].'/'.$plug.'/lang/en/'.$subplugin.'.php');
        } else {
            debugging("English lang file must exist", DEBUG_DEVELOPER);
        }

        // Override with lang file if exists.
        if (file_exists($CFG->dirroot.'/'.$subplugins[$type].'/'.$plug.'/lang/'.$lang.'/'.$subplugin.'.php')) {
            include($CFG->dirroot.'/'.$subplugins[$type].'/'.$plug.'/lang/'.$lang.'/'.$subplugin.'.php');
        } else {
            $string = array();
        }
        $plugstring[$plug] = $string;
    }

    if (array_key_exists($identifier, $plugstring[$plug])) {
        $result = $plugstring[$plug][$identifier];
        if ($a !== null) {
            if (is_object($a) || is_array($a)) {
                $a = (array)$a;
                $search = array();
                $replace = array();
                foreach ($a as $key => $value) {
                    if (is_int($key)) {
                        // We do not support numeric keys - sorry!
                        continue;
                    }
                    $search[]  = '{$a->'.$key.'}';
                    $replace[] = (string)$value;
                }
                if ($search) {
                    $result = str_replace($search, $replace, $result);
                }
            } else {
                $result = str_replace('{$a}', (string)$a, $result);
            }
        }
        // Debugging feature lets you display string identifier and component.
        if (!empty($CFG->debugstringids) && optional_param('strings', 0, PARAM_INT)) {
            $result .= ' {' . $identifier . '/' . $subplugin . '}';
        }
        return $result;
    }

    if (!empty($CFG->debugstringids) && optional_param('strings', 0, PARAM_INT)) {
        return "[[$identifier/$subplugin]]";
    } else {
        return "[[$identifier]]";
    }
}

/**
 * Sets up global $DB moodle_database instance
 *
 * @global stdClass $CFG The global configuration instance.
 * @see config.php
 * @see config-dist.php
 * @global stdClass $DB The global moodle_database instance.
 * @return void|bool Returns true when finished setting up $DB. Returns void when $DB has already been set.
 */
function vmoodle_setup_db($vmoodle) {
    global $CFG;

    if (!isset($vmoodle->vdblogin)) {
        $vmoodle->vdblogin = '';
    }

    if (!isset($vmoodle->vdbpass)) {
        $vmoodle->vdbpass = '';
    }

    if (!isset($vmoodle->vdbname)) {
        $vmoodle->vdbname = '';
    }

    if (!isset($vmoodle->dblibrary)) {
        $vmoodle->dblibrary = 'native';
        // Use new drivers instead of the old adodb driver names.
        switch ($vmoodle->vdbtype) {
            case 'postgres7':
                $vmoodle->vdbtype = 'pgsql';
                break;

            case 'mssql_n':
                $vmoodle->vdbtype = 'mssql';
                break;

            case 'oci8po':
                $vmoodle->vdbtype = 'oci';
                break;

            case 'mysql':
                $vmoodle->vdbtype = 'mysqli';
                break;

            case 'mariadb':
                $vmoodle->vdbtype = 'mariadb';
                break;
        }
    }

    if (!isset($vmoodle->dboptions)) {
        $vmoodle->dboptions = array();
    }

    if (isset($vmoodle->vdbpersist)) {
        $vmoodle->dboptions['dbpersist'] = $vmoodle->vdbpersist;
    }

    if (!$vdb = moodle_database::get_driver_instance($vmoodle->vdbtype, $vmoodle->dblibrary)) {
        throw new dml_exception('dbdriverproblem', "Unknown driver $vmoodle->dblibrary/$vmoodle->dbtype");
    }

    $vdb->connect($vmoodle->vdbhost, $vmoodle->vdblogin, $vmoodle->vdbpass, $vmoodle->vdbname,
                  $vmoodle->vdbprefix, $vmoodle->dboptions);

    $vmoodle->vdbfamily = $vdb->get_dbfamily(); // TODO: BC only for now.

    return $vdb;
}

/**
 * Synchronizes the vmoodle register to all active subhosts residing in the same DB server.
 */
function vmoodle_sync_register() {
    global $DB, $CFG;

    $allhosts = $DB->get_records('local_vmoodle', array('enabled' => 1));
    $targethosts = $DB->get_records('local_vmoodle', array('enabled' => 1));

    $i = 1;
    if ($allhosts) {
        foreach ($targethosts as $t) {

            if ($t->vdbhost != $CFG->dbhost) {
                echo "Not same vdb host for {$t->shortname} {$t->vhostname} . Skipping\n";
                continue;
            }

            echo "Copying VMoodle register in {$t->shortname} {$t->vhostname} \n";

            $sql = "TRUNCATE `{$t->vdbname}`.{local_vmoodle} ";
            $DB->execute($sql);

            foreach ($allhosts as $h) {

                $name = str_replace("'", "\\'", $h->name);
                $description = str_replace("'", "\\'", $h->description);

                $sql = "
                    INSERT INTO
                        `{$t->vdbname}`.{local_vmoodle} (
                            `name`,
                            `shortname`,
                            `description`,
                            `vhostname`,
                            `vdbtype`,
                            `vdbhost`,
                            `vdblogin`,
                            `vdbpass`,
                            `vdbname`,
                            `vdbprefix`,
                            `vdbpersist`,
                            `vdatapath`,
                            `mnet`,
                            `enabled`,
                            `timecreated`
                        )
                    VALUES (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?,
                        ?)
                ";

                $params = array($name,
                                $h->shortname,
                                $description,
                                $h->vhostname,
                                $h->vdbtype,
                                $h->vdbhost,
                                $h->vdblogin,
                                $h->vdbpass,
                                $h->vdbname,
                                $h->vdbprefix,
                                $h->vdbpersist,
                                $h->vdatapath,
                                $h->mnet,
                                $h->enabled,
                                $h->timecreated);
                $DB->execute($sql, $params);

                echo '.';
            }
            echo "\n";
        }
    }

    echo "Copied.\n";
}

/**
 * parses a physical config file describing a moodle to 
 * extract data for vmoodle integration.
 *
 * @param string $configfile the path of the config file.
 *
 * @return an array of vmoodle attributes.
 */
function vmoodle_parse_config($configfile) {
    global $CFG;

    // Protect $CFG from overwrite by the analysed config file.
    $cfg = $CFG;

    if (!is_readable($configfile)) {
        return null;
    }

    $shortname = basename($configfile, '.php');
    $shortname = str_replace('config-moodle-', '', $shortname);

    include($configfile);

    $data['vhostname'] = $CFG->wwwroot;
    $data['name'] = '';
    $data['shortname'] = $shortname;
    $data['vdbname'] = $CFG->dbname;
    $data['vdbhost'] = $CFG->dbhost;
    $data['vdblogin'] = $CFG->dbuser;
    $data['vdbprefix'] = $CFG->prefix;
    $data['vdbpass'] = $CFG->dbpass;
    $data['vdatapath'] = $CFG->dataroot;
    $data['vdbpersist'] = 0;

    // Restore original $CFG values.
    $CFG = $cfg;

    return $data;
}

function vmoodle_add_subpath(&$vmoodle) {
    global $CFG;

    $config = get_config('local_vmoodle');

    if ($CFG->ostype != 'WINDOWS') {

        // Take first path fragmnent.
        $parts = explode('/', preg_replace('#https?://#', '', $vmoodle->vhostname));
        array_shift($parts); // remove domain name.
        $vmoodlepath = array_shift($parts);

        if (!is_link($CFG->dirroot.'/'.$vmoodlepath)) {

            $cmd = '';
            if (!empty($config->sudoer)) {
                $cmd = "sudo -u{$config->sudoer} ";
            }
            $cmd .= "ln -s {$CFG->dirroot} {$CFG->dirroot}/{$vmoodlepath}";

            exec($cmd, $output, $return);
            if ($return) {
                // We assume the following man statement:
                /*
                 * sudo exits with a value of 1 if there is a configuration/permission problem or if sudo
                 * cannot execute the given command.
                 */
                mtrace('Symlink creation failed. You may create the vmoodle virtual subdir by hand.');
                mtrace($cmd);
                mtrace(implode("\n", $output));
            }

        }
    } else {
        mtrace('VMoodle Sub path cannot be used on Windows systems. Symlink not created.');
    }
}

function vmoodle_del_subpath(&$vmoodle) {
    global $CFG;

    $config = get_config('local_vmoodle');

    if ($CFG->ostype != 'WINDOWS') {

        // Take first path fragmnent.
        $parts = explode('/', preg_replace('#https?://#', '', $vmoodle->vhostname));
        array_shift($parts); // remove domain name.
        $vmoodlepath = array_shift($parts);

        if (is_link($CFG->dirroot.'/'.$vmoodlepath)) {

            $cmd = '';
            if (!empty($config->sudoer)) {
                $cmd = "sudo -u{$config->sudoer} ";
            }
            $cmd .= "unlink {$CFG->dirroot}/{$vmoodlepath}";

            exec($cmd, $output, $return);
            if ($return != 0) {
                // We assume the following man statement:
                /*
                 * sudo exits with negative value if there is a configuration/permission problem or if sudo
                 * cannot execute the given command.
                 */
                mtrace('Symlink deletion failed. You may remove the vmoodle virtual subdir by hand.');
                mtrace($cmd);
                mtrace(implode("\n", $output));
            }

        }
    } else {
        mtrace('VMoodle Sub path cannot be used on Windows systems. Resuming.');
    }
}

function vmoodle_load_command($plugin, $commandname) {
    global $CFG;

    if (!in_array($plugin, array('generic', 'roles', 'plugins', 'courses'))) {
        throw new Exception("Unsupported or unkown plugin $plugin");
    }

    $commandclassfile = $CFG->dirroot.'/local/vmoodle/plugins/'.$plugin.'/classes/Command_'.$commandname.'.php';
    include_once($commandclassfile);
    $commandclass = 'vmoodleadminset_'.$plugin.'\\Command_'.$commandname;
    $command = new $commandclass();
    return $command;
}