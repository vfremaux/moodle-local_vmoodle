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

define('CLI_SCRIPT', true);

require('../../../../../config.php');
require_once('../pluginscontrolslib.php');

$casauthcontrol = new auth_remote_control('cas');
$mnetauthcontrol = new auth_remote_control('mnet');

echo "Test 1 : enable disable mnet \n";
$mnetauthcontrol->action('enable');
echo $CFG->auth."\n";
$mnetauthcontrol->action('disable');
echo $CFG->auth."\n";

echo "Test 2 : enable disable cas \n";
$casauthcontrol->action('enable');
echo $CFG->auth."\n";
$casauthcontrol->action('disable');
echo $CFG->auth."\n";

echo "Test 3 : enable disable cas then mnet \n";
$casauthcontrol->action('enable');
echo $CFG->auth."\n";
$mnetauthcontrol->action('enable');
echo $CFG->auth."\n";
$mnetauthcontrol->action('disable');
echo $CFG->auth."\n";
$casauthcontrol->action('disable');
echo $CFG->auth."\n";

echo "Test 4 : enable disable cas then mnet interleaved\n";

$casauthcontrol->action('enable');
echo $CFG->auth."\n";
$mnetauthcontrol->action('enable');
echo $CFG->auth."\n";
$casauthcontrol->action('disable');
echo $CFG->auth."\n";
$mnetauthcontrol->action('disable');
echo $CFG->auth."\n";
