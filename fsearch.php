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

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/entities/objet.inc.php");

$site = new site ();

// Code barres & co
if ( $site->user->is_in_group("gestion_ae") && $_REQUEST["pattern"] != "" )
{

  $obj = new objet($site->db);
  $obj->load_by_cbar($_REQUEST["pattern"]);
  if ( $obj->id > 0 )
  {
    header("Location: objet.php?id_objet=".$obj->id);
    exit();
  }

  if ( ereg("^([A-Za-z]{1,10})([0-9]{1,20})$",$_REQUEST["pattern"],$regs))
  {
    $objtype = new objtype($site->db);
    $objtype->load_by_code($regs[1]);
    if ( $objtype->id > 0 )
    {
      $obj->load_by_num($objtype->id,$regs[2]);
      if ( $obj->id > 0 )
      {
        header("Location: objet.php?id_objet=".$obj->id);
        exit();
      }
    }
  }

  $user = new utilisateur($site->db);
  $user->load_by_carteae($_REQUEST["pattern"]);
  if ( $user->id > 0 )
  {
    header("Location: user.php?id_utilisateur=".$user->id);
    exit();
  }


}
require_once($topdir. "include/cts/fsearch.inc.php");
$fs  = new fsearch ( $site );
if ( $fs->nb == 1 && $fs->redirect )
{
  header("Location: ".$fs->redirect);
  exit();
}

$site->start_page("","Recherche rapide");
$cts = new contents("Resultats");


$cts->add($fs,false,true,"fsearchblock");

if ( $site->user->is_in_group("gestion_ae") )
{
  $frm = new form("addobjet","objet.php",!$sucess,"POST","Ajouter un objet avec comme code barre ".$_REQUEST["pattern"]);
  $frm->add_hidden("action","addobjet");
  $frm->add_hidden("nb","1");
  $frm->add_hidden("force_cbar",$_REQUEST["pattern"]);
  $frm->add_entity_select("id_objtype", "Type", $site->db, "objtype");
  $frm->add_text_field("nom","Nom");
  $frm->add_text_field("num_serie","Numéro de série");
  $frm->add_date_field("date_achat","Date d'achat");
  $frm->add_entity_select("id_asso_prop", "Propriètaire", $site->db, "asso", false, false, array("id_asso_parent"=>NULL));
  $frm->add_entity_select("id_asso", "Gestionnaire", $site->db, "asso");
  $frm->add_entity_select("id_salle", "Salle", $site->db, "salle");
  $frm->add_price_field("prix","Prix d'achat");
  $frm->add_price_field("caution","Prix de la caution");
  $frm->add_price_field("prix_emprunt","Prix d'un emprunt");
  $frm->add_checkbox("empruntable","Empruntable");
  $frm->add_checkbox("en_etat","En etat");
  $frm->add_text_area("notes","Notes");
  $frm->add_submit("valide","Ajouter");
  $cts->add($frm,true);
}


$site->add_contents($cts);
$site->end_page();


?>
