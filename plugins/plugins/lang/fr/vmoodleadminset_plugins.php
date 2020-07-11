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
$string['privacy:metadata'] = 'Le composant VMoodle Plugins ne détient directement aucune donnée relative aux utilisateurs.';

$string['backtocomparison'] = 'Retour à la comparaison des plugins';
$string['cmdcomparedesc'] = 'Compare l\'équipement en plugins.';
$string['cmdcomparename'] = 'Comparaison de plugins';
$string['cmdsyncdesc'] = 'Synchronise l\'équipement en plugins.';
$string['cmdsyncname'] = 'Synchronisation des plugins';
$string['compareplugins'] = 'Comparaison des plugins "{$a}"';
$string['cmdpluginsetupdesc'] = 'Active ou désactive les plugins à distance.';
$string['cmdpluginsetupname'] = 'Activation de plugin';
$string['errorblockdoesnotexit'] = 'Le bloc {$a} n\'est pas trouvé.';
$string['mnetadmin_name'] = 'Meta Administration Réseau Moodle';
$string['mnetadmin_description'] = 'Fournit des fonctions pour exécuter des commandes d\'administration à travers le réseau MNET, telles que la synchronisation de configuration ou de roles.';
$string['nosrcpltfrm'] = 'Pas de plate-forme source.';
$string['nosyncpltfrm'] = 'Pas de plate-formes à synchroniser.';
$string['notinstalled'] = 'Non installé.';
$string['platformparamsyncdesc'] = 'Plate-forme source du rôle à copier';
$string['plugintypeparamcomparedesc'] = 'Le type de plugins à comparer';
$string['plugintypeparamsyncdesc'] = 'Le type de plugins à synchroniser';
$string['pluginname'] = 'Commandes relatives aux plugins';
$string['pluginparamdesc'] = 'Plugin';
$string['pluginstateparamdesc'] = 'Commande';
$string['synchronize'] = 'Synchroniser';
$string['syncwithitself'] = 'Synchronisation de la plate-forme "{$a->platform}" avec elle-même.';
$string['tableparamdesc'] = 'Table d\'autorisations';
$string['enable'] = 'Activer';
$string['disable'] = 'Désactiver';

$string['rolecompare_help'] = '
<h2>Comparaison d\'un rôle</h2>
<table style="width: 80%;">
  <caption>Légende du tableau de comparaison :</caption>
  <thead>
    <tr>
      <th style="width: 16px;">Icône</th>
      <th>Légende</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td colspan="2" style="font-size 1.2 em; font-style: italic;">Les permissions :</td>
    </tr>
  </tbody>
</table>
';