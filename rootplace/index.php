<?php

/* Copyright 2007
 * - Julien Etelain < julien at pmad dot net >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation; either version 2 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir="../";

require_once($topdir. "include/site.inc.php");

$site = new site ();

if ( !$site->user->is_in_group("root") )
  $site->error_forbidden("none","group",7);

$site->start_page("none","Administration");

$cts = new contents("Administration");

$cts->add_paragraph("Révision en production : ".get_rev());

$cts->add_title(2,"Dev");
$lst = new itemlist();
$lst->add("<a href=\"".$topdir."ae/infotodo.php\">Tâches équipe info (todo)</a>");
$cts->add($lst);

$cts->add_title(2,"Administration");
$lst = new itemlist();
$lst->add("<a href=\"".$topdir."group.php\">Gestion des groupes</a>");
$lst->add("<a href=\"logs.php\">Affichage des logs</a>");
$lst->add("<a href=\"warning.php\">Message d'alerte</a>");
$lst->add("<a href=\"updatebyxml.php\">Mise à jour xml</a>");
$lst->add("<a href=\"saslicences.php\">Gestion des licences pour le sas</a>");
$lst->add("<a href=\"forum.php\">Gestion du forum</a>");
$cts->add($lst);

$cts->add_title(2,"AECMS");
$lst = new itemlist();
$lst->add("<a href=\"aecms.php\">Liste des AECMS</a>");
$lst->add("<a href=\"aecms.php?page=raz\">RAZ d'un AECMS</a> (remet les paramètres aux valeurs par défaut)");
$lst->add("<a href=\"aecms.php?page=install\">Installation d'un AECMS</a> (ou re-installation)");
$cts->add($lst);

$cts->add_title(2,"SVN");
$lst = new itemlist();
$lst->add("<a href=\"svn.php\">Gestion des svn</a>");
$cts->add($lst);

$cts->add_title(2,"Maintenance");
$lst = new itemlist();
$lst->add("<a href=\"droits.php\">Expiration des droits</a>");
$lst->add("<a href=\"pollcoti.php\">Expiration des cotisations</a>");
$lst->add("<a href=\"".$topdir."ae/fermeture_comptes.php\">Clôture des comptes des non cotisants de plus de 2 ans</a>");
$lst->add("<a href=\"fix_accounts.php\">Fix comptes</a>");
//$lst->add("<a href=\"repairdb.php\">Auto-Reparation de la base de données</a>");
$lst->add("<a href=\"affiches_cleanup.php\">Nettoyage des affiches</a>");
$lst->add("<a href=\"checkup_files.php\">Verification des fichiers</a>");

$cts->add($lst);

$cts->add_title(2,"Outils");
$lst = new itemlist();
$lst->add("<a href=\"prod_cron.php\">Passage de /taiste en production</a>");
$lst->add("<a href=\"userdelete.php\">Supprimer un utilisateur</a>");
$lst->add("<a href=\"userfusion.php\">Fusionner des utilisateurs</a>");
$lst->add("<a href=\"photomassiveimport.php\">iport massif import photo identité</a>");

$cts->add($lst);

$site->add_contents($cts);

$site->end_page();

?>
