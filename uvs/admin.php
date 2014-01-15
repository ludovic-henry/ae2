<?php
  /** @file
   *
   * @brief Page d'administration de la partie pédagogique du site de
   * l'AE.
   * Cette page a pour vocation :
   *
   * - De modérer les commentaires jugés abusifs et/out marqués comme
   *   supprimés
   * - De modifier les séances de l'emploi du temps
   * - Autres actions relatives à l'administration (à définir).
   *
   */

  /* Copyright 2008
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
$site->add_css("css/doku.css");
$site->add_css("css/d.css");
$site->add_css("css/pedagogie.css");

$site->add_box("uvsmenu", get_uvsmenu_box() );
$site->set_side_boxes("left",array("uvsmenu", "connexion"));

$site->start_page("services", "AE - Pédagogie - Modération");

$path = "<a href=\"".$topdir."uvs/\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" />  Pédagogie </a>";
$path .= "/" . " <a href=\"./admin.php\">Administration</a>";

if ($_REQUEST['sub'] == 'modseance')
  {
    $path .= " / <a href=\"./admin.php?sub=modseance\">Modification des séances horaires</a>";
  }
else if ($_REQUEST['sub'] == 'modcomments')
  {
    $path .= " / <a href=\"./admin.php?sub=modcomments\">Modération des commentaires</a>";
  }

$cts = new contents($path);

// vérification d'usage

/** @todo : selon Zoror, un groupe spécifique à la modération de la
 * partie pédagogie serait pertinant. Je (pedrov) ne prends pas de
 * décision la dessus.
 */
if (! $site->user->is_in_group("gestion_ae"))
  {
    $site->error_forbidden("services");
  }


require_once($topdir . "include/cts/uvcomment.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");

// page selon la "subsection"
// modification des séances
if ($_REQUEST['sub'] == 'modseance')
  {
    require_once($topdir . "include/entities/edt.inc.php");

    $cts->add_title(1, "Modification des séances horaires");

    // on sait ce qu'on doit modifier
    if (isset($_REQUEST['id_seance']))
      {
        $idseance = intval($_REQUEST['id_seance']);
        $req = new requete($site->db, "SELECT * FROM `edu_uv_groupe` INNER JOIN `edu_uv` USING (`id_uv`) WHERE ".
                           "id_uv_groupe = $idseance");
        if ($req->lines != 1)
          {
            //            $cts->add_paragraph("<b>Erreur : séance introuvable.</b>");
            $edt = new edt($site->db, $site->dbrw);
            $ret = $edt->modify_grp($idseance,      //iduv
                                    $_REQUEST['mod_typegrp'], //type
                                    $_REQUEST['mod_numgrp'],  // numgrp
                                    $_REQUEST['mod_hdebgrp'], // hdeb
                                    $_REQUEST['mod_hfingrp'],  // hfin
                                    $_REQUEST['mod_jourgrp'],  //jourgrp
                                    $_REQUEST['mod_freqgrp'],  //frequence
                                    $_REQUEST['mod_sallegrp'],  // salle
                                    $_REQUEST['mod_lieu']); // lieu

            if ($ret == true)
              $cts->add_paragraph("Séance horaire modifiée avec succès");
            else
              $cts->add_paragraph("<b>Erreur lors de la modification de la séance.</b>");
          }
        else
          {
            $res = $req->get_row();
            $cts->add_title(2, "Séance de ".
                            $res['type_grp'] == "C" ? "cours" :
                            $res['type_grp'] . " de " . $res['code_uv']);

            $frm = new form('modseance', './admin.php?sub=modseance', true);
            $frm->add_hidden('id_seance', $res['id_uv_groupe']);

            $frm->add_select_field('mod_typegrp', 'Type de séance',
                                   array('C' => 'cours', "TD" => "TD", "TP" => "TP"),
                                   $res['type_grp'], "", true);
            $frm->add_text_field('mod_numgrp', 'Numéro de groupe',
                                 $res['numero_grp']);

            $frm->add_text_field('mod_hdebgrp', 'Heure de début',
                                 $res['heure_debut_grp']);

            $frm->add_text_field('mod_hfingrp', 'Heure de fin',
                                 $res['heure_fin_grp']);

            $frm->add_select_field('mod_jourgrp', 'Jour', $jour, $res['jour_grp']);
            $frm->add_select_field('mod_freqgrp', 'Fréquence',
                                   array('1' => 'Hebdomadaire', '2' => 'Bimensuelle'),
                                   $res['frequence_grp'], "", true);

            $frm->add_text_field('mod_sallegrp', 'Salle',
                                 $res['salle_grp']);
            $frm->add_select_field('mod_lieu', 'Lieu',
                                   array(null => '--',
                                         4 => 'UTBM Site de Sévenans',
                                         5 => 'UTBM Site de Belfort',
                                         9 => 'UTBM Site de Montbéliard'),
                                   $res['id_lieu']);

            $frm->add_submit('modsubmit', 'Modifier');
            $cts->add($frm);
          }

      }
    // sinon, il faut chercher
    /**
     * @todo : implémenter une recherche par code d'UV ? (autres, des idées ?)
     */
    else
      {
        /**
         * @todo : entitizer les séances horaires ?
         */
        $cts->add_title(1, "Modification des séances horaires");
        $cts->add_paragraph("Veuillez entrer l'identifiant de séance qui vous a été ".
                            "communiqué dans le formulaire ci-dessous.");

        $frm = new form('searchseance', './admin.php?sub=modseance', true);
        $frm->add_text_field('id_seance', 'Identifiant de séance');
        $frm->add_submit('searchseance_sbmt', 'Rechercher');
        $cts->add($frm);
      }
  }
// modération des commentaires
else if ($_REQUEST['sub'] == 'modcomments')
  {
    require_once($topdir. "include/cts/uvcomment.inc.php");

    $cts->add_title(1, "Modération des commentaires");

    $cts->add_title(2, "Commentaires abusifs");

    $cts->add_paragraph("Cette section liste les commentaires ayant ".
                        "été jugés abusifs par les étudiants. Vous ".
                        "pouvez ensuite prendre une décision afin de ".
                        "retirer leur publication, les remettre à ".
                        "l'état normal, ou les laisser tels quels. Notez".
                        " qu'un commentaire dans l'état \"abusif\" ".
                        "apparaît en rouge, à l'utilisateur les ".
                        "consultant de faire la part des choses et ".
                        "d'apprécier le commentaire en question à sa ".
                        "juste valeur.");

    $req = new requete($site->db, "SELECT
                                           `id_comment`
                                   FROM
                                           `edu_uv_comments`
                                   WHERE
                                           `state_comment` = 1"); // 1 = UVCOMMENT_ABUSE

    $comms = array();
    if ($req->lines > 0)
      {
        for ($i = 0 ; $i < $req->lines; $i++)
          {
            $res = $req->get_row();
            $comms[$i] = new uvcomment($site->db);
            $comms[$i]->load_by_id($res['id_comment']);
          }

        $cts->add(new uvcomment_contents($comms,
                                         $site->db,
                                         $site->user, "admin.php"));
      }
    else
      {
        $cts->add_paragraph("<b>Aucun commentaire signalé abusif.</b>");
      }

    $cts->add_title(2, "Commentaires en quarantaine");

    $cts->add_paragraph("Cette section liste les commentaires ayant ".
                        "été retirés, et mis en modération par l'équipe".
                        "modératrice de la partie pédagogie. Par conséquent ".
                        "ils n'apparaissent plus actuellement sur le site (mise ".
                        "en quarantaine).");

    $req = new requete($site->db, "SELECT
                                           `id_comment`
                                   FROM
                                           `edu_uv_comments`
                                   WHERE
                                           `state_comment` = 2"); // 2 = UVCOMMENT_QUARANTINE

    $comms = array();
    if ($req->lines > 0)
      {
        for ($i = 0 ; $i < $req->lines; $i++)
          {
            $res = $req->get_row();
            $comms[$i] = new uvcomment($site->db);
            $comms[$i]->load_by_id($res['id_comment']);
          }

        $cts->add(new uvcomment_contents($comms,
                                         $site->db,
                                         $site->user, "admin.php"));
      }
    else
      {
        $cts->add_paragraph("<b>Aucun commentaire en quarantaine.</b>");
      }


  }




$cts->add_title(1, "Modération");

$cts->add_paragraph("Cette partie du site est réservée à la modération ".
                    "de la partie pédagogie. Elle vous permet de modifier".
                    " une séance horaire d'UV qui n'aurait pas été saisie".
                    " correctement par un utilisateur, de modérer les ".
                    "commentaires jugés abusifs et/ou supprimés.");

$lst = array("<a href=\"./admin.php?&sub=modseance\">Modification des séances</a>",
             "<a href=\"./admin.php?&sub=modcomments\">Modération des commentaires</a>");

$cts->add(new itemlist("actions", false, $lst));

$site->add_contents($cts);

$site->end_page();


?>
