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
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/objet.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
$site = new site ();

$site->allow_only_logged_users("services");

$assos=$site->user->get_assos(ROLEASSO_MEMBREBUREAU);

if ( !count($assos) && !$site->user->is_in_group("gestion_ae") )
  $site->error_forbidden("services","group",9);

$objtype = new objtype($site->db,$site->dbrw);

if ( isset($_REQUEST["id_objtype"]) )
{
  $objtype->load_by_id($_REQUEST["id_objtype"]);
  if ( !$objtype->is_valid() )
  {
    $site->error_not_found("services");
    exit();
  }

  if ( ($_REQUEST["action"] == "delete") && $site->user->is_in_group("gestion_ae") )
  {
    $objet = new objet($site->db,$site->dbrw);
    $objet->load_by_id($_REQUEST["id_objet"]);
    if ( $objet->id > 0 )
      $objet->delete_objet();
  }

  $site->start_page("services",$objtype->nom);

  $cts = new contents("<a href=\"objtype.php\">Inventaire</a> / ".$objtype->get_html_link());

  $sql = new requete ( $site->db, "SELECT COUNT(*) FROM `inv_objet` " .
        "WHERE `id_objtype`='".$objtype->id."'" );

  list($count) = $sql->get_row();

  $cts->add(new tabshead(array(

        array("","objtype.php?id_objtype=".$objtype->id, "Inventaire ($count)"),
        array("infos","objtype.php?id_objtype=".$objtype->id."&view=infos", "Informations"),

        ),$_REQUEST["view"]));

  if ( $_REQUEST["view"] == "" )
  {
    if ( $site->user->is_in_group("gestion_ae") )
      $cts->add_paragraph("<a href=\"etiquette.php?id_objtype=".$objtype->id."\">Imprimer codes barres</a>");
    else
    {
      foreach($assos as $key=>$val)$assos_keys[]=$key;
      $filter = " AND `asso_gest`.`id_asso` IN (".implode(",",$assos_keys).") ";
    }

    $frm = new form("addobjet","objet.php",true,"POST","Ajouter des objets");
    $frm->add_hidden("id_objtype",$objtype->id);
    $frm->add_text_field("nb","Nombre à ajouter","1",true);
    if ( !$site->user->is_in_group("gestion_ae") )
      $frm->add_select_field("id_asso","Association",$assos);
    $frm->add_submit("valide","Poursuivre");
    $cts->add($frm,true);

    $req = new requete ( $site->db, "SELECT `inv_objet`.`id_objet`," .
        "CONCAT(`inv_objet`.`nom_objet`,' ',`inv_objet`.`cbar_objet`) AS `nom_objet`, " .
        "`asso_gest`.`id_asso` AS `id_asso_gest`, " .
        "`asso_gest`.`nom_asso` AS `nom_asso_gest`, " .
        "`asso_prop`.`id_asso` AS `id_asso_prop`, " .
        "`asso_prop`.`nom_asso` AS `nom_asso_prop`, " .
        "`sl_batiment`.`id_batiment`,`sl_batiment`.`nom_bat`," .
        "`sl_salle`.`id_salle`,`sl_salle`.`nom_salle`, ".
        "`utilisateurs`.`id_utilisateur`, " .
        "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur` " .

        "FROM `inv_objet` " .
        "INNER JOIN `asso` AS `asso_gest` ON `inv_objet`.`id_asso`=`asso_gest`.`id_asso` " .
        "INNER JOIN `asso` AS `asso_prop` ON `inv_objet`.`id_asso_prop`=`asso_prop`.`id_asso` " .
        "INNER JOIN `sl_salle` ON `inv_objet`.`id_salle`=`sl_salle`.`id_salle` " .
        "INNER JOIN `sl_batiment` ON `sl_batiment`.`id_batiment`=`sl_salle`.`id_batiment` " .
        "LEFT JOIN inv_emprunt ON ( inv_emprunt.id_emprunt=( SELECT inv_emprunt.id_emprunt FROM inv_emprunt_objet ".
          "INNER JOIN inv_emprunt USING(id_emprunt) ".
          "WHERE ".
          "inv_emprunt_objet.id_objet=`inv_objet`.`id_objet` ".
          "AND inv_emprunt_objet.retour_effectif_emp IS NULL ".
          "AND inv_emprunt.date_prise_emp IS NOT NULL LIMIT 1)   ) ".
        "LEFT JOIN utilisateurs ON ( utilisateurs.id_utilisateur=inv_emprunt.id_utilisateur)".

        "WHERE `id_objtype`='".$objtype->id."' $filter" .
        "GROUP BY `inv_objet`.`id_objet`".
        "ORDER BY `inv_objet`.`nom_objet`" );

    $tbl = new sqltable(
      "listobjets",
      "Inventaire", $req, "objtype.php?id_objtype=".$objtype->id,
      "id_objet",
      array("nom_objet"=>"Objet","nom_asso_gest"=>"Gestionnaire","nom_asso_prop"=>"Propriétaire","nom_salle"=>"Salle","nom_bat"=>"Batiment","nom_utilisateur"=>"Actuellement emprunté par"),
      $site->user->is_in_group("gestion_ae")?array("delete"=>"Supprimer"):array(), array(), array()
      );

    $cts->add($tbl,true);
  }
  elseif($_REQUEST["view"] == "infos")
  {

  $cts->add_paragraph("<a href=\"objtype.php\">Autres types d'objets</a>");

  if ( $site->user->is_in_group("gestion_ae") )
  {
    $frm = new form("saveobjtype","objtype.php?view=infos&id_objtype=".$objtype->id,true,"POST","Editer");
    $frm->add_hidden("action","saveobjtype");
    $frm->add_text_field("nom","Nom",$objtype->nom,true);
    $frm->add_price_field("prix","Prix d'achat",$objtype->prix);
    $frm->add_price_field("caution","Prix de la caution",$objtype->caution);
    $frm->add_price_field("prix_emprunt","Prix d'un emprunt",$objtype->prix_emprunt);
    $frm->add_text_field("code","Code (3 lettres)",$objtype->code,true);
    $frm->add_checkbox("empruntable","Empruntable",$objtype->empruntable);
    $frm->add_text_area("notes","Notes",$objtype->notes);
    $frm->add_submit("valide","Enregistrer");
    $cts->add($frm,true);
  }
  else
  {
    $tbl = new table("Informations");
    $tbl->add_row(array("Code:",$objtype->code));
    $tbl->add_row(array("Empruntable:",$objtype->empruntable?"Oui":"Non"));
    $cts->add($tbl,true);
  }



  }

  $site->add_contents($cts);
  $site->end_page();

  exit();
}

if ( $_REQUEST["action"] == "newobjtype" && $site->user->is_in_group("gestion_ae") )
{

  if ( $_REQUEST["nom"] && strlen($_REQUEST["code"])==3)
  {
    $objtype->add ( $_REQUEST["nom"], $_REQUEST["prix"],
        $_REQUEST["caution"],
        $_REQUEST["prix_emprunt"], $_REQUEST["code"],
        $_REQUEST["empruntable"]==true, $_REQUEST["notes"] );
  }
}


$site->start_page("services","Type d'objets");

$req = new requete($site->db,"SELECT * FROM inv_type_objets ORDER BY nom_objtype");

$cts = new contents("<a href=\"objtype.php\">Inventaire</a>");

$tbl = new sqltable(
      "listobjtype",
      "Types d'objets", $req, "objtype.php",
      "id_objtype",
      array("nom_objtype"=>"Type"),
      array(), array(), array()
      );

$cts->add($tbl,true);

if ( $site->user->is_in_group("gestion_ae") )
{
  $frm = new form("newobjtype","objtype.php",true,"POST","Nouveau type d'objet");
  $frm->add_hidden("action","newobjtype");

  $frm->add_text_field("nom","Nom","",true);
  $frm->add_price_field("prix","Prix d'achat");
  $frm->add_price_field("caution","Prix de la caution");
  $frm->add_price_field("prix_emprunt","Prix d'un emprunt");
  $frm->add_text_field("code","Code (3 lettres)","",true);
  $frm->add_checkbox("empruntable","Empruntable");
  $frm->add_text_area("notes","Notes");
  $frm->add_submit("valide","Ajouter");

  $cts->add($frm,true);
}

$site->add_contents($cts);

$site->end_page();
?>
