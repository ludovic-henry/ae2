<?
/** @file
 *  Rendu d'un trajet.
 *
 */

/* Copyright 2007
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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

session_start ();

$topdir = "../";

include($topdir. "include/site.inc.php");

require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/cts/imgloc.inc.php");
require_once($topdir. "include/entities/pays.inc.php");
require_once($topdir. "include/entities/ville.inc.php");
require_once($topdir. "include/entities/trajet.inc.php");
require_once($topdir. "include/entities/lieu.inc.php");


$db = new mysqlae ();

$level = IMGLOC_COUNTRY;

if (isset($_REQUEST['level']))
{
  $level = intval($_REQUEST['level']);
}

$img = new imgloc(400, $level, $db, new pgsqlae());

$trajet = new trajet($db);
$trajet->load_by_id($_REQUEST['id_trajet']);

$img->add_step_by_idville($trajet->ville_depart->id, false);

if (isset($_REQUEST['date']))
{
  $etapes = $trajet->get_steps_by_date($_REQUEST['date']);


  if (is_array($etapes) && (count($etapes)))
    {
      foreach ($etapes as $etape)
	{
	  $idville = $etape['ville'];

	  if ($_REQUEST['hlstp'] == $etape['id'])
	    {
	      $img->add_step_by_idville($idville, false, true);
	      continue;
	    }

	  if ($etape['etat'] == 1)
	    $img->add_step_by_idville($idville, false);
	  else if ($_REQUEST['id_etape'] == $etape['id'])
	    $img->add_step_by_idville($idville, false, true);
	}
    }
}

$img->add_step_by_idville($trajet->ville_arrivee->id, false);


$img->add_context();

$img = $img->generate_img();


require_once ($topdir . "include/watermark.inc.php");
$wm_img = new img_watermark ($img->imgres);

$wm_img->output();

?>
