<?php
/* Copyright 2006,2007
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
require_once($topdir . "include/entities/entreprise.inc.php");
require_once($topdir . "include/entities/efact.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");

$site = new sitecompta();

$site->allow_only_logged_users("services");

if ( !$site->user->is_in_group("compta_admin") )
  $site->error_forbidden("services");
$site->start_page ("services", "Tout en 2007" );

$cts = new contents("Tout en 2007");

$na = array(1);

$req = new requete($site->db,"SELECT a.id_asso ".  "FROM asso AS a ".
  "JOIN asso AS b ON (a.id_asso_parent=b.id_asso) ".
  "WHERE a.id_asso_parent=1 OR b.id_asso_parent=1");

while(list($id)=$req->get_row())
  $na[]=$id;

$y=2007;

if ( isset($_GET["y"]) )
  $y = intval($_GET["y"]);

$filter="( date_op>='$y-01-01' AND date_op <='$y-12-31' ";
//1- Ignorer les opérations entre comptes
$filter.= " AND (cpta_operation.id_asso NOT IN (".implode(",",$na).") OR cpta_operation.id_asso IS NULL) ";
$filter.= " AND cpta_operation.id_cptasso IS NULL ";
//2- Ignorer les 791,678,689,789 (sera recalculé plus tard)
$filter.= " AND (code_plan NOT IN (791,678,689,789) OR code_plan IS NULL) ";


$filter.= ")";


  $req = new requete ( $site->db, "SELECT " .

    "IF(`entreprise`.`id_ent` IS NOT NULL, CONCAT('ident-',`entreprise`.`id_ent`)," .
    "IF(`asso`.`id_asso` IS NOT NULL,  CONCAT('idasso-',`asso`.`id_asso`), " .
    "IF( `cpta_cpasso`.`id_cptasso` IS NOT NULL, CONCAT('idcptasso-',`cpta_cpasso`.`id_cptasso`)," .
    "CONCAT('idutl-',`utilisateurs`.`id_utilisateur`)))) AS id_actor," .
    "SUM(`montant_op`)/100 AS `sum`, ".
    "`entreprise`.`nom_entreprise`, " .
    "`entreprise`.`id_ent`, " .

    "`asso`.`id_asso`, " .
    "`asso`.`nom_asso`, " .

    "`cpta_cpasso`.`id_cptasso`, " .
    "CONCAT(`asso2`.`nom_asso`,' sur ',`cpta_cpbancaire`.`nom_cptbc` ) AS `nom_cptasso`, " .

    "`utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur` " .

    "FROM `cpta_operation` " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
    "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
    "LEFT JOIN `cpta_cpasso` ON `cpta_operation`.`id_cptasso`=`cpta_cpasso`.`id_cptasso` ".
    "LEFT JOIN `asso` ON `cpta_operation`.`id_asso`=`asso`.`id_asso` ".
    "LEFT JOIN `entreprise` ON `cpta_operation`.`id_ent`=`entreprise`.`id_ent` ".
    "LEFT JOIN `utilisateurs` ON `cpta_operation`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
    "LEFT JOIN `asso` AS `asso2` ON `cpta_cpasso`.`id_asso`=`asso2`.`id_asso` ".
    "LEFT JOIN `cpta_cpbancaire` ON `cpta_cpasso`.`id_cptbc`=`cpta_cpbancaire`.`id_cptbc` ".
    "WHERE $filter " .
    "AND (`cpta_op_clb`.`type_mouvement`=1 OR`cpta_op_plcptl`.`type_mouvement`=1) " .
    "GROUP BY id_actor" );

  $cts->add(new sqltable(
    "listops",
    "Credit", $req, "classeur.php?id_classeur=".$cla->id,
    "type",
    array(
      "nom_entreprise"=>array("Entreprise/asso/compte","nom_utilisateur","nom_entreprise","nom_asso","nom_cptasso"),
      "sum"=>"Somme"
      ),
    array(),
    array(),
    array()
    ),true);

  $req->go_first();
  $sum=0;
  while ( $row = $req->get_row() ) $sum += $row['sum'];
  $cts->add_paragraph("Total : $sum");

  $req = new requete ( $site->db, "SELECT " .

    "IF(`entreprise`.`id_ent` IS NOT NULL, CONCAT('ident-',`entreprise`.`id_ent`)," .
    "IF(`asso`.`id_asso` IS NOT NULL,  CONCAT('idasso-',`asso`.`id_asso`), " .
    "IF( `cpta_cpasso`.`id_cptasso` IS NOT NULL, CONCAT('idcptasso-',`cpta_cpasso`.`id_cptasso`)," .
    "CONCAT('idutl-',`utilisateurs`.`id_utilisateur`)))) AS id_actor," .
    "SUM(`montant_op`)/-100 AS `sum`, ".
    "`entreprise`.`nom_entreprise`, " .
    "`entreprise`.`id_ent`, " .

    "`asso`.`id_asso`, " .
    "`asso`.`nom_asso`, " .

    "`cpta_cpasso`.`id_cptasso`, " .
    "CONCAT(`asso2`.`nom_asso`,' sur ',`cpta_cpbancaire`.`nom_cptbc` ) AS `nom_cptasso`, " .

    "`utilisateurs`.`id_utilisateur`, " .
    "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur` " .

    "FROM `cpta_operation` " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
    "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
    "LEFT JOIN `cpta_cpasso` ON `cpta_operation`.`id_cptasso`=`cpta_cpasso`.`id_cptasso` ".
    "LEFT JOIN `asso` ON `cpta_operation`.`id_asso`=`asso`.`id_asso` ".
    "LEFT JOIN `entreprise` ON `cpta_operation`.`id_ent`=`entreprise`.`id_ent` ".
    "LEFT JOIN `utilisateurs` ON `cpta_operation`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
    "LEFT JOIN `asso` AS `asso2` ON `cpta_cpasso`.`id_asso`=`asso2`.`id_asso` ".
    "LEFT JOIN `cpta_cpbancaire` ON `cpta_cpasso`.`id_cptbc`=`cpta_cpbancaire`.`id_cptbc` ".
    "WHERE $filter " .
    "AND (`cpta_op_clb`.`type_mouvement`=-1 OR`cpta_op_plcptl`.`type_mouvement`=-1) " .
    "GROUP BY id_actor" );

  $cts->add(new sqltable(
    "listops",
    "Debit", $req, "classeur.php?id_classeur=".$cla->id,
    "type",
    array(
      "nom_entreprise"=>array("Entreprise/asso/compte","nom_utilisateur","nom_entreprise","nom_asso","nom_cptasso"),
      "sum"=>"Somme"
      ),
    array(),
    array(),
    array()
    ),true);

  $req->go_first();
  $sum=0;
  while ( $row = $req->get_row() ) $sum += $row['sum'];
  $cts->add_paragraph("Total : $sum");

  $req = new requete ( $site->db, "SELECT " .
    "IF (`cpta_op_clb`.`libelle_opclb` IS NULL, CONCAT(`cpta_op_plcptl`.`code_plan`,' ',`cpta_op_plcptl`.`libelle_plan`),`cpta_op_clb`.`libelle_opclb`) AS `type`, " .
    "SUM(`montant_op`)/100 AS `sum` " .
    "FROM `cpta_operation` " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
    "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
    "WHERE $filter AND " .
    "(`cpta_op_clb`.`type_mouvement`=1 OR`cpta_op_plcptl`.`type_mouvement`=1)" .
    "GROUP BY `type`" );

  $cts->add(new sqltable(
    "listops",
    "Credit", $req, "classeur.php?id_classeur=".$cla->id,
    "type",
    array(
      "type"=>"Nature(type) d'opération",
      "sum"=>"Somme"

      ),
    array(),
    array(),
    array()
    ),true);

  $req->go_first();
  $sum=0;
  while ( $row = $req->get_row() ) $sum += $row['sum'];
  $cts->add_paragraph("Total : $sum");


  $req = new requete ( $site->db, "SELECT " .
    "IF (`cpta_op_clb`.`libelle_opclb` IS NULL, CONCAT(`cpta_op_plcptl`.`code_plan`,' ',`cpta_op_plcptl`.`libelle_plan`),`cpta_op_clb`.`libelle_opclb`) AS `type`, " .
    "SUM(`montant_op`)/-100 AS `sum` " .
    "FROM `cpta_operation` " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
    "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
    "WHERE $filter AND " .
    "(`cpta_op_clb`.`type_mouvement`=-1 OR`cpta_op_plcptl`.`type_mouvement`=-1)" .
    "GROUP BY `type`" );

  $cts->add(new sqltable(
    "listops",
    "Debit", $req, "classeur.php?id_classeur=".$cla->id,
    "type",
    array(
      "type"=>"Nature(type) d'opération",
      "sum"=>"Somme"

      ),
    array(),
    array(),
    array()
    ),true);

  $req->go_first();
  $sum=0;
  while ( $row = $req->get_row() ) $sum += $row['sum'];
  $cts->add_paragraph("Total : $sum");




    $req = new requete ( $site->db, "SELECT " .
    "COALESCE(`cpta_op_plcptl`.`code_plan`,`cpta_op_clb`.`type_mouvement`) , " .
    "SUM(COALESCE(`cpta_op_plcptl`.`type_mouvement`,`cpta_op_clb`.`type_mouvement`)*`montant_op`) AS `sum` " .
    "FROM `cpta_operation` " .
    "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
    "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
    "WHERE $filter " .
    "GROUP BY COALESCE(`cpta_op_plcptl`.`code_plan`,`cpta_op_clb`.`type_mouvement`)" );

  $pl["debit"]=0;
  $pl["credit"]=0;

  while( list($code,$sum) = $req->get_row())
  {
    if ( $code == -1 )
    {
      $pl["debit"] += abs($sum);
      $pl["6"] += abs($sum);
    }
    elseif ( $code == 1 )
    {
      $pl["credit"] += abs($sum);
      $pl["7"] += abs($sum);
    }
    else
    {
      for($i=1;$i<=strlen($code);$i++)
      {
        $pl[substr($code,0,$i)] += abs($sum);
      }
    }
  }

  $sum = $pl["7"]-$pl["6"];

  if ( $sum > 0 )
  {
    $pl["681"] += abs($sum);
    $pl["68"] += abs($sum);
    $pl["6"] += abs($sum);
  }
  else if ( $sum < 0 )
  {
    $pl["781"] += abs($sum);
    $pl["78"] += abs($sum);
    $pl["7"] += abs($sum);
  }

  $req = new requete ( $site->db, "SELECT " .
    "`code_plan`, `libelle_plan` " .
    "FROM  `cpta_op_plcptl`  ".
    "WHERE `type_mouvement`!=0 " .
    "ORDER BY `code_plan`");

  $tbl = new table("Bilan comptable","plct");

  while( list($code,$libelle) = $req->get_row())
  {
    if ( $pl[$code]  )
      $tbl->add_row(array($code,$libelle,$pl[$code]/100),"plct".strlen($code));
    if ( $code == "6" && $pl["debit"] > 0 )
      $tbl->add_row(array("6?","Debit non codé",$pl["debit"]/100),"plct2");
     if ( $code == "7" && $pl["credit"] > 0 )
      $tbl->add_row(array("7?","Credit non codé",$pl["credit"]/100),"plct2");
  }
  $cts->add($tbl);

$site->add_contents($cts);

$site->end_page ();

?>
