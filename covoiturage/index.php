<?php
/**
 * @brief L'accueil du covoiturage
 *
 */

/* Copyright 2006 - 2007
 * Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des étudiants de
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

require_once($topdir . "include/site.inc.php");
require_once($topdir . "include/pgsqlae.inc.php");
require_once($topdir . "include/entities/ville.inc.php");
require_once($topdir . "include/entities/trajet.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");


$site = new site();

$site->start_page ("services", "Covoiturage - Accueil");

if ($site->user->id <= 0)
{
  $site->error_forbidden("services");
  exit();

}

$accueil = new contents("Accueil - Covoiturage",
      "Bienvenue sur le système de covoiturage de l'AE.<br/><br/>");


/* "Mes trajets proposés" */

$sql = new requete($site->db, "SELECT
                                      `id_trajet`
                               FROM
                                      `cv_trajet`
                               WHERE
                                      `id_utilisateur` = ".$site->user->id .  "
                               ORDER BY
                                       `id_trajet`");
if ($sql->lines)
{

  $trajet = new trajet($site->db);
  $usrtrj = new utilisateur($site->db);

  $idtrjs = array();

  while ($res = $sql->get_row())
    {
      $idtrjs[] = $res['id_trajet'];

      $trajet->load_by_id($res['id_trajet']);
      $trajet->load_steps();


      if ($trajet->has_expired())
  $mytrj[] = "<a href=\"./gerer.php?id_trajet=".$trajet->id."\">Trajet ". $trajet->ville_depart->nom .
    " / " . $trajet->ville_arrivee->nom . "<b> - TRAJET EXPIRE (cliquez pour ajouter une date)</b></a>";
      else
  {
    $str = "<a href=\"./gerer.php?id_trajet=".$trajet->id."\">Trajet ". $trajet->ville_depart->nom .
      " / " . $trajet->ville_arrivee->nom;

    if ($trajet->has_pending_steps())
      $str .= "<b> - ETAPES EN ATTENTE DE VALIDATION !</b>";

    $str .= "</a>";

    $mytrj[] = $str;
  }
    }

  if (count($mytrj) > 0)
    {
      $accueil->add_title(2, "Mes trajets ponctuels proposés");
      $accueil->add_paragraph("Cliquez sur un lien ci-dessous pour passer sur la page de gestion du trajet concerné.");
      $mytrjs = new itemlist(false, false, $mytrj);
      $accueil->add($mytrjs);
    }


  $idtrjs = implode(",", $idtrjs);
  $req = new requete($site->db, "SELECT `id_trajet` FROM `cv_trajet_etape`
                                 WHERE `id_trajet` IN (".$idtrjs.") AND `accepted_etape` = '0'");

}



/* mes "étapes" proposées */

$req = new requete($site->db, "SELECT
                                       *
                               FROM
                                       `cv_trajet_etape`
                               INNER JOIN
                                       `cv_trajet`
                               USING (`id_trajet`)
                               WHERE
                                       `cv_trajet_etape`.`id_utilisateur` = " . $site->user->id);


if ($req->lines > 0)
{
  $trajet = new trajet($site->db);

  while ($rs = $req->get_row())
    {
      if ($rs['accepted_etape'] == 2)
  $state = "Refusé";
      else if ($rs['accepted_etape'] == 1)
  $state = "Acceptée";
      else if ($rs['accepted_etape'] == 3)
  $state = "DATE DE TRAJET SUPPRIMEE";
      else
  $state = "En attente";


      $trajet->load_by_id($rs['id_trajet']);

      $desc = "Trajet ".$trajet->ville_depart->nom." / ".
  $trajet->ville_arrivee->nom;

      $date = HumanReadableDate($rs['trajet_date'], "", false, true);

      $stepsarr[] = array("id" => $rs['id_etape'].','.$rs['id_trajet'].','.$rs['trajet_date'],
        "description" => $desc,
        "date" => $date ,
        "state" => $state);

    }


  $accueil->add_title(2, "Mes étapes");

  $accueil->add_paragraph("Cette partie liste les étapes sur les trajets que vous ".
        "souhaitez rejoindre, ainsi que l'état d'acceptation des étapes");

  $accueil->add(new sqltable("mysteps", "Mes étapes", $stepsarr, "./details.php", "id",
           array("description" => "Description du trajet",
           "date" => "Date",
           "state" => "Etat de la demande"),
           array("delete" => "supprimer", "view" => "Voir"), array()));

} // fin "mes étapes"

/* options */
$accueil->add_title(2, "Autres options");

$opts[] = "<a href=\"./propose.php\">Proposer un trajet</a>";

$opts[] = "<a href=\"./search.php\">Rechercher un trajet</a>";

$options = new itemlist(false, false, $opts);
$accueil->add($options);


$site->add_contents ($accueil);


/* fin page */
$site->end_page ();
?>
