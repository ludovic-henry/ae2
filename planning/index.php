<?php

/* Copyright 2008
 * - Sarah Amsellem <sarah DOT amsellem AT gmail DOT com>
 * - Benjamin Collet <bcollet AT oxynux DOT org>
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

define("BUREAU_AE_BELFORT", 164);
define("BUREAU_AE_MONTBELIARD", 165);
define("BUREAU_AE_SEVENANS", 166);
define("BUREAU_BDF_BELFORT", 167);
define("BUREAU_BDF_SEVENANS", 168);
define("BUREAU_BDS_BELFORT", 169);
define("BUREAU_BDS_SEVENANS", 170);
//define("TEST", 191);

$topdir = "../";
require_once($topdir. "include/site.inc.php");

require_once($topdir. "include/entities/planning.inc.php");
require_once($topdir. "include/cts/planning.inc.php");

$site = new site ();

$lieux = array(164=>"Bureau AE Belfort", 165 => "Bureau AE Montbéliard", 166=>"Bureau AE Sevenans", 167=>"Foyer", 168=>"KFet", 169=>"Bureau BDS Belfort", 170=>"Bureau BDS Sevenans"/*, 191=>"Test"*/);


if ( $_REQUEST["action"] == "searchpl" )
{
  $site->add_css("css/weekplanning.css");

  $site->start_page("services","Planning");
  $cts = new contents("<a href=\"index.php\">Planning</a> / ".$lieux[$_REQUEST['id_salle']]." / Affichage");

  // TEST
  /*$planning = new planning($site->db,$site->dbrw);
  $start_date = strtotime("2008-03-24");
  $end_date = strtotime("2008-07-27");

  $planning->add (
    1,
    "Planning de test", -1, $start_date, $end_date, true );

  $lundi = 0;
  $mardi = 3600*24;
  $mercredi = 3600*24*2;
  $jeudi = 3600*24*3;
  $vendredi = 3600*24*4;
  $samedi = 3600*24*5;
  $lundi2 = 3600*24*7;
  $mardi2 = 3600*24*8;
  $mercredi2 = 3600*24*9;
  $jeudi2 = 3600*24*10;
  $vendredi2 = 3600*24*11;
  $samedi2 = 3600*24*12;
  $h8 = 8*3600;
  $h9 = 9*3600;
  $h10 = 10*3600;
  $h11 = 11*3600;
  $h12 = 12*3600;
  $h13 = 13*3600;
  $h14 = 14*3600;
  $h15 = 15*3600;
  $h16 = 16*3600;
  $h17 = 17*3600;
  $h18 = 18*3600;
  $h19 = 19*3600;
  $h20 = 20*3600;
  $h21 = 21*3600;
  $h22 = 22*3600;

  $id_creneau_1 = $planning->add_gap( $lundi+$h8, $lundi+$h9 );
  $id_creneau_2 = $planning->add_gap( $lundi+$h9, $lundi+$h10 );
  $id_creneau_3 = $planning->add_gap( $lundi+$h10, $lundi+$h11 );
  $id_creneau_4 = $planning->add_gap( $lundi+$h11, $lundi+$h12 );
  $id_creneau_5 = $planning->add_gap( $lundi+$h12, $lundi+$h13 );
  $id_creneau_6 = $planning->add_gap( $lundi+$h13, $lundi+$h14 );
  $id_creneau_7 = $planning->add_gap( $lundi+$h14, $lundi+$h15 );
  $id_creneau_8 = $planning->add_gap( $lundi+$h15, $lundi+$h16 );
  $id_creneau_9 = $planning->add_gap( $lundi+$h16, $lundi+$h17 );
  $id_creneau_10 = $planning->add_gap( $lundi+$h17, $lundi+$h18 );
  $id_creneau_11 = $planning->add_gap( $lundi+$h18, $lundi+$h19 );
  $id_creneau_12 = $planning->add_gap( $lundi+$h19, $lundi+$h20 );
  $id_creneau_13 = $planning->add_gap( $lundi+$h20, $lundi+$h21 );
  $id_creneau_14 = $planning->add_gap( $lundi+$h21, $lundi+$h22 );

  $planning->add_gap( $lundi2+$h8, $lundi2+$h9 );
  $planning->add_gap( $lundi2+$h9, $lundi2+$h10 );
  $planning->add_gap( $lundi2+$h10, $lundi2+$h11 );
  $planning->add_gap( $lundi2+$h11, $lundi2+$h12 );
  $planning->add_gap( $lundi2+$h12, $lundi2+$h13 );
  $planning->add_gap( $lundi2+$h13, $lundi2+$h14 );
  $planning->add_gap( $lundi2+$h14, $lundi2+$h15 );
  $planning->add_gap( $lundi2+$h15, $lundi2+$h16 );
  $planning->add_gap( $lundi2+$h16, $lundi2+$h17 );
  $planning->add_gap( $lundi2+$h17, $lundi2+$h18 );
  $planning->add_gap( $lundi2+$h18, $lundi2+$h19 );
  $planning->add_gap( $lundi2+$h19, $lundi2+$h20 );
  $planning->add_gap( $lundi2+$h20, $lundi2+$h21 );
  $planning->add_gap( $lundi2+$h21, $lundi2+$h22 );

  $planning->add_gap( $mardi2+$h8, $mardi2+$h9 );
  $planning->add_gap( $mardi2+$h9, $mardi2+$h10 );
  $planning->add_gap( $mardi2+$h10, $mardi2+$h11 );
  $planning->add_gap( $mardi2+$h11, $mardi2+$h12 );
  $planning->add_gap( $mardi2+$h12, $mardi2+$h13 );
  $planning->add_gap( $mardi2+$h13, $mardi2+$h14 );
  $planning->add_gap( $mardi2+$h14, $mardi2+$h15 );
  $planning->add_gap( $mardi2+$h15, $mardi2+$h16 );
  $planning->add_gap( $mardi2+$h16, $mardi2+$h17 );
  $planning->add_gap( $mardi2+$h17, $mardi2+$h18 );
  $planning->add_gap( $mardi2+$h18, $mardi2+$h19 );
  $planning->add_gap( $mardi2+$h19, $mardi2+$h20 );
  $planning->add_gap( $mardi2+$h20, $mardi2+$h21 );
  $planning->add_gap( $mardi2+$h21, $mardi2+$h22 );

    $planning->add_gap( $mercredi2+$h8, $mercredi2+$h9 );
  $planning->add_gap( $mercredi2+$h9, $mercredi2+$h10 );
  $planning->add_gap( $mercredi2+$h10, $mercredi2+$h11 );
  $planning->add_gap( $mercredi2+$h11, $mercredi2+$h12 );
  $planning->add_gap( $mercredi2+$h12, $mercredi2+$h13 );
  $planning->add_gap( $mercredi2+$h13, $mercredi2+$h14 );
  $planning->add_gap( $mercredi2+$h14, $mercredi2+$h15 );
  $planning->add_gap( $mercredi2+$h15, $mercredi2+$h16 );
  $planning->add_gap( $mercredi2+$h16, $mercredi2+$h17 );
  $planning->add_gap( $mercredi2+$h17, $mercredi2+$h18 );
  $planning->add_gap( $mercredi2+$h18, $mercredi2+$h19 );
  $planning->add_gap( $mercredi2+$h19, $mercredi2+$h20 );
  $planning->add_gap( $mercredi2+$h20, $mercredi2+$h21 );
  $planning->add_gap( $mercredi2+$h21, $mercredi2+$h22 );

  $planning->add_gap( $jeudi2+$h8, $jeudi2+$h9 );
  $planning->add_gap( $jeudi2+$h9, $jeudi2+$h10 );
  $planning->add_gap( $jeudi2+$h10, $jeudi2+$h11 );
  $planning->add_gap( $jeudi2+$h11, $jeudi2+$h12 );
  $planning->add_gap( $jeudi2+$h12, $jeudi2+$h13 );
  $planning->add_gap( $jeudi2+$h13, $jeudi2+$h14 );
  $planning->add_gap( $jeudi2+$h14, $jeudi2+$h15 );
  $planning->add_gap( $jeudi2+$h15, $jeudi2+$h16 );
  $planning->add_gap( $jeudi2+$h16, $jeudi2+$h17 );
  $planning->add_gap( $jeudi2+$h17, $jeudi2+$h18 );
  $planning->add_gap( $jeudi2+$h18, $jeudi2+$h19 );
  $planning->add_gap( $jeudi2+$h19, $jeudi2+$h20 );
  $planning->add_gap( $jeudi2+$h20, $jeudi2+$h21 );
  $planning->add_gap( $jeudi2+$h21, $jeudi2+$h22 );

$planning->add_gap( $vendredi2+$h8, $vendredi2+$h9 );
  $planning->add_gap( $vendredi2+$h9, $vendredi2+$h10 );
  $planning->add_gap( $vendredi2+$h10, $vendredi2+$h11 );
  $planning->add_gap( $vendredi2+$h11, $vendredi2+$h12 );
  $planning->add_gap( $vendredi2+$h12, $vendredi2+$h13 );
  $planning->add_gap( $vendredi2+$h13, $vendredi2+$h14 );
  $planning->add_gap( $vendredi2+$h14, $vendredi2+$h15 );
  $planning->add_gap( $vendredi2+$h15, $vendredi2+$h16 );
  $planning->add_gap( $vendredi2+$h16, $vendredi2+$h17 );
  $planning->add_gap( $vendredi2+$h17, $vendredi2+$h18 );
  $planning->add_gap( $vendredi2+$h18, $vendredi2+$h19 );
  $planning->add_gap( $vendredi2+$h19, $vendredi2+$h20 );
  $planning->add_gap( $vendredi2+$h20, $vendredi2+$h21 );
  $planning->add_gap( $vendredi2+$h21, $vendredi2+$h22 );

$planning->add_gap( $samedi2+$h8, $samedi2+$h9 );
  $planning->add_gap( $samedi2+$h9, $samedi2+$h10 );
  $planning->add_gap( $samedi2+$h10, $samedi2+$h11 );
  $planning->add_gap( $samedi2+$h11, $samedi2+$h12 );
  $planning->add_gap( $samedi2+$h12, $samedi2+$h13 );
  $planning->add_gap( $samedi2+$h13, $samedi2+$h14 );
  $planning->add_gap( $samedi2+$h14, $samedi2+$h15 );
  $planning->add_gap( $samedi2+$h15, $samedi2+$h16 );
  $planning->add_gap( $samedi2+$h16, $samedi2+$h17 );
  $planning->add_gap( $samedi2+$h17, $samedi2+$h18 );
  $planning->add_gap( $samedi2+$h18, $samedi2+$h19 );
  $planning->add_gap( $samedi2+$h19, $samedi2+$h20 );
  $planning->add_gap( $samedi2+$h20, $samedi2+$h21 );
  $planning->add_gap( $samedi2+$h21, $samedi2+$h22 );

  $id_creneau_21 = $planning->add_gap( $mardi+$h8, $mardi+$h9 );
  $id_creneau_22 = $planning->add_gap( $mardi+$h9, $mardi+$h10 );
  $id_creneau_23 = $planning->add_gap( $mardi+$h10, $mardi+$h11 );
  $id_creneau_24 = $planning->add_gap( $mardi+$h11, $mardi+$h12 );
  $id_creneau_25 = $planning->add_gap( $mardi+$h12, $mardi+$h13 );
  $id_creneau_26 = $planning->add_gap( $mardi+$h13, $mardi+$h14 );
  $id_creneau_27 = $planning->add_gap( $mardi+$h14, $mardi+$h15 );
  $id_creneau_28 = $planning->add_gap( $mardi+$h15, $mardi+$h16 );
  $id_creneau_29 = $planning->add_gap( $mardi+$h16, $mardi+$h17 );
  $id_creneau_210 = $planning->add_gap( $mardi+$h17, $mardi+$h18 );
  $id_creneau_211 = $planning->add_gap( $mardi+$h18, $mardi+$h19 );
  $id_creneau_212 = $planning->add_gap( $mardi+$h19, $mardi+$h20 );
  $id_creneau_213 = $planning->add_gap( $mardi+$h20, $mardi+$h21 );
  $id_creneau_214 = $planning->add_gap( $mardi+$h21, $mardi+$h22 );

  $id_creneau_31 = $planning->add_gap( $mercredi+$h8, $mercredi+$h9 );
  $id_creneau_32 = $planning->add_gap( $mercredi+$h9, $mercredi+$h10 );
  $id_creneau_33 = $planning->add_gap( $mercredi+$h10, $mercredi+$h11 );
  $id_creneau_34 = $planning->add_gap( $mercredi+$h11, $mercredi+$h12 );
  $id_creneau_35 = $planning->add_gap( $mercredi+$h12, $mercredi+$h13 );
  $id_creneau_36 = $planning->add_gap( $mercredi+$h13, $mercredi+$h14 );
  $id_creneau_37 = $planning->add_gap( $mercredi+$h14, $mercredi+$h15 );
  $id_creneau_38 = $planning->add_gap( $mercredi+$h15, $mercredi+$h16 );
  $id_creneau_39 = $planning->add_gap( $mercredi+$h16, $mercredi+$h17 );
  $id_creneau_310 = $planning->add_gap( $mercredi+$h17, $mercredi+$h18 );
  $id_creneau_311 = $planning->add_gap( $mercredi+$h18, $mercredi+$h19 );
  $id_creneau_312 = $planning->add_gap( $mercredi+$h19, $mercredi+$h20 );
  $id_creneau_313 = $planning->add_gap( $mercredi+$h20, $mercredi+$h21 );
  $id_creneau_314 = $planning->add_gap( $mercredi+$h21, $mercredi+$h22 );

  $id_creneau_41 = $planning->add_gap( $jeudi+$h8, $jeudi+$h9 );
  $id_creneau_42 = $planning->add_gap( $jeudi+$h9, $jeudi+$h10 );
  $id_creneau_43 = $planning->add_gap( $jeudi+$h10, $jeudi+$h11 );
  $id_creneau_44 = $planning->add_gap( $jeudi+$h11, $jeudi+$h12 );
  $id_creneau_45 = $planning->add_gap( $jeudi+$h12, $jeudi+$h13 );
  $id_creneau_46 = $planning->add_gap( $jeudi+$h13, $jeudi+$h14 );
  $id_creneau_47 = $planning->add_gap( $jeudi+$h14, $jeudi+$h15 );
  $id_creneau_48 = $planning->add_gap( $jeudi+$h15, $jeudi+$h16 );
  $id_creneau_49 = $planning->add_gap( $jeudi+$h16, $jeudi+$h17 );
  $id_creneau_410 = $planning->add_gap( $jeudi+$h17, $jeudi+$h18 );
  $id_creneau_411 = $planning->add_gap( $jeudi+$h18, $jeudi+$h19 );
  $id_creneau_412 = $planning->add_gap( $jeudi+$h19, $jeudi+$h20 );
  $id_creneau_413 = $planning->add_gap( $jeudi+$h20, $jeudi+$h21 );
  $id_creneau_414 = $planning->add_gap( $jeudi+$h21, $jeudi+$h22 );

  $id_creneau_51 = $planning->add_gap( $vendredi+$h8, $vendredi+$h9 );
  $id_creneau_52 = $planning->add_gap( $vendredi+$h9, $vendredi+$h10 );
  $id_creneau_53 = $planning->add_gap( $vendredi+$h10, $vendredi+$h11 );
  $id_creneau_54 = $planning->add_gap( $vendredi+$h11, $vendredi+$h12 );
  $id_creneau_55 = $planning->add_gap( $vendredi+$h12, $vendredi+$h13 );
  $id_creneau_56 = $planning->add_gap( $vendredi+$h13, $vendredi+$h14 );
  $id_creneau_57 = $planning->add_gap( $vendredi+$h14, $vendredi+$h15 );
  $id_creneau_58 = $planning->add_gap( $vendredi+$h15, $vendredi+$h16 );
  $id_creneau_59 = $planning->add_gap( $vendredi+$h16, $vendredi+$h17 );
  $id_creneau_510 = $planning->add_gap( $vendredi+$h17, $vendredi+$h18 );
  $id_creneau_511 = $planning->add_gap( $vendredi+$h18, $vendredi+$h19 );
  $id_creneau_512 = $planning->add_gap( $vendredi+$h19, $vendredi+$h20 );
  $id_creneau_513 = $planning->add_gap( $vendredi+$h20, $vendredi+$h21 );
  $id_creneau_514 = $planning->add_gap( $vendredi+$h21, $vendredi+$h22 );

  $id_creneau_61 = $planning->add_gap( $samedi+$h8, $samedi+$h9 );
  $id_creneau_62 = $planning->add_gap( $samedi+$h9, $samedi+$h10 );
  $id_creneau_63 = $planning->add_gap( $samedi+$h10, $samedi+$h11 );
  $id_creneau_64 = $planning->add_gap( $samedi+$h11, $samedi+$h12 );
  $id_creneau_65 = $planning->add_gap( $samedi+$h12, $samedi+$h13 );
  $id_creneau_66 = $planning->add_gap( $samedi+$h13, $samedi+$h14 );
  $id_creneau_67 = $planning->add_gap( $samedi+$h14, $samedi+$h15 );
  $id_creneau_68 = $planning->add_gap( $samedi+$h15, $samedi+$h16 );
  $id_creneau_69 = $planning->add_gap( $samedi+$h16, $samedi+$h17 );
  $id_creneau_610 = $planning->add_gap( $samedi+$h17, $samedi+$h18 );
  $id_creneau_611 = $planning->add_gap( $samedi+$h18, $samedi+$h19 );
  $id_creneau_612 = $planning->add_gap( $samedi+$h19, $samedi+$h20 );
  $id_creneau_613 = $planning->add_gap( $samedi+$h20, $samedi+$h21 );
  $id_creneau_614 = $planning->add_gap( $samedi+$h21, $samedi+$h22 );*/

 // FIN TEST

  if((($_REQUEST['id_salle']==BUREAU_AE_BELFORT || $_REQUEST['id_salle']==BUREAU_AE_SEVENANS || $_REQUEST['id_salle']==BUREAU_AE_MONTBELIARD) && $site->user->is_in_group("gestion_ae"))
 || ($_REQUEST['id_salle']==BUREAU_BDF_BELFORT && $site->user->is_in_group("foyer_barman")) || ($_REQUEST['id_salle']==BUREAU_BDF_SEVENANS && $site->user->is_in_group("kfet_barman"))
 || (($_REQUEST['id_salle']==BUREAU_BDS_BELFORT || $_REQUEST['id_salle']==BUREAU_BDS_SEVENANS) && $site->user->is_in_group("bds-bureau")))
  {
    $sql =
      "SELECT id_gap, start_gap, end_gap, pl_gap.id_planning,
       COALESCE(utl_etu_utbm.surnom_utbm, CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl), '(personne)') AS texte
       FROM pl_gap
       LEFT JOIN pl_gap_user USING(id_gap)
       LEFT JOIN utilisateurs USING(id_utilisateur)
       LEFT JOIN utl_etu_utbm USING ( id_utilisateur )
       WHERE pl_gap.id_planning='".$_REQUEST['id_salle']."'";

  if(isset($_REQUEST['semainedeux']))
  {
    $cts->add_paragraph("<a href=\"index.php?action=affich&id_salle=".$_REQUEST['id_salle']."&semainedeux\">Affichage</a>");

    $pl = new weekplanning ("Planning semaine B", $site->db, $sql, "id_gap", "start_gap", "end_gap", "texte", "index.php?action=searchpl&id_salle=".$_REQUEST['id_salle'], "index.php?action=details&id_salle=".$_REQUEST['id_salle']."&semainedeux", "", PL_LUNDI, true);
  }
  else
  {
    $cts->add_paragraph("<a href=\"index.php?action=affich&id_salle=".$_REQUEST['id_salle']."\">Affichage</a>");

    $pl = new weekplanning ("Planning semaine A", $site->db, $sql, "id_gap", "start_gap", "end_gap", "texte", "index.php?action=searchpl&id_salle=".$_REQUEST['id_salle'], "index.php?action=details&id_salle=".$_REQUEST['id_salle'], "", PL_LUNDI, true);
  }

}
else
{
  $sql =
    "SELECT id_gap, start_gap, end_gap, pl_gap.id_planning,
    COALESCE(utl_etu_utbm.surnom_utbm, CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl)) AS texte
  FROM pl_gap
  LEFT JOIN pl_gap_user
  USING ( id_gap )
  LEFT JOIN utilisateurs
  USING ( id_utilisateur )
  LEFT JOIN utl_etu_utbm
  USING ( id_utilisateur )
  WHERE pl_gap_user.id_planning='".$_REQUEST['id_salle']."'
  AND pl_gap_user.id_utilisateur IS NOT NULL";

  $cts->add_paragraph("Seuls les membres du groupe correspondants au planning que vous tentez de visualiser peuvent enregistrer de nouveaux creneaux.");

  $pl = new weekplanning (isset($_REQUEST['semainedeux'])?"Planning semaine B":"Planning semaine A", $site->db, $sql, "id_gap", "start_gap", "end_gap", "texte", "index.php?action=searchpl&id_salle=".$_REQUEST['id_salle'], "index.php?action=affich&id_planning=".$_REQUEST['id_salle'], "", PL_LUNDI, true);
}

  $cts->add($pl,true);

  $frm = new form("searchpl","index.php",false,"POST","Nouvelle recherche");
  $frm->add_hidden("action","searchpl");
  if ( isset($_REQUEST["fallback"]) )
    $frm->add_hidden("fallback",$_REQUEST["fallback"]);
  $frm->add_select_field("id_salle","Lieu",$lieux, $_REQUEST['id_salle']);
  $frm->add_submit("afficher","Afficher le planning");
  $cts->add($frm,true);

  $site->add_contents($cts);
  $site->end_page();

  exit();
}
else if( $_REQUEST['action'] == "affich" )
{
  $site->add_css("css/weekplanning.css");

  $site->start_page("services","Planning");
  $cts = new contents("<a href=\"index.php\">Planning</a> / ".$lieux[$_REQUEST['id_salle']]." / Affichage");

  $cts->add_paragraph("Seuls les membres du groupe correspondants au planning que vous tentez de visualiser peuvent enregistrer de nouveaux creneaux.");

  $sql =
    "SELECT id_gap, start_gap, end_gap, pl_gap.id_planning,
    COALESCE(utl_etu_utbm.surnom_utbm, CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl)) AS texte
  FROM pl_gap
  LEFT JOIN pl_gap_user
  USING ( id_gap )
  LEFT JOIN utilisateurs
  USING ( id_utilisateur )
  LEFT JOIN utl_etu_utbm
  USING ( id_utilisateur )
  WHERE pl_gap_user.id_planning='".$_REQUEST['id_salle']."'
  AND pl_gap_user.id_utilisateur IS NOT NULL";

  if((($_REQUEST['id_salle']==BUREAU_AE_BELFORT || $_REQUEST['id_salle']==BUREAU_AE_SEVENANS || $_REQUEST['id_salle']==BUREAU_AE_MONTBELIARD) && $site->user->is_in_group("gestion_ae"))
 || ($_REQUEST['id_salle']==BUREAU_BDF_BELFORT && $site->user->is_in_group("foyer_barman")) || ($_REQUEST['id_salle']==BUREAU_BDF_SEVENANS && $site->user->is_in_group("kfet_barman"))
 || (($_REQUEST['id_salle']==BUREAU_BDS_BELFORT || $_REQUEST['id_salle']==BUREAU_BDS_SEVENANS) && $site->user->is_in_group("bds-bureau")))
  {
    if(isset($_REQUEST['semainedeux']))
    {
      $cts->add_paragraph("<a href=\"index.php?action=searchpl&id_salle=".$_REQUEST['id_salle']."&semainedeux\">Administration</a>");
    }
    else
    {
      $cts->add_paragraph("<a href=\"index.php?action=searchpl&id_salle=".$_REQUEST['id_salle']."\">Administration</a>");
    }
  }

  $pl = new weekplanning (isset($_REQUEST['semainedeux'])?"Planning semaine B":"Planning semaine A", $site->db, $sql, "id_gap", "start_gap", "end_gap", "texte", "index.php?action=affich&id_salle=".$_REQUEST['id_salle'], "index.php?action=affich&id_salle=".$_REQUEST['id_salle'], "", PL_LUNDI, true);

  $cts->add($pl,true);

  $frm = new form("searchpl","index.php",false,"POST","Nouvelle recherche");
  if($site->user->is_in_group("gestion_ae"))
  $frm->add_hidden("action","searchpl");
  else
  $frm->add_hidden("action","affich");
  if ( isset($_REQUEST["fallback"]) )
    $frm->add_hidden("fallback",$_REQUEST["fallback"]);
  $frm->add_select_field("id_salle","Lieu",$lieux, $_REQUEST['id_salle']);
  $frm->add_submit("afficher","Afficher le planning");
  $cts->add($frm,true);

  $site->add_contents($cts);
  $site->end_page();

  exit();
}
else if( $_REQUEST['action'] == "details" )
{
  $site->add_css("css/weekplanning.css");

    $site->start_page("services","Planning");
    $cts = new contents("<a href=\"index.php\">Planning</a> / ".$lieux[$_REQUEST['id_salle']]." / Affichage");

    if(isset($_REQUEST['semainedeux']))
    {
      $cts->add_paragraph("<a href=\"index.php?action=affich&id_salle=".$_REQUEST['id_salle']."&semainedeux\">Affichage</a>");
    }
    else
    {
      $cts->add_paragraph("<a href=\"index.php?action=affich&id_salle=".$_REQUEST['id_salle']."\">Affichage</a>");
    }

    $test = new requete($site->db, "SELECT id_utilisateur
             FROM pl_gap_user
               WHERE id_gap='".$_REQUEST['id_gap']."' AND id_utilisateur='".$site->user->id."'");

    $planning = new planning($site->db,$site->dbrw);
  $planning->load_by_id($_REQUEST['id_salle']);

  if ( !$planning->is_valid() )
      $site->error_not_found("services");

    if($test->lines == 0)
    {
      $planning->add_user_to_gap($_REQUEST['id_gap'], $site->user->id);
     }
     else
     {
      while($row = $test->get_row())
      {
        if($row['id_utilisateur']==$site->user->id)
        {
          $planning->remove_user_from_gap($_REQUEST['id_gap'], $site->user->id);
        }
      }
     }

    $sql =
    "SELECT id_gap, start_gap, end_gap, pl_gap.id_planning,
     COALESCE(utl_etu_utbm.surnom_utbm, CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl), '(personne)') AS texte
     FROM pl_gap
     LEFT JOIN pl_gap_user USING(id_gap)
     LEFT JOIN utilisateurs USING(id_utilisateur)
     LEFT JOIN utl_etu_utbm USING (id_utilisateur)
     WHERE pl_gap.id_planning='".$_REQUEST['id_salle']."'";

   if(isset($_REQUEST['semainedeux']))
  {
    $pl = new weekplanning ("Planning semaine B", $site->db, $sql, "id_gap", "start_gap", "end_gap", "texte", "index.php?action=searchpl&id_salle=".$_REQUEST['id_salle'], "index.php?action=details&id_salle=".$_REQUEST['id_salle']."&semainedeux", "", PL_LUNDI, true);
  }
  else
  {
    $pl = new weekplanning ("Planning semaine A", $site->db, $sql, "id_gap", "start_gap", "end_gap", "texte", "index.php?action=searchpl&id_salle=".$_REQUEST['id_salle'], "index.php?action=details&id_salle=".$_REQUEST['id_salle'], "", PL_LUNDI, true);
  }

  $cts->add($pl,true);

  $frm = new form("searchpl","index.php",false,"POST","Nouvelle recherche");
  $frm->add_hidden("action","searchpl");
  if ( isset($_REQUEST["fallback"]) )
    $frm->add_hidden("fallback",$_REQUEST["fallback"]);
  $frm->add_select_field("id_salle","Lieu",$lieux, $_REQUEST['id_salle']);
  $frm->add_submit("afficher","Afficher le planning");
  $cts->add($frm,true);

  $site->add_contents($cts);
  $site->end_page();
  exit();
}
else if( $_REQUEST['action'] == "reinit" )
{
   // On supprime tous les utilisateurs de tous les creneaux du planning a reinitialiser.
   $planning = new planning($site->db,$site->dbrw);
   $planning->load_by_id($_REQUEST['id_salle']);

   $gap = new requete($site->db, "SELECT id_gap
             FROM pl_gap
               WHERE id_planning='".$_REQUEST['id_salle']."'");

   while( $row = $gap->get_row() ) {
     $users = new requete($site->db, "SELECT id_utilisateur
             FROM pl_gap_user
             WHERE id_planning='".$_REQUEST['id_salle']."' AND id_gap='".$row['id_gap']."'");

     while( $row2 = $users->get_row() ) {
       $planning->remove_user_from_gap($row['id_gap'], $row2['id_utilisateur']);
     }
   }

   // On change (on essaye de changer plutot) les dates du planning.
   $today = strtotime(date(Y-m-d));
   $today['month'] += 6;

   /*$setdates = new requete($site->db, "UPDATE pl_planning
               SET start_date_planning = '".strtotime(date(Y-m-d))."',
                   end_date_planning = '".$today."'
               WHERE id_planning='".$_REQUEST['id_salle']."'");*/

   $site->add_css("css/weekplanning.css");
   $site->start_page("services","Planning");
   $cts = new contents("<a href=\"index.php\">Planning</a> / Administration");

   //if( $setdates->lines > 0 ) {
     $cts->add_paragraph("Planning réinitialisé avec succès !");
     $cts->add_paragraph("<a href=\"index.php?action=admin\">Retour</a>");
   /*}
   else {
     $cts->add_paragraph("Erreur lors de la réinitialisation.");
     $cts->add_paragraph("<a href=\"index.php?action=admin\">Retour</a>");
   }*/

   $site->add_contents($cts);
   $site->end_page();
   exit();
}
else if( $_REQUEST['action'] == "supp" )
{
  $planning = new planning($site->db,$site->dbrw);
  $planning->load_by_id($_REQUEST['id_salle']);
  $planning->remove();

  $site->add_css("css/weekplanning.css");
  $site->start_page("services","Planning");
  $cts = new contents("<a href=\"index.php\">Planning</a> / Administration");

  $cts->add_paragraph("Planning supprimé avec succès !");
  $cts->add_paragraph("<a href=\"index.php?action=admin\">Retour</a>");

  $site->add_contents($cts);
  $site->end_page();
  exit();
}
else if( $_REQUEST['action'] == "admin" )
{
  $site->add_css("css/weekplanning.css");

  $site->start_page("services","Planning");
  $cts = new contents("<a href=\"index.php\">Planning</a> / Administration");

  $frm = new form("reinit","index.php",false,"POST","Réinitialiser un planning");
  $frm->add_hidden("action","reinit");
  $frm->add_select_field("id_salle","Lieu",$lieux, $_REQUEST['id_salle']);
  $frm->add_submit("reinit","Réinitialiser");
  $cts->add($frm,true);

  $frm = new form("supp","index.php",false,"POST","Supprimer un planning");
  $frm->add_hidden("action","supp");
  $frm->add_select_field("id_salle","Lieu",$lieux, $_REQUEST['id_salle']);
  $frm->add_submit("supp","Supprimer");
  $cts->add($frm,true);

  // Pas coherent pour le moment. Ajout d'une fonctionnalite planning pour chaque club ?
  /*$frm = new form("create","index.php",false,"POST","Créer un planning");
  $frm->add_hidden("action","create");
  $frm->add_submit("reinit","Créer");
  $cts->add($frm,true);*/

  $site->add_contents($cts);
  $site->end_page();
  exit();
}

$site->start_page("services","Planning");

$cts = new contents("<a href=\"index.php\">Planning</a>");

if($site->user->is_in_group("gestion_ae"))
{
  $cts->add_paragraph("<a href=\"index.php?action=admin\">Administration</a>");
}

$frm = new form("searchpl","index.php",false,"POST","Consulter un planning");

if($site->user->is_in_group("gestion_ae") || $site->user->is_in_group("foyer_barman") || $site->user->is_in_group("kfet_barman") || $site->user->is_in_group("bds-bureau"))
  $frm->add_hidden("action","searchpl");
else
  $frm->add_hidden("action","affich");

$frm->add_select_field("id_salle","Lieu",$lieux, $_REQUEST['id_salle']);
$frm->add_submit("afficher","Afficher le planning");
$cts->add($frm,true);

$site->add_contents($cts);
$site->end_page();

?>
