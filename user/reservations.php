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
require_once($topdir. "include/entities/sitebat.inc.php");
require_once($topdir. "include/entities/batiment.inc.php");
require_once($topdir. "include/entities/salle.inc.php");
require_once($topdir. "include/entities/asso.inc.php");

$site = new site ();

$site->allow_only_logged_users("matmatronch");

$user = new utilisateur($site->db);
$userop = new utilisateur($site->db);
$sitebat = new sitebat($site->db);
$bat = new batiment($site->db);
$salle = new salle($site->db);
$asso = new asso($site->db);
$resa = new reservation($site->db, $site->dbrw);

if ( isset($_REQUEST["id_salres"]) )
{
	$resa->load_by_id($_REQUEST["id_salres"]);
	if ( $resa->id < 1 )
    $site->error_not_found("matmatronch");

	$asso->load_by_id($resa->id_asso);
	$can_edit = $site->user->is_in_group("gestion_ae") || ($resa->id_utilisateur == $site->user->id);

	if ( $asso->id > 0 )
		$can_edit = $can_edit || $asso->is_member_role($site->user->id,ROLEASSO_MEMBREBUREAU);

}

if ( $_REQUEST["action"] == "delete" && $can_edit )
{
	$id_salle = $resa->id_salle;
	$resa->delete();
	$resa->id=-1;
}

if ( isset($_REQUEST['id_utilisateur']) )
{
	$user = new utilisateur($site->db,$site->dbrw);

	$user->load_by_id($_REQUEST["id_utilisateur"]);
	if ( !$user->is_valid() )
    $site->error_not_found("matmatronch");

	if ( $user->id != $site->user->id && !$site->user->is_in_group("gestion_ae") )
		$site->error_forbidden("matmatronch","group",1);

}
else
	$user = &$site->user;

$site->start_page("matmatronch","Mes reservations de salles");

$cts = new contents( $user->prenom . " " . $user->nom );

$cts->add(new tabshead($user->get_tabs($site->user),"resa"));

$cts->add_paragraph("<a href=\"".$topdir."salle.php?page=reservation\">Nouvelle reservation</a>");

$req = new requete($site->db,"SELECT  " .
		"`date_demande_res`, " .
		"sl_salle.id_salle, sl_salle.nom_salle," .
		"asso.id_asso, asso.nom_asso," .
		"sl_reservation.id_salres,  sl_reservation.date_debut_salres," .
		"sl_reservation.date_fin_salres, sl_reservation.description_salres, " .
		"sl_reservation.date_accord_res," .
		"(sl_reservation.convention_salres*10+sl_salle.convention_salle) as `convention` " .
		"FROM sl_reservation " .
		"INNER JOIN sl_salle ON sl_salle.id_salle=sl_reservation.id_salle " .
		"LEFT JOIN asso ON asso.id_asso=sl_reservation.id_asso " .
		"WHERE sl_reservation.id_utilisateur='".$user->id."' AND " .
		"((sl_reservation.date_accord_res IS NULL) OR " .
		"(sl_salle.convention_salle=1 AND sl_reservation.convention_salres=0)) " .
		"AND sl_reservation.date_debut_salres > NOW() " .
		"ORDER BY date_debut_salres");

$cts->add(new sqltable(
		"modereres",
		"En attente (de validation ou de convention)", $req, "reservations.php",
		"id_salres",
		array("nom_asso"=>"Association",
			"nom_salle"=>"Salle",
			"date_debut_salres"=>"De",
			"date_fin_salres"=>"A",
			"description_salres" => "Motif",
			"convention"=>"Conv.",
			"date_demande_res"=>"Demandé le"
			),
		array("delete"=>"Annuler"),
		array(),
		array("convention"=>array(0=>"Non requise",1=>"A faire",11=>"Faite") )
		),true);

$req = new requete($site->db,"SELECT `utilisateurs`.`id_utilisateur` as `id_utilisateur_op`, " .
		"CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur_op`, " .
		"`date_accord_res`, " .
		"sl_salle.id_salle, sl_salle.nom_salle," .
		"asso.id_asso, asso.nom_asso," .
		"sl_reservation.id_salres,  sl_reservation.date_debut_salres," .
		"sl_reservation.date_fin_salres, sl_reservation.description_salres, " .
		"sl_reservation.date_accord_res," .
		"(sl_reservation.convention_salres*10+sl_salle.convention_salle) as `convention` " .
		"FROM sl_reservation " .
		"INNER JOIN utilisateurs ON `utilisateurs`.`id_utilisateur`=sl_reservation.id_utilisateur_op " .
		"INNER JOIN sl_salle ON sl_salle.id_salle=sl_reservation.id_salle " .
		"LEFT JOIN asso ON asso.id_asso=sl_reservation.id_asso " .
		"WHERE sl_reservation.id_utilisateur='".$user->id."' AND " .
		"!((sl_reservation.date_accord_res IS NULL) OR " .
		"(sl_salle.convention_salle=1 AND sl_reservation.convention_salres=0)) " .
		"AND sl_reservation.date_debut_salres > NOW() " .
		"ORDER BY date_debut_salres");

$cts->add_paragraph("<a href=\"".$topdir."wiki2/?name=guide_resp:gestion\">Article sur les conventions de locaux.</a>");

$cts->add(new sqltable(
		"modereres",
		"Reservations validés", $req, "reservations.php",
		"id_salres",
		array("nom_asso"=>"Association",
			"nom_salle"=>"Salle",
			"date_debut_salres"=>"De",
			"date_fin_salres"=>"A",
			"description_salres" => "Motif",
			"convention"=>"Conv.",
			"date_accord_res"=>"Accord le",
			"nom_utilisateur_op"=>"donné par"
			),
		array("delete"=>"Annuler"),
		array(),
		array("convention"=>array(0=>"Non requise",1=>"A faire",11=>"Faite") )
		),true);

$site->add_contents($cts);
$site->end_page();
?>
