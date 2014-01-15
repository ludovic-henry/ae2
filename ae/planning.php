<?php

/* Copyright 2010
 * - Mathieu Briand < briandmathieu AT hyprua DOT org >
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
require_once($topdir."include/entities/files.inc.php");


$site = new site ();

if (!$site->user->is_in_group ("gestion_ae"))
  $site->error_forbidden("accueil");

if ($_REQUEST['action'] == "pdf")
{
  require_once($topdir. "include/pdf/planning_news.inc.php");

  $pdf = new pdfplanning_news($site->db, $_REQUEST['title']);
  $pdf->set_options($_REQUEST['xmargin'], $_REQUEST['ymargin'],
                    $_REQUEST['xmargin_b'], $_REQUEST['ymargin_b'],
                    $_REQUEST['title_h'], $_REQUEST['title_fontsize'],
                    $_REQUEST['cell_h'], $_REQUEST['fontsize'],
                    $_REQUEST['space'], $_REQUEST['vspace'],
                    $_REQUEST['section_space'], $_REQUEST['background_file']
                    );

  foreach($_REQUEST['news'] as $jour => $num_textes)
  {
    $textes = array();
    foreach($num_textes as $num_texte => $bleh)
      $pdf->add_texte($jour, $_REQUEST['textes'][$num_texte]);
  }

  $pdf->render();
  $pdf->Output();
  exit();
}

$site->start_page("accueil", "Génération d'un planning de la semaine");

/* Deuxième formulaire : on choisit les évènements
 */
if ($_REQUEST['action'] == "choix_even")
{
  $firstday = $_REQUEST['date'];

  if (date("N", $firstday) != 1)
    $firstday = strtotime("last Monday", $firstday);
  $lastday = strtotime("next Sunday", $firstday);

  $title = "Planning du ".strftime("%A %d %B", $firstday)." au ".strftime("%A %d %B", $lastday);

  $frm = new form ("createplaning", "planning.php", false, "POST", "Création d'un planning");
  $frm->add_hidden("action", "pdf");
  $frm->add_hidden("date", $firstday);
  $frm->add_text_field("title", "Titre", $title, true, 80);

  /* Pour chaque jour on permet de choisir parmis la liste des nouvelles
  */
  $date = $firstday;
  $i = 0;

  do
  {
    /* On ne cherche que dans les nouvelles ponctuelles ou répétitives
     * si elles ont commencées avant le jour concerné, elles doivent se finir après 10h00
     */
    $req = new requete($site->db,
      "SELECT id_nouvelle, titre_nvl, date_debut_eve, id_lieu, nom_lieu, type_nvl
      FROM `nvl_dates`
      INNER JOIN `nvl_nouvelles` USING (`id_nouvelle`)
      LEFT JOIN `loc_lieu` USING ( `id_lieu` )
      WHERE
        (
          (date_debut_eve > '".date("Y-m-d", $date)." 00:00'
          AND date_debut_eve < '".date("Y-m-d", $date)." 24:00')
        OR
          (date_debut_eve < '".date("Y-m-d", $date)." 00:00'
          AND date_fin_eve > '".date("Y-m-d", $date)." 10:00')
        )
        AND `type_nvl` IN ( 1, 2 )
        ORDER BY type_nvl, date_debut_eve");

    if ($req->lines > 0)
    {
      $subfrm = new subform("createplaning".date("N", $date),
                            strftime("%A %d %B", $date));
      while($row = $req->get_row())
      {
        $date_ev = "";

        $time = strtotime($row['date_debut_eve']);
        if ($time > $date)
          $date_ev .= date("G:i", $time);

        if ($row['id_lieu'] != null)
        {
          if ($date_ev != "")
            $date_ev .= ", ";
          $date_ev .= $row['nom_lieu'];
        }

        $subfrm->add_checkbox("news[".date("N", $date)."|".$i."]", $row['titre_nvl'], true);
        $subfrm->add_text_field("textes[".$i."][0]", "", $date_ev, true, 80);
        $subfrm->add_text_field("textes[".$i."][1]", "", $row['titre_nvl'], true, 80);
        $subfrm->add_hidden("textes[".$i."][2]", $row['type_nvl']);
        $i++;
      }

      $frm->addsub($subfrm, false);
    }

    $date = strtotime("+1 day", $date);
  } while (date("N", $date) != 1);


  /* On affiches les nouvelles longues
  */
  $req = new requete($site->db, "
    SELECT id_nouvelle, titre_nvl, date_debut_eve, date_fin_eve
    FROM `nvl_dates`
    INNER JOIN `nvl_nouvelles` USING (`id_nouvelle`)
    WHERE date_debut_eve < '".date("Y-m-d", $lastday)." 24:00'
      AND date_fin_eve > '".date("Y-m-d", $firstday)." 00:00'
      AND `type_nvl` = 0");

  if ($req->lines > 0)
  {
    $subfrm = new subform("createplaningsem", "Toute la semaine");
    while($row = $req->get_row())
    {
      $time1 = strtotime($row['date_debut_eve']);
      $time2 = strtotime($row['date_fin_eve']);

      if (($time1 > $firstday ) && ($time2 < $lastday ))
        $date_ev = "De ".strftime("%A", $time1)." à ".strftime("%A", $time2);
      elseif ($time1 > $firstday )
        $date_ev = "À partir de ".strftime("%A", $time1);
      elseif ($time2 < $lastday )
        $date_ev = "Jusqu'à ".strftime("%A", $time2);
      else
        $date_ev = "";

      $subfrm->add_checkbox("news[sem|".$i."]", $row['titre_nvl'], true);
      $subfrm->add_text_field("textes[".$i."][0]", "", $date_ev, true, 80);
      $subfrm->add_text_field("textes[".$i."][1]", "", $row['titre_nvl'], true, 80);
      $subfrm->add_hidden("textes[".$i."][2]", "0");
      $i++;
    }

    $frm->addsub($subfrm, false);
  }

  $file = new dfile($site->db);
  $file->load_by_id(5418);
  $subfrm = new subform("createplaningopt", "Options", false);
  $subfrm->add_text_field("xmargin", "Marge horizontale (contenu)", "20", true);
  $subfrm->add_text_field("ymargin", "Marge verticale (contenu)", "15", true);
  $subfrm->add_text_field("xmargin_b", "Marge horizontale (fond)", "10", true);
  $subfrm->add_text_field("ymargin_b", "Marge verticale (fond)", "7", true);
  $subfrm->add_text_field("title_h", "Marge pour le titre", "30", true);
  $subfrm->add_text_field("title_fontsize", "Taille du titre", "24", true);
  $subfrm->add_text_field("cell_h", "Interligne des boîtes", "12", true);
  $subfrm->add_text_field("fontsize", "Taille de la police", "8", true);
  $subfrm->add_text_field("space", "Espacement horizontal (boîtes)", "12", true);
  $subfrm->add_text_field("vspace", "Espacement vertical (boîtes)", "12", true);
  $subfrm->add_text_field("section_space", "Espacement vertical (sections)", "15", true);
  $subfrm->add_entity_smartselect("background_file", "Image de fond", $file, true);
  $frm->addsub($subfrm, true);

  $frm->add_submit("valid","Générer");

  $site->add_contents($frm);
}
/* Premier formulaire : on choisit la date du planning
 */
else
{
  $frm = new form ("createplaning","planning.php",false,"POST","Création d'un planning");
  $frm->add_hidden("action","choix_even");
  $frm->add_date_field("date","Semaine concernée", time(), true);

  $frm->add_submit("valid","Choisir les évènements");

  $site->add_contents ($frm);
}

$site->end_page();

// TODO : ajout evenement

?>
