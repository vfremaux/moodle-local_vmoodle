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
 * @author Valery Fremaux (valery.fremaux@gmail.com)
 */

// Privacy.
$string['privacy:metadata'] = 'The local plugin vmoodeladminset Plugins does not directly store any personal data about any user.';

$string['available'] = 'Available';
$string['backtocomparison'] = 'Back to plugins comparison';
$string['cmdcomparedesc'] = 'Compare plugins equipement.';
$string['cmdcomparename'] = 'Plugins comparison';
$string['cmdsyncdesc'] = 'Synchronize a plugin equipement.';
$string['cmdsyncname'] = 'Synchronization of the plugin equipement';
$string['cmdpluginsetupdesc'] = 'Enables disables remote plugins.';
$string['cmdpluginsetupname'] = 'Plugin control';
$string['cmd3statepluginsetupname'] = '3 State plugin control';
$string['cmd3statepluginsetupdesc'] = 'Enables, disables or makes pluging available';
$string['compareplugins'] = 'Comparing plugins "{$a}"';
$string['errorblockdoesnotexit'] = 'The block {$a} is not found';
$string['manageplugins'] = 'Manage plugins "{$a}"';
$string['mnetadmin_name'] = 'MNET Meta Administration';
$string['mnetadmin_description'] = 'Provides functions to perform network scoped administration operations, such as role or configuration settigns synchronisation.';
$string['nosrcpltfrm'] = 'No source platform.';
$string['nosyncpltfrm'] = 'Any platforms to synchronize.';
$string['notinstalled'] = 'Not installed.';
$string['platformparamsyncdesc'] = 'Source platform of the role to copy';
$string['pluginname'] = 'Plugins management related commands';
$string['pluginparamdesc'] = 'Plugin';
$string['pluginstateparamdesc'] = 'Activation state';
$string['plugintypeparamcomparedesc'] = 'The plugin type to compare';
$string['plugintypeparamsyncdesc'] = 'The plugintype to synchronize';
$string['pluginname'] = 'Plugin management dedicated commands';
$string['synchronize'] = 'Synchronize';
$string['tableparamdesc'] = 'Authorisation table';
$string['syncwithitself'] = 'Synchronizing "{$a->platform}" platform with itself.';
$string['enable'] = 'Enable';
$string['disable'] = 'Disable';
$string['confirmpluginvisibilitysync'] = 'Confirm';

$string['plugincompare_help'] = '
<h2>Plugin comparison</h2>
<table style="width: 80%;">
  <caption>Legend of comparison table:</caption>
  <thead>
    <tr>
      <th style="width: 16px;">Icon</th>
      <th>Legend</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td colspan="2" style="font-size 1.2 em; font-style: italic;">Plugin status:</td>
    </tr>
  </tbody>
</table>
';