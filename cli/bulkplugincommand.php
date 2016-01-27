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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

define('CLI_SCRIPT', true);

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions

// Ensure options are blanck;
unset($options);

// Now get cli options.

list($options, $unrecognized) = cli_get_params(
    array(
        'help'             => false,
        'plugin'           => false,
        'fulldelete'       => false,
        'test'             => false
    ),
    array(
        'h' => 'help',
        'p' => 'plugin',
        'f' => 'fulldelete',
        't' => 'test'
    )
);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    $help = "Command line ENT Global Updater.
Options:
-h, --help              Print out this help
-p, --plugin            Complete plugin name (ex : local_vmoodle)
-f, --fulldelete        To completely remove source from wwwroot after uninstalling
-t, --test              Stops after host resolution, telling the actual config that will be used
";
    echo $help;
    die;
}

$allhosts = $DB->get_records('local_vmoodle');

mtrace("Start uninstalling plugin {$options['plugin']}...\n");

$phpcmd = "php";
if(!isset($CFG->ostype) || $CFG->ostype == 'WINDOWS') {
    if(isset($CFG->phpinstallpath))
        $phpcmd = '"'.$CFG->phpinstallpath.'/php.exe"';
}

$test = '';
if($options['test']) {
    $test = ' --test';
}
$alldone = true;

foreach ($allhosts as $host) {
    $cmd = "$phpcmd {$CFG->dirroot}/local/vmoodle/cli/plugin.php --host=\"{$host->vhostname}\" --plugin=\"{$options['plugin']}\" --non-interactive".$test;
    mtrace("Executing :$cmd\n");

    $output = array();
    exec($cmd, $output, $return);
    if ($return) {
        mtrace("Script on {$host->vhostname} ended with error\n");
        $alldone = false;
    }
    else {
        mtrace(implode("\n", $output)."\n");
    }
    mtrace("\n######################################################\n");
}

//Upgrade master host wich is not into its self VMoodle hosts SQL table 
$cmd = "$phpcmd {$CFG->dirroot}/local/vmoodle/cli/plugin.php --host=\"{$CFG->wwwroot}\" --plugin=\"{$options['plugin']}\" --non-interactive".$test;
mtrace("Executing $cmd\n");
$output = array();
exec($cmd, $output, $return);
if ($return) {
    mtrace("uninstallation ended with error for master host\n");
}
else {
    mtrace(implode("\n", $output)."\n");
}
mtrace("\n######################################################\n");

if($options['fulldelete']) {
    // get plugin info before uninstallation
    require_once($CFG->libdir . '/adminlib.php');
    require_once($CFG->libdir . '/filelib.php');
    
    $pluginman = core_plugin_manager::instance();
    $pluginfo = $pluginman->get_plugin_info($options['plugin']);

    if (is_null($pluginfo)) {
        cli_error("Error : unknown plugin {$options['plugin']} into master host");
    }
    $pluginname = $pluginman->plugin_name($pluginfo->component);

    if (!is_null($pluginfo->versiondb)) {
        cli_error("Error : plugin {$options['plugin']} still installed on master host");
    }
    if (!$pluginman->is_plugin_folder_removable($pluginfo->component)) {
        cli_error("Error : plugin root dir {$pluginfo->rootdir} cannot be removed");
    }
    if (strpos($pluginfo->rootdir, $CFG->dirroot) !== 0) {
        cli_error("Error : plugin root dir {$pluginfo->rootdir}  is not within Moodle installation tree");
    }
    if(!$alldone) {
        mtrace("Plugin {$options['plugin']} had an error during uninstallation for 1 or more virtual hosts");
        mtrace("Are you sure to completely remove plugin root dir {$pluginfo->rootdir} ?");
        $prompt = get_string('cliyesnoprompt', 'admin');
        $input = cli_input($prompt, '', array(get_string('clianswerno', 'admin'), get_string('cliansweryes', 'admin')));
        if ($input == get_string('clianswerno', 'admin')) {
            exit(1);
        }
    }

    if(fulldelete($pluginfo->rootdir)) {
        mtrace("Plugin root dir {$pluginfo->rootdir} has been removed");
    }
    else {
        cli_error("Error : fulldelete on {$pluginfo->rootdir} has returned an error");
    }

    // Reset op code caches.
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
}
mtrace("\n######################################################\n");
echo "done.";
