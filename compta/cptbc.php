<?php
/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
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
$topdir="../";
require_once("include/compta.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");

$site = new sitecompta();
$cpbc  = new compte_bancaire($site->db);

if ( !$site->user->is_in_group("compta_admin") )
  $site->error_forbidden("services");

$cpbc->load_by_id($_REQUEST["id_cptbc"]);

if ( $cpbc->id < 1 )
{
  $site->error_not_found("services");
  exit();
}

$site->start_page ("services", "Compte bancaire" );

$cts = new contents ("<a href=\"./\">Compta</a> / ".$cpbc->get_html_link());

$req_sql = new requete ($site->db,
      "SELECT `cpta_classeur`.`id_classeur`,`cpta_classeur`.`nom_classeur`," .
      "`cpta_classeur`.`date_debut_classeur`,`cpta_classeur`.`date_fin_classeur`," .
      "`cpta_cpasso`.`id_cptasso`, `asso`.`nom_asso` as nom_cptasso," .
      "(SELECT " .
      "SUM(IF(`cpta_op_plcptl`.`type_mouvement` IS NULL,`cpta_op_clb`.`type_mouvement`,`cpta_op_plcptl`.`type_mouvement`)*`montant_op`) " .
      "FROM `cpta_operation` " .
      "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
      "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
      "WHERE `cpta_operation`.id_classeur=`cpta_classeur`.id_classeur)/100 AS `solde` " .
      "FROM `cpta_classeur` " .
      "INNER JOIN `cpta_cpasso` ON `cpta_cpasso`.`id_cptasso` = `cpta_classeur`.`id_cptasso` " .
      "INNER JOIN `asso` ON `asso`.`id_asso`=`cpta_cpasso`.`id_asso` " .
      "WHERE `cpta_classeur`.`ferme`=0 AND cpta_cpasso.id_cptbc='".$cpbc->id."' " .
      "ORDER BY  `asso`.`nom_asso`");

$tbl = new sqltable ("lstclasseur",
               "Classeur ouverts",
               $req_sql,
               "index.php",
               "id_classeur",
               array(
            "nom_cptasso" => "Association",
            "nom_classeur" => "Classeur",
               "solde"=>"Solde",
               "date_debut_classeur"=>"De",
               "date_fin_classeur"=>"Au"

             ),
               array(),
               array(),
               array());
$cts->add($tbl,true);


/* principes de l'algo de bilans pour les comptes bancaires de types trésorie interne
 * 1- On detecte le compte asso "master", c'es dire celui de l'association niveau 1
 * 2- On récupère son dernier classeur ouvert, qui est considéré comme référence
 * 3- On récupére tous les classeurs entrant dans la période grossomodo couverte par le classeur de référence
 * 4- Génération du bilan
 *     - Opérations internes à un compte asso : c'est les opération d'ouverture/fermeture de classeurs
 *          - la première dans l'histoire est considéré comme ouverture
 *          - la dernière est considéré comme fermeture
 *          - s'il y en a une seule : cas classeur ouvert -> ouverture
 *                                    cas classeur fermé -> si sa date est vers la fin du classeur: fermeture, sinon ouverture (trés subjectif)
 *     - Opération interne au compte bancaire
 *     - Opération externes
 */

$sql =   new requete ($site->db,"SELECT id_cptasso " .
      "FROM `cpta_cpasso` " .
      "INNER JOIN asso ON asso.id_asso=cpta_cpasso.id_asso " .
      "WHERE cpta_cpasso.id_cptbc='".$cpbc->id."' AND asso.id_asso_parent IS NULL");

if ( $sql->lines == 1 )
{

list($id_cptasso) = $sql->get_row();

$sql = new requete ($site->db,
      "SELECT `id_classeur`,`nom_classeur`," .
      "`date_debut_classeur`,`date_fin_classeur` " .
      "FROM `cpta_classeur` " .
      "WHERE `ferme`=0 AND id_cptasso='".$id_cptasso."' " .
      "ORDER BY `date_debut_classeur` DESC");

if ( $sql->lines >= 1 )
{

list($id_classeur,$nom_classeur,$debut,$fin) = $sql->get_row();

$sql = new requete ($site->db,
      "SELECT `id_classeur`,`nom_classeur`," .
      "`date_debut_classeur`,`date_fin_classeur`," .
      "`cpta_cpasso`.`id_cptasso`, `asso`.`nom_asso` as nom_cptasso " .
      "FROM `cpta_classeur` " .
      "INNER JOIN cpta_cpasso ON cpta_classeur.id_cptasso = cpta_cpasso.id_cptasso  " .
      "INNER JOIN asso ON asso.id_asso = cpta_cpasso.id_asso ".
      "WHERE ((date_debut_classeur >= '$debut' AND  date_debut_classeur < '$fin') OR " .
      "(date_fin_classeur >= '$debut' AND  date_fin_classeur < '$fin')) " .
      "AND (cpta_classeur.id_cptasso!='".$id_cptasso."' OR `id_classeur`='$id_classeur') " .
      "AND cpta_cpasso.id_cptbc='".$cpbc->id."' " .
      "ORDER BY asso.id_asso_parent, asso.nom_asso, date_debut_classeur");

$all = array();

while ( $cla = $sql->get_row() )
{

  $sql2 = new requete($site->db,"SELECT " .
    "SUM(IF(`cpta_op_plcptl`.`type_mouvement` IS NULL,`cpta_op_clb`.`type_mouvement`,`cpta_op_plcptl`.`type_mouvement`)*`montant_op`) " .
    "FROM `cpta_operation` " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
    "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
    "WHERE `cpta_operation`.id_classeur='".$cla["id_classeur"]."' ");

  list($sum) = $sql2->get_row();



  $sql2 = new requete($site->db,"SELECT " .
    "SUM(IF(`cpta_op_plcptl`.`type_mouvement` IS NULL,`cpta_op_clb`.`type_mouvement`,`cpta_op_plcptl`.`type_mouvement`)*`montant_op`) " .
    "FROM `cpta_operation` " .
    "INNER JOIN cpta_cpasso ON cpta_operation.id_cptasso = cpta_cpasso.id_cptasso  " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
    "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
    "WHERE `cpta_operation`.id_classeur='".$cla["id_classeur"]."' AND " .
    "(cpta_cpasso.id_cptbc='".$cpbc->id."') AND " .
    "(cpta_operation.id_cptasso!=".$cla["id_cptasso"].") AND ".
    "(cpta_operation.id_utilisateur IS NULL) AND ".
    "(cpta_operation.id_asso IS NULL) AND ".
    "(cpta_operation.id_ent IS NULL)");

  list($sum_mvint) = $sql2->get_row();

  $sql2 = new requete($site->db,"SELECT " .
    "SUM(IF(`cpta_op_plcptl`.`type_mouvement` IS NULL,`cpta_op_clb`.`type_mouvement`,`cpta_op_plcptl`.`type_mouvement`)*`montant_op`) " .
    "FROM `cpta_operation` " .
    "LEFT JOIN cpta_cpasso ON cpta_operation.id_cptasso = cpta_cpasso.id_cptasso  " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
    "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
    "WHERE `cpta_operation`.id_classeur='".$cla["id_classeur"]."' AND " .
    "!((cpta_cpasso.id_cptbc='".$cpbc->id."') AND " .
    "(cpta_operation.id_utilisateur IS NULL) AND ".
    "(cpta_operation.id_asso IS NULL) AND ".
    "(cpta_operation.id_ent IS NULL))");

  list($sum_ext) = $sql2->get_row();

  $sql2 = new requete($site->db,"SELECT " .
    "IF(`cpta_op_plcptl`.`type_mouvement` IS NULL,`cpta_op_clb`.`type_mouvement`,`cpta_op_plcptl`.`type_mouvement`)*`montant_op` AS `montant`," .
    "`date_op` " .
    "FROM `cpta_operation` " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
    "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
    "WHERE `cpta_operation`.id_classeur='".$cla["id_classeur"]."' AND " .
    "((cpta_operation.id_cptasso IS NULL) OR (cpta_operation.id_cptasso=".$cla["id_cptasso"].")) AND ".
    "(cpta_operation.id_utilisateur IS NULL) AND ".
    "(cpta_operation.id_asso IS NULL) AND ".
    "(cpta_operation.id_ent IS NULL) " .
    "ORDER BY `date_op`");

  if ( $sql2->lines > 1 )
  {
    $sum_ouv=0;
    for($i=0;$i<$sql2->lines-1;$i++)
    {
      list($m,$d) = $sql2->get_row();
      $sum_ouv += $m;
    }

    list($sum_clo,$d) = $sql2->get_row();
  }
  else if ( $sql2->lines == 1 )
  {
    $sum_ouv=0;
    $sum_clo=0;

    list($m,$d) = $sql2->get_row();

    $d = strtotime($d);

    if ( $d-strtotime($cla["date_fin_classeur"]) > -(10*24*60*60) )
      $sum_clo = $m;
    else
      $sum_ouv = $m;
  }
  else
  {
    $sum_ouv=0;
    $sum_clo=0;
  }

  $cla["sum"] = $sum/100;
  $cla["sum_mvint"] = $sum_mvint/100;
  $cla["sum_ext"] = $sum_ext/100;
  $cla["sum_ouv"] = $sum_ouv/100;
  $cla["sum_clo"] = $sum_clo/100;

  $csum += $sum;
  $csum_mvint += $sum_mvint;
  $csum_ext += $sum_ext;
  $csum_ouv += $sum_ouv;
  $csum_clo += $sum_clo;

  $all[] = $cla;
}
$cla = array("nom_classeur"=>"TOTAL");
$cla["sum"] = $csum/100;
$cla["sum_mvint"] = $csum_mvint/100;
$cla["sum_ext"] = $csum_ext/100;
$cla["sum_ouv"] = $csum_ouv/100;
$cla["sum_clo"] = $csum_clo/100;
$all[] = $cla;

$tbl = new sqltable ("bilan",
               "Bilan général $nom_classeur ",
               $all,
               "index.php",
               "id_classeur",
               array(
            "nom_cptasso" => "Association",
            "nom_classeur" => "Classeur",
               "sum"=>"Solde",
            "sum_ouv"=>"Ouverture",
            "sum_mvint"=>"Mouvements internes",
            "sum_ext"=>"Mouvements",
            "sum_clo"=>"Fermeture",
               "date_debut_classeur"=>"De",
               "date_fin_classeur"=>"Au"

             ),
               array(),
               array(),
               array());

$cts->add($tbl,true);

}
}

$req_sql = new requete ($site->db,
      "SELECT id_cptasso, asso.nom_asso as nom_cptasso, " .
      "asso.id_asso, asso.nom_asso " .
      "FROM `cpta_cpasso` " .
      "INNER JOIN asso ON asso.id_asso=cpta_cpasso.id_asso " .
      "WHERE cpta_cpasso.id_cptbc='".$cpbc->id."' " .
      "ORDER BY `asso`.`nom_asso`");

$tbl = new sqltable ("cpta_cptasso",
             "Comptes association",
             $req_sql,
             "./index.php",
             "id_cptasso",
             array("nom_cptasso" => "Compte association",
             "nom_asso" => "Association"),
             array(),
             array(),
             array());
$cts->add($tbl,true);

$site->add_contents ($cts);
$site->end_page ();

?>
