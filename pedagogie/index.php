<?php
/**
 * Copyright 2008
 * - Manuel Vonthron  <manuel DOT vonthron AT acadis DOT org>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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
require_once($topdir. "include/cts/sqltable.inc.php");
require_once("include/pedagogie.inc.php");
require_once("include/cts/pedagogie.inc.php");
require_once("include/uv.inc.php");
require_once("include/pedag_user.inc.php");

$site = new site();
$site->add_js("pedagogie/pedagogie.js");
$site->allow_only_logged_users("services");

$site->start_page("services", "AE - Pédagogie");
$site->add_box("pedag_menu", pedag_menu_box());
$site->add_box("pedag_last_comments", last_comments_box($site->db));
$site->set_side_boxes("right", array("pedag_menu", "pedag_last_comments"));

$user = new pedag_user($site->db, $site->dbrw);
$user->load_by_id($site->user->id);

$path = "<a href=\"".$topdir."uvs/\"><img src=\"".$topdir."images/icons/16/lieu.png\" class=\"icon\" />  Pédagogie </a>";
$path .= "/" . " Accueil";
$cts = new contents($path);
$cts->add_paragraph("Bienvenue sur la partie pédagogie du site de l'AE");

$cts->add_paragraph("<b>Note : </b>Toutes les informations ont été normalement
  transférées depuis la précédente version, cependant si vous constatez
  des erreurs, n'hésitez pas à les corriger. De même, certaines informations
  n'existaient pas, telles que le semestre d'ouverture, le nombre d'heures de cours,
  etc... Nous comptons sur vous pour rendre ce bout du site de l'AE aussi
  utile et pertinent que possible.");


/** idem accueil edt.php */
$tab = array();
$edts = $user->get_edt_list();
if(!empty($edts))
{
  foreach($edts as $edt)
  {
    $tab[$edt]['semestre'] = $edt;
    $tab[$edt]['semestre_bold'] = "<b>".$edt."</b>";
    $i=0;
    foreach($user->get_edt_detail($edt) as $uv){
      $tab[$edt]['code_'.++$i] = $uv['code'];
      $tab[$edt]['id_uv_'.$i] = $uv['id_uv'];
    }
  }
}

if(count($tab) > 1)
  sort_by_semester($tab, 'semestre');

$cts->add(new sqltable("edtlist", "Liste de vos emplois du temps", $tab, "edt.php", 'semestre',
                        array("semestre_bold"=>"Semestre",
                              "code_1" => "UV 1",
                              "code_2" => "UV 2",
                              "code_3" => "UV 3",
                              "code_4" => "UV 4",
                              "code_5" => "UV 5",
                              "code_6" => "UV 6",
                              "code_7" => "UV 7"),
                        array("view" => "Voir détails",
                              "print" => "Format imprimable",
                              "schedule" => "Export iCal",
                              "edit" => "Éditer",
                              "delete" => "Supprimer"),
                        array(), array(), false), true);
$cts->add_paragraph("<input type=\"submit\" class=\"isubmit\" "
                    ."value=\"+ Importer emploi du temps depuis le mail du SME\" "
                    ."onclick=\"edt.add_auto();\" "
                    ."name=\"add_edt_auto\" id=\"add_edt_auto\"/>");

$cts->add_paragraph("<input type=\"submit\" class=\"isubmit\" "
                    ."value=\"+ Ajouter un emploi du temps (manuel)\" "
                    ."onclick=\"edt.add();\" "
                    ."name=\"add_edt\" id=\"add_edt\"/>");
$site->add_contents($cts);



$cts = new contents("Résumé de votre parcours");
  $lst = new itemlist(false);
  $lst->add("Vous venez de ");
  $lst->add("Vous avez obtenu ".$user->get_nb_uv_result(RESULT_EQUIV)." UV en équivalence");
  $lst->add("bla bla");
$cts->add($lst);
//$site->add_contents($cts);

$site->end_page();
?>
