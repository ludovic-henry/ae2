<?php
/* Copyright 2006
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
$topdir = "../";
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/asso.inc.php");

$site = new site ();
$asso = new asso($site->db,$site->dbrw);
$asso->load_by_id($_REQUEST["id_asso"]);
if ( $asso->id < 1 )
{
  $site->error_not_found("presentation");
  exit();
}
if ( !$site->user->is_in_group("gestion_ae") && !$asso->is_member_role($site->user->id,ROLEASSO_MEMBREBUREAU) )
  $site->error_forbidden("presentation");

$site->start_page("presentation",$asso->nom);

$cts = new contents($asso->get_html_path());

$cts->add(new tabshead($asso->get_tabs($site->user),"inv"));

$show_all = false;
if (isset($_REQUEST['showall']))
  $show_all = true;


$cts->add_paragraph("<a href=\"../objet.php?id_asso=".$asso->id."\">Ajouter un objet</a>");
$cts->add_paragraph("<a href=\"../etiquette.php?id_asso=".$asso->id."\">Imprimer codes barres</a>");
$cts->add_paragraph("<a href=\"inventaire.php?id_asso=".$asso->id."&showall\">Afficher les objets archivés</a>");
$cts->add_paragraph("<a href=\"invlist.php?id_asso=".$asso->id."\">Imprimer relevés</a>");



$req = new requete ( $site->db, "SELECT `inv_objet`.`id_objet`," .
    "CONCAT(`inv_objet`.`nom_objet`,' ',`inv_objet`.`cbar_objet`) AS `nom_objet`, " .
    "`asso_gest`.`id_asso` AS `id_asso_gest`, " .
    "`asso_gest`.`nom_asso` AS `nom_asso_gest`, " .
    "`asso_prop`.`id_asso` AS `id_asso_prop`, " .
    "`asso_prop`.`nom_asso` AS `nom_asso_prop`, " .
    "`sl_batiment`.`id_batiment`,`sl_batiment`.`nom_bat`," .
    "`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`,  " .
    "`inv_type_objets`.`id_objtype`,`inv_type_objets`.`nom_objtype`  " .
    ($show_all ? ", if(`inv_objet`.`archive_objet` = 1, 'Oui', 'Non') archive_objet " : "").
    "FROM `inv_objet` " .
    "INNER JOIN `asso` AS `asso_gest` ON `inv_objet`.`id_asso`=`asso_gest`.`id_asso` " .
    "INNER JOIN `asso` AS `asso_prop` ON `inv_objet`.`id_asso_prop`=`asso_prop`.`id_asso` " .
    "INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
    "INNER JOIN `sl_batiment` ON `sl_batiment`.`id_batiment`=`sl_salle`.`id_batiment` " .
    "INNER JOIN `inv_type_objets` ON `inv_objet`.`id_objtype`=`inv_type_objets`.`id_objtype` " .
    "WHERE `inv_objet`.`id_asso`='".$asso->id."'".
    ($show_all ? "" : "AND `archive_objet` = 0")
        );

$columns = array(
    "nom_objet"=>"Objet",
    "nom_objtype"=>"Type",
    "nom_asso_gest"=>"Gestionnaire",
    "nom_asso_prop"=>"Propriétaire",
    "nom_salle"=>"Salle",
    "nom_bat"=>"Batiment",
    );

if ($show_all)
    $columns["archive_objet"] = "Archivé";

$tbl = new sqltable(
  "listobjets",
  "Inventaire", $req, "asso.php",
  "id_objet",
  $columns,
  array(), array(), array()
  );

$cts->add($tbl);

$site->add_contents($cts);
$site->end_page();
?>
