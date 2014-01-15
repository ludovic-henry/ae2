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
require_once($topdir. "include/entities/sitebat.inc.php");
require_once($topdir. "include/entities/batiment.inc.php");
require_once($topdir. "include/entities/salle.inc.php");
$site = new site ();
$emp = new emprunt ( $site->db, $site->dbrw );
$asso = new asso($site->db);
$user = new utilisateur($site->db);

$site->allow_only_logged_users("matmatronch");

if ( isset($_REQUEST['id_utilisateur']) )
{
	$user = new utilisateur($site->db,$site->dbrw);
	$user->load_by_id($_REQUEST["id_utilisateur"]);
	if ( !$user->is_valid() )
    $site->error_not_found("matmatronch");

	if ( !( $user->id==$site->user->id || $site->user->is_in_group("gestion_ae") ) )
		$site->error_forbidden("matmatronch","group",1);
}
else
{
	$user = &$site->user;
	$can_edit = true;
}


$site->start_page("matmatronch", $user->prenom . " " . $user->nom );

$cts = new contents ( $user->prenom . " " . $user->nom );

$cts->add(new tabshead($user->get_tabs($site->user),"emp"));

$cts->add_paragraph("<a href=\"../emprunt.php?page=reservation\">Nouvelle reservation de matériel</a>");

$req = new requete($site->db,"SELECT inv_emprunt.*, " .
		"IF(etat_emprunt=0,'Non fixé',caution_emp/100) AS caution," .
		"asso.nom_asso, asso.id_asso  " .
		"FROM inv_emprunt " .
		"LEFT JOIN asso ON inv_emprunt.id_asso=asso.id_asso " .
		"WHERE id_utilisateur='".$user->id."' AND etat_emprunt<=1 " .
		"ORDER BY etat_emprunt,date_debut_emp");

$cts->add(new sqltable(
		"attenteemprunt",
		"Reservations de matériel en attente", $req, "../emprunt.php",
		"id_emprunt",
		array(
			"id_emprunt"=>"N° d'emprunt",
			"date_debut_emp"=>"De",
			"date_fin_emp"=>"Au",
			"nom_asso"=>"Pour",
			"caution"=>"Caution",
			"etat_emprunt"=>"Etat",
			"notes_emprunt"=>"Notes"
			),
		array("info"=>"Details","delete"=>"Annuler"),
		array(),
		array("etat_emprunt"=>$EmpruntObjetEtats)
		),true);

$req = new requete($site->db,"SELECT inv_emprunt.*, " .
		"`inv_objet`.`id_objet`," .
		"CONCAT(`inv_objet`.`nom_objet`,' ',`inv_objet`.`cbar_objet`) AS `nom_objet`, " .
		"`inv_type_objets`.`id_objtype`,`inv_type_objets`.`nom_objtype`, " .
		"asso.nom_asso, asso.id_asso  " .
		"FROM inv_emprunt_objet " .
		"INNER JOIN inv_emprunt ON inv_emprunt_objet.id_emprunt=inv_emprunt.id_emprunt " .
		"INNER JOIN inv_objet ON inv_emprunt_objet.id_objet=inv_objet.id_objet " .
		"INNER JOIN inv_type_objets ON inv_objet.id_objtype=inv_type_objets.id_objtype " .
		"LEFT JOIN asso ON inv_emprunt.id_asso=asso.id_asso " .
		"WHERE inv_emprunt.id_utilisateur='".$user->id."' AND (inv_emprunt.etat_emprunt > 1) AND inv_emprunt_objet.retour_effectif_emp IS NULL " .
		"ORDER BY inv_emprunt.date_fin_emp");

$cts->add(new sqltable("listobjets",
		"Objets empruntés", $req, "emprunt.php",
		"id_emprunt",
		array("id_emprunt"=>"N° d'emprunt","nom_objet"=>"Objet","nom_asso"=>"Pour","date_fin_emp"=>"A rendre le"),
		array("view"=>"Information sur l'emprunt"), array(), array()
		),true);

$site->add_contents($cts);

$site->end_page();
?>
