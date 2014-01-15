<?php
/**
 * @brief Covoiturage - Recherche d'un trajet.
 *
 */

/* Copyright 2007
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


$site = new site();

$site->start_page ("services", "Covoiturage - Recherche");



$accueil = new contents("Recherche", "");

$accueil->add_title(1, "Trajets ponctuels proposés : ");

$sql = new requete($site->db, "SELECT
                                      `id_trajet`
                               FROM
                                      `cv_trajet`
                               INNER JOIN
                                      `cv_trajet_date`
                               USING (`id_trajet`)
                               WHERE
                                      `trajet_date` >= DATE_FORMAT(NOW(), '%Y-%m-%d')
                               AND
                                       `type_trajet` = 0
                               GROUP BY
                                       `id_trajet`
                               ORDER BY
                                       `id_trajet` DESC");

if ($sql->lines > 0)
{
  $trajet = new trajet($site->db);
  $propusr = new utilisateur($site->db);


  while ($req = $sql->get_row())
    {
      $trajet->load_by_id($req['id_trajet']);
      $propusr->load_by_id($trajet->id_utilisateur);
      $trj = "Trajet ". $trajet->ville_depart->nom .
	" / " . $trajet->ville_arrivee->nom .
	", par " . $propusr->get_html_link();

      $accueil->add_title(3, $trj);

      $dates = array();

      foreach ($trajet->dates as $date)
	{
	  if (strtotime($date) > time())
	    $dates[] = "<a href=\"./details.php?id_trajet=".$trajet->id
	      ."&amp;date=".$date."\">Le " .
	      HumanReadableDate($date, "", false, true) . "</a>";
	}
      if (count($dates))
	$accueil->add(new itemlist(false, false, $dates));


    }
}



$accueil->add_title(2, "Autres options");
$opts[] = "<a href=\"./\">Retour à la page d'accueil du covoiturage</a>";
$opts[] = "<a href=\"./propose.php\">Proposer un trajet</a>";

$options = new itemlist(false, false, $opts);
$accueil->add($options);


$site->add_contents ($accueil);


/* fin page */
$site->end_page ();
?>
