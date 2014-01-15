<?php
/** @file
 *
 * @brief Page d'informations diverses sur les UVs.
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
require_once($topdir . "include/entities/uv.inc.php");
require_once($topdir . "include/extdb/xml.inc.php");
require_once($topdir."include/cts/gallery.inc.php");


$site = new site();

  $site->redirect("/pedagogie/uv.php");

$site->add_css("css/doku.css");
$site->add_css("css/d.css");
$site->add_css("css/pedagogie.css");

$site->allow_only_logged_users("services");

$site->add_box("uvsmenu", get_uvsmenu_box() );
$site->set_side_boxes("left",array("uvsmenu", "connexion"));

$site->start_page("services", "Informations UV");

$cts = new contents("Pédagogie : Maintenance");
$cts->add_paragraph("La partie pédagogie est partiellement fermée pour une refonte complète.");
$cts->add_paragraph("Pour tout bug ou demande de fonctionnalité, contactez <a href=\"http://ae.utbm.fr/user.php?id_utilisateur=1956\">Gliss</a>.");
$site->add_contents($cts);

// Génération d'un camembert sur les
// statitistiques d'obtention d'une uv
if ($_REQUEST['action'] == 'statobt')
{
  $iduv = intval($_REQUEST['id_uv']);

  $req = new requete($site->db,
             "SELECT
                     `note_obtention`
                     , COUNT(`id_utilisateur`) AS `nb_usr`
              FROM
                     `edu_uv_obtention`
              WHERE
                     `id_uv` = " . $iduv ."
              GROUP BY
                     `note_obtention`
              ORDER BY
                     `note_obtention` ASC");

  if ($req->lines > 0)
    {
      require_once($topdir . "include/graph.inc.php");

      $stats = array();


      $cam = new camembert(600,400,array(),2,0,0,0,0,0,0,10,150);

      while ($rs = $req->get_row())
    {
      $cam->data($rs['nb_usr'], $rs['note_obtention']);
    }
      $cam->png_render();

    }

  else
    {
      // not available
      @readfile($topdir . "var/na.png");
    }
  exit();
}

// report d'un commentaire abusif

if ($_REQUEST['action'] == 'reportabuse')
{
  $comm = new uvcomment($site->db, $site->dbrw);
  $comm->load_by_id($_REQUEST['id']);

  $cts = new contents("Rapporter un commentaire jugé inapproprié");

  /* groupe des étudiants utbm actuels */
  if ($site->user->is_in_group_id(10004))
    {
      $ret = $comm->modere(UVCOMMENT_ABUSE);
      if ($ret)
    $cts->add_paragraph("Le commentaire a été marqué comme ".
                "abusif. Il continuera à s'afficher ".
                "aux étudiants jusqu'à modération par ".
                "l'équipe de modération.");
      else
    $cts->add_paragraph("<b>Une erreur est survenue lors ".
                "de la modération</b>");
    }
  else
    $site->error_forbidden("services");
  $site->add_contents($cts);

  $_id_uv = $comm->id_uv;
}

// mise en quarantaine d'un commentaire

if ($_REQUEST['action'] == 'quarantine')
{
  $comm = new uvcomment($site->db, $site->dbrw);
  $comm->load_by_id($_REQUEST['id']);

  $cts = new contents("Modération du commentaire");

  /* groupe des étudiants utbm actuels */
  if ($site->user->is_in_group_id(10004))
    {
      $ret = $comm->modere(UVCOMMENT_QUARANTINE);
      if ($ret)
    $cts->add_paragraph("Le commentaire a été mis en ".
                "\"quarantaine\". Cela signifie qu'il ".
                "n'est plus visible, mais que l'équipe ".
                " chargée de la modération peut prendre une ".
                "décision.");
      else
    $cts->add_paragraph("<b>Une erreur est survenue lors ".
                "de la modération.</b>");
    }
  else
    $site->error_forbidden("services");

  $site->add_contents($cts);

  $_id_uv = $comm->id_uv;
}

/* suppression d'un commentaire */
if ($_REQUEST['action'] == 'deletecomm')
{
  $comm = new uvcomment($site->db, $site->dbrw);
  $comm->load_by_id($_REQUEST['id']);

  $cts = new contents("Suppression de commentaire");

  if (($comm->is_valid()) && ($comm->id_commentateur == $site->user->id))
    {
      $ret = $comm->delete();
      if ($ret)
    $cts->add_paragraph("Le commentaire a été supprimé");
      else
    $cts->add_paragraph("<b>Erreur lors de la suppression ".
                "du commentaire</b>");
    }

  $site->add_contents($cts);
  $_id_uv = $comm->id_uv;

}

/* validation modifications  commentaire */
if (isset($_REQUEST['comm_mod_sbmt']))
{
  $comm = new uvcomment($site->db, $site->dbrw);
  $comm->load_by_id($_REQUEST['id']);

  $cts = new contents("Modification de commentaire");

  if ($comm->id_commentateur == $site->user->id)
    {
      $ret = $comm->modify($_REQUEST['comm_comm'],
               $_REQUEST['comm_obtention'],
               $_REQUEST['comm_semestre'],
               $_REQUEST['comm_interest'],
               $_REQUEST['comm_utilite'],
               $_REQUEST['comm_note_glbl'],
               $_REQUEST['comm_travail'],
               $_REQUEST['comm_qualite']);
      if ($ret)
    $cts->add_paragraph('Commentaire modifié avec succès');
      else
    $cts->add_paragraph('<b>Une erreur est survenue lors de la modification'.
                ' du commentaire</b>');
    }
  else
    {
      $site->error_forbidden("services");
    }
  $site->add_contents($cts);
  $_id_uv = $comm->id_uv;

}
/* modification de commentaire */
if ($_REQUEST['action'] == 'editcomm')
{
  $idcomment = intval($_REQUEST['id']);
  $comm = new uvcomment($site->db);
  $comm->load_by_id($idcomment);


  if (($comm->is_valid())
      && ($comm->id_commentateur == $site->user->id))
    {
      $commcts = new contents("Modification de votre commentaire");
      $commform = new form('editcomm',
               "uvs.php?action=postmodifcomm&id=".$comm->id.
               "&id_uv=".$comm->id_uv,
               true,
               "post",
               "Modification d'un commentaire");

      $commform->add_select_field('comm_obtention', 'UV obtenue',
                  array (NULL => 'Non renseigné',
                     'A'  => 'Admis : A',
                     'B'  => 'Admis : B',
                     'C'  => 'Admis : C',
                     'D'  => 'Admis : D',
                     'E'  => 'Admis : E',
                     'Fx' => 'Insuffisant : Fx',
                     'F'  => 'Insuffisant : F'),
                  $comm->note_obtention);

      $commform->add_text_field('comm_semestre',
                "Semestre d'obtention".
                ", ex: <b>P07</b>",
                $comm->semestre_obtention, true, 4);


      $commform->add_text_area('comm_comm',
                   'Commentaire (syntaxe Doku)',
                   $comm->comment, 80, 20);

      $commform->add_select_field('comm_interest',
                  'Intéret de l\'UV (pour un ingénieur)',
                  $uvcomm_interet,
                  $comm->interet);

      $commform->add_select_field('comm_utilite',
                  'Utilité de l\'UV (culture'.
                  ' générale ou autres)',
                  $uvcomm_utilite,
                  $comm->utilite);

      $commform->add_select_field('comm_travail',
                  'Charge de travail',
                  $uvcomm_travail,
                  $comm->charge_travail);

      $commform->add_select_field('comm_qualite',
                  'Qualité de l\'enseignement',
                  $uvcomm_qualite,
                  $comm->qualite_ens);

      $commform->add_select_field('comm_note_glbl',
                  'Evalutation globale de l\'UV',
                  $uvcomm_note,
                  $comm->note);

      $commform->add_submit('comm_mod_sbmt', 'Modifier');
      $commcts->add($commform);

      $site->add_contents($commform);
      $site->end_page();
      exit();
    }
}
/* Création commentaire sur les uvs */
if (($site->user->is_in_group_id(10004))
    && (isset($_REQUEST['comm_sbmt'])))
{
  $comm = new uvcomment($site->db, $site->dbrw);
  $ret =   $comm->create($_REQUEST['id_uv'],
             $site->user->id,
             $_REQUEST['comm_comm'],
             $_REQUEST['comm_obtention'],
             $_REQUEST['comm_semestre'],
             $_REQUEST['comm_interest'], /* interet */
             $_REQUEST['comm_utilite'], /* utilite */
             $_REQUEST['comm_note_glbl'], /*note */
             $_REQUEST['comm_travail'], /* travail */
             $_REQUEST['comm_qualite']); /* qualité enseignement */

  $cts = new contents();

  if ($ret)
    $cts->add_paragraph("UV commentée avec succès !");
  else
    $cts->add_paragraph("<b>Erreur lors de l'enregistrement ".
            "du commentaire.</b>");
  $site->add_contents($cts);

}

/* modification d'uv */

if (($site->user->is_in_group('gestion_ae'))
    && (isset($_REQUEST['edituvsubmit'])))
{
  $uv = new uv($site->db, $site->dbrw);
  $uv->load_by_id($_REQUEST['iduv']);

  $departements = array();

  if ($_REQUEST['Humas'] == 1)
    {
      $departements[] = 'Humanites';
      $stats_by_depts['Humanites'] = $_REQUEST['cat_humas'];
    }
  if ($_REQUEST['TC'] == 1)
    {
      $departements[] = 'TC';
      $stats_by_depts['TC'] = $_REQUEST['cat_tc'];
    }
  if ($_REQUEST['GESC'] == 1)
    {
      $departements[] = 'GESC';
      $stats_by_depts['GESC'] = $_REQUEST['cat_gesc'];
    }
  if ($_REQUEST['EE'] == 1)
    {
      $departements[] = 'EE';
      $stats_by_depts['EE'] = $_REQUEST['cat_ee'];
    }
  if ($_REQUEST['GI'] == 1)
    {
      $departements[] = 'GI';
      $stats_by_depts['GI'] = $_REQUEST['cat_gi'];
    }
  if ($_REQUEST['IMAP'] == 1)
    {
      $departements[] = 'IMAP';
      $stats_by_depts['IMAP'] = $_REQUEST['cat_imap'];
    }
  if ($_REQUEST['IMSI'] == 1)
    {
      $departements[] = 'IMSI';
      $stats_by_depts['IMSI'] = $_REQUEST['cat_imsi'];
    }
  if ($_REQUEST['GMC'] == 1)
    {
      $departements[] = 'GMC';
      $stats_by_depts['GMC'] = $_REQUEST['cat_gmc'];
    }
  if ($_REQUEST['EDIM'] == 1)
    {
      $departements[] = 'EDIM';
      $stats_by_depts['EDIM'] = $_REQUEST['cat_edim'];
    }

  $uv->modify($_REQUEST['name'],
          $_REQUEST['intitule'],
          $_REQUEST['objectifs'],
          $_REQUEST['programme'],
          $_REQUEST['cours'],
          $_REQUEST['td'],
          $_REQUEST['tp'],
          $_REQUEST['ects'],
          $departements,
          $stats_by_depts);
}


// page UV (code uv renseigné)
if (isset($_REQUEST['id_uv']) || (isset($_REQUEST['code_uv']))
    || (isset($_id_uv)))
{
  /* on a besoin d'une dbrw pour l'ajout de fichiers */
  if ($_REQUEST['view'] == 'files')
    $uv = new uv($site->db, $site->dbrw);
  else
    $uv = new uv($site->db);


  if (isset($_REQUEST['id_uv']))
    {
      $uv->load_by_id($_REQUEST['id_uv']);
    }
  else if (isset($_id_uv))
    $uv->load_by_id($_id_uv);
  else
    {
      $uv->load_by_code($_REQUEST['code_uv']);
    }

  $tabs = array(array("", "uvs/uvs.php?id_uv=".$uv->id, "Informations générales"),
        array("infosetu", "uvs/uvs.php?view=infosetu&id_uv=".$uv->id, "Historique de suivi"),
        array("commentaires", "uvs/uvs.php?view=commentaires&id_uv=".$uv->id, "Commentaires"),
        array("ressext", "uvs/uvs.php?view=ressext&id_uv=".$uv->id, "Ressources externes"));


  /* TODO : partie fichiers réservée aux étudiants ? */
  if ($site->user->is_in_group_id(10004))
    {
      $tabs[] = array("files", "uvs/uvs.php?view=files&id_uv=".$uv->id, "Fichiers");
    }

  $tab = new tabshead($tabs, $_REQUEST['view']);

    $uv->load_depts();
  $path = "<a href=\"".$topdir."uvs/\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" />  Pédagogie </a>";
  $path .= " / "."<img src=\"".$topdir."images/icons/16/forum.png\" class=\"icon\" />";
      $stop = count($uv->depts);
      for($i=0; $i<$stop; $i++){
          $path .= "<a href=\"".$topdir."uvs/uvs.php?iddept=".$uv->depts[$i]."\"> ".$uv->depts[$i]."</a>";
          if($i+1 < $stop) $path .= ",";
      }
  $path .= " / "."<a href=\"".$topdir."uvs/uvs.php?id_uv=$uv->id\"><img src=\"".$topdir."images/icons/16/emprunt.png\" class=\"icon\" /> $uv->code</a>";

  /* path partie fichiers */

  if ((isset ($uv)) && (isset($_REQUEST['id_folder']))
      && ($uv->check_folder($_REQUEST['id_folder'])))
    {
      $path .= (" / " .$uv->get_path($_REQUEST['id_folder']));
    }

  $cts = new contents($path);

  $cts->add($tab);

  /* départements concernés */
  // ce code est commun a plusieurs onglets.

  for ($i = 0 ; $i < count($uv->depts); $i++)
    {

      $myuvdpts[] = "<a href=\"./uvs.php?iddept=".
    $uv->depts[$i]."\">".$uv->depts[$i]."</a>\n";
      $uvdept[] = $uv->depts[$i];
    }


  /* Code + intitulé + crédits ECTS */
  if (($_REQUEST['view'] == "") || (! isset($_REQUEST['view'])))
    {
      $cts->add_paragraph("<center><i style=\"font-size: 20px;\">\"".
              $uv->intitule."\"</i></center>");

      if (strlen($uv->objectifs) > 4)
    {
      $cts->add_title(2, "Objectifs");
      $cts->add_paragraph(doku2xhtml($uv->objectifs));
    }
      if (strlen($uv->programme) > 4)
    {
      $cts->add_title(2, "Programme");
      $cts->add_paragraph(doku2xhtml($uv->programme));
    }

      $cts->add_title(2, "Crédits");
      $cts->add_paragraph("Cette UV équivaut à <b>".$uv->ects."</b> crédits ECTS");

      /* format horaire */
      $cts->add_title(2, "Formats horaires");

      $parag = "<ul>";

      if ($uv->cours == 1)
    {
      $parag .= "<li>Cours</li>\n";
    }
      if ($uv->td == 1)
    {
      $parag .= "<li>TD</li>\n";
    }
      if ($uv->tp == 1)
    {
      $parag .= "<li>TP</li>\n";
    }
      $tpuv = false;

      $parag .= "</ul>\n";

      if (($uv->cours == 0)
      && ($uv->td == 0)
      && ($uv->tp == 0))
    $parag = "<b>UV Hors Emploi du Temps (HET)</b>";

      $cts->add_paragraph($parag);


      $cts->add_title(2, "Départements dans lequel l'UV est enseignée");

      $lst = new itemlist("Départements",
              false,
              $myuvdpts);
      $cts->add($lst);

      /* formulaire d'édition d'une UV */
      if ($site->user->is_in_group("gestion_ae"))
    {
      $cts2 = new contents("Modification d'UV");

      $edituv = new form("edituv",
                 "uvs.php?id_uv=".$uv->id,
                 true,
                 "post",
                 "Modification de l'UV");

      $edituv->add_hidden('iduv', $uv->id);

      $edituv->add_text_field('name',
                  "Code de l'UV <b>sans espace, ex: 'MT42'</b>",
                  $uv->code, true, 4);

      $edituv->add_text_area('intitule',
                 "Intitulé de l'UV",
                 $uv->intitule);
      $edituv->add_text_area('objectifs',
                 "Objectifs",
                 $uv->objectifs, 80, 20);
      $edituv->add_text_area('programme',
                 "Programme de l'UV",
                 $uv->programme, 80, 20);
      $edituv->add_checkbox('cours',
                "Cours",
                $uv->cours == 1);

      $edituv->add_checkbox('td',
                "TD",
                $uv->td == 1);

      $edituv->add_checkbox('tp',
                "TP",
                $uv->tp == 1);

      $edituv->add_text_field('ects',
                  "Credits ECTS",
                  $uv->ects, false, 1);

      $edituv->add_checkbox('Humas',
                "Humanités",
                in_array('Humanites', $uvdept));

      $edituv->add_checkbox('TC',
                "TC",
                in_array('TC', $uvdept));

      $edituv->add_checkbox('EE',
                "EE",
                in_array('EE', $uvdept));

      $edituv->add_checkbox('GI',
                "GI",
                in_array('GI', $uvdept));

      $edituv->add_checkbox('IMSI',
                "IMSI",
                in_array('IMSI', $uvdept));

      $edituv->add_checkbox('GMC',
                "GMC",
                in_array('GMC', $uvdept));

      $edituv->add_checkbox('EDIM',
                "EDIM",
                in_array('EDIM', $uvdept));

      if (count($uvdept) > 0)
        {
          foreach ($uvdept as $dept)
        {
          if ($dept == 'Humanites')
            $edituv->add_select_field('cat_humas',
                          "Catégorie de l'UV au département Humanités",
                          $humas_cat, $cat_by_depts[$dept]);
          else if ($dept == 'TC')
            $edituv->add_select_field('cat_tc',
                          "Catégorie de l'UV au département TC",
                          $tc_cat, $cat_by_depts[$dept]);
          else if ($dept == 'EE')
            $edituv->add_select_field('cat_ee',
                          "Catégorie de l'UV au département EE",
                          $ee_cat, $cat_by_depts[$dept]);
          else if ($dept == 'GI')
            $edituv->add_select_field('cat_gi',
                          "Catégorie de l'UV au département GI",
                          $gi_cat, $cat_by_depts[$dept]);
          else if ($dept == 'IMSI')
            $edituv->add_select_field('cat_imsi',
                          "Catégorie de l'UV au département IMSI",
                          $imsi_cat, $cat_by_depts[$dept]);
          else if ($dept == 'GMC')
            $edituv->add_select_field('cat_gmc',
                          "Catégorie de l'UV au département GMC",
                          $gmc_cat, $cat_by_depts[$dept]);
          else if ($dept == 'EDIM')
            $edituv->add_select_field('cat_edim',
                          "Catégorie de l'UV au département EDIM",
                          $edim_cat, $cat_by_depts[$dept]);
        }
        }

      $edituv->add_submit('edituvsubmit',
                  "Modifier");

      $cts2->add($edituv);
    }

    }

  /* listing des personnes ayant suivi l'UV */
  else if ($_REQUEST['view'] == 'infosetu')
    {
      /* a migrer dans uv.inc.php ? */
      $suivrq = new requete ($site->db,
                 "SELECT
                                  `id_utilisateur`
                                  , `prenom_utl`
                                  , `nom_utl`
                                  , `surnom_utbm`
                                  , `semestre_grp`
                          FROM
                                  `edu_uv_groupe_etudiant`
                          INNER JOIN
                                  `edu_uv_groupe`
                          USING (`id_uv_groupe`)
                          INNER JOIN
                                   `edu_uv`
                          USING(`id_uv`)
                          INNER JOIN
                                    `utilisateurs`
                          USING(`id_utilisateur`)
                          INNER JOIN
                                    `utl_etu_utbm`
                          USING (`id_utilisateur`)
                          WHERE
                                `id_uv` = ".$uv->id."
                          GROUP BY `code_uv`, `id_utilisateur`

                          UNION

                          SELECT
                                 `edu_uv_obtention`.`id_utilisateur`
                                  , `prenom_utl`
                                  , `nom_utl`
                                  , `surnom_utbm`
                                  , `semestre_obtention` AS `semestre_grp`
                          FROM
                                  `edu_uv_obtention`
                          INNER JOIN
                                    `utilisateurs`
                          ON
                                  `utilisateurs`.`id_utilisateur` = `edu_uv_obtention`.`id_utilisateur`
                          INNER JOIN
                                    `utl_etu_utbm`
                          ON
                                  `utilisateurs`.`id_utilisateur` = `utl_etu_utbm`.`id_utilisateur`
                          WHERE
                                `id_uv` = ".$uv->id."
                          GROUP BY `id_utilisateur`");

      if ($suivrq->lines > 0)
    {
      require_once($topdir . "include/cts/sqltable.inc.php");
      $sqlt = new sqltable('userslst',
                   "Liste des utilisateurs suivant ou ayant suivi l'UV",
                   $suivrq,
                   '../user.php',
                   'id_utilisateur',
                   array('prenom_utl' => 'prenom', 'nom_utl' => 'nom', 'surnom_utbm' => 'surnom', 'semestre_grp' => 'semestre'),
                   array('view' => 'Voir la fiche'),
                   array(),
                   array());
      $cts->add_title(2, "Ils suivent ou ont suivi cette UV");
      $cts->add_paragraph("Ce listing correspond aux personnes ayant rentré un emploi du temps de semestre, au cours duquel ils ont suivi l'UV, ou ayant renseigné un résultat d'obtention.");
      $cts->add($sqlt);
    }

      /* statistiques sur l'obtention */
      $cts->add_title(2, "Statistiques d'obtention");
      $cts->add_paragraph("Ces statistiques sont obtenues en fonction des entrées des utilisateurs concernant leur résultat.".
              " Vous pouvez saisir les votres sur la <a href=\"./index.php\">page d'accueil de ".
              "la partie pédagogie</a>, et ainsi contribuer à l'enrichissement des statistiques.");

      $cts->add_paragraph("<center><img src=\"./uvs.php?action=statobt&id_uv=".$uv->id."\" ".
                  "alt=\"statistiques d'obtention\" /></center>");


    }

  else if ($_REQUEST['view'] == "commentaires")
    {
      /* COMMENTAIRES UV */
      if ($site->user->is_in_group("etudiants-utbm-actuels"))
    {
      /* TODO note : pourquoi ne pas créer par la suite un groupe
       * spécifique à la modération des commentaires ?
       */
      $uv->load_comments($site->user->is_in_group("gestion_ae"));

      if (count($uv->comments) > 0)
        {
          $commented = false;

          foreach ($uv->comments as $comm)
        {
          if ($site->user->id == $comm->id_commentateur)
            {
              $commented = true;
              break;
            }
        }
          require_once($topdir . "include/cts/uvcomment.inc.php");
          $cts->add_title(2, "Commentaires d'étudiants ayant suivi l'UV");
          $cts->add(new uvcomment_contents($uv->comments,
                           $site->db,
                           $site->user));
        }

      /* formulaire de postage de commentaires */
      if ($commented == false)
        {
          $commcts = new contents("Commentaires sur les UVs");
          $commform = new form('commform',
                   "uvs.php?id_uv=".$uv->id,
                   true,
                   "post",
                   "Ajout d'un commentaire");

          $commform->add_select_field('comm_obtention', 'UV obtenue',
                      array (NULL => 'Non renseigné',
                         'A'  => 'Admis : A',
                         'B'  => 'Admis : B',
                         'C'  => 'Admis : C',
                         'D'  => 'Admis : D',
                         'E'  => 'Admis : E',
                         'Fx' => 'Insuffisant : Fx',
                         'F'  => 'Insuffisant : F'), NULL);

          $commform->add_text_field('comm_semestre',
                    "Semestre d'obtention".
                    ", ex: <b>P07</b>",
                    null, true, 4);

        $commform->add_info("Cette section ayant pour but d'aider les étudiants ".
          "dans leurs choix d'UV, merci de ne pas mettre des notes à la va-vite ".
          "sans la moindre phrase et d'être constructif dans vos commentaires. <br />".
          "Tout message offensant pourra se voir supprimé");
          $commform->add_text_area('comm_comm', 'Commentaire (syntaxe Doku)', null, 80, 20);
          $commform->add_select_field('comm_interest',
                      'Intéret de l\'UV (pour un ingénieur)',
                      $uvcomm_interet,
                      2);

          $commform->add_select_field('comm_utilite',
                      'Utilité de l\'UV (culture générale ou autres)',
                      $uvcomm_utilite,
                      2);

          $commform->add_select_field('comm_travail',
                      'Charge de travail',
                      $uvcomm_travail,
                      2);
          $commform->add_select_field('comm_qualite',
                      'Qualité de l\'enseignement',
                      $uvcomm_qualite,
                      2);

          $commform->add_select_field('comm_note_glbl',
                      'Evalutation globale de l\'UV',
                      $uvcomm_note,
                      2);

          $commform->add_submit('comm_sbmt', 'Commenter');
          $commcts->add($commform);

        }
    } // fin commentage uvs
      else
    {
      $cts->add(new error("Accès refusé", "Cette partie est".
                  " réservée aux étudiants de l'UTBM."));
    }
    }

  else if ($_REQUEST['view'] == 'ressext')
    {
      // Ressources spécifiques - Bankexam
      // Cette partie récupère le XML de bankexam relatif aux uvs de l'UTBM,
      // le parse, et affiche si des annales sont disponibles

      $xml = file_get_contents("http://www.bankexam.fr/rss/etablissement?code=UTBM");
      $uvsbe = new u007xml($xml);


      foreach ($uvsbe->arrOutput[0]['childrens'][0]['childrens'] as $key => $value)
    {
      /* au début y'a que de la boue */
      if (($key == 0) || ($key == 1) || ($key == 2))
        continue;

      $codeuv = $value['childrens'][2]['nodevalue'];
      if ($codeuv != $uv->code)
        continue;

      $annaleslink = $value['childrens'][5]['nodevalue'];

      $annee_exam  = $value['childrens'][3]['nodevalue'];

      if (strlen($annaleslink) > 0)
        {

          $arr_anls[] = "<a href=\"".$annaleslink."\">Examen - année ".$annee_exam ."</a>";

          //  $cts->add_paragraph("Il existe des annales d'examen sur Bankexam. <a href=\"".
          //              $annaleslink."\">Cliquez ici pour y accéder.</a>");
          //break;
        }
    }

      if (count($arr_anls) > 0)
    {
          $cts->add_title(2, "Sur <a href=\"http://www.bankexam.fr/\">Bankexam.fr</a>");
          $cts->add(new itemlist("Annales Bankexam", false, $arr_anls));
    }

      /* Ressources externes */
      $cts->add_title(2, "Ailleurs sur le net ...");

      foreach ($uvdept as $departement)
    {
      if ($departement == 'Humanites')
        $exts[] = "<a href=\"http://www.utbm.fr/index.php?pge=207\"><b>Site de l'UTBM</b>, information sur le département des Humanités</a>";
      if ($departement == 'TC')
        $exts[] = "<a href=\"http://www.utbm.fr/index.php?pge=205\"><b>Site de l'UTBM</b>, information sur le département de Tronc Commun</a>";
      if ($departement == 'EE')
        $exts[] = "<a href=\"http://www.utbm.fr/index.php?pge=70\"><b>Site de l'UTBM</b>, information sur le département du Génie Electrique et ".
          "Systèmes de Commande (EE)</a>";
      if ($departement == 'GI')
        $exts[] = "<a href=\"http://www.utbm.fr/index.php?pge=67\"><b>Site de l'UTBM</b>, information sur le département du Génie Informatique (GI)</a>";
      if ($departement == 'IMSI')
        $exts[] = "<a href=\"http://www.utbm.fr/index.php?pge=69\"><b>Site de l'UTBM</b>, information sur le département de l'Ingénierie et".
          " management de process (IMSI)</a>";
      if ($departement == 'GMC')
        $exts[] = "<a href=\"http://www.utbm.fr/index.php?pge=68\"><b>Site de l'UTBM</b>, information sur le département du Génie Mécanique ".
          "et conception (GMC)</a>";
    }

      $exts[] = "<a href=\"http://bankexam.fr/etablissement/1-Universite-de-Technologie-de-Belfort-Montbeliard\">".
    "<b>Bankexam.fr</b>, base de données d'examens</a>";
      $exts[] = "<a href=\"https://webct6.utbm.fr/\"><b>WebCT</b>, la plateforme pédagogique de l'UTBM</a>";


      $itmlst = new itemlist("Ressources externes",
                 false,
                 $exts);

      $cts->add($itmlst);
    }

  /* partie fichiers */

  else if ($_REQUEST['view'] == 'files')
    {
      require_once($topdir . "include/entities/folder.inc.php");
      require_once($topdir . "include/entities/files.inc.php");


      if (! $site->user->is_in_group_id(10004))
    {
      $site->error_forbidden("services");
    }


      if (isset($_REQUEST['id_file']) &&
          ($_REQUEST['action'] == "download"))
        {
          header("location: http://ae.utbm.fr/d.php?id_file=".
                 $_REQUEST['id_file'].
                 "&action=download");
        }

      $cts->add_paragraph("Fichiers relatifs à l'UV ".$uv->code.
              "<br/><br/>".
              "<b>Note importante : ces fichiers sont ".
              "proposés par les utilisateurs du site et ".
              "l'AE n'est pas responsable du contenu mis ".
              "à disposition.<br/>Conformément aux lois, tout ".
              "fichier succeptible de ne pas respecter la ".
              "legislation pourra être supprimé sans ".
              "préavis.</b><br/>".
                          "Notez par ailleurs que les fichiers ajoutés ".
                          "seront soumis à modération.");

      // creation du dossier si inexistant
      if (! $uv->load_folder())
    {
      $uv->create_folder();
    }

      // dorénavant, le répertoire est considéré comme créé

      // formulaire ajout fichier posté
      if ($_REQUEST['action'] == "addfile")
    {
      $nfile = new dfile($site->db, $site->dbrw);


      if ((isset($_REQUEST['id_folder']))
          && ($uv->check_folder($_REQUEST['id_folder'])))
        {
              $nfolder = new dfolder($site->db);
              $nfolder->load_by_id($_REQUEST['id_folder']);

              $nfile->herit($nfolder);
              $nfile->set_rights($site->user,
                                 DROIT_LECTURE, /* rights */
                                 10001, /* groupe : utbm */
                                 8 /* groupe admin : modérateur site */
                                 );


          $nfile->add_file ($_FILES["file"],
                                $_REQUEST["nom"],
                                $_REQUEST['id_folder'],
                                $_REQUEST["description"],
                                null);
        }
      else
        {

              $nfolder = new dfolder($site->db);
              $nfolder->load_by_id($uv->folder->id);

              $nfile->herit($nfolder);
              $nfile->set_rights($site->user,
                                 DROIT_LECTURE, /* rights */
                                 10001, /* groupe : utbm */
                                 8 /* groupe admin : modérateur site */
                                 );

          $nfile->add_file ($_FILES["file"],
                                $_REQUEST["nom"],
                                $uv->folder->id,
                                $_REQUEST["description"],
                                null);
        }
      $nfile->set_tags($_REQUEST["tags"]);
    } // fin addfile

      // formulaire création répertoire posté
      if ($_REQUEST['action'] == "addfolder")
    {
      $nfolder = new dfolder($site->db, $site->dbrw);

      // TODO @feu : ce sont les droits repompés
      // de la création de dossiers relatifs aux uvs.
      // oui / non ?

      $nfolder->id_groupe_admin = 7;
      $nfolder->id_groupe = 7;
      $nfolder->droits_acces = 0xDDD;
      $nfolder->id_utilisateur = null;

      // controle si le répertoire est bien créé dans un sous-répertoire
      // de l'UV.
      // sinon on crée un sous répertoire du répertoire de l'UV.

      // TODO : la fonction check_folder() n'a pas été testée
      if ((isset($_REQUEST['id_folder']))
          && ($uv->check_folder($_REQUEST['id_folder'])))
        {
          $pfold = $_REQUEST['id_folder'];
        }
      else
        {
          $pfold = $uv->folder->id;
        }

      $nfolder->add_folder ($_REQUEST["nom"],
                $pfold,
                $_REQUEST["description"],
                null);

      if ($nfolder->id == null)
        {
          $ErreurAjout = "Erreur lors de l'ajout.";
        }

    } // fin ajout effectif (traitement des données postées)

      if ($_REQUEST['page'] == 'newfolder')
    {
      if (! isset($_REQUEST['id_folder']))
        {
          $frm = new form("addfolder",
                  "./uvs.php?view=files&id_uv=".$uv->id.
                  "&action=addfolder");
        }
      else
        {

          $frm = new form("addfolder",
                  "./uvs.php?view=files&id_uv=".$uv->id.
                  "&action=addfolder&id_folder=".
                  intval($_REQUEST['id_folder']));
        }
      $frm->allow_only_one_usage();
      $frm->add_hidden("action","addfolder");

      if ( $ErreurAjout )
        $frm->error($ErreurAjout);

      $frm->add_text_field("nom","Nom","",true);
      $frm->add_text_area("description","Description","");
      $frm->add_submit("valid","Ajouter");
      $cts->add($frm);
    } // fin formulaire création dossiers

      else if ($_REQUEST['page'] == 'newfile')
    {
      if (! isset($_REQUEST['id_folder']))
        {
          $frm = new form("addfile",
                  "./uvs.php?view=files&id_uv=".$uv->id.
                  "&action=addfile");
        }
      else
        {
          $frm = new form("addfile","./uvs.php?view=files&id_uv=".$uv->id.
                  "&id_folder=".intval($_REQUEST['id_folder']) .
                  "&action=addfile");
        }

      $frm->allow_only_one_usage();
      $frm->add_hidden("action","addfile");

      if ($ErreurAjout)
        {
          $frm->error($ErreurAjout);
        }

      $frm->add_file_field("file","Fichier",true);
      $frm->add_text_field("nom","Nom","",true);
      $frm->add_text_field("tags","Tags (séparateur: virgule)","");
      $frm->add_text_area("description","Description","");
      $frm->add_submit("valid","Ajouter");

      $cts->add($frm);

    } // fin formulaire création fichier

      // pompé de d.php
      $gal = new gallery("Fichiers et dossiers",
             "aedrive",
             false,
             "./uvs.php?view=files&id_uv=".$uv->id
             ."&id_folder_parent=".
             $uv->folder->id,
             array("download"=>"Télécharger",
                   "info"=>"Details",
                   "edit"=>"Editer",
                   "delete"=>"Supprimer"));


      $fd = new dfolder($site->db);

      if (! isset($_REQUEST['id_folder']))
    {
      $sub1 = $uv->folder->get_folders ($site->user);
    }
      else
    {
      $fdtmp = new dfolder($site->db);
      $fdtmp->load_by_id($_REQUEST['id_folder']);
      $sub1 = $fdtmp->get_folders($site->user);
    }

      while ($row = $sub1->get_row ())
    {
      $acts = false;
      $fd->_load($row);

      $desc  = $fd->description;
      if (strlen($desc) > 72)
        $desc = substr($desc,0,72)."...";

      $gal->add_item ( "<img src=\"/images/icons/128/folder.png\" alt=\"dossier\" />",
               "<a href=\"./uvs.php?view=files&amp;id_folder=".$fd->id.
               "&amp;id_uv=".$uv->id."\" class=\"itmttl\">".
               $fd->titre."</a><br/><span class=\"itmdsc\">".$desc."</span>",
               "id_folder=".$fd->id,
               $acts,
               "folder");

    }

      if (! isset($fdtmp))
    {
      $sub2 = $uv->folder->get_files($site->user);
    }
      else
    {
      $sub2 = $fdtmp->get_files($site->user);
    }

      $fd = new dfile ($site->db);

      while ($row = $sub2->get_row())
    {
      $acts = array("download","info");
      $fd->_load($row);

      if (! file_exists($fd->get_thumb_filename()))
        $img = $topdir."images/icons/128/".$fd->get_icon_name();
      else
        $img = "../d.php?id_file=".$fd->id."&amp;action=download&amp;download=thumb";

      $desc = $fd->description;
      if (strlen($desc) > 72)
        $desc = substr($desc,0,72)."...";

      $gal->add_item ("<img src=\"$img\" alt=\"fichier\" />",
              "<a href=\"../d.php?id_file=".$fd->id.
              "&amp;". "\" class=\"itmttl\">".$fd->titre.
              "</a><br/><span class=\"itmdsc\">".$desc.
              "</span>",
              "id_file=".$fd->id,
              $acts,
              "file");

    } // fin while fichiers

      $cts->add($gal, true);


      // options de base
      if (! isset($_REQUEST['id_folder']))
    {
      $cts->add_paragraph("<a href=\"./uvs.php?view=files&amp;id_uv=".
                  $uv->id.
                  "&amp;page=newfolder\">Ajouter un dossier</a>");

      $cts->add_paragraph("<a href=\"./uvs.php?view=files&amp;id_uv=".
                  $uv->id.
                  "&amp;page=newfile\">Ajouter un fichier</a>");
    }
      else
    {
      $cts->add_paragraph("<a href=\"./uvs.php?view=files&amp;id_uv=".
                  $uv->id.
                  "&amp;page=newfolder&amp;id_folder=".
                  intval($_REQUEST['id_folder']).
                  "\">Ajouter un dossier</a>");

      $cts->add_paragraph("<a href=\"./uvs.php?view=files&amp;id_uv=".
                  $uv->id.
                  "&amp;page=newfile&amp;id_folder=".
                  intval($_REQUEST['id_folder'])
                  ."\">Ajouter un fichier</a>");


        }

    } // files

    // Fin des tests sur la vue sélectionnée

  $site->add_contents($cts);

  /* commentaire sur l'UV */
  if ($commcts)
    {
      $site->add_contents($commcts);
    }

  /* modification d'une uv (gestion AE) */
  if ($cts2)
    {
      $site->add_contents($cts2);
    }

  $site->end_page();

  exit();
}


// affichage du listing des uvs par département

if (isset($_REQUEST['iddept']))
{
  if (in_array($_REQUEST['iddept'], $departements))
    {
            $dept = mysql_real_escape_string($_REQUEST['iddept']);

            $path = "<a href=\"".$topdir."uvs/\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" />  Pédagogie </a>";
          $path .= " / "."<a href=\"".$topdir."uvs/uvs.php?iddept=$dept\"><img src=\"".$topdir."images/icons/16/forum.png\" class=\"icon\" /> $dept</a>";
      $cts = new contents ($path);

      $req = new requete($site->db,
             "SELECT
                             `edu_uv`.`id_uv`
                             , `edu_uv`.`code_uv`
                             , `edu_uv`.`intitule_uv`
                          FROM
                             `edu_uv`
                          LEFT JOIN
                             `edu_uv_dept`
                          USING (`id_uv`)
                          WHERE
                             `id_dept` = '".$dept."'
                          ORDER BY
                             `edu_uv`.`code_uv`");

    $table = "<table class=\"uvlist\">\n";
    $table .= " <tr>\n";
    $i = 0;
      $uvs = array();
      while ($rs = $req->get_row())
    {
            $table .= "  <td><a href=\"./uvs.php?id_uv=".$rs['id_uv']."\" title=\"".$rs['intitule_uv']."\">".$rs['code_uv']."</a></td>\n";
            $i++;
            if($i == 15)
                { $table .= "</tr><tr>\n"; $i = 0; }

      $uvs[] = "<a href=\"./uvs.php?id_uv=".$rs['id_uv']."\">".
        $rs['code_uv'] . " - " . $rs['intitule_uv'] . "</a>";
    }
        $table .= "\n </tr>\n</table>\n";
        $cts->puts($table);


      $lst = new itemlist($dept,
              false,
              $uvs);
            $cts->add_title(3, "Liste détaillée"); // faudra donner plus de détails du coup...
      $cts->add($lst);

      $site->add_contents($cts);

      $site->end_page();
      exit();
    }
}

$path = "<a href=\"".$topdir."uvs/\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" />  Pédagogie </a>";
$path .= " / "."Toutes les UV";
$cts = new contents($path);

$tmp = "";

foreach ($departements as $dept)
{
  $tmp .= "<a href=\"#dept_".$dept . "\">$dept</a><br/>";
}
$cts->add_title(2, "Accès direct aux départements");

$cts->add_paragraph($tmp);

foreach ($departements as $dept)
{
  $req = new requete($site->db,
             "SELECT
                             `edu_uv`.`id_uv`
                             , `edu_uv`.`code_uv`
                             , `edu_uv`.`intitule_uv`
                      FROM
                             `edu_uv`
                      LEFT JOIN
                             `edu_uv_dept`
                      USING (`id_uv`)
                      WHERE
                             `id_dept` = '".$dept."'
                      ORDER BY
                             `edu_uv`.`code_uv`");


    $table = "<table class=\"uvlist\">\n";
    $table .= " <tr>\n";
    $i = 0;
      $uvs = array();
      while ($rs = $req->get_row())
    {
            $table .= "  <td><a href=\"./uvs.php?id_uv=".$rs['id_uv']."\">".$rs['code_uv']."</a></td>\n";
            $i++;
            if($i == 15)
                { $table .= "</tr><tr>\n"; $i = 0; }

      $uvs[] = "<a href=\"./uvs.php?id_uv=".$rs['id_uv']."\">".
        $rs['code_uv'] . " - " . $rs['intitule_uv'] . "</a>";
    }
    $table .= "\n </tr>\n</table>\n";

  $lst = new itemlist($dept,
              false,
              $uvs);
  $cts->add_title(2,"<a id=\"dept_".$dept."\" ".
          "href=\"./uvs.php?iddept=$dept\">$dept</a>");

    $cts->puts($table);

    $cts->add_title(3, "Liste détaillée");
  $cts->add($lst);
}

$site->add_contents($cts);


$site->end_page();

?>
