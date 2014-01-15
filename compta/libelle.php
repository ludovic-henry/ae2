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
$topdir="../";
require_once("include/compta.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");

$site = new sitecompta();
$asso  = new asso($site->db);
$libelle = new compta_libelle($site->db,$site->dbrw);

if ( !$site->user->is_valid() )
  $site->error_forbidden("services");

$asso->load_by_id($_REQUEST["id_asso"]);
if( $asso->id < 1 )
{
  $site->error_not_found("services");
  exit();
}

if ( !$site->user->is_in_group("compta_admin") && !$asso->is_member_role($site->user->id,ROLEASSO_TRESORIER) )
  $site->error_forbidden("services");

$site->set_current($asso->id,$asso->nom,null,null,null);

if ( $_REQUEST["action"] == "new" )
{
  if ( $_REQUEST["libelle"]  )
    $libelle->add_libelle($asso->id, $_REQUEST["libelle"]);
}
elseif ( $_REQUEST["action"] == "save" )
{
  $libelle->load_by_id( $_REQUEST["id_libelle"]);

  if ( $_REQUEST["libelle"] &&  $libelle->id > 0 )
    $libelle->update_libelle($_REQUEST["libelle"]);
}
elseif ( $_REQUEST["action"] == "delete" )
{
  $libelle->load_by_id( $_REQUEST["id_libelle"]);

  if ( $libelle->id > 0 )
    $libelle->remove_libelle();
}
elseif ( $_REQUEST["action"] == "edit" )
{
  $libelle->load_by_id($_REQUEST["id_libelle"]);

  if( $libelle->id < 1 )
  {
    $site->error_not_found("services");
    exit();
  }

  $site->start_page ("services", "Etiquette ".$asso->nom );

  $frm = new form ("save","libelle.php?id_asso=".$asso->id,true,"POST","Edition");
  $frm->add_hidden("action","save");
  $frm->add_hidden("id_libelle",$libelle->id);
  $frm->add_text_field("libelle","Nom",$libelle->nom,true);
  $frm->add_submit("valid","Enregistrer");

  $site->add_contents($frm);

  $site->add_contents(new contents(false,"<a href=\"libelle.php?id_asso=".$asso->id."\">Annuler</a>"));


  $site->end_page ();

  exit();
}


$site->start_page ("services", "Etiquettes ".$asso->nom );

$cts = new contents("Etiquettes ".$asso->nom );

$req = new requete ($site->db, "SELECT * " .
    "FROM cpta_libelle " .
    "WHERE id_asso='".$asso->id."' " .
    "ORDER BY nom_libelle");

$cts->add(new sqltable(
  "listlbl",
  "Etiquettes", $req, "libelle.php?id_asso=".$asso->id,
  "id_libelle",
  array(
    "nom_libelle"=>"Libellé"
    ),
  array("edit"=>"Editer","delete"=>"Supprimer"),
  array(),
  array()
  ),true);


$frm = new form ("new","libelle.php?id_asso=".$asso->id,true,"POST","Ajouter une étiquette");
$frm->add_hidden("action","new");
$frm->add_text_field("libelle","Nom","",true);
$frm->add_submit("valid","Ajouter");
$cts->add($frm,true);

$site->add_contents($cts);
$site->end_page ();



?>
