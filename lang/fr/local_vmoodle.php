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

// Capabilities.

$string['vmoodle:execute'] = 'Exécuter des méta-commandes';
$string['vmoodle:managevmoodles'] = 'Gérer les satellites moodle';
$string['vmoodle:myaddinstance'] = 'Peut ajouter une instance aux pages My';
$string['vmoodle:addinstance'] = 'Peut ajouter une instance';

// Local Strings.

$string['addall'] = 'Tout ajouter';
$string['addformdbgroup'] = 'Base de données ';
$string['addformdescription'] = 'Description ';
$string['addformfeaturesgroup'] = 'Caractéristiques ';
$string['addforminputtexterror'] = 'Vous devez saisir une valeur ici ';
$string['addformname'] = 'Nom ';
$string['addformnfgroup'] = 'Réseau et fichiers ';
$string['addformshortname'] = 'Nom raccourci ';
$string['addtoselection'] = 'Ajouter à la selection';
$string['addvmoodle'] = 'Définir une nouvelle plate-forme virtuelle moodle';
$string['adjustconfig'] = 'Ajuster les fichiers de configuration';
$string['administrate'] = 'Administrer';
$string['administration'] = 'Administration';
$string['advancedmode'] = 'Mode avancé';
$string['allowmnetusersasadmin'] = 'Autoriser les utilisateurs réseau à être administrateur de site';
$string['assistedcommand'] = 'Commandes assistées';
$string['automateschema'] = 'Automatiser le schéma ';
$string['automateschema_desc'] = 'Si activé, les données par défaut suivantes seront proposées pour toute nouvelle création d\'instance.';
$string['available'] = 'Disponible(s)';
$string['backupdbcopyscript'] = 'Scripts pour backuper les bases de données';
$string['badblockinsert'] = 'Impossible d\'insérer dans la table \'local_vmoodle\'.';
$string['badblockupdate'] = 'Impossible de mettre à jour la table \'local_vmoodle\'.';
$string['badbootstraphost'] = 'Une erreur est survenue lors de l\'amorçage réseau de la plate-forme {$a} (clés ou autres).';
$string['badbootstrapnewhost'] = 'Une erreur est survenue lors de l\'amorçage réseau de la nouvelle plate-forme (clés ou autres).';
$string['badbothblockhost'] = 'Aucun block possède le même \'vhostname\' que le \'wwwroot\' de la plate-forme sélectionnée.';
$string['badclientuser'] = 'Utilisateur du client incorrect';
$string['badconnection'] = 'Erreur de connexion à la base de données.';
$string['baddatabasenamealreadyused'] = 'Ce nom de base de données est déjà utilisé.';
$string['baddatabasenamecoherence'] = 'Veuillez vérifier la cohérence du nom de la base de données.';
$string['baddatabaseprefixvalue'] = 'Ce préfixe de table n\'est pas autorisé.';
$string['baddumpcommandpath'] = 'Veuillez vérifier le ciblage des programmes nécessaires aux dumps dans le fichier \'vconfig.php\'.';
$string['badformmode'] = 'Mauvais mode de formulaire';
$string['badhostalreadydeleted'] = 'Impossible de supprimer une plate-forme déjà supprimée.';
$string['badhostnamealreadyused'] = 'Ce nom d\'hôte est déjà utilisé.';
$string['badmoodledatapath'] = 'Le chemin spécifié n\'est pas correct. Veuillez vérifier sa forme.';
$string['badmoodledatapathalreadyused'] = 'Le chemin spécifié est déjà utilisé par une autre plate-forme.';
$string['badmoodledatapathbackslash'] = 'Un seul backslash \'\\\' est nécessaire pour séparer les chaînes.';
$string['badmoodleid'] = 'Cette plate-forme n\'existe plus.';
$string['badnohyphensindbname'] = 'Pas de caractère tiret (moins) dans les noms de base de données !';
$string['badregexp'] = 'Format attendu : /regexp/modifiers';
$string['badservicesnumber'] = 'Il n\'existe plus aucun service sur cette plate-forme.';
$string['badshortname'] = 'Le nom raccourci ne doit pas comporter d\'espaces...';
$string['badtemplatation'] = 'Erreur dans le chargement de la base de données depuis le template choisi (lecture écriture ou suppression de fichiers).';
$string['badthishostdata'] = 'Impossible de récupérer les données de la plate-forme courante, depuis la table \'mnet_host\'.';
$string['badvhostname'] = 'Forme de l\'hôte incorrecte...';
$string['behaviour'] = 'Comportement';
$string['cancelled'] = 'Opération annulée';
$string['clustering'] = 'Réglages de clusterisation';
$string['configclusters'] = 'Clusters';
$string['configclusterix'] = 'Numéro de cluster';
$string['cancelcommand'] = 'Annuler la commande';
$string['capfilter'] = 'Filtre de capacités';
$string['categoryignored'] = 'La catégorie {$a} a été ignorée :';
$string['categorywrongname'] = 'Le nom de la catégorie est incorrect.';
$string['categorywrongpluginname'] = 'Le nom du plugin associé à la catégorie {$a} est incorrect.';
$string['certificate'] = 'Certificat';
$string['clidestroynode'] = 'Destruction du noeud {$a}';
$string['climakenode'] = 'Construction du noeud {$a}';
$string['climakestep'] = 'Phase de construction {$a}';
$string['climissingtemplateskip'] = 'Les fichiers du modèle {$a} n\'ont pas été trouvés';
$string['clinodeexistsskip'] = 'Le noeud {$a} existe déjà. Noeud ignoré.';
$string['clinodemissingskip'] = 'Le noeud {$a} n\'existe pas. Ligne ignorée.';
$string['clioperated'] = 'Opéré par la console';
$string['cliprocesserror'] = 'Une erreur irrécupérable est survenue. Abandon.';
$string['cliprocesserror'] = 'Une erreur irrécupérable est survenue. Aborting.';
$string['clisnapnode'] = 'Capture du noeud {$a}';
$string['clisnapstep'] = 'Phase de capture {$a}';
$string['clistart'] = 'Démarrage de la production';
$string['cliusingtemplate'] = 'Construction sur le modèle {$a}';
$string['closewindow'] = 'Fermer cette fenêtre';
$string['commanddescription'] = 'Description';
$string['commandemptydescription'] = 'La description de la commande est vide.';
$string['commandemptyname'] = 'Le nom de la commande est vide.';
$string['commandformnotlinked'] = 'Aucune commande liée au formulaire.';
$string['commandnotaparameter'] = 'Un des paramètres de la commande {$a} n\'est pas un object de type Vmoodle_Parameter.';
$string['commandnotexists'] = 'La commande n\'existe pas';
$string['commandnotrun'] = 'La commande n\'a pas été exécutée.';
$string['commands'] = 'Commandes';
$string['commandsadministration'] = 'Administration des commandes :';
$string['commandwrongparametertype'] = 'Les paramètres de la commande {$a} sont de types non supportées.';
$string['commentformat'] = 'Format de commentaire';
$string['confirmdelete'] = 'Voulez-vous vraiment supprimer (désactiver) cette plate-forme ?';
$string['confirmedit'] = 'Les changements effectués sont sous la responsabilité de l\'administrateur, en particulier en cas de modification du nom de la base de données, du préfixe des tables et du chemin "moodledata". Etes-vous sûr(e) de vouloir continuer ?';
$string['confirmfulldelete'] = 'Voulez-vous vraiment détruire (definitif) cette plate-forme ?';
$string['connectionok'] = 'Connexion OK';
$string['contains'] = 'contient';
$string['copyscripts'] = 'Scripts pour la copie des instances VMoodle';
$string['couldnotconnecttodb'] = 'Impossible de se connecter à la base de données';
$string['couldnotcreateclient'] = 'Impossible de créer un client MNET sur "{$a}".';
$string['couldnotcreatedataroot'] = 'Impossible de créer la racine des fichiers';
$string['couldnotcreatedb'] = 'Impossible de créer la base de données';
$string['couldnotdropdb'] = 'Impossible de supprimer le base de données';
$string['couldnotfixdatabase'] = 'ERREUR: Impossible d\'utiliser la base de données.';
$string['couldnotkeyboot'] = 'Impossible de booter la clef distante : {$a}';
$string['cron'] = 'Tâches cron';
$string['cronlines'] = 'Lignes de cron à ajouter';
$string['cronmode'] = 'Mode cron';
$string['crons'] = 'Crons';
$string['crontab'] = 'Table de tâches CRON ';
$string['csvencoding'] = 'Encodage CSV';
$string['csvencoding_desc'] = 'Choisissez l\'encodage pour le fichier nodelist.csv';
$string['databasecreated'] = 'Base de données créée';
$string['databaseloaded'] = 'Base de données initialisée ';
$string['databasesetup'] = 'Base de données configurée ';
$string['datacopyscript'] = 'Scripts pour la copie des fichiers utilisateurs';
$string['dataexchange_description'] = 'Les hôtes abonnés à ce service peuvent evoyer des demandes de données à d\'autres Moodle<br/>Les hôtes publiant ce service peuvent fournir des données à d\'autres Moodle<br/>';
$string['dataexchange_description'] = 'Ouvre des finctions permettant l\'échange de données texte de configuration simple entre systèmes ';
$string['dataexchange_name'] = 'Echange de données';
$string['dataexchange_name'] = 'Echange de données';
$string['datapath'] = 'Chemin des fichiers';
$string['datapathavailable'] = 'Le référentiel de fichiers est disponible. Le répertoire existe et est vide.';
$string['datapathbase'] = 'Base du chemin "moodledata" ';
$string['datapathcreated'] = 'Référentiel de fichiers créé : ';
$string['datatbasedroped'] = 'Base de donnée supprimée';
$string['datatpathunbound'] = 'Erreur dans la mise en place du chemin de fichiers';
$string['db'] = 'Base de données';
$string['dbbasename'] = 'Nom de la base';
$string['dbcommanddoesnotmatchanexecutablefile'] = 'La cible de la commande de base de données n\'est pas exécutable depuis le serveur : {$a}';
$string['dbcopyscript'] = 'Scripts pour la copie des bases de données';
$string['dbhost'] = 'Hôte de la base';
$string['dblogin'] = 'Login de base';
$string['dbname'] = 'Nom de la base';
$string['dbpass'] = 'Mot de passe';
$string['dbpersist'] = 'Connexion persistante';
$string['dbprefix'] = 'Préfixe des tables';
$string['dbschema'] = 'Base de données';
$string['dbtype'] = 'Type de base';
$string['delete'] = 'Supprimer';
$string['deleteconfirm'] = 'Cette opération n\\\'est pas réversible. Continuer ?';
$string['deletehost'] = 'Supprimer (désactiver) la configuration';
$string['deleteinstances'] = 'détruire les instances ';
$string['description'] = 'Description';
$string['details'] = 'Détails :';
$string['disableinstances'] = 'désactiver les instances ';
$string['donotopenservices'] = 'Ne pas ouvrir les services réseau MNET';
$string['dropbackup'] = 'Supprimer la sauvegarde';
$string['edithost'] = 'Modifier la configuration';
$string['editvmoodle'] = 'Modifier une définition de plate-forme virtuelle';
$string['elements'] = 'élément(s)';
$string['enableinstances'] = 'activer les instances ';
$string['errorbindingmnet'] = 'Erreur de raccordement réseau MNET';
$string['errorinvalidsessionorplatform'] = 'Les données de session VMoodle sont invalides ou aucune platforme n\'a été indiquée.';
$string['errorplatformnotavailable'] = 'La plate-forme {$a} n\'est pas disponible.';
$string['errorreactivetemplate'] = 'Le nom du chemin du moodle data et le nom de la base de données est introuvable pour réactiver la plateforme désirée.';
$string['errorsetupdb'] = 'Erreur de mise en place de la base de données';
$string['emulatecommunity'] = 'Emuler la version communautaire';
$string['emulatecommunity_desc'] = 'Bascule le code sur la version communautaire. Le résultat est plus compatible avec d\'autres installations, mais certaines fonctionnalités avancées ne seront plus disponibles.';
$string['failedplatforms'] = 'Echec des plates-formes :';
$string['fileschema'] = 'Position des fichiers';
$string['filter'] = 'Filtrer';
$string['fixcommand'] = 'Corriger la commande';
$string['forcedns'] = 'Forcer le déployement même si le nom de domaine n\'est pas résolu';
$string['forcehttpsproto'] = 'Forcer le Proto HTTPS (Cas)';
$string['fromversion'] = 'Motif de version d\'origine';
$string['fulldeletehost'] = 'Détruire totalement';
$string['generate'] = 'Générer';
$string['generateconfigs'] = 'Génerer les fichiers de configuration';
$string['generatecopyscripts'] = 'Génerer les scripts de copie';
$string['generatecustomscripts'] = 'Génerer des scripts';
$string['generatedconfigs'] = 'Fichiers générés: {$a}';
$string['generatedscript'] = 'Script généré';
$string['hostexists'] = 'Cet hôte existe';
$string['hostnameexists'] = 'Ce nom d\'hôte existe déjà';
$string['hostsource'] = 'Source des hôtes';
$string['insuffisantcapabilities'] = 'Capacités insuffisantes';
$string['key_autorenew_parms'] = 'Réactualisation automatique des clefs réseau';
$string['lastcron'] = 'Dernier Cron';
$string['lastcrongap'] = '&Delta;';
$string['licenseprovider'] = 'Fournisseur version Pro';
$string['licenseprovider_desc'] = 'Entrez la clef de votre distributeur.';
$string['licensekey'] = 'Clef de license pro';
$string['licensekey_desc'] = 'Entrez ici la clef de produit que vous avez reçu de votre distributeur.';
$string['maindb'] = 'Base de données principale';
$string['mainpath'] = 'fichiers utilisateur principaux';
$string['mainservicesformselection'] = 'Patron vis à vis du Moodle maître<br/>Ce patron est appliqué au nouveau noeud. Le patron "miroir" est appliqué au Moodle maître (ce moodle) ';
$string['makebackup'] = 'Faire des bases de sauvegarde';
$string['manualcommand'] = 'Commande manuelle';
$string['massdeployment'] = 'Déploiement massif';
$string['mastermnetnotice'] = 'Le réseau de l\'hôte maître n\'est pas activé. L\'installation de l\'instance virtuelle continue cependant mais sans l\'activation du réseau. Vous devrez activer les fonctions du réseau Moolde à posteriori';
$string['mnet'] = 'MNET';
$string['mnetkeyautorenew'] = 'Renouvellement des clefs';
$string['mnetkeyautorenewenable'] = 'Activation';
$string['mnetkeyautorenewgap'] = 'Délai d\'anticipation';
$string['mnetkeyautorenewgap_desc'] = 'Moodle envisagera un remplacement des clefs si l\'obsolescence de la clef est à venir dans ce délai';
$string['mnetkeyautorenewtime'] = 'Heure de renouvellement des clefs';
$string['mnetkeyautorenewtime_desc'] = '';
$string['mnetactivationrequired'] = 'Activation de MNET requise';
$string['mnetadmin_description'] = 'En publiant ce service, vous autorisez la plate-forme à être administrée par la méta-administration du site maître.<br/><br/>En vous abonnant à ce service, vous pouvez administrer les autres plates-formes du réseau à partir de cette plate-forme.<br/><br/>';
$string['mnetadmin_name'] = 'Service de méta-administration';
$string['mnetbound'] = 'Connecté par MNET';
$string['mnetdisabled'] = 'MNET désactivé';
$string['mnetenabled'] = 'MNET activé';
$string['mnetfree'] = 'Plate-forme autonome';
$string['mnetnew']    = 'Nouveau sous-réseau MNET';
$string['mnetopenservices'] = 'Services MNET ';
$string['mnetschema'] = 'Réseau MNET';
$string['multimnet'] = 'Sous-réseau MNET ';
$string['musthaveshortname'] = 'Le nom court est obligatoire';
$string['mysqlcmd'] = 'Emplacement de la commande mysql';
$string['mysqldumpcmd'] = 'Emplacement de la commande mysqldump';
$string['name'] = 'Nom';
$string['newvmoodle'] = 'Nouvelle plate-forme virtuelle';
$string['newplatformregistered'] = 'Un nouvelle instance de moodle a été enregistrée';
$string['nextstep'] = 'Continuer';
$string['nomnet'] = 'Pas de réseau Moodle';
$string['none'] = 'Aucune';
$string['noplatformchosen'] = 'Aucune plate-forme choisie.';
$string['notallowed'] = 'Non autorisé';
$string['notcontains'] = 'ne contient pas';
$string['notemplates'] = 'Aucun template (snapshot) existant pour définir une nouvelle plate-forme virtuelle moodle';
$string['novmoodle'] = 'La plateforme et/ou son bloc correspondant n\'existe plus.';
$string['novmoodles'] = 'Aucune plate-forme virtuelle définie';
$string['off'] = 'Désactivé';
$string['on'] = 'Activé';
$string['openallservices'] = 'Ouvrir tous les services';
$string['operation'] = 'Opération';
$string['organization'] = 'Organisation';
$string['organizationmail'] = 'Email';
$string['organizationunit'] = 'Unité';
$string['parameterallowedvaluesnotgiven'] = 'Les valeurs autorisées de l\'énumération {$a} ne sont pas données.';
$string['parameteremptydescription'] = 'La description du paramètre {$a} est vide.';
$string['parameteremptyname'] = 'Le nom du paramètre est vide.';
$string['parameterforbiddentype'] = 'Le type du paramètre {$a} est interdit.';
$string['parameterinternalconstantnotgiven'] = 'La constante "{$a->constant_name}" du paramètre "{$a->parameter_name}" n\'est pas connu.';
$string['parameterinternalfunctionfailed'] = 'La fonction "{$a->function_name}" a levé une exception {$a->message} à son exécution.';
$string['parameterinternalfunctionnotexists'] = 'La fonction "{$a->function_name}" du paramètre interne "{$a->parameter_name}" n\'existe pas.';
$string['parameterinternalparameternotgiven'] = 'Le paramètre "{$a->parameter_need}" du paramètre "{$a->parameter_name}" n\'est pas fourni.';
$string['parametervaluenotdefined'] = 'La valeur du paramètre "{$a}" n\'est pas définie.';
$string['parameterwrongdefaultvalue'] = 'La valeur par défaut du paramètre "{$a}" est incorrecte.';
$string['peerservicesformselection'] = 'Patron vis à vis des pairs du sous-réseau<br/>Ce patron est appliqué au nouveau noeud. Le patron "miroir" est appliqué à tous les pairs du même sous réseau)';
$string['pgsqlcmd'] = 'Emplacement de la commande Postgres';
$string['pgsqldumpcmd'] = 'Emplacement de la commande sqldump de Postgres';
$string['platformreactivate'] = 'Plateforme réactivée';
$string['platformname'] = 'Nom de plateforme';
$string['plugin'] = 'Plugin';
$string['plugindisabled'] = 'Le plugin a été désactivé.';
$string['pluginenabled'] = 'Le plugin a été activé.';
$string['pluginname'] = 'VMoodle';
$string['plugindist'] = 'Distribution du plugin';
$string['pluginnotdisabled'] = 'Le plugin n\'a pas été désactivé.';
$string['pluginnotenabled'] = 'Le plugin n\'a pas été activé.';
$string['pluginnotuninstalled'] = 'Le plugin n\'a pas été désintallé.';
$string['pluginsadministration'] = 'Administration des plugins :';
$string['pluginuninstalled'] = 'Le plugin {$a} a été correctement désinstallé.';
$string['postupgrade'] = 'Tâches post-mise à jour';
$string['preupgrade'] = 'Tâches pré-mise à jour';
$string['publish'] = 'Publication';
$string['rawstrategy'] = 'Stratégie (valeur brute)';
$string['rawstrategy_desc'] = 'Vous pouvez utiliser cette forme dans des fichiers de réglages par défauts ou directement en base de données.';
$string['reactiveorregistertemplate'] = 'Réactiver la plateforme ou enregistrer une nouvelle identité';
$string['regexp'] = 'exp reg';
$string['removeall'] = 'Tout retirer';
$string['removefromselection'] = 'Retirer de la sélection';
$string['renewallbindings'] = 'Renouveller toutes les paires';
$string['restart'] = 'Redémarrer la procédure';
$string['restorebackup'] = 'Restaurer des bases de sauvegarde';
$string['retrieveplatforms'] = 'Récupérer les plates-formes';
$string['rpcstatus100'] = 'Commande en mode test.';
$string['rpcstatus200'] = 'Commande exécutée avec succès.';
$string['rpcstatus404'] = 'Echec RPC. Url cible non trouvée. Erreur 404.';
$string['rpcstatus500'] = 'Echec RPC. Error 500.';
$string['rpcstatus501'] = 'Pas de compte local pour l\'utilisateur appellant.';
$string['rpcstatus502'] = 'Echec de configuration.';
$string['rpcstatus503'] = 'Erreur applicative distante.';
$string['rpcstatus510'] = 'Droits insuffisants.';
$string['rpcstatus511'] = 'Echec MNET.';
$string['rpcstatus520'] = 'Impossible de récupérer l\'enregistrement SQL.';
$string['rpcstatus521'] = 'Impossible d\'exécuter la commande SQL.';
$string['runcmdagain'] = 'Ré-exécuter la commande';
$string['runnewcommand'] = 'Exécuter une nouvelle commande';
$string['runothercommand'] = 'Exécuter une autre commande sur ces plates-formes';
$string['runotherplatforms'] = 'Ré-exécuter la commande sur d\'autres plates-formes';
$string['runvcron'] = 'Exécuter VCron manuellement';
$string['scriptgenerator'] = 'Générateur de scripts';
$string['selected'] = 'Sélectionnée(s)';
$string['services'] = 'Services';
$string['servicesformselection'] = 'Patron des services par défaut pour les nouvelles instances';
$string['servicesstrategy'] = 'Stratégie de services';
$string['servicesstrategydefault']    = 'Stratégie de services par défaut';
$string['servicesstrategysubnetwork'] = 'Stratégie de services du sous-réseau';
$string['siteschema'] = 'Hôte moodle virtuel';
$string['shortname'] = 'Nom canonique';
$string['shortnameexists'] = 'Le nom court existe';
$string['skip'] = 'Sauter';
$string['snapshothost'] = 'Capturer la configuration';
$string['snapshotmaster'] = 'Capturer la plate-forme principale';
$string['sqlcommand'] = 'Commande SQL';
$string['sqlfile'] = 'Fichier SQL';
$string['startingstate'] = 'Etat initial :';
$string['status'] = 'Etat';
$string['subscribe'] = 'Souscription';
$string['successaddnewhost'] = 'Ajout de la nouvelle plate-forme terminé.';
$string['successaddnewhostwithoutmnet'] = 'Ajout d\'une nouvelle plate-forme hors réseau terminé.';
$string['successdeletehost'] = 'Suppression (désactivation) de la plate-forme terminée.';
$string['successedithost'] = 'Edition de la plate-forme terminée.';
$string['successfinishedcapture'] = 'Capture de la plate-forme terminée.';
$string['successfullplatforms'] = 'Succès des plates-formes :';
$string['successstrategyservices'] = 'Déploiement de la stratégie de service(s) par défaut effectué.';
$string['sudoer'] = 'Sudoer';
$string['syncvmoodleregister'] = 'Synchroniser les registres VMoodle';
$string['tabpoolmanage'] = 'Gestion des instances';
$string['tabpoolsadmin'] = 'Super administration';
$string['tabpoolservices'] = 'Stratégie de services';
$string['template'] = 'Modèle de plateforme';
$string['templatehead'] = 'Modèle de script';
$string['templatetext'] = 'Texte du script';
$string['testconnection'] = 'Test de connexion à la base';
$string['testdatapath'] = 'Test du dossier Moodledata';
$string['tools'] = 'Outils';
$string['toversion'] = 'Version de remplacement';
$string['unablepopulatecommand'] = 'Impossible de compléter la commande.';
$string['uninstall'] = 'Désinstaller';
$string['unknownhost'] = 'L\'hôte que vous tentez de mettre en place n\'est pas connu sur le réseau. Cela va poser des problèmes pour la configuration réseau.';
$string['unknownhost'] = 'Le nom de domaine de la plate-forme ne peut être résolu. Vous pouvez forcer le passage en reexécutant la procédure, mais les fonctions réseau ne pourront probablement pas être activées correctement.';
$string['unknownhostforced'] = 'L\'hôte que vous tentez de mettre en place n\'est pas connu sur le réseau. Vous avez demandé un déploiment en mode forcé. Les fonctions du réseau Moodle seront désactivées après déploiement et devront être configurées manuellement.';
$string['unknownuserhost'] = 'Plate-forme hôte de l\'utilisateur inconnu';
$string['emptyormalformedvhostname'] = 'Url vide ou malformée';
$string['upgrade'] = 'Mettre à jour les données';
$string['uploadscript'] = 'Uploader un script';
$string['vdatapath'] = 'Chemin "moodledata" ';
$string['vdbbasename'] = 'Préfixe de base ';
$string['vdbhost'] = 'Hôte de base de données ';
$string['vdblogin'] = 'Identifiant ';
$string['vdbname'] = 'Nom de la base ';
$string['vdbpass'] = 'Mot de passe ';
$string['vdbpersist'] = 'Persistance des connexions ';
$string['vdbprefix'] = 'Préfixe des tables ';
$string['vdbs'] = 'Bases de données virtuelles';
$string['vdbtype'] = 'Type de la base de données ';
$string['vlogfilepattern'] = 'Fichier journal du VCron';
$string['vhostname'] = 'Nom d\'hôte';
$string['virtualplatforms'] = 'Plates-formes virtuelles';
$string['vmoodleadministration'] = 'Administration des plates-formes virtuelles ';
$string['vmoodleappname'] = 'Virtualisation Moodle';
$string['vmoodledoadd1'] = 'ETAPE 1 de 4 : La base de données de la nouvelle plate-forme est chargée. La prochaine étape va traiter la nouvelle base de données.';
$string['vmoodledoadd2'] = 'ETAPE 2 de 4 : La base de données a été convertie. La prochaine étape va charger les fichiers de données.';
$string['vmoodledoadd3'] = 'ETAPE 3 de 4 : Les fichiers de données sont disponibles. La prochaine étape enregistre l\'existance de la nouvelle plate-forme.';
$string['vmoodledoadd4'] = 'ETAPE 4 de 4 : La plate-forme est enregistrée. Son URL est activée. La dernière étape établit la politique du réseau Moodle.';
$string['vmoodlehost'] = 'Hôte virtuel';
$string['vmoodleip'] = 'Adresse IP';
$string['vmoodlemanager'] = 'Gestionnaire de plates-formes virtuelles';
$string['vmoodlesnapshot1'] = 'ETAPE 1 de 3 : Préparation du snapshot effectuée. La prochaine étape capture la base de données. Elle peut être plus ou moins longue suivant sa taille.';
$string['vmoodlesnapshot2'] = 'ETAPE 2 de 3 : Base de données capturée. La prochaîne étape capture les fichiers du Moodledata. Elle peut être très longue si de nombreux fichiers ont été ajoutés à la plate-forme.';
$string['vmoodlesnapshot3'] = 'ETAPE 3 de 3 : Fichiers capturés. La capture de la plate-forme est terminée.';
$string['vpaths'] = 'Fichiers utilisateurs virtuels';
$string['vtemplate'] = 'Template de Vmoodle';
$string['weboperated'] = 'Opéré en ligne';
$string['withmessage'] = 'avec le message "{$a}"';
$string['withoutmessage'] = 'sans message';
$string['withselection'] = 'Avec la sélection: ';
$string['wrongplugin'] = 'Plugin incorrect.';
$string['wwwrootexceedscsrlimits'] = 'Le nom d\'hôte choisi dépasse 64 caractères. Ceci n\'est pas compatible avec les règles de construction des certificats SSL (MNET).';

/*** Help Strings ***/
$string['name_help'] = '
<p><b>Nom courant de l\'instance</b></p>

<p>Ce nom est à la fois le nom de l\'instance, connu à partir de la
plate-forme maître, mais sera également copié comme nom courant du site
généré. Ce nom peut être modifié par la suite.</p>

';

$string['shortname_help'] = '
<p><b>Nom court de l\'instance</b></p>

<p>Le nom court de l\'instance est recopié comme nom court du nouveau
site Moodle créé. Ce nom court sert également à la constitution du nom
de base de données et des répertoires de fichiers physiques. Il ne peut
pas être modifié une fois la plate-forme créée.</p>
<p>Ce nom court NE DOIT PAS COMPORTER D\'ESPACES.</p>
';

$string['description_help'] = '
<p><b>Descriptif de l\'instance</b></p>

<p>Ce texte est recopié comme descriptif du nouveau site créé. Il
peut être modifié par la suite.</p>
';

$string['vhostname_help'] = '
<p><b>Hôte apparent de l\'instance</b></p>

<p>Ce champ permet de définir le nom Web apparent du nouveau site.
En général, ce nom doit comporter une métabalise &lt;%%INSTANCE%%&gt;
qui permet la différenciation de chaque nouvelle adresse Web pour
chacune des instances. Le nom court est utilisé (ramené en minuscules)
pour la constitution de ce sous-domaine.</p>
<p>Si la différentiation se fait sur un sous-domaine (exemple :
%%INSTANCE%%.mondomaine.org), la stratégie de fabrication des instances
se base sur un des sous domaines virtualisés du serveur Apache,
conduisant au même DocumentRoot.</p>
<p>Une configuration typique pour Apache dans ce cas de figure est :</p>
<pre>
&lt;VirtualHost 127.0.0.1&gt;
    ServerAdmin admin@foo.org
    ServerName default.mondomaine.org
    ServerAlias *.mondomaine.org
    VirtualDocumentRoot "chemin_vers_défaut/vmoodle_defaut"
    ErrorLog logs/vmoodle-error_log
    CustomLog logs/vmoodle-access_log common
&lt;/VirtualHost&gt;
</pre>
<p>D\'autres méthodes sont probablement possibles, mais n\'ont pas
fait l\'objet d\'évaluations.</p>
';

$string['vdbhost_help']='
<p><b>Base de donnée d\'instance</b></p>

<p>Ce champ permet de définir l\'hôte (hôte:port) où réside la base
de données sur laquelle doit fonctionner l\'instance. Cet hôte doit avoir
une base de données installée et opérationnelle, et doit être accessible
à partir du serveur où le module de virtualisation est opéré.</p>';

$string['vdbtype_help'] = '
<p><b>Type de la base de données d\'instance</b></p>

<p>Il est actuellement possible de déployer des instances sur les
bases MySQL et PostgreSQL. Le déployement peut également être hétérogène
à partir du même serveur maître. Les autres bases support de Moodle ne
sont pour l\'instant pas prises en charge.</p>
';

$string['vdbname_help'] = '
<p><b>Nom de la base de données d\'instance</b></p>

<p>Permet de définir le nom de la base de données accueillant le
modèle de données de la nouvelle instance. Ce nom NE PEUT être changé
par la suite. Il est en principe construit automatiquement à partir du
nom court du nouveau site.</p>
';

$string['vdbpersist_help'] = '
<p><b>Persistance des connexions à la base</b></p>

<p>Ce paramètre permet de définir si l\'instance doit utiliser des
connexions persistantes. Ce paramètre peut être modifié par la suite.</p>
';

$string['vdatapath_help'] = '
<p><b>Système de fichiers d\'instance</b></p>

<p>Tous les fichiers utilisateurs sont enregistrés dans un volume de
fichiers unique appelé "moodledata". Ce champ permet de définir
l\'emplacement de ce volume de fichiers. Il est en principe autogénéré
par rapport à une racine par défaut configurée dans les paramètres
généraux de configuration du composant local "vmoodle", et à partir du nom court
de l\'instance.</p>
<p>Le répertoire créé est pré-alimenté par les fichiers prédéfinis
dans le "template de chargement".
<p>Attention : veillez à ce que la racine par défaut pour tous ces
répertoires soit insciptible par votre serveur.</p>
<p>Ce chemin NE PEUT PLUS ETRE MODIFIE APRES CREATION DE L\'INSTANCE.</p>
';

$string['mnet_help'] = '
<p><b>Activation du réseau Moodle</b></p>

<p>Il est possible de choisir diverses stratégies d\'intégration réseau de la nouvelle plate-forme créée.</p>
<p>Dans tous les cas, les stratégies réseau des plates-formes pourront toujours être modifiées par l\'administrateur local de chaque Moodle virtuel</p>

<h4>Premier cas : Pas de réseau Moodle</h4>

<p>La plate-forme créée est désactivée au niveau réseau. Elle ne peut communiquer avec aucune autre plate-forme, ni même avec la plate-forme
maître de réseau. Elle ne pourra donc pas être administrée par celle-ci.</p>

<h4>Deuxième cas : Plate-forme autonome</h4>

<p>La plate-forme créée est initialisée réseau avec la plate-forme maître pour que celle-ci puisse la téléadministrer (SSO et méta-adminsitration). Elle prend le sous-réseau d\'ID 0, qui ne sera pas publié comme sous-réseau disponible. Toutes les plates-formes virtuelles du sous-réseau 0 ne communiquent pas entre elles après leur matérialisation.</p>

<h4>Troisème cas : Plate-forme dans un réseau existant</h4>

<p>En choisissant l\'un des sous-réseaux existants, la nouvelle plate-forme sera initialisée réseau et sera attachée à toutes les Moodle virtuels référencés dans ce sous-réseau. La plate-forme sera initialisée avec le gabarit de services mémorisé dans l\'interface de la ferme de Moodle.

<h4>Quatrième cas : Plate-forme initiant un nouveau sous-réseau</h4>

<p>En choisissant cette option, la plate-forme créée initie un nouveau sous-réseau qui sera publié comme choix possible pour les prochaines
matérialisations. Elle est initiée réseau avec le maître et active les services de SSO et de meta-administration avec celle-ci</p>
';

$string['services_help'] = '
<p><b>Stratégie de services</b></p>

<p>Ce réglage permet de définir la stratégie de services à appliquer
lors de la définition d\'une nouvelle plate-forme.</p>

<p>La stratégie par défaut ne met en place que les liaisons SSO entre
la nouvelle plate-forme et tous les autres hôtes du sous-réseau.</p>

<p>La stratégie de sous-réseau utilise le masque défini par l\'administrateur
dans l\'onglet "Stratégie de services". Elle permet un réglage grossier des
services entre pairs, mais devra probablement être affinée par l\'administrateur
pour répondre aux besoins de l\'architecture.</p>
';

$string['vtemplate_help'] = '
<p><b>Template de chargement</b></p>

<p>Lorsqu\'un moodle est instancié virtuellement, la partie
contextuelle de la plate-forme peut être initialisée avec un modèle de
données prédéfini et un jeu de fichiers d\'exploitation déjà constitué.</p>
<p>Ce chargement permet de mettre en service une plate-forme Moodle
dans un état déjà configuré</p>
<p>Le paramètre "Template de chargement" permet de choisir une des
configurations disponibles.</p>
<p>Les configurations préréglées des instances doivent être formées
de deux répertoires :</p>
<ul>
    <li>Le premier contient deux scripts SQL appelés :
    "moodle_master.&lt;base&gt;.sql" et
    "moodle_setup_template.&lt;base&gt;.sql". &lt;base&gt; peut être soit
    "mysqli" ou "postgres", selon la plate-forme qui a servi de modèle au
    template. Le premier script permet de charger la base avec un modèle de
    donnée type, issu de l\'export d\'une plate-forme modèle. Le deuxième
    fichier permet d\'exécuter des requêtes SQL contextualisées utilisant
    quelques paramètres spécifiques récoltés dans le formulaire de création
    de l\'instance.</li>
    <li>Le deuxième contient une copie d\'un répertoire moodledata
    préconstitué. Ce répertoire peut contenir n\'importe quel jeu de
    fichiers issus de l\'utilisation de la plate forme qui a servi de modèle
    au template.</li>
</ul>
<p>Afin d\'effectuer une correction de tous les chemins absolus qui
pourraient être inscrits par la plate-forme modèle, le template demande
la présence d\'un fichier "manifest" supplémentaire, indiquant les
racines Web et Système de fichiers de la plate-forme modèle. Cette
indication permet à l\'algorithme de déploiement de corriger les
éventuels chemins absolus de la nouvelle instance.</p>
';

$string['templatetext_help'] = 'Ecrivez ici le texte du script avec des marque places :

<li>%WWWROOT% : url du site virtuel</li>
<li>%DBHOST% : Hôte de la base de données</li>
<li>%DBUSER% : Utilisateur de la base de données</li>
<li>%DBPASS% : Mot de passe de la base de données</li>
<li>%DATAROOT% : Position du moodledata</li>
';

$string['configclusters_desc'] = 'Indiquez ici le nombre de clusters qui servent vos pages Moodle.';

$string['configclusterix_desc'] = 'Ceci est le numéro du cluster courant pour le traitement de vos tâches de fond distribuées. Sa valeur
doit être forcée par un fichier de configuration physique (pas de valeur en base de données ni de cache partagé), à travers une initialisation
$CFG->forced_plugin_settings[\'vmoodle\'][\'clusterix\'] assurée localement. Veuillez à ce que chaque cluster impose une valeur différente.';

$string['sudoer_desc'] = 'Un utilisateur linux capable d\'opérer dans le répertoire d\'installation de moodle. Cet utilisateur doit bénéficier
d\'une règle de sudo du type : www-data  ALL = (user) NOPASSWD:/usr/bin/ln
';

$string['vlogfilepattern_desc'] = 'Un motif exprimant un chemin système où enregistrer les journaux d\'exécution des VCrons. Le motif
accepte un emplacement %VHOSTNAME% et supprime les préfixes de protocoles de la valeur finale.
';

$string['plugindist_desc'] = '
<p>Ce plugin est distribué dans la communauté Moodle pour l\'évaluation de ses fonctions centrales
correspondant à une utilisation courante du plugin. Une version "professionnelle" de ce plugin existe et est distribuée
sous certaines conditions, afin de soutenir l\'effort de développement, amélioration; documentation et suivi des versions.</p>
<p>Contactez un distributeur pour obtenir la version "Pro" et son support.</p>
<p>Notez que les deux composant local_sharedresources et mod_sharedresource doivent fonctionner au même niveau de distribution</p>
<p><a href="http://www.mylearningfactory.com/index.php/documentation/Distributeurs?lang=fr_utf8">Distributeurs MyLF</a></p>';
