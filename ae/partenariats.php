<?php
/* Copyright 2010
 * - Mathieu Briand <briandmathieu CHEZ hyprua POINT org>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License a
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
/** Affiche les utilisateurs en attente des avantages des partenaires
 */

$topdir = "../";
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable2.inc.php");
require_once($topdir. "include/entities/partenariat_utl.inc.php");

$partenaires=array(1=>"Société Générale", 2=>"SMEREB");

$site = new site ();

if ( !$site->user->is_in_group("gestion_ae") )
  $site->error_forbidden("services","group","gestion_ae");

$site->add_js("js/sqltable2.js");
$site->start_page("services","Partenariats en attente");
$partenariat = new Partenariat($site->db, $site->dbrw);

if ($_REQUEST['action'] == "add")
{
  $partenariat->load_by_partenariat_utilisateur($_REQUEST['id_partenariat'], $_REQUEST['id_utilisateur']);
  if($partenariat->is_valid())
    $this->add_contents(new error("Partenariat en attente déjà enregistré pour l'utilisateur"));
  else
    $partenariat->add($_REQUEST['id_partenariat'], $_REQUEST['id_utilisateur']);
}
elseif ($_REQUEST['action'] == "deletes")
{
  foreach($_REQUEST['id_partenariat_utls'] as $id )
  {
    $partenariat->load_by_id($id);
    $partenariat->remove();
  }
}

$sql = "SELECT `partenariats_utl`.`id_partenariat_utl`, `partenariats_utl`.`id_partenariat`, ".
      "`partenariats_utl`.`date_partenariat`, " .
      "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur` ".
      "FROM `partenariats_utl` ".
      "LEFT JOIN `utilisateurs` USING (`id_utilisateur`) ".
      "ORDER BY `partenariats_utl`.`id_partenariat`, `nom_utilisateur`";

$cts = new contents("Partenariats en attente");

$tbl = new sqltable2("partenariats_utl", null, "partenariats.php");
$tbl->add_batch_action("deletes", "Supprimer");
$tbl->add_column_text('id_partenariat', 'Partenaire');
$tbl->set_column_enumeration('id_partenariat', $partenaires);
$tbl->add_column_text('nom_utilisateur', 'Nom');
$tbl->add_column_date('date_partenariat', 'Date');
$tbl->set_sql($site->db, 'id_partenariat_utl', $sql);
$cts->add($tbl,true);

$frm = new form("partenariat","partenariats.php",true,"POST", "Ajouter un partenariat en attente");
$frm->add_hidden("action","add");
$frm->add_select_field("id_partenariat", "Partenaire", $partenaires);
$utl = new utilisateur($site->db);
$frm->add_entity_smartselect ("id_utilisateur","Cotisant", $utl, false, true);
$frm->add_submit("submit","Ajouter");
$cts->add($frm,true);


$site->add_contents($cts);
$site->end_page();


?>
