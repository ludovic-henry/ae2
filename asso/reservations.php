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
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/entities/objet.inc.php");

$site = new site ();
$asso = new asso($site->db,$site->dbrw);
$asso->load_by_id($_REQUEST["id_asso"]);
if ( $asso->id < 1 )
{
  $site->error_not_found("services");
  exit();
}
if ( !$site->user->is_in_group("gestion_ae") && !$asso->is_member_role($site->user->id,ROLEASSO_MEMBREBUREAU) )
  $site->error_forbidden("presentation");

$site->start_page("presentation",$asso->nom);

$cts = new contents($asso->get_html_path());

$cts->add(new tabshead($asso->get_tabs($site->user),"res"));

$cts->add_paragraph("<a href=\"../salle.php?page=reservation&amp;id_asso=".$asso->id."\">Nouvelle reservation de salle</a>");
$cts->add_paragraph("<a href=\"../emprunt.php?page=reservation&amp;id_asso=".$asso->id."\">Nouvelle reservation de matériel</a>");

$req = new requete($site->db,"SELECT  " .
    "`date_demande_res`, " .
    "sl_salle.id_salle, sl_salle.nom_salle," .
    "sl_reservation.id_salres,  sl_reservation.date_debut_salres," .
    "sl_reservation.date_fin_salres, sl_reservation.description_salres, " .
    "sl_reservation.date_accord_res," .
    "(sl_reservation.convention_salres*10+sl_salle.convention_salle) as `convention` " .
    "FROM sl_reservation " .
    "INNER JOIN sl_salle ON sl_salle.id_salle=sl_reservation.id_salle " .
    "WHERE sl_reservation.id_asso='".$asso->id."' AND " .
    "((sl_reservation.date_accord_res IS NULL) OR " .
    "(sl_salle.convention_salle=1 AND sl_reservation.convention_salres=0)) " .
    "AND sl_reservation.date_debut_salres > NOW() " .
    "ORDER BY date_debut_salres");
if ( $req->lines )
{
$cts->add(new sqltable(
    "modereres",
    "Reservation de salle en attente (de validation ou de convention)", $req, "reservations.php?id_asso=".$_REQUEST['id_asso'],
    "id_salres",
    array(
      "nom_salle"=>"Salle",
      "date_debut_salres"=>"De",
      "date_fin_salres"=>"A",
      "description_salres" => "Motif",
      "convention"=>"Conv.",
      "date_demande_res"=>"Demandé le"
      ),
    array("info"=>"Details","delete"=>"Annuler"),
    array(),
    array("convention"=>array(0=>"Non requise",1=>"A faire",11=>"Faite") )
    ),true);

$cts->add_paragraph("<a href=\"".$topdir."wiki2/?name=guide_resp:gestion\">Article sur les conventions de locaux.</a>");
}

$req = new requete($site->db,"SELECT inv_emprunt.*, IF(etat_emprunt=0,'Non fixé',caution_emp/100) AS caution  " .
    "FROM inv_emprunt " .
    "WHERE id_asso='".$asso->id."' AND etat_emprunt<=1 " .
    "ORDER BY etat_emprunt,date_debut_emp");
if ( $req->lines )
$cts->add(new sqltable(
    "attenteemprunt",
    "Reservations de matériel en attente", $req, "../emprunt.php",
    "id_emprunt",
    array(
      "id_emprunt"=>"N° d'emprunt",
      "date_debut_emp"=>"De",
      "date_fin_emp"=>"Au",
      "caution"=>"Caution",
      "etat_emprunt"=>"Etat",
      "notes_emprunt"=>"Notes"
      ),
    array("info"=>"Details","delete"=>"Annuler"),
    array(),
    array("etat_emprunt"=>$EmpruntObjetEtats)
    ),true);


$req = new requete($site->db,"SELECT `utilisateurs`.`id_utilisateur` as `id_utilisateur_op`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur_op`, " .
    "`date_accord_res`, " .
    "sl_salle.id_salle, sl_salle.nom_salle," .
    "sl_reservation.id_salres,  sl_reservation.date_debut_salres," .
    "sl_reservation.date_fin_salres, sl_reservation.description_salres, " .
    "sl_reservation.date_accord_res," .
    "(sl_reservation.convention_salres*10+sl_salle.convention_salle) as `convention` " .
    "FROM sl_reservation " .
    "INNER JOIN utilisateurs ON `utilisateurs`.`id_utilisateur`=sl_reservation.id_utilisateur_op " .
    "INNER JOIN sl_salle ON sl_salle.id_salle=sl_reservation.id_salle " .
    "WHERE sl_reservation.id_asso='".$asso->id."' AND " .
    "!((sl_reservation.date_accord_res IS NULL) OR " .
    "(sl_salle.convention_salle=1 AND sl_reservation.convention_salres=0)) " .
    "AND sl_reservation.date_debut_salres > NOW() " .
    "ORDER BY date_debut_salres");

if ( $req->lines )
$cts->add(new sqltable(
    "modereres",
    "Reservations de salle validés", $req, "reservations.php?id_asso=".$_REQUEST['id_asso'],
    "id_salres",
    array(
      "nom_salle"=>"Salle",
      "date_debut_salres"=>"De",
      "date_fin_salres"=>"A",
      "description_salres" => "Motif",
      "convention"=>"Conv.",
      "date_accord_res"=>"Accord le",
      "nom_utilisateur_op"=>"donné par"
      ),
    array("info"=>"Details","delete"=>"Annuler"),
    array(),
    array("convention"=>array(0=>"Non requise",1=>"A faire",11=>"Faite") )
    ),true);


$req = new requete($site->db,"SELECT inv_emprunt.*, " .
    "`inv_objet`.`id_objet`," .
    "CONCAT(`inv_objet`.`nom_objet`,' ',`inv_objet`.`cbar_objet`) AS `nom_objet`, " .
    "`inv_type_objets`.`id_objtype`,`inv_type_objets`.`nom_objtype` " .

    "FROM inv_emprunt_objet " .
    "INNER JOIN inv_emprunt ON inv_emprunt_objet.id_emprunt=inv_emprunt.id_emprunt " .
    "INNER JOIN inv_objet ON inv_emprunt_objet.id_objet=inv_objet.id_objet " .
    "INNER JOIN inv_type_objets ON inv_objet.id_objtype=inv_type_objets.id_objtype " .

    "WHERE inv_emprunt.id_asso='".$asso->id."' AND (inv_emprunt.etat_emprunt > 1) AND inv_emprunt_objet.retour_effectif_emp IS NULL " .
    "ORDER BY inv_emprunt.date_fin_emp");

if ( $req->lines )
$cts->add(new sqltable("listobjets",
    "Objets empruntés", $req, "../emprunt.php",
    "id_emprunt",
    array("id_emprunt"=>"N° d'emprunt","nom_objet"=>"Objet","date_fin_emp"=>"A rendre le"),
    array("view"=>"Information sur l'emprunt"), array(), array()
    ),true);

$site->add_contents($cts);
$site->end_page();
?>
