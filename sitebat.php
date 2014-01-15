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

$site = new site ();
$sitebat = new sitebat($site->db,$site->dbrw);

if ( isset($_REQUEST["id_site"]) )
{
  $sitebat->load_by_id($_REQUEST["id_site"]);
  if ( $sitebat->id < 1 )
  {
    $site->error_not_found("services");
    exit();
  }
  if ( $_REQUEST["action"] == "addbat" && $site->user->is_in_group("gestion_ae") )
  {
    $batiment = new batiment($site->db,$site->dbrw);
    if ( $_REQUEST["nom"] != "" )
      $batiment->add($sitebat->id,$_REQUEST["nom"], $_REQUEST["fumeur"], $_REQUEST["convention"], $_REQUEST["notes"] );
  }
}
else if ( $_REQUEST["action"] == "addsite" && $site->user->is_in_group("gestion_ae") )
{
  if ( $_REQUEST["nom"] != "" )
    $sitebat->add($_REQUEST["nom"], $_REQUEST["fumeur"], $_REQUEST["convention"], $_REQUEST["notes"] );
}

if ( $sitebat->id > 0 )
{
  $site->start_page("services",$sitebat->nom);
  $cts = new contents($sitebat->get_html_link());

  $cts->add_paragraph("Voir aussi : <a href=\"sitebat.php\">Autre sites</a>");

  $req = new requete($site->db,"SELECT * FROM `sl_batiment` WHERE `id_site`='".$sitebat->id."'");
  $tbl = new sqltable(
    "listsites",
    "Batiments", $req, "sitebat.php?id_site=".$sitebat->id,
    "id_batiment",
    array("nom_bat"=>"Batiment"),
    array(), array(),array()
    );
  $cts->add($tbl,true);

  $req = new requete($site->db,"SELECT `id_salle`,`nom_salle`,`etage`,`sl_batiment`.`id_batiment`,`nom_bat` FROM `sl_salle` " .
                "INNER JOIN `sl_batiment` ON `sl_batiment`.`id_batiment`=`sl_salle`.`id_batiment` " .
                "WHERE `id_site`='".$sitebat->id."'");
  $tbl = new sqltable(
    "listsalles",
    "Salles", $req, "salle.php",
    "id_salle",
    array("nom_salle"=>"Salle","etage"=>"Etage","nom_bat"=>"Batiment"),
    array(), array(),array()
    );
  $cts->add($tbl,true);

  if ( $site->user->is_in_group("gestion_ae") )
  {
    $frm = new form("newbat","sitebat.php?id_site=".$sitebat->id,true,"POST","Nouveau batiment");
    $frm->add_hidden("action","addbat");
    $frm->add_text_field("nom","Nom du batiment","",true);
    $frm->add_checkbox("fumeur","Fumeur",$sitebat->fumeur);
    $frm->add_checkbox("convention","Convention de locaux",$sitebat->convention);
    $frm->add_text_area("notes","Notes");
    $frm->add_submit("valid","Ajouter");
    $cts->add($frm,true);
  }
  $site->add_contents($cts);
  $site->end_page();
  exit();
}

$site->start_page("services","Sites");

$req = new requete($site->db,"SELECT * FROM `sl_site`");
$tbl = new sqltable(
  "listsites",
  "Sites", $req, "sitebat.php",
  "id_site",
  array("nom_site"=>"Site"),
  array(), array(),array()
  );
$site->add_contents($tbl);

if (  $site->user->is_in_group("gestion_ae") )
{
  $frm = new form("newsitebat","sitebat.php",true,"POST","Nouveau site");
  $frm->add_hidden("action","addsite");
  $frm->add_text_field("nom","Nom du site","",true);
  $frm->add_checkbox("fumeur","Fumeur");
  $frm->add_checkbox("convention","Convention de locaux");
  $frm->add_text_area("notes","Notes");
  $frm->add_submit("valid","Ajouter");
  $site->add_contents($frm);
}
$site->end_page();

?>
