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
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 */

// Privacy.
$string['privacy:metadata'] = 'Le composant VMoodle Roles ne détient directement aucune donnée relative aux utilisateurs.';

$string['backtocomparison'] = 'Retour à la comparaison du rôle';
$string['capabilityparamsyncdesc'] =  'La capacité à synchroniser';
$string['cmdcomparedesc'] = 'Compare les capacités d\'un rôle.';
$string['cmdcomparename'] = 'Comparaison d\'un rôle';
$string['cmdsynccapabilitydesc'] = 'Synchronise une capacité d\'un rôle.';
$string['cmdsynccapabilityname'] = 'Synchronisation d\'une capacité d\'un rôle';
$string['cmdsyncdesc'] = 'Synchronise les capacités d\'un rôle.';
$string['cmdsyncname'] = 'Synchronisation d\'un rôle';
$string['cmdallowsyncdesc'] = 'Synchroniser une table d\'autorisation d\'assignation, surcharge ou changement de role.';
$string['cmdallowsyncname'] = 'Synchronization des autorisations d\'assignation, surcharge ou changement de role';
$string['cmdallowcomparedesc'] = 'Comparer la table de permission d\'assignation, surcharge ou changement de role.';
$string['cmdallowcomparename'] = 'Comparaison des autorisations d\'assignation, surcharge ou changement de role.';
$string['comparerole'] = 'Comparaison du role "{$a}"';
$string['confirmrolecapabilitysync'] = 'Vous êtes sur le point de modifier une capacité de rôle sur plusieurs plate-formes. Voulez-vous continuer ?';
$string['editrole'] = 'Editer le rôle';
$string['editallowtable'] = 'Editer une table de permissions de role';
$string['mnetadmin_name'] = 'Meta Administration Réseau Moodle';
$string['mnetadmin_description'] = 'Fournit des fonctions pour exécuter des commandes d\'administration à travers le réseau MNET, telles que la synchronisation de configuration ou de roles.';
$string['nocapability'] = 'Pas de capacité sélectionnée.';
$string['nosrcpltfrm'] = 'Pas de plate-forme source.';
$string['nosyncpltfrm'] = 'Pas de plate-formes à synchroniser.';
$string['platformparamsyncdesc'] = 'Plate-forme source du rôle à copier';
$string['problematiccomponent'] = 'Capacités inconnues';
$string['roleparamcomparedesc'] = 'Le rôle à comparer';
$string['roleparamsyncdesc'] = 'Le rôle à synchroniser';
$string['pluginname'] = 'Commandes relatives aux rôles';
$string['synchronize'] = 'Synchroniser';
$string['syncwithitself'] = 'Synchronisation du rôle "{$a->role}" de la plate-forme "{$a->platform}" avec elle-même.';
$string['assigntable'] = 'Autorisations d\'assignation de rôles';
$string['overridetable'] = 'Autorisations de surcharge de rôles';
$string['switchtable'] = 'Autorisations de changement de rôle';
$string['tableparamdesc'] = 'Table d\'autorisations';

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
    <tr>
      <td><img src="/local/vmoodle/plugins/roles/pix/permissionallow.png" alt="Permettre"/></td>
      <td>Signifie que la capacité est permise.</td>
    </tr>
    <tr>
      <td><img src="/local/vmoodle/plugins/roles/pix/permissionprevent.png" alt="Empécher"/></td>
      <td>Signifie que la capacité est empéchée.</td>
    </tr>
    <tr>
      <td><img src="/local/vmoodle/plugins/roles/pix/permissionforbid.png" alt="Interdire"/></td>
      <td>Signifie que la capacité est interdite.</td>
    </tr>
    <tr>
      <td colspan="2" style="font-size 1.2 em; font-style: italic;">Les contextes :</td>
    </tr>
    <tr>
      <td><img src="/local/vmoodle/plugins/roles/pix/contextB.png" alt="Contexte B"/></td>
      <td>Signifie que la capacité est de contexte "block".</td>
    </tr>
    <tr>
      <td><img src="/local/vmoodle/plugins/rolels/pix/contextC.png" alt="Contexte C"/></td>
      <td>Signifie que la capacité est de contexte "course".</td>
    </tr>
    <tr>
      <td><img src="/local/vmoodle/plugins/roles/pix/contextCC.png" alt="Contexte CC"/></td>
      <td>Signifie que la capacité est de contexte "course category".</td>
    </tr>
    <tr>
      <td><img src="/local/vmoodle/plugins/roles/pix/contextG.png" alt="Contexte G"/></td>
      <td>Signifie que la capacité est de contexte "group".</td>
    </tr>
    <tr>
      <td><img src="/local/vmoodle/plugins/roles/pix/contextM.png" alt="Contexte M"/></td>
      <td>Signifie que la capacité est de contexte "module".</td>
    </tr>
    <tr>
      <td><img src="/local/vmoodle/plugins/roles/pix/contextS.png" alt="Contexte S"/></td>
      <td>Signifie que la capacité est de contexte "system".</td>
    </tr>
    <tr>
      <td><img src="/local/vmoodle/plugins/roles/pix/contextU.png" alt="Contexte U"/></td>
      <td>Signifie que la capacité est de contexte "user".</td>
    </tr>
    <tr>
      <td colspan="2" style="font-size 1.2 em; font-style: italic;">Les absences de capacités :</td>
    </tr>
    <tr>
      <td><img src="/local/vmoodle/plugins/roles/pix/norolecapability.png" alt="Pas de capacité de rôle"/></td>
      <td>Signifie que la capacité n\'est pas définie pour ce rôle.</td>
    </tr>
    <tr>
      <td><img src="/local/vmoodle/plugins/roles/pix/nocapability.png" alt="Pas de capacité"/></td>
      <td>Signifie que la capacité n\'est pas définie sur cette plate-forme.</td>
    </tr>
    <tr>
      <td colspan="2" style="font-size 1.2 em; font-style: italic;">Les marqueurs de différences :</td>
    </tr>
    <tr>
      <td style="background-color: #F2FF98;"><img src="/local/vmoodle/plugins/roles/pix/blank.png" alt=" "/></td>
      <td>Signifie que la capacité possède un contexte différent de celui des autres plate-formes.</td>
    </tr>
    <tr>
      <td style="background-color: #FF607D;"><img src="/local/vmoodle/plugins/roles/pix/blank.png" alt=" "/></td>
      <td>Signifie que la capacité possède une permission différente de celles des autres plate-formes.</td>
    </tr>
    <tr>
  </tbody>
</table>
';