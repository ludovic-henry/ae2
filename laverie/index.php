<?php

/* Copyright 2007
 * - Benjamin Collet < bcollet AT oxynux DOT org >
 * - Manuel Vonthron < manuel DOT vonthron AT acadis DOT org >
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

define("ID_ASSO_LAVERIE", 84);
define("GRP_BLACKLIST", 29);
define("CPT_MACHINES", 8);
define("JET_LAVAGE", 224);
define("JET_SECHAGE", 225);

$topdir = "../";
require_once($topdir. "include/site.inc.php");

require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/cts/planning.inc.php");
require_once($topdir. "include/cts/user.inc.php");

require_once($topdir. "include/entities/salle.inc.php");
require_once($topdir. "include/entities/jeton.inc.php");
require_once($topdir. "include/entities/machine.inc.php");
require_once($topdir. "include/entities/page.inc.php");

require_once($topdir. "comptoir/include/comptoirs.inc.php");
require_once($topdir. "comptoir/include/venteproduit.inc.php");

$site = new site();
$site->add_css("css/weekplanning.css");

$site->allow_only_logged_users("services");

if(!$site->user->ae && !$site->user->is_in_group("gestion_machines") )
  $site->error_forbidden("services","Service réservé aux cotisants AE");

if ( $site->user->is_in_group("blacklist_machines") )
  $site->error_forbidden("services","blacklist_machines");

// En dure en attendant la correction de la base de donnés
$salles = array(6=>"Laverie belfort",8=>"Laverie Sevenans");

$is_admin = $site->user->is_in_group("gestion_machines");

/*if ( $_REQUEST["contrat"] == "accept" )
{
  new insert($site->dbrw, "mc_contrat", array('id_utilisateur' => $site->user->id));
}

$req = new requete($site->db, "SELECT * FROM mc_contrat WHERE id_utilisateur='".$site->user->id."'");

if($req->lines == 0)
{
  $site->start_page("services","Laverie");
  $cts = new contents("Laverie");

  $cts->add_title(2, "Contrat d'utilisation");
  $cts->add_paragraph("Mettre tout le règlement ici");

  $frm = new form("accepter_contrat","index.php",false);
  $frm->add_hidden("contrat", "accept");
  $frm->add_submit("submit","J'ai lu et j'accepte le contrat d'utilisation de la laverie");

  $cts->add($frm);
  $site->add_contents($cts);
  $site->end_page();
  exit();
}*/

if ( $_REQUEST["action"] == "delete" )
{
  $machine = new machine($site->db,$site->dbrw);
  $machine->load_by_id_creneau($_REQUEST["id_creneau"],$debut);
  $machine->free_creneau($_REQUEST["id_creneau"],$site->user->id);
}

if ( $_REQUEST["action"] == "reserver" )
{
  $machine = new machine($site->db,$site->dbrw);

  $user = $site->user;

  if ( $is_admin && isset($_REQUEST["id_utilisateur"]) )
  {
    $user = new utilisateur($site->db);
    $user->load_by_id($_REQUEST["id_utilisateur"]);
  }

  if(!$user->is_valid())
    $Erreur = "Utilisateur inconnu";
  elseif($user->is_in_group("cpt_bloque"))
    $Erreur = "Le compte de cet utilisateur est bloqué !";
  elseif($user->is_in_group("blacklist_machines"))
    $Erreur = "Cet utilisateur n'est pas autorisé à emprunter de jeton !";
  elseif( !$user->ae )
    $Erreur = "Cotisation non renouvelée.";
  else
  {
    $machine->load_by_id_creneau($_REQUEST["id_creneau1"],$debut);
    $machine->take_creneau($_REQUEST["id_creneau1"],$user->id);
    if ( isset($_REQUEST["id_creneau2"]) )
    {
      $machine->load_by_id_creneau($_REQUEST["id_creneau2"],$debut);
      $machine->take_creneau($_REQUEST["id_creneau2"],$user->id);
    }

    if ( isset($_REQUEST["fallback"]) && $_REQUEST["fallback"] == "admin" )
    {
      header("Location: admin.php?id_salle=".$machine->id_salle);
      exit();
    }
  }
}

if ( $_REQUEST["page"] == "reserver" )
{
  if ( isset($_REQUEST["id_creneau1"]) )
  {
    $id_creneau1 = $_REQUEST["id_creneau1"];
    $id_creneau2 = isset($_REQUEST["id_creneau2"])?$_REQUEST["id_creneau2"]:null;
  }
  elseif ( strpos($_REQUEST["id_creneau"],",") !== false )
  {
    list($id_creneau1,$id_creneau2) = explode(",",$_REQUEST["id_creneau"]);
  }
  else
  {
    $id_creneau1 = $_REQUEST["id_creneau"];
    $id_creneau2 = null;
  }

  $machine1 = new machine($site->db);
  $machine2 = new machine($site->db);

  $machine1->load_by_id_creneau($id_creneau1,$debut1);

  if ( !is_null($id_creneau2) )
    $machine2->load_by_id_creneau($id_creneau2,$debut2);

  $site->start_page("services","Laverie");
  $cts = new contents("<a href=\"index.php\">Laverie</a> / ".$salles[$machine1->id_salle]." / Reservation");

  $cts->add_title(2,"Confirmation de la reservation");

  $blabla = array("laver"=>"de la machine à laver","secher"=>"du sèche linge");

  $cts->add_paragraph("Reservation ".$blabla[$machine1->type]." ".$machine1->lettre." le ".date("d/m/Y",$debut1)." à partir de ".date("H:i",$debut1));

  if ( !is_null($id_creneau2) )
    $cts->add_paragraph("et reservation ".$blabla[$machine2->type]." ".$machine2->lettre." le ".date("d/m/Y",$debut2)." à partir de ".date("H:i",$debut2));

  $frm = new form("reserver","index.php",false);
  $frm->add_hidden("action","reserver");
  $frm->add_hidden("id_creneau1",$id_creneau1);

  if ( isset($_REQUEST["fallback"]) )
    $frm->add_hidden("fallback",$_REQUEST["fallback"]);

  if ( !is_null($id_creneau2) )
    $frm->add_hidden("id_creneau2",$id_creneau2);

  if ( $Erreur )
    $frm->error($Erreur);

  if ( $is_admin )
    $frm->add_entity_smartselect ( "id_utilisateur", "Reserver pour", $site->user );

  $frm->add_submit("valid","Confirmer");
  $cts->add($frm);


  $site->add_contents($cts);
  $site->end_page();

  exit();
}
elseif ( $_REQUEST["page"] == "viewreserv" )
{
  $site->start_page("services","Laverie");
  $titre = "Créneaux réservés (".($_REQUEST["operation"] ==  1 ? 'lavage' : 'sechage').")";
  $cts = new contents("<a href=\"index.php\">Laverie</a> / ".$salles[$_REQUEST["id_salle"]]." / ".$titre);

  $type = $_REQUEST["operation"] ==  1 ? 'laver' : 'secher';

  if ( $site->user->is_in_group("gestion_machines"))
    $sql =
    "SELECT
     id_creneau,
     debut_creneau, SUBTIME(fin_creneau,'00:00:01') as fin_creneau,
     COALESCE(GROUP_CONCAT(prenom_utl, ' ', nom_utl), 'Choisir') AS texte
     FROM mc_creneaux
     INNER JOIN mc_machines ON ( mc_creneaux.id_machine = mc_machines.id  )
     LEFT JOIN utilisateurs ON ( mc_creneaux.id_utilisateur = utilisateurs.id_utilisateur )
     WHERE mc_machines.type='".mysql_real_escape_string($type)."'
     AND mc_machines.loc='".mysql_real_escape_string($_REQUEST["id_salle"])."'
     AND debut_creneau > NOW()";

    $pl = new weekplanning ( "Selectionner un creneau", $site->db, $sql, "id_creneau", "debut_creneau", "fin_creneau", "texte", "index.php?action=searchmc&operation=".$_REQUEST["operation"]."&id_salle=".$_REQUEST["id_salle"], "index.php?page=reserver".$extraurl, "GROUP BY debut_creneau" );
    $cts->add($pl,true);


  $frm = new form("searchmc","index.php",false,"POST","Nouvelle recherche");
  $frm->add_hidden("action","searchmc");
  if ( isset($_REQUEST["fallback"]) )
    $frm->add_hidden("fallback",$_REQUEST["fallback"]);
  $frm->add_select_field("id_salle","Lieu",$salles, $_REQUEST["id_salle"]);
  $frm->add_select_field("operation","Machines désirées",array(3=>"Lavage et sechage",1=>"Lavage seulement",2=>"Sechage seulement"));
  $frm->add_submit("search","Rechercher un créneau");
  $cts->add($frm,true);

  if ( $is_admin )
  {
    $cts->add_paragraph("<a href=\"admin.php\">Administration</a>");
    $cts->add_paragraph("<a href=\"index.php?page=viewreserv&amp;operation=1&amp;id_salle=".$_REQUEST["id_salle"]."\">Afficher les réservations pour les lavages</a>");
    $cts->add_paragraph("<a href=\"index.php?page=viewreserv&amp;operation=2&amp;id_salle=".$_REQUEST["id_salle"]."\">Afficher les réservations pour les sechages</a>");
  }

  $cts->add_paragraph("<a href=\"index.php\">Créneaux déjà réservés</a>");

  $site->add_contents($cts);
  $site->end_page();

  exit();
}
elseif ( $_REQUEST["action"] == "searchmc" )
{
  $site->start_page("services","Laverie");
  $cts = new contents("<a href=\"index.php\">Laverie</a> / ".$salles[$_REQUEST["id_salle"]]." / Recherche");

  $extraurl="";
  if ( isset($_REQUEST["fallback"]) )
    $extraurl = "&fallback=".rawurlencode($_REQUEST["fallback"]);

  if ( $_REQUEST["operation"] ==  3 )
  {
    $sql =
    "SELECT
     CONCAT(MIN(cl.id_creneau),',',MIN(cs.id_creneau)) AS id_creneau,
     cl.debut_creneau, SUBTIME(cl.fin_creneau,'00:00:01') AS fin_creneau,
     'Choisir' AS texte
     FROM mc_creneaux AS cl
     INNER JOIN mc_machines AS ml ON ( cl.id_machine = ml.id AND ml.type='laver' )
     INNER JOIN mc_creneaux AS cs ON ( cs.debut_creneau = cl.fin_creneau )
     INNER JOIN mc_machines AS ms ON ( cs.id_machine = ms.id AND ms.type='secher' )
     WHERE ml.loc='".mysql_real_escape_string($_REQUEST["id_salle"])."'
     AND ms.loc='".mysql_real_escape_string($_REQUEST["id_salle"])."'
     AND cs.id_utilisateur IS NULL
     AND cl.id_utilisateur IS NULL
     AND cl.debut_creneau > NOW()";

    $pl = new weekplanning ( "Selectionner un creneau", $site->db, $sql, "id_creneau", "cl.debut_creneau", "cl.fin_creneau", "texte", "index.php?action=searchmc&operation=".$_REQUEST["operation"]."&id_salle=".$_REQUEST["id_salle"].$extraurl, "index.php?page=reserver".$extraurl, "GROUP BY cl.debut_creneau" );
    $cts->add($pl,true);
  }
  else
  {
    $type = $_REQUEST["operation"] ==  1 ? 'laver' : 'secher';

    $sql =
    "SELECT
     id_creneau,
     debut_creneau, SUBTIME(fin_creneau,'00:00:01') as fin_creneau,
     'Choisir' AS texte
     FROM mc_creneaux
     INNER JOIN mc_machines ON ( mc_creneaux.id_machine = mc_machines.id  )
     WHERE mc_machines.type='".mysql_real_escape_string($type)."'
     AND mc_machines.loc='".mysql_real_escape_string($_REQUEST["id_salle"])."'
     AND id_utilisateur IS NULL
     AND debut_creneau > NOW()";

    $pl = new weekplanning ( "Selectionner un creneau", $site->db, $sql, "id_creneau", "debut_creneau", "fin_creneau", "texte", "index.php?action=searchmc&operation=".$_REQUEST["operation"]."&id_salle=".$_REQUEST["id_salle"].$extraurl, "index.php?page=reserver".$extraurl, "GROUP BY debut_creneau" );
    $cts->add($pl,true);
  }



  $frm = new form("searchmc","index.php",false,"POST","Nouvelle recherche");
  $frm->add_hidden("action","searchmc");
  if ( isset($_REQUEST["fallback"]) )
    $frm->add_hidden("fallback",$_REQUEST["fallback"]);
  $frm->add_select_field("id_salle","Lieu",$salles, $_REQUEST["id_salle"]);
  $frm->add_select_field("operation","Machines désirées",array(3=>"Lavage et sechage",1=>"Lavage seulement",2=>"Sechage seulement"));
  $frm->add_submit("search","Rechercher un créneau");
  $cts->add($frm,true);

  if ( $is_admin )
  {
    $cts->add_paragraph("<a href=\"admin.php\">Administration</a>");
    $cts->add_paragraph("<a href=\"index.php?page=viewreserv&amp;operation=1&amp;id_salle=".$_REQUEST["id_salle"]."\">Afficher les réservations pour les lavages</a>");
    $cts->add_paragraph("<a href=\"index.php?page=viewreserv&amp;operation=2&amp;id_salle=".$_REQUEST["id_salle"]."\">Afficher les réservations pour les sechages</a>");
  }

  $cts->add_paragraph("<a href=\"index.php\">Créneaux déjà réservés</a>");

  $site->add_contents($cts);
  $site->end_page();

  exit();
}


$site->add_css("css/doku.css");
$site->start_page("services","Laverie");
$cts = new contents("<a href=\"index.php\">Laverie</a>");

$page = new page($site->db);
$page->load_by_pagename("activites:laverie");
$cts->add($page->get_contents());
$cts->add_paragraph("");

$cts->add_title(1, "Réservation de créneaux");

$frm = new form("searchmc","index.php",false,"POST","Reserver un creneau");
$frm->add_hidden("action","searchmc");
$frm->add_select_field("id_salle","Lieu",$salles, $_REQUEST["id_salle"]);
$frm->add_select_field("operation","Machines désirées",array(3=>"Lavage et sechage",1=>"Lavage seulement",2=>"Sechage seulement"));
$frm->add_submit("search","Rechercher un créneau");
$cts->add($frm, true);

$sql = new requete($site->db,"SELECT id_creneau, debut_creneau, fin_creneau, lettre, type, mc_machines.loc AS id_salle, nom_jeton
      FROM mc_creneaux
      INNER JOIN mc_machines ON mc_creneaux.id_machine = mc_machines.id
      LEFT JOIN mc_jeton ON mc_creneaux.id_jeton = mc_jeton.id_jeton
      WHERE mc_creneaux.id_utilisateur = '".$site->user->id."'
      AND fin_creneau > '".date("Y-m-d H").":00:00'
      ORDER BY debut_creneau, lettre");

$tbl = new sqltable("lstcrfutur",
  "Liste des créneaux réservés",
  $sql,
  "index.php",
  "id_creneau",
  array(
    "debut_creneau" => "Début du créneau",
    "fin_creneau" => "Fin du créneau",
    "lettre" => "Lettre",
    "type" => "Type de la machine",
    "id_salle" => "Lieu",
    "nom_jeton" => "Jeton à utiliser"),
  array("delete" => "Annuler la réservation"),
  array(),
  array("type"=>$GLOBALS['types_machines'],"id_salle"=>$salles) );

$cts->add($tbl, true);

//TODO: liste des jetons empruntés ?

if ( $is_admin )
  $cts->add_paragraph("<a href=\"admin.php\">Administration</a>");

$site->add_contents($cts);
$site->end_page();

?>
