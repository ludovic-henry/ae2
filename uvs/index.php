<?php
  /** @file
   *
   * @brief Page d'accueil de la partie pédagogique du site de l'AE.
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

$path = "<a href=\"".$topdir."uvs/\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" />  Pédagogie </a>";
$path .= "/" . " Accueil";
$cts = new contents($path);

$cts = new contents("Pédagogie : Maintenance");
$cts->add_paragraph("La partie pédagogie est partiellement fermée pour une refonte complète.");
$cts->add_paragraph("<b>La base de donnée de l'ancien système à désormais été synchronisée avec la nouvelle, les nouvelles modifications ne pourront être prises en compte.</b>");
$cts->add_paragraph("Soyez conscients que certains éléments tels que le calcul des crédits peuvent être erronés, nous vous rappelons que seuls les informations fournies par l'UTBM font foi.");
$cts->add_paragraph("Pour tout bug ou demande de fonctionnalité, contactez <a href=\"http://ae.utbm.fr/user.php?id_utilisateur=1956\">Gliss</a>.");


$cts->add_paragraph("Bienvenue sur la partie Pédagogie du site de l'AE");

if ($site->user->utbm)
  {

    require_once($topdir . "include/cts/uvcomment.inc.php");
    require_once($topdir . "include/cts/sqltable.inc.php");

    $cts->add_title(1, "TOP 10 des UVs les mieux notées de mon département");

    $sql = new requete($site->db,
                       "SELECT `code_uv`, `intitule_uv`, `id_uv`, `note_uv`
                      FROM `edu_uv_comments`
                      INNER JOIN `edu_uv_dept` USING (`id_uv`)
                      INNER JOIN `edu_uv` USING(`id_uv`)
                      WHERE `id_dept` = '".
                       strtoupper($site->user->departement)."'
                      GROUP BY `id_uv`
                      ORDER BY `note_uv` DESC LIMIT 10");


    for ($i = 0; $i < $sql->lines; $i++)
      {
        $res = $sql->get_row();
        $tab[$i] = $res;
        $tab[$i]['note_stars'] = p_stars($res['note_uv']);
      }

    $table = new sqltable('best_uv_dept', "", $tab, "",
                          "id_uv",
                          array("code_uv" => "Code de l'UV",
                                "intitule_uv" => "Intitulé de l'UV",
                                "note_stars"  => "Note de l'UV"),
                          array (),
                          array(), array(), false);

    $cts->add($table);

    $cts->add_title(1, "TOP 10 des UVs les mieux notées du département Humanités");

    $sql = new requete($site->db,
                       "SELECT `code_uv`, `id_uv`, `note_uv`, `intitule_uv`
                      FROM `edu_uv_comments`
                      INNER JOIN `edu_uv_dept` USING (`id_uv`)
                      INNER JOIN `edu_uv` USING(`id_uv`)
                      WHERE `id_dept` = 'Humanites'
                      GROUP BY `id_uv`
                      ORDER BY `note_uv` DESC LIMIT 10");

    $tab = array();

    for ($i = 0; $i < $sql->lines; $i++)
      {
        $res = $sql->get_row();
        $tab[$i] = $res;
        $tab[$i]['note_stars'] = p_stars($res['note_uv']);
      }


    $table = new sqltable('best_uv_humas', "", $tab, "",
                          "id_uv",
                          array("code_uv" => "Code de l'UV",
                                "intitule_uv" => "Intitulé de l'UV",
                                "note_stars"      => "Note de l'UV"),
                          array (),
                          array(), array(), false);

    $cts->add($table);

    if ($_REQUEST['action'] == 'delete')
      {
        $ret = delete_result_uv($site->user->id,
                                $_REQUEST['id_uv'],
                                $_REQUEST['semestre'],
                                $site->dbrw);

        $cts->add_title(2, "Suppression de résultat");

        if ($ret == true)
          $cts->add_paragraph("Résultat supprimé !");
        else
          $cts->add_paragraph("<b>Erreur lors de la ".
                              "suppresion du résultat.</b>");

      }

    /* generation de camembert */
    if ($_REQUEST['action'] == "camembert")
      {
        $cam = get_creds_cts($site->user, $site->db, true);
        $cam->png_render();

        exit();
      }

    if ($_REQUEST['action'] == 'add_obt')
      {
        $ret = add_result_uv($site->user->id,
                             $_REQUEST['obt_uv'],
                             $_REQUEST['obt_mention'],
                             $_REQUEST['obt_semestre'],
                             $site->dbrw);

        if ($ret == true)
          $cts->add_paragraph("Résultat d'UV ajouté avec succès !");
        else
          $cts->add_paragraph("<b>Erreur lors de l'ajout du résultat</b>");
      }



    $cts->add_title(1, "Mon parcours pédagogique");
    $cts->add(get_creds_cts($site->user, $site->db));

    $cts->add_title(3, "Statistiques d'obtention");
    $cts->add_paragraph("<center><img src=\"./index.php?action=camembert\" alt=\"statistiques d'obtention\" /></center>");



    $cts->add_title(3, "Ajout d'un résultat d'UV");
    $frm = new form('add_obt', "./?action=add_obt", true);

    $frm->add_entity_smartselect('obt_uv',
                                 "UV concernée",
                                 new uv($site->db), false, true);

    $frm->add_select_field('obt_mention',
                           "Note obtenue",
                           array("A" => "A",
                                 "B" => "B",
                                 "C" => "C",
                                 "D" => "D",
                                 "E" => "E",
                                 "Fx" => "Fx",
                                 "F" => "F",
                                 "EQUIV" => "équivalence"),
                           "D", "", true);

    $frm->add_text_field('obt_semestre',
                         "Semestre d'obtention, ex <b>P07</b>",
                         "", false);


    $frm->add_submit('obt_sbmt', 'Valider');

    $cts->add($frm);



    $cts->add_title(1, "Génération d'emploi du temps");
    $cts->add_paragraph("Cette partie permet aux étudiants de l'UTBM de générer leurs emplois
du temps en graphique, et ainsi le partager facilement.");

    $lst[] = "<a href=\"./create.php\">Créer un emploi du temps</a>";
    $lst[] = "<a href=\"./edt.php\">Gérer mes emplois du temps</a>";

    $itemlst = new itemlist("edt_lst", false, $lst);
    $cts->add($itemlst);

  }

$cts->add_title(1, "Informations sur les UVs");
$cts->add_paragraph("Grâce à cette section, vous pouvez consulter les UVs dispensées à
l'UTBM. Ces informations ont été copiées du <a href=\"http://www.utbm.fr/upload/gestionFichiers/GUIDEUV_1370.pdf\">Guide
officiel des UVs 2006</a>, complétées par les étudiants / pour les étudiants, et aucune garantie n'est donnée quant à la
justesse des informations.");

$lst = array();

$lst[] = "<a href=\"./uvs.php\">guide des UVs format \"site AE\"</a>";
$lst[] = "<a href=\"http://www.utbm.fr/upload/gestionFichiers/GUIDEUV_1941.pdf\"><b>guide
des UVs officiel</b> (édition 2007 / 2008, format PDF)</a>";

foreach ($departements as $dept)
$lst[] = "<a href=\"./uvs.php?iddept=".$dept."\">UVs du département $dept</a>";

$itemlst = new itemlist("edt_lst", false, $lst);
$cts->add($itemlst);


$site->add_contents($cts);
$site->end_page();

?>
