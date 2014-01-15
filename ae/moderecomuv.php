<?php
/* Copyright 2011
 *
 * - Mathieu Briand < briandmathieu at hyprua dot org >
 *
 * Ce fichier fait partie du site de l'Association des Ãtudiants de
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
require_once($topdir . "include/cts/sqltable.inc.php");

$site = new site ();

if (!$site->user->is_in_group ("gestion_ae"))
  $site->error_forbidden("accueil");

$site->start_page ("services", "Modération des commentaires d'uv");


/* presentation des affiches en attente de moderation */
$req = new requete($site->db, "SELECT `id_commentaire`, `code`,
        CONCAT(`utilisateurs`.`prenom_utl`, ' ', `utilisateurs`.`nom_utl`) AS `nom_utilisateur`
      FROM `pedag_uv_commentaire`
      LEFT JOIN `pedag_uv` USING (`id_uv`)
      LEFT JOIN `utilisateurs` USING (`id_utilisateur`)
      WHERE `valid`='0'
      ORDER BY `code`");

$modhelp = new contents("Mod&eacute;ration des commentaires d'uv",
      "<p>Sur cette page, vous pouvez voir les commentaires d'uv signalés comme étant abusifs.</p>");

$tabl = new sqltable ("moderecomuv_list",
    "Commentaires en attente de mod&eacute;ration",
    $req,
    "../pedagogie/uv.php?view=commentaires",
    "id_commentaire",
    array ("code" => "UV",
           "nom_utilisateur" => "Auteur"),
    array("view"=>"Voir le commentaire"),
    array (),
    array (),
    true, true, array(), "#cmt_");

$modhelp->add ($tabl);
$site->add_contents ($modhelp);


$site->end_page ();

?>
