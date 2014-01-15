<?php

/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
 * - Benjamin Collet < bcollet at oxynux dot org >
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
require_once($topdir. "include/entities/group.inc.php");

$site = new site ();

if ( !$site->user->is_in_group("root") && !$site->user->is_in_group("gestion_ae"))
  $site->error_forbidden("accueil","group",1);

$grp = new group ( $site->db,$site->dbrw);

if ( isset($_REQUEST["id_groupe"]) )
{
  $grp->load_by_id($_REQUEST["id_groupe"]);
  if ( $grp->id < 1 )
  {
    $site->error_not_found("accueil");
    exit();
  }
}

if ( $_REQUEST["action"] == "delete" && !isset($_REQUEST["id_utilisateur"]) && $site->user->is_in_group("root") )
{
  // Opération **trés** critique (la suppression d'un groupe barman, ou d'admin serai trés dommagable)
  if ( $site->is_sure ( "","Suppression du groupe ".$grp->nom,"delgrp".$grp->id, 2 ) )
  {
    _log($site->dbrw,"Retrait d'un groupe","Retrait du groupe ". $grp->nom ."(id : ". $grp->id .")","Groupes",$site->user);
    $grp->delete_group();
  }
  $grp->id = -1;
}

if (  $grp->id > 0)
{

  if($site->user->is_in_group("gestion_ae") || $site->user->is_in_group("root"))
  {
    if ( $_REQUEST["action"] == "delete" )
    {
      if ( ($grp->id != 7
            && $grp->id != 46
            && $grp->id != 47
            && $site->user->is_in_group_id ($grp->id)
           ) || $site->user->is_in_group("root") )
      {
        $grp->remove_user_from_group($_REQUEST["id_utilisateur"]);
        $user = new utilisateur($site->db);
        $user->load_by_id($_REQUEST["id_utilisateur"]);
        _log($site->dbrw,"Retrait d'un utilisateur du groupe ". $grp->nom,"Retrait de l'utilisateur ".$user->nom." ".$user->prenom." (id : ".$user->id.") du groupe ". $grp->nom ." (id : ".$grp->id.")","Groupes",$site->user);
      }
      else
        $Error = "Veuillez contacter l'équipe informatique pour modifier les groupes dont vous n'êtes pas membre ou les groupes systèmes.";
    }
    elseif ( $_REQUEST["action"] == "deletes" && !empty($_REQUEST["id_utilisateurs"]) )
    {
      if ( ($grp->id != 7 && $grp->id != 46 && $grp->id != 47 && $site->user->is_in_group_id ($grp->id)) || $site->user->is_in_group("root") )
      {
        foreach($_REQUEST["id_utilisateurs"] as $id_utilisateur)
        {
            $grp->remove_user_from_group($id_utilisateur);
            $user = new utilisateur($site->db);
            $user->load_by_id($id_utilisateur);
            _log($site->dbrw,"Retrait d'un utilisateur du groupe ". $grp->nom,"Retrait de l'utilisateur ".$user->nom." ".$user->prenom." (id : ".$user->id.") du groupe ". $grp->nom ." (id : ".$grp->id.")","Groupes",$site->user);
        }
      }
      else
        $Error = "Veuillez contacter l'équipe informatique pour modifier les groupes dont vous n'êtes pas membre ou les groupes systèmes.";
    }
    elseif ( $_REQUEST["action"] == "add" )
    {
      if ( ($grp->id != 7 && $grp->id != 46 && $grp->id != 47 && $site->user->is_in_group_id ($grp->id)) || $site->user->is_in_group("root") )
      {
        $user = new utilisateur($site->dbrw);
        $user->load_by_id($_REQUEST["id_utilisateur"]);
        if ( $user->id > 0 )
        {
          $grp->add_user_to_group($user->id);
          _log($site->dbrw,"Ajout d'un utilisateur au groupe ". $grp->nom,"Ajout de l'utilisateur ".$user->nom." ".$user->prenom." (id : ".$user->id.") au groupe ". $grp->nom ." (id : ".$grp->id.")","Groupes",$site->user);
        }
      }
      else
        $Error = "Veuillez contacter l'équipe informatique pour modifier les groupes dont vous n'êtes pas membre ou les groupes systèmes.";
    }
  }
  $site->start_page("accueil","Groupe");

  $cts = new contents("<a href=\"group.php\">Groupes</a> / ".$grp->get_html_link());
  $cts->add_paragraph($grp->description);
  $req = new requete($site->db,
    "SELECT `utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur` " .
    "FROM `utl_groupe` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`utl_groupe`.`id_utilisateur` " .
    "WHERE `utl_groupe`.`id_groupe`='".$grp->id."' " .
    "ORDER BY `utilisateurs`.`nom_utl`,`utilisateurs`.`prenom_utl`");

  $tbl = new sqltable(
      "listmemb",
      "Membres", $req, "group.php?id_groupe=".$grp->id,
      "id_utilisateur",
      array("nom_utilisateur"=>"Utilisateur"),
      array("delete"=>"Supprimer"),
      array("deletes"=>"Supprimer"),
      array( )
      );
  $cts->add($tbl,true);

  $frm = new form("adduser","group.php?id_groupe=".$grp->id, false,"POST","Ajouter un utilisateur");
  $frm->add_hidden("action","add");

  if ( $Error )
    $frm->error($Error);

  $frm->add_user_fieldv2("id_utilisateur","Utilisateur");
  $frm->add_submit("valid","Ajouter");
  $cts->add($frm,true);

  $site->add_contents($cts);

  $site->end_page();
  exit();
}

if ( $_REQUEST["action"] == "addgroup" && $site->user->is_in_group("root"))
{
  if ( !$_REQUEST["nom"] )
    $Error = "Un nom est requis.";
  else
  {
    $grp->add_group($_REQUEST["nom"],$_REQUEST["description"],$_REQUEST['type']);
    _log($site->dbrw,"Ajout d'un groupe","Ajout du groupe ". $_REQUEST["nom"] ."(". $_REQUEST["description"] .")","Groupes",$site->user);
  }
}

$site->start_page("accueil","Groupes");
$cts = new contents("Groupes");

$req = new requete($site->db,
  "SELECT * FROM `groupe` " .
  "ORDER BY nom_groupe");

if ( $site->user->is_in_group("root") )
{
  $tbl = new sqltable(
      "listgrp",
      "Groupes", $req, "group.php",
      "id_groupe",
      array("id_groupe" => "ID", "nom_groupe"=>"Groupe","description_groupe"=>"Description"),
      array("delete"=>"Supprimer"),
      array(),
      array( )
      );
}
else
{
  $tbl = new sqltable(
      "listgrp",
      "Groupes", $req, "group.php",
      "id_groupe",
      array("id_groupe" => "ID", "nom_groupe"=>"Groupe","description_groupe"=>"Description"),
      array(),
      array(),
      array( )
      );
}
$cts->add($tbl,true);

if ( $site->user->is_in_group("root") )
{
  $frm = new form("addgroup","group.php", false,"POST","Créer un groupe");
  $frm->add_hidden("action","addgroup");
  if ( $Error )
    $frm->error($Error);
  $frm->add_text_field("nom","Nom (unix)","",true);
  $frm->add_select_field("type","Type",$types_groupes);
  $frm->add_text_field("description","Description","");
  $frm->add_submit("valide","Ajouter");
  $cts->add($frm,true);
}
else
  $cts->add_paragraph("Pour ajouter ou supprimer des groupes, veuillez contacter l'équipe informatique.");

$site->add_contents($cts);
$site->end_page();
?>
