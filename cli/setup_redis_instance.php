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
 * @copyright   2016 Valery Fremaux
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
global $CLI_VMOODLE_PRECHECK;

$CLI_VMOODLE_PRECHECK = true; // Force first config to be minimal.
require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
require_once($CFG->dirroot.'/lib/clilib.php'); // CLI only functions.

// Ensure errors are well explained.
$CFG->debug = E_ALL;

// Now get cli options.
list($options, $unrecognized) = cli_get_params(array('verbose' => false,
                                                     'help'    => false,
                                                     'redisname'    => false,
                                                     'redishost'    => false,
                                                     'redisdbid'    => false,
                                                     'redisprefix'  => false,
                                                     'redispwd'    => false,
                                                     'activate'    => false,
                                                     'debug'    => false,
                                                     'host'    => false),
                                               array('h' => 'help',
                                                     'N' => 'redisname',
                                                     'R' => 'redishost',
                                                     'i' => 'redisdbid',
                                                     'p' => 'redispwd',
                                                     'P' => 'redisprefix',
                                                     'a' => 'activate',
                                                     'v' => 'verbose',
                                                     'H' => 'host'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error($unrecognized.' are not recognized options ');
}

if ($options['help']) {
    $help = "
Setup a redis cache instance if not exists and activates it.

    Options:
    --verbose           Provides lot of output
    -N, --redisname     Instance name
    -R, --redishost     Instance host
    -i, --redisdbid     Instance db id
    -i, --redisprefix   Key prefix. If not given, will be randomized as [0-1a-z]{8}_
    -s, --serialiser    Serializer (integer) defaults to 1.
    -p, --redispwd      Redis server password
    -a, --activate      If present, will activate definition mappings to the instance.
    -h, --help          Print out this help
    -d, --debug         Set debug mode on
    -H, --host          Set the host (physical or virtual) to operate on
"; // TODO: localize - to be translated later when everything is finished.

    echo $help;
    die;
}

if (!empty($options['host'])) {
    // Arms the vmoodle switching.
    echo('Arming for '.$options['host']); // Mtrace not yet available.
    define('CLI_VMOODLE_OVERRIDE', $options['host']);
}

// Replay full config whenever. If vmoodle switch is armed, will switch now config.

require(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php'); // Global moodle config file.
echo('Config check : playing for '.$CFG->wwwroot);

require_once($CFG->dirroot.'/cache/locallib.php');


$cacheconfig = cache_config::instance();
$cacheconfig->load();
$stores = $cacheconfig->get_all_stores();

if (array_key_exists($options['redisname'], $stores)) {
    die("Store already configured\n");
}

$modemappings = $cacheconfig->get_mode_mappings();

$configuration = [];
$configuration['server'] = $options['redishost'];
if (!empty($options['redisprefix'])) {
    $configuration['prefix'] = $options['redisprefix'];
} else {
    $configuration['prefix'] = randomize_prefix();
}
$configuration['dbid'] = $options['redisdbid'];
$configuration['password'] = $options['redispwd'];
$configuration['serializer'] = $options['redisserializer'];

$writer = cache_config_writer::instance();
$writer->add_store_instance($options['redisname'], 'redis', $configuration);

if (!empty($options['activate'])) {
    $mappings = array(
        cache_store::MODE_APPLICATION => array($options['redisname']),
        cache_store::MODE_SESSION => array($options['redisname']),
        cache_store::MODE_REQUEST => array($modemappings[cache_store::MODE_REQUEST]),
    );
}
$writer->set_mode_mappings($mappings);

function randomize_prefix() {
    $randomset = '1234567890abcdefghijklmnopqrstuvwyz';

    for ($i = 0; $i < 8; $i++) {
        $rpos = rand(0, strlen($randomset) - 1);
        $key .= $randomset[$rpos];
    }

    return $key.'_';
}