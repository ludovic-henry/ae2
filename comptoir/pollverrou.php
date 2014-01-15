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
require_once("include/comptoirs.inc.php");

$site = new sitecomptoirs();
$usr = new utilisateur(null);
$vp = new venteproduit ($site->db, $site->dbrw);

$sql = new requete($site->db, "SELECT * FROM `cpt_verrou` WHERE DATEDIFF(NOW(),`date_res`) > 2");

while ( $row = $sql->get_row() )
{
  $usr->id = $row["id_utilisateur"];
  $vp->load_by_id($row["id_produit"],$row["id_comptoir"]);
  $vp->debloquer ( $usr, $row["quantite"] );
}

?>
