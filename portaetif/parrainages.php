<?php

/* Copyright 2007
 *
 * - Simon Lopez < simon dot lopez at ayolo dot org >
 *
 * Ce fichier fait partie du site de l'Association des étudiants
 * de l'UTBM, http://ae.utbm.fr.
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
include($topdir. "include/site.inc.php");
include("include/log.inc.php");
$site = new site ();

if (!$site->user->is_in_group ("gestion_ae") && !$site->user->is_in_group ("portaetif"))
  $site->error_forbidden();


if ( $_REQUEST["action"] == "addparrain" )
{
  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST["id_utilisateur_fillot"]);
  if ( $user->id > 0 )
  {
    $user2 = new utilisateur($site->db);
    $user2->load_by_id($_REQUEST["id_utilisateur_parrain"]);
    if ( $user2->id > 0 )
    {
      if ( $user2->id == $user->id )
        $ErreurParrain = "On joue pas au boulet !";
      else
      {
        if(log_add("parrains",array("id_utilisateur"=>$user2->id,"id_utilisateur_fillot"=>$user->id)))
          $user->add_parrain($user2->id);
        else
          $ErreurParrain = "Une erreur s'est produite lors de l'écriture des logs, veillez le signaler au responsable informatique";
      }
    }
    else
      $ErreurParrain = "Utilisateur 'parrain' inconnu.";
  }
  else
    $ErreurParrain = "Utilisateur 'fillot' inconnu.";
}

$cts = new contents("Ajout de parrainage");
$frm = new form("addparrain","parrainages.php",true,"POST","Ajouter un parrainage");
$frm->add_hidden("action","addparrain");
if ( $ErreurParrain ) $frm->error($ErreurParrain);
$frm->add_user_fieldv2("id_utilisateur_parrain","Parrain");
$frm->add_user_fieldv2("id_utilisateur_fillot","Fillot");
$frm->add_submit("addresp","Ajouter");
$cts->add($frm,true);


/* c'est tout */
$site->add_contents($cts);

$site->end_page();

?>
