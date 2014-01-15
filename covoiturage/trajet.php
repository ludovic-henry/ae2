<?php
/**
 * @brief Creation d'un trajet - covoiturage
 *
 */

/* Copyright 2006
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
require_once($topdir . "include/entities/ville.inc.php");

$site = new site();

$site->start_page ("services", "Creation d'un trajet");
$accueil = new contents("Covoiturage",
      "<p>Bienvenue sur la page du covoiturage, "
      ."<br/><strong>AE-UTBM - Recherche & Dev.".
      "</strong></p>");

$site->add_contents ($accueil);

if (isset($_REQUEST['reset']))
{
  unset($_SESSION['trajet']);
}


/* formulaire envoye */
if (isset($_REQUEST['submit']))
{
  if ((isset($_REQUEST['start'])) && ($_REQUEST['start'] != 0))
    $_SESSION['trajet']['start'] = intval($_REQUEST['start']);

  if ((isset($_REQUEST['etape'])) && ($_REQUEST['etape'] !== 0))
    $_SESSION['trajet']['etapes'][] = intval($_REQUEST['etape']);

  if ((isset($_REQUEST['stop'])) && ($_REQUEST['stop'] != 0))
    $_SESSION['trajet']['stop'] = intval($_REQUEST['stop']);
}

$ville = new ville($site->db);


/* formulaire */
$frm = new form ("trip",
     "trajet.php",
     true);

if (!isset($_SESSION['trajet']['start']))
{
     $frm->add_entity_smartselect("start", "Ville de départ", $ville);
}

$infos = "<p><h3>Etapes actuelles</h3><br/>
          <ul>\n";
if (count($_SESSION['trajet']['etapes']))
{
  $villeaff = new ville($site->db);

  foreach ($_SESSION['trajet']['etapes'] as $id_etape)
    {
      $villeaff->load_by_id($id_etape);
      $infos .= ("<li>". $villeaff->nom . "</li>\n");
    }
}

$infos .= "</ul>\n</p>\n";

$frm->add_info ($infos);

$frm->add_entity_smartselect("etape", "Etape", $ville);


if (!isset($_SESSION['trajet']['stop']))
{
  $frm->add_entity_smartselect("stop", "Arrivée", $ville);
}

$frm->add_submit ("submit",
      "Envoyer");

$frm->add_submit ("reset",
      "Effacer le trajet");


/* carte */
$carte = new contents ("Trajet",
           "<img src=\"./generate.php\" alt=\"carte\" />");



$site->add_contents ($frm);
$site->add_contents ($carte);

/* fin page */
$site->end_page ();
?>
