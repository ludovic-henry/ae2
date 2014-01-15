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

if ( $_REQUEST["action"] == "fusion" && $GLOBALS["svalid_call"] )
{
  $user1 = new utilisateur($site->db,$site->dbrw);
  $user1->load_by_id($_REQUEST["id_utilisateur1"]);

  $user2 = new utilisateur($site->db);
  $user2->load_by_id($_REQUEST["id_utilisateur2"]);

  if ( !$user1->is_valid() || !$user2->is_valid() )
    $Erreur="ID invalide";
  elseif ( $site->is_sure ( "","Suppression de l'utilisateur N°".$user1->id." : ".$user1->get_html_link()." en faveur de l'utilisateur N°".$user2->id." : ".$user2->get_html_link(),"fusionusr".$user1->id, 2 ) )
    $Success = $user1->replace_and_remove($user2);

}

$site->start_page("none","Administration");

$cts = new contents("<a href=\"./\">Administration</a> / Fusion de deux utilisateurs");

if ( $Success )
  $cts->add_paragraph("Utilisateurs fusionnés avec succès : ".$user2->get_html_link());

$frm = new form("rmuser", "userfusion.php", false, "POST", "Fusion");
$frm->allow_only_one_usage();
$frm->add_hidden("action","fusion");
if ( $Erreur )
  $frm->error($Erreur);
$frm->add_text_field("id_utilisateur1","ID qui sera supprimé");
$frm->add_text_field("id_utilisateur2","ID sui sera conservé");

$frm->add_submit("valid","Fusion");
$cts->add($frm,true);

$site->add_contents($cts);

$site->end_page();

?>
