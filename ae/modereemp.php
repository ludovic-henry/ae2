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

$topdir = "../";
require_once($topdir. "include/site.inc.php");

require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/objet.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/cts/planning.inc.php");

$site = new site ();

if ( !$site->user->is_in_group("gestion_ae") )
  $site->error_forbidden("accueil");

$emp = new emprunt ( $site->db, $site->dbrw );
$asso = new asso($site->db);
$user = new utilisateur($site->db);

if ( $_REQUEST["id_emprunt"] )
{
  $emp->load_by_id($_REQUEST["id_emprunt"]);
  if ( $emp->id > 1 )
    $asso->load_by_id($emp->id_asso);
}

/*if ( $emp->id > 0 && $_REQUEST["action"] == "delete" && isset($_REQUEST["id_objet"]) && $emp->etat < EMPRUNT_PRIS )
{
  $emp->remove_object($_REQUEST["id_objet"]);
}
else*/if ( $emp->id > 0 && $_REQUEST["action"] == "delete" && (($emp->etat < EMPRUNT_RETOURPARTIEL && $site->user->is_in_group("gestion_ae")) || ($emp->etat < EMPRUNT_PRIS)) )
{
  $emp->remove_emp();
  $emp->id = -1;
}
elseif ( $emp->id > 0 && $_REQUEST["action"] == "valide" && ($emp->etat == EMPRUNT_RESERVATION ))
{
  $emp->modere ( $site->user->id, $_REQUEST["caution"], $_REQUEST["prix_emprunt"], $_REQUEST["notes"] );
  $emp->id = -1;
}
elseif ( $emp->id > 0 && $_REQUEST["action"] == "retrait" && ($emp->etat == EMPRUNT_MODERE ))
{
  $emp->retrait ( $site->user->id, $_REQUEST["caution"], $_REQUEST["prix_emprunt"], $_REQUEST["notes"] );

  $Message = new contents("Emprunt de matériel retiré.",$emp->get_html_link()." : <a href=\"".$topdir."emprunt.php?action=print&amp;id_emprunt=".$emp->id."\">Imprimer</a>");
  $emp->id = -1;
}

$site->start_page("services","Modération des emprunts de matériel");

$cts = new contents ( "Emprunts de matériel" );

$tabs = array(array("","ae/modereemp.php", "Modération"),
      array("togo","ae/modereemp.php?view=togo", "A venir"),
      array("out","ae/modereemp.php?view=out", "Matériel prété"),
      array("nan","emprunt.php", "Reserver"),
      array("nan","emprunt.php?page=retrait", "Preter"),
      array("nan","emprunt.php?page=retour", "Retour")
      );
$cts->add(new tabshead($tabs,$_REQUEST["view"]));


if ( isset($Message) )
  $cts->add($Message,true);
if ( $_REQUEST["view"] == "" )
{
  $cts->add_paragraph("<a href=\"../emprunt.php\">Reserver du matériel</a>");
  $cts->add_paragraph("<a href=\"../emprunt.php?page=retrait\">Retrait sans réservation</a> (retrait immédiat)");

  $cts->add_paragraph("Cliquez sur l'icone info pour visualiser la demande et procéder à sa modération.");

  $req = new requete($site->db,"SELECT inv_emprunt.*, " .
      "`inv_objet`.`id_objet`," .
      "CONCAT(`inv_objet`.`nom_objet`,' ',`inv_objet`.`cbar_objet`) AS `nom_objet`, " .
      "`inv_type_objets`.`id_objtype`,`inv_type_objets`.`nom_objtype`, " .
      "asso.nom_asso, asso.id_asso, " .
      "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`," .
      "`utilisateurs`.`id_utilisateur` " .
      "FROM inv_emprunt " .
      "LEFT OUTER JOIN inv_emprunt_objet ON inv_emprunt_objet.id_emprunt=inv_emprunt.id_emprunt " .
      "INNER JOIN utilisateurs ON utilisateurs.id_utilisateur=inv_emprunt.id_utilisateur " .
      "LEFT OUTER JOIN inv_objet ON inv_emprunt_objet.id_objet=inv_objet.id_objet " .
      "LEFT OUTER JOIN inv_type_objets ON inv_objet.id_objtype=inv_type_objets.id_objtype " .
      "LEFT JOIN asso ON inv_emprunt.id_asso=asso.id_asso " .
      "WHERE etat_emprunt=0 AND date_fin_emp >= NOW() " .
      "ORDER BY etat_emprunt,date_debut_emp");

  $cts->add(new sqltable(
      "attenteemprunt",
      "Reservations de matériel à modérer", $req, "modereemp.php",
      "id_emprunt",
      array(
        "id_emprunt"=>"N° d'emprunt",
        "nom_utilisateur"=>"Qui",
        "nom_objet"=>"Quoi",
        "date_debut_emp"=>"De",
        "date_fin_emp"=>"Au",
        "nom_asso"=>"Pour"
        ),
      array(),
      array(),
      array()
      ),true);
}
elseif ( $_REQUEST["view"] == "togo" )
{
  $cts->add_paragraph("<a href=\"../emprunt.php?page=retrait\">Retrait sans réservation</a> (retrait immédiat)");

  $req = new requete($site->db,"SELECT inv_emprunt.*, " .
      "asso.nom_asso, asso.id_asso," .
      "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`," .
      "`utilisateurs`.`id_utilisateur` " .
      "FROM inv_emprunt " .
      "INNER JOIN utilisateurs ON utilisateurs.id_utilisateur=inv_emprunt.id_utilisateur " .
      "LEFT JOIN asso ON inv_emprunt.id_asso=asso.id_asso " .
      "WHERE etat_emprunt=1 " .
      "ORDER BY etat_emprunt,date_debut_emp");

  $empts=array();
  while($row=$req->get_row())
  {
    $t = array();
    $t["id_emprunt"]=$row["id_emprunt"];
    $t["id_utilisateur"]=$row["id_utilisateur"];
    $t["nom_utilisateur"]=$row["nom_utilisateur"];
    $t["nom_asso"]=$row["nom_asso"];
    $t["id_asso"]=$row["id_asso"];
    $t["date_debut_emp"]=$row["date_debut_emp"];
    $t["date_fin_emp"]=$row["date_fin_emp"];
    $_req = new requete($site->db, "SELECT " .
                        "CONCAT(`inv_objet`.`nom_objet`,' ',`inv_objet`.`cbar_objet`) AS `nom_objet`, " .
                        "`inv_type_objets`.`nom_objtype` " .
                        "FROM inv_emprunt_objet " .
                        "INNER JOIN `inv_objet` ON `inv_objet`.`id_objet`=`inv_emprunt_objet`.`id_objet` " .
                        "INNER JOIN `inv_type_objets` ON `inv_objet`.`id_objtype`=`inv_type_objets`.`id_objtype` " .
                        "WHERE `inv_emprunt_objet`.`id_emprunt`='".$t["id_emprunt"]."'" );
    $detail="";
    while(list($nom,$type)=$_req->get_row())
    {
      if(empty($detail))
        $detail=$nom." (".$type.")";
      else
        $detail.=", ".$nom." (".$type.")";
    }
    $t["detail"]=$detail;
    $empts[]=$t;
  }

  $cts->add(new sqltable(
      "attenteemprunt",
      "Reservations de matériel modérés à venir",
      $empts,
      "modereemp.php?view=togo",
      "id_emprunt",
      array(
        "id_emprunt"=>"N° d'emprunt",
        "nom_utilisateur"=>"Qui",
        "date_debut_emp"=>"De",
        "date_fin_emp"=>"Au",
        "nom_asso"=>"Pour",
        "detail"=>"Détail"
        ),
      array("delete"=>"Supprimer"),
      array(),
      array()
      ),true);
}
elseif ( $_REQUEST["view"] == "out" )
{

  $cts->add_paragraph("<a href=\"../emprunt.php?page=retour\">Retour de matériel</a>");


  $req = new requete($site->db,"SELECT inv_emprunt.*, " .
      "`inv_objet`.`id_objet`," .
      "CONCAT(`inv_objet`.`nom_objet`,' ',`inv_objet`.`cbar_objet`) AS `nom_objet`, " .
      "`inv_type_objets`.`id_objtype`,`inv_type_objets`.`nom_objtype`, " .
      "asso.nom_asso, asso.id_asso, " .
      "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`," .
      "`utilisateurs`.`id_utilisateur` " .
      "FROM inv_emprunt_objet " .
      "INNER JOIN inv_emprunt ON inv_emprunt_objet.id_emprunt=inv_emprunt.id_emprunt " .
      "INNER JOIN utilisateurs ON utilisateurs.id_utilisateur=inv_emprunt.id_utilisateur " .
      "INNER JOIN inv_objet ON inv_emprunt_objet.id_objet=inv_objet.id_objet " .
      "INNER JOIN inv_type_objets ON inv_objet.id_objtype=inv_type_objets.id_objtype " .
      "LEFT JOIN asso ON inv_emprunt.id_asso=asso.id_asso " .
      "WHERE (inv_emprunt.etat_emprunt > 1) AND inv_emprunt_objet.retour_effectif_emp IS NULL " .
      "ORDER BY inv_emprunt.date_fin_emp");

  $cts->add(new sqltable("listobjets",
      "Objets empruntés", $req, "../emprunt.php",
      "id_emprunt",
      array("id_emprunt"=>"N° d'emprunt","nom_objet"=>"Objet","nom_utilisateur"=>"Qui","nom_asso"=>"Pour","date_fin_emp"=>"A rendre le"),
      array("view"=>"Information sur l'emprunt"), array(), array()
      ),true);

}

$site->add_contents($cts);

$site->end_page();

?>
