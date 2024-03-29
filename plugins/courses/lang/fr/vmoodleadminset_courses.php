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
 * French traduction for assisted commands for Pairform@ance.
 * 
 * @package local_vmoodle
 * @category local
 * @author Bruce Bujon (bruce.bujon@gmail.com)
 */

// Privacy.
$string['privacy:metadata'] = 'Le composant VMoodle Courses ne détient directement aucune donnée relative aux utilisateurs.';

$string['noenrol'] = 'Pas d\'inscription';
$string['managersonly'] = 'Gestionnaires uniquement';
$string['siteadmins'] = 'Administrateurs de site uniquement';
$string['bothadminsandmanagers'] = 'Gestionnaires et administrateurs de site';
$string['cmdcreatecategory'] = 'Créer une catégorie de cours';
$string['cmdcreatecategory_desc'] = 'Crée une catégorie de cours par son chemin (noms complets de catégorie)';
$string['cmdcheckcourse'] = 'Rechercher des cours';
$string['cmdcheckcourse_desc'] = 'Rechercher l\'existance de cours par motif dans le nom court, le numéro d\'identification ou le nom long. vous pouvez utiliser les jokers ? (un caractère) et % (une chaine quelconque).';
$string['cmdrestorecourse'] = 'Restaurer un cours';
$string['cmdrestorecourse_desc'] = 'Restore un cours à partir d\'une archive sur le serveur';
$string['cmddeletecourse'] = 'Supprimer un cours';
$string['cmddeletecourse_desc'] = 'Supprime un cours sur la base de son nom court ou de son numéro d\'identification';
$string['cmddeletecoursecategory'] = 'Supprimer une catégorie de cours';
$string['cmddeletecoursecategory_desc'] = 'Supprime complètement une catégorie de cours (par numero d\'identification)';
$string['cmdemptycoursecategory'] = 'Vider une catégorie de cours';
$string['cmdemtycoursecategory_desc'] = 'Supprime complètement tout le contenu d\'une catégorie de cours (par numero d\'identification)';
$string['mnetadmin_description'] = 'En publiant ce service, vous autorisez la plate-forme à être administrée par la méta-administration du site maître.<br/><br/>En vous abonnant à ce service, vous pouvez administrer les autres plates-formes du réseau à partir de cette plate-forme.<br/><br/>';
$string['mnetadmin_name'] = 'Service de méta-administration';
$string['pluginname'] = 'Fonctions relatives aux cours';
$string['enroladmins'] = 'Inscrire les administrateurs comme enseignants.';
$string['errornolocation'] = 'Il n\'y a pas de fichier à l\'emplacement donné ou ce fichier n\'est pas lisible.';
$string['errornotamoodlearchive'] = 'L\'emplacement indiqué ne contient pas une archive Moodle.';
$string['errornocategory'] = 'La catégorie cible n\'existe pas (par numéro d\'identification).';
$string['errorcoursealreadyexists'] = 'Un cours existe déjà avec ce nom court. vous devez le supprimer d\'abord.';
$string['errorcourseidnumberexists'] = 'Un cours existe avec ce numéro d\'identification. Vous devez supprmier cette référence avant de pouvoir déployer avec le numéro d\'identiifcation fourni.';
$string['errornopermission'] = 'Vous n\'avez pas les droits avec votre utilisateur distant pour restaurer.';
$string['errorduringrestore'] = 'Une erreur est survenue lors de la restauration. . Exception : {$a}';
$string['errorafterrestore'] = 'La restauration a abouti, mais aucun cours n\'a été créé.';
$string['errornocourse'] = 'Le cours à supprimer n\'existe pas';
$string['path'] = 'Chemin complet de la catégorie (ex: "Année 2020/Histoire/Histoire contemporaine")';
$string['catidnumber'] = 'Numéro d\'identification de la nouvelle catégorie';
$string['restorecatidnumber'] = 'Numéro d\'identification de la catégorie cible';
$string['catvisible'] = 'Categorie visible initialement';
$string['coursevisible'] = 'Cours initiallement visible';
$string['setcategoryvisibility'] = 'Changer la visibilité de la catégorie de cours';
$string['setcategoryvisibility_desc'] = 'A partir d\'un numéro d\'identification valide, active ou enlève la visibilité d\'une catégorie de cours distante.';
$string['setcoursevisibility'] = 'Change la visibilité du cours';
$string['setcoursevisibility_desc'] = 'A partir d\'un numéro d\'identification valide, active ou enlève la visibilité d\'un cours distant.';
$string['location'] = 'Chemin système de l\'archive de cours Moodle sur le distant.';
$string['rundelay'] = 'Delai (minutes) pour la restauration distante';
$string['spread'] = 'Période (minutes) de distribution aléatoire pour la restauration (utile pour traiter la commande sur de nombreuses cibles).';
$string['byshortname'] = 'Chercher par nom court (partiel)';
$string['byidnumber'] = 'Chercher par Numéro d\'identification (partiel)';
$string['infullname'] = 'Chercher dans le nom complet (partiel)';
$string['fullnamelike'] = 'Par nom complet (jokers %)';
$string['nocourses'] = 'Aucun cours trouvé';
$string['seed'] = 'Indicatif d\'operation';

