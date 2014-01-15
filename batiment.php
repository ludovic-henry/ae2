<?php
/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
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
 $topdir = "./";
include($topdir. "include/site.inc.php");

require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/sitebat.inc.php");
require_once($topdir. "include/entities/batiment.inc.php");
require_once($topdir. "include/entities/salle.inc.php");

$site = new site ();
$sitebat = new sitebat($site->db,$site->dbrw);
$bat = new batiment($site->db,$site->dbrw);

$bat->load_by_id($_REQUEST["id_batiment"]);
if ( $bat->id < 1 )
{
  $site->error_not_found("services");
  exit();
}
$sitebat->load_by_id($bat->id_site);

if ( $_REQUEST["action"] == "addsalle" )
{

  if ($_REQUEST["nom"] != "" && $_REQUEST["etage"]  != "")
  {
    $salle = new salle($site->db,$site->dbrw);
    $salle->add ( $bat->id, $_REQUEST["nom"], $_REQUEST["etage"], $_REQUEST["fumeur"], $_REQUEST["convention"], $_REQUEST["reservable"], $_REQUEST["surface"], $_REQUEST["tel"], $_REQUEST["notes"], $_REQUEST['bar_bdf'] );
  }
}

$site->start_page("services","Batiment ".$bat->nom);
$cts = new contents($sitebat->get_html_link()." / ".$bat->get_html_link());
$cts->add_paragraph("Site : ".$sitebat->get_html_link());
$cts->add_paragraph("Voir aussi : <a href=\"sitebat.php\">Autre sites</a>");

$req = new requete($site->db,"SELECT * FROM `sl_salle` WHERE `id_batiment`='".$bat->id."'");
$tbl = new sqltable(
  "listsalles",
  "Salles", $req, "batiment.php?id_batiment=".$bat->id,
  "id_salle",
  array("nom_salle"=>"Salle","etage"=>"Etage"),
  array(), array(),array()
  );
$cts->add($tbl,true);


if ( $site->user->is_in_group("gestion_ae") )
{
  $frm = new form("newsalle","batiment.php?id_batiment=".$bat->id,true,"POST","Nouvelle salle");
  $frm->add_hidden("action","addsalle");
  $frm->add_text_field("nom","Nom","",true);
  $frm->add_text_field("etage","Etage","",true);
  $frm->add_checkbox("fumeur","Fumeur",$bat->fumeur);
  $frm->add_checkbox("convention","Convention de locaux",$bat->convention);
  $frm->add_checkbox("bar_bdf","La salle contient un bar géré par le BDF",false);
  $frm->add_checkbox("reservable","Reservable");
  $frm->add_text_field("surface","Surface");
  $frm->add_text_field("tel","Téléphone");
  $frm->add_text_area("notes","Notes");
  $frm->add_submit("valid","Ajouter");
  $cts->add($frm,true);
}
$site->add_contents($cts);
$site->end_page();
exit();
?>
