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

if ( $_REQUEST["action"] == "delete" && $GLOBALS["svalid_call"] )
{
  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST["id_utilisateur"]);

  if ( !$user->is_valid() )
    $Erreur="ID invalide";
  elseif ( $site->is_sure ( "","Suppression de l'utilisateur N°".$user->id." : ".$user->get_html_link(),"delusr".$user->id, 2 ) )
    $Success = $user->delete_utilisateur();

}

$site->start_page("none","Administration");

$cts = new contents("<a href=\"./\">Administration</a> / Suppression d'un utilisateur");

if ( $Success )
  $cts->add_paragraph("Utilisateur ".$user->id." supprimé avec succès");

$frm = new form("rmuser", "userdelete.php", false, "POST", "Supprimer");
$frm->allow_only_one_usage();
$frm->add_hidden("action","delete");
if ( $Erreur )
  $frm->error($Erreur);
$frm->add_text_field("id_utilisateur","ID Utilisateur");
$frm->add_submit("valid","Supprimer");
$cts->add($frm,true);

$site->add_contents($cts);

$site->end_page();

?>
