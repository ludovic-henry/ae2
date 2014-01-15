<?php
/** @file
 *
 * @brief Page de résultat aux UVs de la partie pédagogique du site de
 * l'AE.
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
 * along with this program; if not, write to the Free Sofware
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "../";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/entities/uv.inc.php");


$site = new site();

  $site->redirect("/pedagogie/");

$site->add_box("uvsmenu", get_uvsmenu_box() );
$site->set_side_boxes("left",array("uvsmenu", "connexion"));

$site->start_page("services", "AE - Pédagogie");

$path = "<a href=\"".$topdir."uvs/\"><img src=\"".
$topdir."images/icons/16/lieu.png\" class=\"icon\" />  Pédagogie </a>";

$path .= "/" . " Résultats aux examens";
$cts = new contents($path);


$cts->add_paragraph("Résultats aux examens");

if ($site->user->utbm)
{
  if (! isset($_REQUEST['inesbmt']))
  {
    $form = new form ("ineform", "./resuv.php", true);

    $form->add_text_field('ine_etu',
			  'Numéro INE', false);
    $form->add_submit('inesbmt', 'Valider');
    $cts->add($form);
  }
  else
  {
    $result = get_results(strtoupper($site->user->nom),
			  $_REQUEST['ine_etu']);
    $cts->add_paragraph("<pre>" . print_r($result, true) . "</pre>");
  }
}

else
{
  $cts->add_paragraph("Pour accéder à cette partie, il est nécessaire ".
		      "d'être étudiant UTBM.");
}


$site->add_contents($cts);
$site->end_page();

?>
