<?
/** @file
 *
 * @brief Gestion du planning par semestre, fourni par
 *  le SME
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


if (! $site->user->is_in_group("gestion_ae"))
{
  $site->error_forbidden("services");
  exit();

}



$cts = new contents("Site de l'AE - Espace Pédagogie - ".
        "Gestion du planning");


$cts->add_paragraph("Cette page est réservée au groupe gestion-ae.".
        " Elle permet de modifier / d'entrer le planning ".
        "du semestre courant, tel qu'il nous est fourni par ".
        "les Services des Moyens de l'Enseignement");

$form = new form('add_evt', 'planning.php?action=add_evt');

$form->add_select_field('addevt_type', "Type d'événement",
      array(0 => "Semaine A",
            1 => "Semaine B",
            2 => "Examen d'espagnol (Cervantès)",
            3 => "Examen d'anglais (TOEIC)",
            4 => "Examen d'allemand (Goethe)",
            5 => "Remise des diplomes",
            6 => "Vacances",
            7 => "Examens médians",
            8 => "Examens finaux",
            9 => "Examen médian lié à une UV",
            10 => "Examen final lié à une UV",
            11 => "Tables rondes TC",
            12 => "Soutenances",
            13 => "Journées Portes Ouvertes",
            14 => "Pré-rentrée",
            15 => "Rentrée",
            16 => "Premier jury de suivi des études",
            17 => "Deuxième jury de suivi des études",
            18 => "Date limite de résultat aux UVs",
            19 => "Activités d'intersemestre",
            99 => "Semestre (dates)"), 0);


$form->add_date_field('addevt_datedeb', "Date de début", -1, true);
$form->add_date_field('addevt_datefin', "Date de fin", -1, true);

/* a définir ... je crois que je vais faire du javascript ici */
$form->add_entity_smartselect('addevt_entity', "Entité liée éventuelle", new uv($site->db));



$form->add_submit('addevt_submit', "Ajouter");
$cts->add($form);


$site->add_contents($cts);

$site->end_page();

?>
