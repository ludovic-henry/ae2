<?php

/* Copyright 2007
 * - Julien Etelain < julien dot etelain at gmail dot com >
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Simon Lopez < simon DOT lopez AT ayolo DOT org >
 * - Benjamin Collet < bcollet AT oxynux DOT org >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License a
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

$topdir = "./";
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/cts/user.inc.php");
require_once($topdir . "include/graph.inc.php");
require_once($topdir."include/cts/progressbar.inc.php");
$site = new site ();

$site->start_page("presentation","Statistiques");
$cts = new contents("Statistiques");

if(!$site->user->is_in_group ("gestion_ae"))
{
  $tabs = array(array("","stats.php", "Informations"),
                array("utilisateurs","stats.php?view=utilisateurs", "Utilisateurs"),
                array("sas","stats.php?view=sas", "SAS"),
                );
  if($site->user->is_asso_role ( 2, 2 ))
    $tabs[]=array("comptoirs","stats.php?view=comptoirs", "Comptoirs");
}
else
{
  $tabs = array(array("","stats.php", "Informations"),
                array("cotisants","stats.php?view=cotisants", "Cotisants"),
                array("utilisateurs","stats.php?view=utilisateurs", "Utilisateurs"),
                array("sas","stats.php?view=sas", "SAS"),
                array("actifs","stats.php?view=actifs", "Membres actifs"),
                array("forum","stats.php?view=forum", "Forum"),
                array("comptoirs","stats.php?view=comptoirs", "Comptoirs"),
                array("elections","stats.php?view=elections", "Élections")
                );
}

$cts->add(new tabshead($tabs,$_REQUEST["view"]));

if (($_REQUEST["view"] == "cotisants" ) && isset($_REQUEST['bananas']) && ($_REQUEST['bananas'] == "cuitasaussi"))
{
  if (!$site->user->is_in_group("gestion_ae"))
    exit();

  $datas = array("années" => "Cotisants");

  $req = new requete($site->db,
  "SELECT COUNT( DISTINCT `id_utilisateur` ) nbcotis, `fin_semestre` ".
  "FROM `ae_cotisations` , ( ".
    "SELECT `date_fin_cotis` fin_semestre FROM `ae_cotisations` ".
    "GROUP BY `date_fin_cotis`)semestres ".
  "WHERE `date_fin_cotis` >= fin_semestre ".
  "AND `date_cotis` <= CONCAT( fin_semestre, ' 00:00:00' ) ".
  "GROUP BY `fin_semestre` ".
  "ORDER BY fin_semestre"
  );

  while ($row = $req->get_row())
  {
    $date = explode('-', $row['fin_semestre']);
    if (($date[1] == '08') && ($date[2] == '15'))
      $semestre = 'P'.substr($date[0], 2);
    elseif (($date[1] == '02') && ($date[2] == '15'))
      $semestre = 'A'.substr(($date[0]-1), 2);

    $datas[$semestre] = $row['nbcotis'];
  }

  $hist = new histogram($datas, "Top 10", true);
  $hist->png_render();
  $hist->destroy();

  exit();
}
elseif ( $_REQUEST["view"] == "cotisants" )
{
  if (!$site->user->is_in_group ("gestion_ae"))
    $site->error_forbidden("presentation","group",9);

  $req = new requete($site->db,"SELECT COUNT(*) FROM `utilisateurs` WHERE `ancien_etudiant_utl`='0'");
  list($total) = $req->get_row();

  $req = new requete($site->db,"SELECT COUNT(DISTINCT (id_utilisateur)) FROM `ae_cotisations` WHERE `date_fin_cotis` > NOW()");
  list($cotisants) = $req->get_row();

  if ( $site->user->is_in_group("gestion_ae") )
  {
    $cts2 = new contents("Évolution du nombre de cotisants");
    $cts2->add_paragraph("<center><img src=\"./stats.php?view=cotisants&bananas=cuitasaussi\" alt=\"Évolution du nombre de cotisants\" /></center>");
    $cts2->add_paragraph("Cotisants ce semestre : $cotisants, ".round($cotisants*100/$total,1)." % des inscrits hors anciens");
    $cts->add($cts2,true);
  }

  $req = new requete($site->db,"SELECT ROUND(COUNT(*)*100/$cotisants,1) AS `count`, `mode_paiement_cotis` FROM `ae_cotisations` WHERE `date_fin_cotis` > NOW() GROUP BY `mode_paiement_cotis` ORDER BY `count` DESC");

  $tbl = new sqltable(
    "paie",
    "Mode de paiement des cotisations", $req, "",
    "",
    array("count"=>"%","mode_paiement_cotis"=>"Mode"),
    array(), array(),
    array("mode_paiement_cotis"=> array(1=>"Espèces",2=>"Carte bleu",3=>"Chèque",4=>"Administration",5=>"E-Boutic"))
    );
  $cts->add($tbl,true);

  $req = new requete($site->db,"SELECT SUM(a_pris_carte),SUM(a_pris_cadeau)  FROM `ae_cotisations` WHERE `date_fin_cotis` > NOW()");

  list($nbcarte,$nbcadeau) = $req->get_row();

  $cts->add_title(2,"Retraits");
  $cts->add_paragraph("".round($nbcarte*100/$cotisants,1)."% ont pris leur carte de membre");
  $cts->add_paragraph("".round($nbcadeau*100/$cotisants,1)."% ont pris leur cadeau");


  $req = new requete($site->db,"SELECT ROUND(SUM(`ae_utl`='1')*100/$cotisants,1) AS `count`, ROUND(SUM(`ae_utl`='1')*100/SUM(IF (ancien_etudiant_utl='0' OR ae_utl='1',1,0)),1) AS `taux`,IF(`utl_etu_utbm`.`promo_utbm` IS NULL,0,`utl_etu_utbm`.`promo_utbm`)AS `promo` FROM `utilisateurs` LEFT JOIN `utl_etu_utbm` USING(`id_utilisateur`) GROUP BY `promo` ORDER BY `count` DESC");

  $tbl = new sqltable(
    "paie",
    "Distribution par promo", $req, "",
    "",
    array("count"=>"%","taux"=>"Taux de cotisants","promo"=>"Promo"),
    array(), array(),
    array()
    );
  $cts->add($tbl,true);

  $req = new requete($site->db,"SELECT ROUND(SUM(`ae_utl`='1')*100/$cotisants,1) AS `count`, ROUND(SUM(`ae_utl`='1')*100/SUM(IF (ancien_etudiant_utl='0' OR ae_utl='1',1,0)),1) AS `taux`,IF(`utl_etu_utbm`.`departement_utbm` IS NULL,'na',`utl_etu_utbm`.`departement_utbm`) AS `dep` FROM `utilisateurs` LEFT JOIN `utl_etu_utbm` USING(`id_utilisateur`) GROUP BY `dep` ORDER BY `count` DESC");

  $tbl = new sqltable(
    "paie",
    "Distribution par departement", $req, "",
    "",
    array("count"=>"%","taux"=>"Taux de cotisants","dep"=>"Branche"),
    array(), array(),
    array("dep"=>$GLOBALS["utbm_departements"])
    );
  $cts->add($tbl,true);



  $month = date("m");
  $day = date("d");

  if ( ($month >= 2 && $month < 8) || ($month == 8 && $day < 15) )
    $debut_semestre = date("Y")."-02-01";
  else if ( $month >= 9 ||  ($month == 8 && $day >= 15))
    $debut_semestre = date("Y")."-08-15";
  else
    $debut_semestre = (date("Y")-1)."-08-15";

  $cts->add_title(2,"Carte AE");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT ae_cotisations.id_utilisateur)  FROM `ae_cotisations` INNER JOIN cpt_debitfacture ON(ae_cotisations.`id_utilisateur`=cpt_debitfacture.`id_utilisateur_client`) WHERE `mode_paiement`='AE' AND `date_fin_cotis` > NOW() AND `date_facture`>'$debut_semestre'");

  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont utilisé le paiement par carte AE ce semestre");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT ae_cotisations.id_utilisateur) FROM `ae_cotisations` INNER JOIN cpt_debitfacture ON(ae_cotisations.`id_utilisateur`=cpt_debitfacture.`id_utilisateur_client`) WHERE `id_comptoir`='3' AND `date_fin_cotis` > NOW() AND `date_facture`>'$debut_semestre'");

  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont utilisé l'e-boutic ce semestre");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT ae_cotisations.id_utilisateur) FROM `ae_cotisations` INNER JOIN cpt_debitfacture ON(ae_cotisations.`id_utilisateur`=cpt_debitfacture.`id_utilisateur_client`) WHERE `id_comptoir`='1' AND `date_fin_cotis` > NOW() AND `date_facture`>'$debut_semestre'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont consomé à la kfet ce semestre");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT ae_cotisations.id_utilisateur) FROM `ae_cotisations` INNER JOIN cpt_debitfacture ON(ae_cotisations.`id_utilisateur`=cpt_debitfacture.`id_utilisateur_client`) WHERE `id_comptoir`='2' AND `date_fin_cotis` > NOW() AND `date_facture`>'$debut_semestre'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont consomé au foyer ce semestre");


  $cts->add_title(2,"Matmatronch");

  $req = new requete($site->db,"SELECT COUNT(*) FROM `ae_cotisations` INNER JOIN utilisateurs USING(`id_utilisateur`) WHERE `date_fin_cotis` > NOW() AND `date_maj_utl`>'$debut_semestre'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont mis à jour leur fiche matmatronch ce semestre");

  $req = new requete($site->db,"SELECT COUNT(*) FROM `ae_cotisations` INNER JOIN utilisateurs USING(`id_utilisateur`) WHERE `date_fin_cotis` > NOW() AND `hash_utl`='valid'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont bien activé leur compte");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT ae_cotisations.id_utilisateur) FROM `ae_cotisations` INNER JOIN asso_membre USING(`id_utilisateur`) WHERE `date_fin_cotis` > NOW() AND ( `date_fin` IS NULL OR `date_fin`>'$debut_semestre' ) ");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% sont inscrits à une activité (sur le site)");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT ae_cotisations.id_utilisateur) FROM `ae_cotisations` LEFT JOIN parrains AS p1 USING(`id_utilisateur`) LEFT JOIN parrains AS p2 ON(ae_cotisations.`id_utilisateur`=p2.`id_utilisateur_fillot`) WHERE `date_fin_cotis` > NOW() AND (p2.`id_utilisateur` IS NOT NULL OR p1.`id_utilisateur` IS NOT NULL )");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont un parrain ou un fillot renseigné sur le site");


  $cts->add_title(2,"Site ae.utbm.fr");

  $req = new requete($site->db,"SELECT COUNT(*) FROM `ae_cotisations` INNER JOIN utilisateurs USING(`id_utilisateur`) WHERE `date_fin_cotis` > NOW() AND `droit_image_utl`='1'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont accordé leur droit à l'image de façon systèmatique");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT ae_cotisations.id_utilisateur) FROM `ae_cotisations` INNER JOIN sdn_a_repondu USING(`id_utilisateur`) WHERE `date_fin_cotis` > NOW() AND `date_reponse`>'$debut_semestre'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont répondu à un sondage ce semestre sur le site");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT ae_cotisations.id_utilisateur) FROM `ae_cotisations` INNER JOIN utilisateurs USING(`id_utilisateur`) WHERE `date_fin_cotis` > NOW() AND `derniere_visite_utl`>'$debut_semestre'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont consulté le site ce semestre");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT ae_cotisations.id_utilisateur) FROM `ae_cotisations` INNER JOIN utilisateurs USING(`id_utilisateur`) WHERE `date_fin_cotis` > NOW() AND DATEDIFF(NOW(),`derniere_visite_utl`) < 30 ");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont consulté le site dans les 30 derniers jours");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT ae_cotisations.id_utilisateur) FROM `ae_cotisations` INNER JOIN utilisateurs USING(`id_utilisateur`) WHERE `date_fin_cotis` > NOW() AND DATEDIFF(NOW(),`derniere_visite_utl`) < 7 ");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont consulté le site dans les 7 derniers jours");

  $cts->add_title(2,"Vrac");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT ae_cotisations.id_utilisateur) FROM `ae_cotisations` INNER JOIN inv_emprunt USING(`id_utilisateur`) WHERE `date_fin_cotis` > NOW() AND `date_demande_emp`>'$debut_semestre'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$cotisants,1)."% ont fait un emprunt de matériel ce semestre (livres, video projecteur, tables...)");

}
elseif ( $_REQUEST["view"] == "utilisateurs" )
{
  $req = new requete($site->db,"SELECT COUNT(*) FROM `utilisateurs` WHERE `ancien_etudiant_utl`='0'");
  list($total) = $req->get_row();

  if ( $site->user->is_in_group("gestion_ae") )
    $cts->add_paragraph("Total : $total");

  $req = new requete($site->db,"SELECT ROUND(COUNT(*)*100/$total,1) AS `count`, IF(`utl_etu_utbm`.`promo_utbm` IS NULL,0,`utl_etu_utbm`.`promo_utbm`)AS `promo` FROM `utilisateurs` LEFT JOIN `utl_etu_utbm` USING(`id_utilisateur`) WHERE `ancien_etudiant_utl`='0'  GROUP BY `promo` ORDER BY `count` DESC");

  $tbl = new sqltable(
    "paie",
    "Distribution par promo", $req, "",
    "",
    array("count"=>"%","promo"=>"Promo"),
    array(), array(),
    array()
    );
  $cts->add($tbl,true);


  $req = new requete($site->db,"SELECT ROUND(COUNT(*)*100/$total,1) AS `count`, IF(`utl_etu_utbm`.`departement_utbm` IS NULL,'na',`utl_etu_utbm`.`departement_utbm`) AS `dep` FROM `utilisateurs` LEFT JOIN `utl_etu_utbm` USING(`id_utilisateur`) WHERE `ancien_etudiant_utl`='0'  GROUP BY `dep` ORDER BY `count` DESC");

  $tbl = new sqltable(
    "dep",
    "Distribution par departement", $req, "",
    "",
    array("count"=>"%","dep"=>"Branche"),
    array(), array(),
    array("dep"=>$GLOBALS["utbm_departements"])
    );
  $cts->add($tbl,true);

  $month = date("m");
  $day = date("d");

  if ( ($month >= 2 && $month < 8) || ($month == 8 && $day < 15) )
    $debut_semestre = date("Y")."-02-01";
  else if ( $month >= 9 ||  ($month == 8 && $day >= 15))
    $debut_semestre = date("Y")."-08-15";
  else
    $debut_semestre = (date("Y")-1)."-08-15";


  $cts->add_title(2,"Matmatronch");

  $req = new requete($site->db,"SELECT COUNT(*) FROM `utilisateurs` WHERE `ancien_etudiant_utl`='0' AND `date_maj_utl`>'$debut_semestre'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$total,1)."% ont mis à jour leur fiche matmatronch ce semestre");

  $req = new requete($site->db,"SELECT COUNT(*) FROM `utilisateurs` WHERE `ancien_etudiant_utl`='0' AND `hash_utl`='valid'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$total,1)."% ont bien activé leur compte");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT utilisateurs.id_utilisateur) FROM `utilisateurs` INNER JOIN asso_membre USING(`id_utilisateur`) WHERE `ancien_etudiant_utl`='0' AND ( `date_fin` IS NULL OR `date_fin`>'$debut_semestre' ) ");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$total,1)."% sont inscrits à une activité (sur le site)");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT utilisateurs.id_utilisateur) FROM `utilisateurs` LEFT JOIN parrains AS p1 USING(`id_utilisateur`) LEFT JOIN parrains AS p2 ON(utilisateurs.`id_utilisateur`=p2.`id_utilisateur_fillot`) WHERE `ancien_etudiant_utl`='0' AND (p2.`id_utilisateur` IS NOT NULL OR p1.`id_utilisateur` IS NOT NULL )");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$total,1)."% ont un parrain ou un fillot renseigné sur le site");


  $cts->add_title(2,"Site ae.utbm.fr");

  $req = new requete($site->db,"SELECT COUNT(*) FROM `utilisateurs` WHERE `ancien_etudiant_utl`='0' AND `droit_image_utl`='1'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$total,1)."% ont accordé leur droit à l'image de façon systèmatique");

  $req = new requete($site->db,"SELECT COUNT(DISTINCT utilisateurs.id_utilisateur) FROM `utilisateurs` INNER JOIN sdn_a_repondu USING(`id_utilisateur`) WHERE `ancien_etudiant_utl`='0' AND `date_reponse`>'$debut_semestre'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$total,1)."% ont répondu à un sondage ce semestre sur le site");

  $req = new requete($site->db,"SELECT COUNT(*) FROM `utilisateurs` WHERE `ancien_etudiant_utl`='0' AND `derniere_visite_utl`>'$debut_semestre'");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$total,1)."% ont consulté le site ce semestre");

  $req = new requete($site->db,"SELECT COUNT(*) FROM `utilisateurs` WHERE `ancien_etudiant_utl`='0' AND DATEDIFF(NOW(),`derniere_visite_utl`) < 30 ");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$total,1)."% ont consulté le site dans les 30 derniers jours");

  $req = new requete($site->db,"SELECT COUNT(*) FROM `utilisateurs` WHERE `ancien_etudiant_utl`='0' AND DATEDIFF(NOW(),`derniere_visite_utl`) < 7 ");
  list($nbutil) = $req->get_row();
  $cts->add_paragraph("".round($nbutil*100/$total,1)."% ont consulté le site dans les 7 derniers jours");
}
elseif ( $_REQUEST["view"] == "sas" )
{

  $cts->add_title(2,"Photos");

  $req = new requete($site->db,"SELECT COUNT(*) FROM `sas_photos`");
  list($total) = $req->get_row();

  $cts->add_paragraph("".$total." photos (et vidéos) dans le SAS");

  $req = new requete($site->db,"SELECT COUNT(*) FROM `sas_photos` WHERE droits_acquis=1");
  list($nbphotos) = $req->get_row();

  $cts->add_paragraph("".round($nbphotos*100/$total,1)."% photos dont tous les droits sont acquis");
  
    $req = new requete($site->db,"SELECT COUNT(*) FROM `sas_photos` WHERE droits_acquis=0 AND incomplet=0");
  list($nbphotos) = $req->get_row();

  $cts->add_paragraph("".round($nbphotos*100/$total,1)."% photos complètes dont les droits ne sont pas acquis");
  
  
  $req = new requete($site->db,"SELECT COUNT(*) FROM `sas_photos` WHERE incomplet=1");
  list($nbphotos) = $req->get_row();

  $cts->add_paragraph("".round($nbphotos*100/$total,1)."% photos incomplètes");




  $req = new requete ($site->db, "SELECT COUNT(sas_photos.id_photo) as `count`, `utilisateurs`.`id_utilisateur`, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur` " .
          "FROM sas_photos " .
          "INNER JOIN utilisateurs ON sas_photos.id_utilisateur=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` ".
          "GROUP BY sas_photos.id_utilisateur " .
          "ORDER BY count DESC LIMIT 50");

  $lst = new itemlist("Les meilleurs contributeurs (50)");
  $n=1;

  {
    while ( $row = $req->get_row() )
    {
      $lst->add("$n : <a href=\"user.php?id_utilisateur=".$row["id_utilisateur"]."\">".htmlentities($row["nom_utilisateur"],ENT_NOQUOTES,"UTF-8")."</a> (".$row['count']." photos)");
      $n++;
    }
  }

  $cts->add($lst,true);
  $month = date("m");
  $day = date("d");

  if ( ($month >= 2 && $month < 8) || ($month == 8 && $day < 15) )
    $debut_semestre = date("Y")."-02-01";
  else if ( $month >= 9 ||  ($month == 8 && $day >= 15))
    $debut_semestre = date("Y")."-08-15";
  else
    $debut_semestre = (date("Y")-1)."-08-15";


  $req = new requete ($site->db, "SELECT COUNT(sas_personnes_photos.id_photo) as `count`, `utilisateurs`.`id_utilisateur`, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur` " .
          "FROM sas_personnes_photos " .
          "INNER JOIN utilisateurs ON sas_personnes_photos.id_utilisateur=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` ".
          "GROUP BY sas_personnes_photos.id_utilisateur " .
          "ORDER BY count DESC LIMIT 50");

  $req2 = new requete ($site->db, "SELECT COUNT(sas_personnes_photos.id_photo) as `count`, `utilisateurs`.`id_utilisateur`, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur` " .
          "FROM sas_personnes_photos " .
          "INNER JOIN utilisateurs ON sas_personnes_photos.id_utilisateur=utilisateurs.id_utilisateur " .
	  "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` ".
	  "INNER JOIN `sas_photos` ON `sas_photos`.`id_photo`=`sas_personnes_photos`.`id_photo` ".
	  "WHERE `sas_photos`.`date_ajout_ph` > '$debut_semestre' ".
          "GROUP BY sas_personnes_photos.id_utilisateur " .
          "ORDER BY count DESC LIMIT 50");


  $lst = new itemlist("Les plus photographi&eacute;s (tout semestres) (50)");
  $lst2 = new itemlist("Les plus photographi&eacute;s (ce semestre) (50)");
  $n=1;

  if(!$site->user->is_in_group("sas_admin") && !$site->user->is_in_group("gestion_ae"))
  {
    while ( $row = $req->get_row() )
    {
      $lst->add("$n : <a href=\"user.php?id_utilisateur=".$row["id_utilisateur"]."\">".htmlentities($row["nom_utilisateur"],ENT_NOQUOTES,"UTF-8")."</a>");
      $n++;
    }
    $n = 1;
    while ( $row = $req2->get_row() )
    {
      $lst2->add("$n : <a href=\"user.php?id_utilisateur=".$row["id_utilisateur"]."\">".htmlentities($row["nom_utilisateur"],ENT_NOQUOTES,"UTF-8")."</a>");
      $n++;
    }
  }
  else
  {
    while ( $row = $req->get_row() )
    {
      $lst->add("$n : <a href=\"user.php?id_utilisateur=".$row["id_utilisateur"]."\">".htmlentities($row["nom_utilisateur"],ENT_NOQUOTES,"UTF-8")."</a> (".$row['count']." photos)");
      $n++;
    }
    $n = 1;
    while ( $row = $req2->get_row() )
    {
      $lst2->add("$n : <a href=\"user.php?id_utilisateur=".$row["id_utilisateur"]."\">".htmlentities($row["nom_utilisateur"],ENT_NOQUOTES,"UTF-8")."</a> (".$row['count']." photos)");
      $n++;
    }
  }

  $cts->add($lst,true);
  $cts->add($lst2,true);
  $req = new requete ($site->db, "SELECT COUNT(sas_photos.id_photo) as `count`, `sas_photos`.`id_utilisateur_moderateur` as id_utilisateur, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur` " .
          "FROM sas_photos " .
          "INNER JOIN utilisateurs ON sas_photos.id_utilisateur_moderateur=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` ".
          "GROUP BY sas_photos.id_utilisateur_moderateur " .
          "ORDER BY count DESC LIMIT 50");

  $lst = new itemlist("Mod&eacute;ration (50)");
  $n=1;

  {
    while ( $row = $req->get_row() )
    {
      $lst->add("$n : <a href=\"user.php?id_utilisateur=".$row["id_utilisateur"]."\">".htmlentities($row["nom_utilisateur"],ENT_NOQUOTES,"UTF-8")."</a> (".$row['count']." photos)");
      $n++;
    }
  }

  $cts->add($lst,true);

  $req = new requete ($site->db, "SELECT COUNT(*) as `count`, `sas_personnes_photos`.`id_utl_propose` as id_utilisateur, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur` " .
          "FROM sas_personnes_photos " .
          "INNER JOIN utilisateurs ON sas_personnes_photos.id_utl_propose=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` ".
          "GROUP BY sas_personnes_photos.id_utl_propose " .
          "ORDER BY count DESC LIMIT 50");

  $lst = new itemlist("Proposition de nom (50)");
  $n=1;

  {
    while ( $row = $req->get_row() )
    {
      $lst->add("$n : <a href=\"user.php?id_utilisateur=".$row["id_utilisateur"]."\">".htmlentities($row["nom_utilisateur"],ENT_NOQUOTES,"UTF-8")."</a> (".$row['count']." propositions)");
      $n++;
    }
  }

  $cts->add($lst,true);

  $req = new requete ($site->db, "SELECT COUNT(*) as `count`, `sas_personnes_photos`.`id_utl_modere` as id_utilisateur, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur` " .
          "FROM sas_personnes_photos " .
          "INNER JOIN utilisateurs ON sas_personnes_photos.id_utl_modere=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` ".
	  "WHERE (sas_personnes_photos.id_utl_propose <> sas_personnes_photos.id_utl_modere OR sas_personnes_photos.id_utl_propose IS NULL)".
          "GROUP BY sas_personnes_photos.id_utl_modere " .
          "ORDER BY count DESC LIMIT 50");

  $lst = new itemlist("Mod&eacute;ration de nom (50)");
  $n=1;

  {
    while ( $row = $req->get_row() )
    {
      $lst->add("$n : <a href=\"user.php?id_utilisateur=".$row["id_utilisateur"]."\">".htmlentities($row["nom_utilisateur"],ENT_NOQUOTES,"UTF-8")."</a> (".$row['count']." propositions)");
      $n++;
    }
  }

  $cts->add($lst,true);


}
elseif ( $_REQUEST["view"] == "forum" )
{
  if (!$site->user->is_in_group ("gestion_ae"))
    $site->error_forbidden("presentation","group",9);

  if (isset($_REQUEST['toptenimg']))
    {
      $req = "SELECT
                COUNT(`id_message`) as totmesg
              , `utilisateurs`.`alias_utl`
              , `utl_etu_utbm`.`surnom_utbm`
          FROM
                `frm_message`
          INNER JOIN
                `utilisateurs`
          USING (`id_utilisateur`)
          INNER JOIN
                `utl_etu_utbm`
          USING (`id_utilisateur`)
          GROUP BY
                 `id_utilisateur`
          ORDER BY
                 COUNT(`id_message`) DESC LIMIT 10";

      $rs = new requete($site->db, $req);


      $datas = array("utilisateur" => "Nbmessages");


      while ($plouf = $rs->get_row())
      {
        if ($plouf['surnom_utbm'] != null)
          $nom = explode(' ', $plouf['surnom_utbm']);
        else
          $nom = explode(' ', $plouf['alias_utl']);
        $nom = $nom[0];

        $datas[utf8_decode($nom)] = $plouf['totmesg'];
      }


      $hist = new histogram($datas, "Top 10");

      $hist->png_render();

      $hist->destroy();

      exit();

    }

  if (isset($_REQUEST['mesgbyday']))
    {
      if (!isset($_REQUEST['db']))
  $db = date("Y")."-01-01";
      else
  $db = $_REQUEST['db'];

      if (!isset($_REQUEST['de']))
  $de = date("Y-m-d");
      else
  $de = $_REQUEST['de'];

      $db = mysql_real_escape_string($db);
      $de = mysql_real_escape_string($de);


      $query =
  "SELECT
            DATE_FORMAT(date_message,'%Y-%m-%d') AS `datemesg`
            , COUNT(id_message) AS `nbmesg`
     FROM
            `frm_message`
     WHERE
            `date_message` >= '".$db."'
     AND
            `date_message` <= '".$de."'
     GROUP BY
            `datemesg`";

      $req = new requete($site->db, $query);

      $i = 0;

      $step = (int) ($req->lines / 5);

      while ($rs = $req->get_row())
  {
    if (($i % $step) == 0)
      $xtics[$i]  = $rs['datemesg'];
    $coords[] = array('x' => $i,
          'y' => $rs['nbmesg']);
    $i++;
  }


      $grp = new graphic("",
       "messages par jour",
       $coords,
       false,
       $xtics);

      $grp->png_render();

      $grp->destroy_graph();

      exit();
    }



  $fcts = new contents("Statistiques du forum");
  $fcts->add_title(1, "Top 10 des posteurs");
  $fcts->add_paragraph("<center><img src=\"./stats.php?view=forum&toptenimg\" alt=\"top10\" /></center>");

  $fcts->add_title(1, "Messages postés depuis le début de l'année");
  $fcts->add_paragraph("<center><img src=\"./stats.php?view=forum&mesgbyday\" alt=\"Messages par jour\" /></center>");

  $fcts->add_title(1, "Messages postés les 30 derniers jours");

  /* statistiques sur 30 jours */
  $db = date("Y-m-d", time() - (30 * 24 * 3600));

  $fcts->add_paragraph("<center><img src=\"./stats.php?view=forum&mesgbyday&db=".$db."&de=".date("Y-m-d").
          "\" alt=\"Messages par jour\" /></center>");

  $cts->add($fcts);

}

elseif ( ($site->user->is_in_group ("gestion_ae") || $site->user->is_asso_role ( 2, 2 )) && $_REQUEST["view"] == "comptoirs" )
{
  $site->add_css("css/comptoirs.css");

  $month = date("m");
  $day = date("d");

  if ( ($month >= 2 && $month < 8) || ($month == 8 && $day < 15) )
    $debut_semestre = date("Y")."-02-01";
  else if ( $month >= 9 ||  ($month == 8 && $day >= 15))
    $debut_semestre = date("Y")."-08-15";
  else
    $debut_semestre = (date("Y")-1)."-08-15";

  if (isset($_REQUEST["details"]))
  {
    $cts->add_title(2,"Consomateurs foyer: Top 100 (ce semestre)");
    $req = new requete ($site->db, "SELECT id_utilisateur, nom_utilisateur, total, promo_utbm, " .
        "GROUP_CONCAT(IF(role >=2 AND `date_fin` IS NULL AND id_asso_parent IS NULL, nom_asso, NULL) ORDER BY id_asso SEPARATOR ', ') assos, " .
        "ROUND(10000*total/(SELECT SUM(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit) FROM cpt_vendu
       		      INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture 
		      WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut_semestre' 
		      AND id_produit !=338 AND id_comptoir = 2),2) as pourcentage " .
        "FROM ( " .
          "SELECT `utilisateurs`.`id_utilisateur`, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, " .
          "ROUND(sum(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit)/100, 2) as total, promo_utbm " .
          "FROM cpt_vendu " .
          "INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture " .
          "INNER JOIN utilisateurs ON cpt_debitfacture.id_utilisateur_client=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
          "WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut_semestre' " .
          "AND id_produit !=338 " .
	  "AND id_comptoir = 2 ".
          "GROUP BY utilisateurs.id_utilisateur " .
          "ORDER BY total DESC LIMIT 100 " .
        ") top " .
        "LEFT JOIN asso_membre USING ( `id_utilisateur` ) " .
        "LEFT JOIN asso USING ( `id_asso` ) " .
        "GROUP BY id_utilisateur " .
        "ORDER BY total DESC");

    $cols = array( "=num" => "N°",
                  "nom_utilisateur" => "Utilisateur",
                  "promo_utbm" => "Promo",
                  "assos" =>"Associations");
    if (isset($_REQUEST["plus"]))
    {
      $cols["total"] = "Total";
      $cols["pourcentage"] = "Pourcentage vente";
    }

    $tbl = new sqltable("top10",
                        "Consomateurs foyer: Top 100 (ce semestre)", $req, "stats.php",
                         "id_utilisateur",
                         $cols,
                         array(),
                         array(),
                         array(),
                         true,
                         true,
                         array($site->user->id));

    $cts->add($tbl);

    $cts->add_title(2,"Consomateurs MDE: Top 100 (ce semestre)");
    $req = new requete ($site->db, "SELECT id_utilisateur, nom_utilisateur, total, promo_utbm, " .
        "GROUP_CONCAT(IF(role >=2 AND `date_fin` IS NULL AND id_asso_parent IS NULL, nom_asso, NULL) ORDER BY id_asso SEPARATOR ', ') assos, " .
	"ROUND(10000*total/(SELECT SUM(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit) FROM cpt_vendu
       		      INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture 
		      WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut_semestre' 
		      AND id_produit !=338 AND id_comptoir = 1),2) as pourcentage " .
        "FROM ( " .
          "SELECT `utilisateurs`.`id_utilisateur`, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, " .
          "ROUND(sum(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit)/100, 2) as total, promo_utbm " .
          "FROM cpt_vendu " .
          "INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture " .
          "INNER JOIN utilisateurs ON cpt_debitfacture.id_utilisateur_client=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
          "WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut_semestre' " .
          "AND id_produit !=338 " .
	  "AND id_comptoir = 1 ".
          "GROUP BY utilisateurs.id_utilisateur " .
          "ORDER BY total DESC LIMIT 100 " .
        ") top " .
        "LEFT JOIN asso_membre USING ( `id_utilisateur` ) " .
        "LEFT JOIN asso USING ( `id_asso` ) " .
        "GROUP BY id_utilisateur " .
        "ORDER BY total DESC");

    $cols = array( "=num" => "N°",
                  "nom_utilisateur" => "Utilisateur",
                  "promo_utbm" => "Promo",
                  "assos" =>"Associations");
    if (isset($_REQUEST["plus"]))
    {
      $cols["total"] = "Total";
      $cols["pourcentage"] = "Pourcentage vente";
    }

    $tbl = new sqltable("top10",
                        "Consomateurs MDE: Top 100 (ce semestre)", $req, "stats.php",
                         "id_utilisateur",
                         $cols,
                         array(),
                         array(),
                         array(),
                         true,
                         true,
                         array($site->user->id));

    $cts->add($tbl);

    $cts->add_title(2,"Consomateurs Gommette: Top 100 (ce semestre)");
    $req = new requete ($site->db, "SELECT id_utilisateur, nom_utilisateur, total, promo_utbm, " .
        "GROUP_CONCAT(IF(role >=2 AND `date_fin` IS NULL AND id_asso_parent IS NULL, nom_asso, NULL) ORDER BY id_asso SEPARATOR ', ') assos, " .
        "ROUND(10000*total/(SELECT SUM(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit) FROM cpt_vendu
       		      INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture 
		      WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut_semestre' 
		      AND id_produit !=338 AND id_comptoir = 35),2) as pourcentage " .
        "FROM ( " .
          "SELECT `utilisateurs`.`id_utilisateur`, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, " .
          "ROUND(sum(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit)/100, 2) as total, promo_utbm " .
          "FROM cpt_vendu " .
          "INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture " .
          "INNER JOIN utilisateurs ON cpt_debitfacture.id_utilisateur_client=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
          "WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut_semestre' " .
          "AND id_produit !=338 " .
	  "AND id_comptoir = 35 ".
          "GROUP BY utilisateurs.id_utilisateur " .
          "ORDER BY total DESC LIMIT 100 " .
        ") top " .
        "LEFT JOIN asso_membre USING ( `id_utilisateur` ) " .
        "LEFT JOIN asso USING ( `id_asso` ) " .
        "GROUP BY id_utilisateur " .
        "ORDER BY total DESC");

    $cols = array( "=num" => "N°",
                  "nom_utilisateur" => "Utilisateur",
                  "promo_utbm" => "Promo",
                  "assos" =>"Associations");
    if (isset($_REQUEST["plus"]))
    {
      $cols["total"] = "Total";
      $cols["pourcentage"] = "Pourcentage vente";
    }

    $tbl = new sqltable("top10",
                        "Consomateurs Gommette: Top 100 (ce semestre)", $req, "stats.php",
                         "id_utilisateur",
                         $cols,
                         array(),
                         array(),
                         array(),
                         true,
                         true,
                         array($site->user->id));

    $cts->add($tbl);

    $cts->add_title(2,"Consomateurs (tous les lieux de vie): Top 100 (ce semestre)");
    $req = new requete ($site->db, "SELECT id_utilisateur, nom_utilisateur, total, promo_utbm, " .
        "GROUP_CONCAT(IF(role >=2 AND `date_fin` IS NULL AND id_asso_parent IS NULL, nom_asso, NULL) ORDER BY id_asso SEPARATOR ', ') assos, " .
	"ROUND(10000*total/(SELECT SUM(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit) FROM cpt_vendu
       		      INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture 
		      WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut_semestre' 
		      AND id_produit !=338 AND  (id_comptoir = 1 OR  id_comptoir = 2 OR  id_comptoir = 35)),2) as pourcentage " .
        "FROM ( " .
          "SELECT `utilisateurs`.`id_utilisateur`, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, " .
          "ROUND(sum(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit)/100, 2) as total, promo_utbm " .
          "FROM cpt_vendu " .
          "INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture " .
          "INNER JOIN utilisateurs ON cpt_debitfacture.id_utilisateur_client=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
          "WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut_semestre' " .
          "AND id_produit !=338 " .
	  "AND (id_comptoir = 1 OR  id_comptoir = 2 OR  id_comptoir = 35) ".
          "GROUP BY utilisateurs.id_utilisateur " .
          "ORDER BY total DESC LIMIT 100 " .
        ") top " .
        "LEFT JOIN asso_membre USING ( `id_utilisateur` ) " .
        "LEFT JOIN asso USING ( `id_asso` ) " .
        "GROUP BY id_utilisateur " .
        "ORDER BY total DESC");

    $cols = array( "=num" => "N°",
                  "nom_utilisateur" => "Utilisateur",
                  "promo_utbm" => "Promo",
                  "assos" =>"Associations");
    if (isset($_REQUEST["plus"]))
    {
      $cols["total"] = "Total";
      $cols["pourcentage"] = "pourcentage vente";
    }

    $tbl = new sqltable("top10",
                        "Consomateurs (tous les lieux de vie): Top 100 (ce semestre)", $req, "stats.php",
                         "id_utilisateur",
                         $cols,
                         array(),
                         array(),
                         array(),
                         true,
                         true,
                         array($site->user->id));

    $cts->add($tbl);




    $cts->add_title(2,"Consomateurs : Top 100 (ce semestre)");
    $req = new requete ($site->db, "SELECT id_utilisateur, nom_utilisateur, total, promo_utbm, " .
        "GROUP_CONCAT(IF(role >=2 AND `date_fin` IS NULL AND id_asso_parent IS NULL, nom_asso, NULL) ORDER BY id_asso SEPARATOR ', ') assos, " .
	"ROUND(10000*total/(SELECT SUM(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit) FROM cpt_vendu
       		      INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture 
		      WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut_semestre' 
		      AND id_produit !=338),2) as pourcentage " .
        "FROM ( " .
          "SELECT `utilisateurs`.`id_utilisateur`, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, " .
          "ROUND(sum(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit)/100, 2) as total, promo_utbm " .
          "FROM cpt_vendu " .
          "INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture " .
          "INNER JOIN utilisateurs ON cpt_debitfacture.id_utilisateur_client=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
          "WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut_semestre' " .
          "AND id_produit !=338 " .
          "GROUP BY utilisateurs.id_utilisateur " .
          "ORDER BY total DESC LIMIT 100 " .
        ") top " .
        "LEFT JOIN asso_membre USING ( `id_utilisateur` ) " .
        "LEFT JOIN asso USING ( `id_asso` ) " .
        "GROUP BY id_utilisateur " .
        "ORDER BY total DESC");

    $cols = array( "=num" => "N°",
                  "nom_utilisateur" => "Utilisateur",
                  "promo_utbm" => "Promo",
                  "assos" =>"Associations");
    if (isset($_REQUEST["plus"]))
    {
      $cols["total"] = "Total";
      $cols["pourcentage"] = "pourcentage vente";
    }

    $tbl = new sqltable("top10",
                        "Consomateurs: Top 100 (ce semestre)", $req, "stats.php",
                         "id_utilisateur",
                         $cols,
                         array(),
                         array(),
                         array(),
                         true,
                         true,
                         array($site->user->id));

    $cts->add($tbl);


    $cts->add_title(2,"Consomateurs : Top 100 (tous les semestres)");
    $req = new requete ($site->db, "SELECT `utilisateurs`.`id_utilisateur`,  
           IF((MONTH(date_facture) BETWEEN 2 AND 7) OR (DAYOFMONTH(date_facture) <15 AND MONTH(date_facture) = 8) , CONCAT('P',YEAR(date_facture)), CONCAT('A',YEAR(date_facture))) as `semestre`, 
           IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`,    
           ROUND(sum(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit)/100, 2) as total, promo_utbm    
           FROM cpt_vendu    
           INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture    
           INNER JOIN utilisateurs ON cpt_debitfacture.id_utilisateur_client=utilisateurs.id_utilisateur    
           LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur`    
           WHERE cpt_debitfacture.mode_paiement='AE'   
           AND id_produit !=338    
           GROUP BY utilisateurs.id_utilisateur, semestre
           ORDER BY total DESC LIMIT 100");

    $cols = array( "=num" => "N°",
                  "nom_utilisateur" => "Utilisateur",
                  "promo_utbm" => "Promo",
                  "semestre" => "Semestre");
    if (isset($_REQUEST["plus"]))
    {
      $cols["total"] = "Total";
    }

    $tbl = new sqltable("touttop10",
                        "Consomateurs : Top 100 (tous les semestres)", $req, "stats.php",
                         "id_utilisateur",
                         $cols,
                         array(),
                         array(),
                         array(),
                         true,
                         true,
                         array($site->user->id));

    $cts->add($tbl);

    $cts->add_title(2,"Consomateurs (tous les semestres, cumulé) Top 100 (ce semestre)");
    $req = new requete ($site->db, "SELECT id_utilisateur, nom_utilisateur, total, promo_utbm, " .
        "GROUP_CONCAT(IF(role >=2 AND `date_fin` IS NULL AND id_asso_parent IS NULL, nom_asso, NULL) ORDER BY id_asso SEPARATOR ', ') assos, " .
        "ROUND(10 * log10(total), 2) as total_db " .
        "FROM ( " .
          "SELECT `utilisateurs`.`id_utilisateur`, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, " .
          "ROUND(sum(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit)/100, 2) as total, promo_utbm " .
          "FROM cpt_vendu " .
          "INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture " .
          "INNER JOIN utilisateurs ON cpt_debitfacture.id_utilisateur_client=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
          "WHERE cpt_debitfacture.mode_paiement='AE' " .
          "AND id_produit !=338 " .
          "GROUP BY utilisateurs.id_utilisateur " .
          "ORDER BY total DESC LIMIT 100 " .
        ") top " .
        "LEFT JOIN asso_membre USING ( `id_utilisateur` ) " .
        "LEFT JOIN asso USING ( `id_asso` ) " .
        "GROUP BY id_utilisateur " .
        "ORDER BY total DESC");

    $cols = array( "=num" => "N°",
                  "nom_utilisateur" => "Utilisateur",
                  "promo_utbm" => "Promo",
                  "assos" =>"Associations");
    if (isset($_REQUEST["plus"]))
    {
      $cols["total"] = "Total";
      $cols["total_db"] = "Total (dB€)";
    }

    $tbl = new sqltable("top10",
                        "Consomateurs (tous les semestres, cumulé): Top 100 (ce semestre)", $req, "stats.php",
                         "id_utilisateur",
                         $cols,
                         array(),
                         array(),
                         array(),
                         true,
                         true,
                         array($site->user->id));

    $cts->add($tbl);

  }
  else
  {
    $cts->add_title(2,"Consomateurs : Top 100 (ce semestre)");
    $req = new requete ($site->db, "SELECT `utilisateurs`.`id_utilisateur`, " .
        "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, " .
        "sum(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit) as total " .
        "FROM cpt_vendu " .
        "INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture " .
        "INNER JOIN utilisateurs ON cpt_debitfacture.id_utilisateur_client=utilisateurs.id_utilisateur " .
        "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
        "WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut_semestre' " .
        "AND id_produit !=338 " .
        "GROUP BY utilisateurs.id_utilisateur " .
        "ORDER BY total DESC LIMIT 100");

    $lst = new itemlist(false,"top10");

    $n=1;

    while ( $row = $req->get_row() )
    {
      $class = $n<=10?"top":false;

      if ( $row["id_utilisateur"] == $site->user->id )
        $class = $class?"$class me":"me";
            if ( !$site->user->is_in_group("gestion_ae") && !$site->user->is_in_group("foyer_admin") && !$site->user->is_in_group("kfet_admin"))
        $lst->add("N°$n : <a href=\"user.php?id_utilisateur=".$row["id_utilisateur"]."\">".htmlentities($row["nom_utilisateur"],ENT_NOQUOTES,"UTF-8")."</a>",$class);
            else
        $lst->add("N°$n : <a href=\"../user.php?id_utilisateur=".$row["id_utilisateur"]."\">".htmlentities($row["nom_utilisateur"],ENT_NOQUOTES,"UTF-8")."</a>".(isset($_REQUEST["plus"])?" ".($row["total"]/100):""),$class);
      $n++;

    }

    $cts->add($lst);
  }
  if ($site->user->is_in_group ("gestion_ae") || $site->user->is_in_group ("kfet_admin") || $site->user->is_in_group ("foyer_admin"))
  {
    $req = new requete ($site->db, "SELECT `utilisateurs`.`id_utilisateur`, " .
        "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, " .
        "ROUND(SUM( UNIX_TIMESTAMP( `activity_time` ) - UNIX_TIMESTAMP( `logged_time` ) ) /60/60,1) as total " .
        "FROM cpt_tracking " .
        "INNER JOIN utilisateurs ON cpt_tracking.id_utilisateur=utilisateurs.id_utilisateur " .
        "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` ".
        "WHERE logged_time > '$debut_semestre' " .
        "GROUP BY utilisateurs.id_utilisateur " .
        "ORDER BY total DESC LIMIT 30");

    $cts->add(new sqltable(
      "barmens",
      "Barmens : Permanences cumulées (ce semestre)", $req, "",
      "",
      array("nom_utilisateur"=>"Barmen","total"=>"Heures cumulées"),
      array(), array(),
      array()
      ),true);
	  
	$req = new requete ($site->db, "SELECT `utilisateurs`.`id_utilisateur`, " .
        "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, " .
        "ROUND(SUM( UNIX_TIMESTAMP( `activity_time` ) - UNIX_TIMESTAMP( `logged_time` ) ) /60/60,1) as total " .
        "FROM cpt_tracking " .
        "INNER JOIN utilisateurs ON cpt_tracking.id_utilisateur=utilisateurs.id_utilisateur " .
        "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` ".
        "GROUP BY utilisateurs.id_utilisateur " .
        "ORDER BY total DESC LIMIT 30");

    $cts->add(new sqltable(
      "barmens",
      "Barmens : Permanences cumulées (tout les semestres)", $req, "",
      "",
      array("nom_utilisateur"=>"Barmen","total"=>"Heures cumulées"),
      array(), array(),
      array()
      ),true);

    if(isset($_REQUEST["details"]))
    {
	$req = new requete($site->db, "SELECT `utilisateurs`.`id_utilisateur`,
					IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,
						utl_etu_utbm.surnom_utbm, 
						CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) 
					as `nom_utilisateur`,
					COUNT(*) as nombre_commande,
					SUM(montant_facture)/100 as total
					FROM cpt_debitfacture
					JOIN utilisateurs ON cpt_debitfacture.id_utilisateur = utilisateurs.id_utilisateur
					JOIN utl_etu_utbm ON cpt_debitfacture.id_utilisateur = utl_etu_utbm.id_utilisateur
					WHERE mode_paiement = 'AE'
					AND cpt_debitfacture.date_facture > '$debut_semestre'
					GROUP BY utilisateurs.id_utilisateur
					ORDER BY nombre_commande DESC LIMIT 30");

	$cts->add(new sqltable(
      	    	"barmens",
      		"Barmens : Nombre de commande (ce semestre)", $req, "",
		"",
	        array("nom_utilisateur"=>"Barmen", "nombre_commande" => "Nombre de commande" ,"total"=>"Somme totale"),
	        array(), array(),
	        array()
	        ),true);
	$req = new requete($site->db, "SELECT `utilisateurs`.`id_utilisateur`,
					IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,
						utl_etu_utbm.surnom_utbm, 
						CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) 
					as `nom_utilisateur`,
					COUNT(*) as nombre_commande,
					SUM(montant_facture)/100 as total
					FROM cpt_debitfacture
					JOIN utilisateurs ON cpt_debitfacture.id_utilisateur = utilisateurs.id_utilisateur
					JOIN utl_etu_utbm ON cpt_debitfacture.id_utilisateur = utl_etu_utbm.id_utilisateur
					WHERE mode_paiement = 'AE'
					AND cpt_debitfacture.date_facture > '$debut_semestre'
					GROUP BY utilisateurs.id_utilisateur
					ORDER BY total DESC LIMIT 30");

	$cts->add(new sqltable(
      	    	"barmens",
      		"Barmens : Montant vendu (ce semestre)", $req, "",
		"",
	        array("nom_utilisateur"=>"Barmen", "nombre_commande" => "Nombre de commande" ,"total"=>"Somme totale"),
	        array(), array(),
	        array()
	        ),true);

	$req = new requete($site->db, "SELECT `utilisateurs`.`id_utilisateur`,
					IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,
						utl_etu_utbm.surnom_utbm, 
						CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) 
					as `nom_utilisateur`,
					COUNT(*) as nombre_commande,
					SUM(montant_facture)/100 as total
					FROM cpt_debitfacture
					JOIN utilisateurs ON cpt_debitfacture.id_utilisateur = utilisateurs.id_utilisateur
					JOIN utl_etu_utbm ON cpt_debitfacture.id_utilisateur = utl_etu_utbm.id_utilisateur
					WHERE mode_paiement = 'AE'
					GROUP BY utilisateurs.id_utilisateur
					ORDER BY nombre_commande DESC LIMIT 30");

	$cts->add(new sqltable(
      	    	"barmens",
      		"Barmens : Nombre de commande (depuis toujours)", $req, "",
		"",
	        array("nom_utilisateur"=>"Barmen", "nombre_commande" => "Nombre de commande" ,"total"=>"Somme totale"),
	        array(), array(),
	        array()
	        ),true);
	$req = new requete($site->db, "SELECT `utilisateurs`.`id_utilisateur`,
					IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,
						utl_etu_utbm.surnom_utbm, 
						CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) 
					as `nom_utilisateur`,
					COUNT(*) as nombre_commande,
					SUM(montant_facture)/100 as total
					FROM cpt_debitfacture
					JOIN utilisateurs ON cpt_debitfacture.id_utilisateur = utilisateurs.id_utilisateur
					JOIN utl_etu_utbm ON cpt_debitfacture.id_utilisateur = utl_etu_utbm.id_utilisateur
					WHERE mode_paiement = 'AE'
					GROUP BY utilisateurs.id_utilisateur
					ORDER BY total DESC LIMIT 30");

	$cts->add(new sqltable(
      	    	"barmens",
      		"Barmens : Montant vendu (depuis toujours)", $req, "",
		"",
	        array("nom_utilisateur"=>"Barmen", "nombre_commande" => "Nombre de commande" ,"total"=>"Somme totale"),
	        array(), array(),
	        array()
	        ),true);

	$req = new requete($site->db, "SELECT cpt_produits.id_produit as id, cpt_produits.nom_prod as nom, 
                                        SUM(if(cpt_vendu.prix_unit > 0, 1, 0)) as nombre_commande, SUM(cpt_vendu.quantite) as nombre, 
                                        SUM(cpt_vendu.quantite)/SUM(if(cpt_vendu.prix_unit > 0, 1, 0)) as moyenne
                                        FROM cpt_produits
                                        JOIN cpt_vendu ON cpt_vendu.id_produit = cpt_produits.id_produit
                                        JOIN cpt_debitfacture ON cpt_debitfacture.id_facture = cpt_vendu.id_facture
                                        WHERE cpt_debitfacture.date_facture > '$debut_semestre'
                                        AND (cpt_produits.id_typeprod < 10 OR cpt_produits.id_typeprod = 27)
                                        GROUP BY id ORDER BY nombre DESC LIMIT 30");

	$cts->add(new sqltable(
      	    	"topproduit",
      		"Top 30 produits (ce semestre)", $req, "",
		"",
	        array("nom"=>"Produit", "nombre_commande" => "Nombre de commande" ,"nombre"=>"Nombre de produits vendu", "moyenne"=>"Moyenne par commande"),
	        array(), array(),
	        array()
	        ),true);



    }
  }

}
elseif ( $_REQUEST["view"] == "elections" )
{
  if (!$site->user->is_in_group ("gestion_ae"))
    $site->error_forbidden("presentation","group",9);

  $histo=false;
  $histo2=false;
  if(isset($_REQUEST['bananas']) && $_REQUEST['bananas'] == "cuitas")
    $histo=true;
  if(isset($_REQUEST['bananas']) && $_REQUEST['bananas'] == "cuitasoupas")
    $histo2=true;

  $cts2 = new contents("Participation aux élections");
  $cts2->add_paragraph("<center><img src=\"./stats.php?view=elections&bananas=cuitas\" alt=\"bananas cuitas\" /></center>");
  $cts2->add_paragraph("<center><img src=\"./stats.php?view=elections&bananas=cuitasoupas\" alt=\"bananas cuitas\" /></center>");
  $req = new requete($site->db,
         "SELECT ".
         "  id_election".
         ", nom_elec".
         ", date_debut ".
         ", date_fin ".
         "FROM vt_election ".
         "WHERE id_groupe IN (10000,10012) ".
         "ORDER BY date_debut, date_fin");
  if($histo)
    $datas = array(0=>"Participation en pourcentage");
  if($histo2)
    $datas = array(0=>"Nombre de votant");

  $i=0;
  while(list($id,$nom,$deb,$fin)=$req->get_row())
  {
    $i++;
    $req2 = new requete($site->db,
      "SELECT ".
      "COUNT(DISTINCT(id_utilisateur)) as nb ".
      "FROM `ae_cotisations` ".
      "WHERE ".
      "`date_fin_cotis` > '".$fin."' ".
      "AND `date_cotis` < '".$fin."' ");
    list($cot)=$req2->get_row();

    $req2 = new requete($site->db,
      "SELECT ".
      "COUNT(*) as nb ".
      "FROM `vt_a_vote` ".
      "WHERE `id_election` =".$id);
    list($vot)=$req2->get_row();

    $cts3 = new contents($nom." (".$i.")");
    $lst = new itemlist();
    $lst->add("Début : ".date("d/m/Y H:i",strtotime($deb)));
    $lst->add("Fin : ".date("d/m/Y H:i",strtotime($fin)));
    $lst->add("Cotisants : ".$cot);
    $lst->add("Votants : ".$vot);
    $cts3->add($lst);

    $part = round(($vot/$cot)*100,1);
    if($histo)
      $datas[$i]=min($part, 100);
    if($histo2)
      $datas[$i]=$vot;
    $prog = new progressbar($part);
    $cts3->add($prog);
    $cts2->add($cts3,true);
  }
  if($histo)
  {
    $hist = new histogram($datas, utf8_decode("Taux de participation aux élections"));
    $hist->png_render();
    $hist->destroy();
    exit();
  }
  if($histo2)
  {
    $hist = new histogram($datas, utf8_decode("Nombre de votants"));
    $hist->png_render();
    $hist->destroy();
    exit();
  }
  $cts->add($cts2,true);
}
elseif ($_REQUEST["view"] == "actifs" )
{
  require_once($topdir. "include/entities/asso.inc.php");

  $roleasso_short = array(
    ROLEASSO_PRESIDENT=>"Responsable",
    ROLEASSO_MEMBREBUREAU=>"Bureau",
    ROLEASSO_MEMBREACTIF=>"Membre actif",
    ROLEASSO_MEMBRE=>"Membre"
  );

  if (!$site->user->is_in_group("gestion_ae"))
    exit();

  $graph1 = $graph2 = $graph3 = false;
  if (isset($_REQUEST['bananas']) && ($_REQUEST['bananas'] == "cuitastoujours"))
    $graph1 = true;
  if (isset($_REQUEST['bananas']) && ($_REQUEST['bananas'] == "cuitaspourlavie"))
    $graph2 = true;
  elseif (isset($_REQUEST['bananas']) && ($_REQUEST['bananas'] == "cuitasencore"))
    $graph3 = true;

  $cts2 = new contents("Personnes actives");
  $cts2->add_paragraph("<center><img src=\"./stats.php?view=actifs&bananas=cuitastoujours\" alt=\"Personnes actives par postes\" /></center>");
  $cts2->add_paragraph("<center><img src=\"./stats.php?view=actifs&bananas=cuitaspourlavie\" alt=\"Personnes actives par postes\" /></center>");

  if (!$graph3)
  {
    $req = new requete($site->db,
    "SELECT IF((role <10 AND ROLE >=2), 2, role) rle, COUNT(*) c1, COUNT(DISTINCT id_utilisateur) c2 ".
    "FROM `asso_membre` ".
    "LEFT JOIN `asso` USING ( `id_asso` ) ".
    "LEFT JOIN `asso` asso_p ON ( `asso_p`.`id_asso` = `asso`.`id_asso_parent` ) ".
    "WHERE date_fin IS NULL ".
    "AND (`asso_p`.`id_asso_parent`=1 OR `asso`.`id_asso_parent` =1) ".
    "GROUP BY `rle`"
    );

    if ($graph1)
    {
      $datas = array("Poste" => "Avec double compte");
      while ($row = $req->get_row())
        $datas[utf8_decode($roleasso_short[$row['rle']])] = $row['c1'];

      $hist = new histogram($datas, "Personnes actives avec double compte");
      $hist->png_render();
      $hist->destroy();

      exit();
    }
    elseif ($graph2)
    {
      $datas = array("Poste" => "Sans double compte");
      while ($row = $req->get_row())
        $datas[utf8_decode($roleasso_short[$row['rle']])] = $row['c2'];

      $hist = new histogram($datas, "Personnes actives sans double compte");
      $hist->png_render();
      $hist->destroy();

      exit();
    }
    else
    {
      $tbl = new sqltable(
        "statsresp",
        "Personnes actives", $req, "",
        "role",
        array("rle"=>"Role","c1"=>"Avec double compte","c2"=>"Sans double compte"),
        array(), array(),
        array("rle"=>$GLOBALS['ROLEASSO100'] )
        );

      $cts2->add($tbl);
    }
  }
  $cts->add($cts2,true);

  if ($graph3)
  {
    $req = new requete($site->db,
      "SELECT rle, COUNT(DISTINCT id_utilisateur) count FROM ( ".
        "SELECT IF((MAX(role) <10 AND MAX(ROLE) >=2), 2, MAX(role)) rle, `utilisateurs`.`id_utilisateur` ".
        "FROM `utilisateurs` ".
        "LEFT JOIN `asso_membre` ON (`utilisateurs`.`id_utilisateur` = `asso_membre`.`id_utilisateur` AND date_fin IS NULL) ".
        "LEFT JOIN `asso` USING ( `id_asso` ) ".
        "LEFT JOIN `asso` asso_p ON ( `asso_p`.`id_asso` = `asso`.`id_asso_parent` ) ".
        "WHERE (`asso_p`.`id_asso_parent`=1 OR `asso`.`id_asso_parent` =1 OR `asso`.`id_asso` IS NULL) ".
        "AND `ae_utl` = '1' ".
        "GROUP BY `id_utilisateur`) roles ".
      "GROUP BY `rle` ".
      "ORDER BY rle DESC"
    );

    $cam = new camembert(750,400,array(),2,0,0,0,0,0,0,10,240);
    while ($row = $req->get_row())
      $cam->data($row['count'], utf8_decode($row['rle']!=null ? $GLOBALS['ROLEASSO100'][$row['rle']] : "Autres cotisants"));

    $cam->png_render();
    $cam->destroy_graph();

    exit();
  }
  $cts2 = new contents("Parts d'actifs parmis les cotisants");
  $cts2->add_paragraph("<center><img src=\"./stats.php?view=actifs&bananas=cuitasencore\" alt=\"Personnes actives parmis les cotisants\" /></center>");
  $cts->add($cts2,true);

}
elseif ( ($site->user->is_in_group ("gestion_ae") || $site->user->is_asso_role ( 2, 2 )) && $_REQUEST["view"] == "temps_reel" )
{
	if(isset($_REQUEST["date"]))
		$date = intval($_REQUEST["date"]); 
	else
		$date = time();
	$debut = date("Y-m-d H:i:s",$date);
	$addresse = "stats.php?view=temps_reel&date=".$date;
	$req = new requete ($site->db, "SELECT id_utilisateur, nom_utilisateur, total, promo_utbm, " .
        "GROUP_CONCAT(IF(role >=2 AND `date_fin` IS NULL AND id_asso_parent IS NULL, nom_asso, NULL) ORDER BY id_asso SEPARATOR ', ') assos, " .
	"ROUND(10000*total/(SELECT SUM(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit) FROM cpt_vendu
       		      INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture 
		      WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut' 
		      AND id_produit !=338),2) as pourcentage " .
        "FROM ( " .
          "SELECT `utilisateurs`.`id_utilisateur`, " .
          "IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,utl_etu_utbm.surnom_utbm, CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) as `nom_utilisateur`, " .
          "ROUND(sum(`cpt_vendu`.`quantite`*`cpt_vendu`.prix_unit)/100, 2) as total, promo_utbm " .
          "FROM cpt_vendu " .
          "INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture=cpt_vendu.id_facture " .
          "INNER JOIN utilisateurs ON cpt_debitfacture.id_utilisateur_client=utilisateurs.id_utilisateur " .
          "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
          "WHERE cpt_debitfacture.mode_paiement='AE' AND date_facture > '$debut' " .
          "AND id_produit !=338 " .
          "GROUP BY utilisateurs.id_utilisateur " .
          "ORDER BY total DESC LIMIT 10 " .
        ") top " .
        "LEFT JOIN asso_membre USING ( `id_utilisateur` ) " .
        "LEFT JOIN asso USING ( `id_asso` ) " .
        "GROUP BY id_utilisateur " .
        "ORDER BY total DESC");

	echo 	"<!DOCTYPE html>
		<html>
			<head>
	      			<title>Top temps réel</title>
				<meta charset=\"UTF-8\">
				<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
		    						<!-- Latest compiled and minified CSS -->
				<link rel=\"stylesheet\" href=\"//netdna.bootstrapcdn.com/bootstrap/3.0.2/css/bootstrap.min.css\">

				<!-- Latest compiled and minified JavaScript -->
				<script src=\"https://code.jquery.com/jquery.js\"></script>
				<script src=\"//netdna.bootstrapcdn.com/bootstrap/3.0.2/js/bootstrap.min.js\"></script>


				<script src=\"https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js\"></script>
      				<script src=\"https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js\"></script>
      			</head>
			<body>
				<script>function reload() { window.location.replace(\"$addresse\"); }

					$( document ).ready(function() { setTimeout(reload,10000) });
				</script><div class=\"container\">";

	echo "<table class=\"table table-striped\">\n";
	echo "<thead><tr>";
	echo "<th>#</th>";
	echo "<th>Nom</th>";
	echo "<th>Promo</th>";
	echo "<th>Assos</th>";
	echo "<th>Pourcentage</th>";
	echo "</tr></thead><tbody>";

$i = 0;
while( $row = $req->get_row())
{
	$i++;
	echo "<tr>\n";
	echo "<td>".$i."</td>";
	echo "<td>".$row["nom_utilisateur"]."</td>";
	echo "<td>".$row["promo_utbm"]."</td>";
	echo "<td>".$row["assos"]."</td>";
	echo "<td>".$row["pourcentage"]."</td>";
	echo "</tr>";
}


  	echo "		</tbody></table></div></body>
  		</html>";
        exit();

}
else
{
  $cts->add_paragraph("<br />Vous trouverez ici l'ensemble des statistiques (complètement in)utiles du site AE. ".
                      "Enjoy it ;)");
}


$site->add_contents($cts);
$site->end_page();

?>
