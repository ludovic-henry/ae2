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
require_once($topdir . "include/cts/sqltable.inc.php");

$site = new sitecompta();

if ( !$site->user->is_valid() )
  $site->error_forbidden("services");

$assos=$site->user->get_assos(ROLEASSO_TRESORIER);

if ( !count($assos) && !$site->user->is_in_group("compta_admin") )
  $site->error_forbidden("services");

$site->start_page ("services", "Accueil compta");

$cts = new contents ();

if ( count($assos) )
{

  foreach($assos as $key=>$val)
    $assos_keys[]=$key;
  $filter = " `asso`.`id_asso` IN (".implode(",",$assos_keys).") ";

  $cts->add_title(1,"Comptabilité de mes associations");

  $req_sql = new requete ($site->db,
        "SELECT `cpta_classeur`.`id_classeur`,`cpta_classeur`.`nom_classeur`," .
        "`cpta_classeur`.`date_debut_classeur`,`cpta_classeur`.`date_fin_classeur`," .
        "`cpta_cpasso`.`id_cptasso`, `asso`.`nom_asso` AS `nom_cptasso`," .
        "`cpta_cpbancaire`.`nom_cptbc`,`cpta_cpbancaire`.`id_cptbc`," .
        "(SELECT " .
        "SUM(IF(`cpta_op_plcptl`.`type_mouvement` IS NULL,`cpta_op_clb`.`type_mouvement`,`cpta_op_plcptl`.`type_mouvement`)*`montant_op`) " .
        "FROM `cpta_operation` " .
        "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
        "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
        "WHERE `cpta_operation`.id_classeur=`cpta_classeur`.id_classeur)/100 AS `solde` " .
        "FROM `cpta_classeur` " .
        "INNER JOIN `cpta_cpasso` ON `cpta_cpasso`.`id_cptasso` = `cpta_classeur`.`id_cptasso` " .
        "INNER JOIN `cpta_cpbancaire` ON `cpta_cpbancaire`.`id_cptbc` = `cpta_cpasso`.`id_cptbc` " .
        "INNER JOIN `asso` ON `asso`.`id_asso`=`cpta_cpasso`.`id_asso` " .
        "WHERE `cpta_classeur`.`ferme`=0 AND $filter " .
        "ORDER BY `cpta_cpbancaire`.`nom_cptbc`, `asso`.`nom_asso`");

  $tbl = new sqltable ("lstclasseur",
                 "Classeur ouverts",
                 $req_sql,
                 "index.php",
                 "id_classeur",
                 array(
              "nom_cptbc" => "Compte bancaire",
              "nom_cptasso" => "Compte asso.",
              "nom_classeur" => "Classeur",
                 "solde"=>"Solde",
                 "date_debut_classeur"=>"De",
                 "date_fin_classeur"=>"Au"

               ),
                 array(),
                 array(),
                 array());


  $cts->add($tbl,true);


  $req_sql = new requete ($site->db,
        "SELECT id_cptasso, asso.nom_asso as nom_cptasso, " .
        "cpta_cpbancaire.nom_cptbc, cpta_cpbancaire.id_cptbc " .
        "FROM `cpta_cpasso` " .
        "INNER JOIN asso ON asso.id_asso=cpta_cpasso.id_asso " .
        "INNER JOIN cpta_cpbancaire ON cpta_cpbancaire.id_cptbc=cpta_cpasso.id_cptbc " .
        "WHERE $filter " .
        "ORDER BY `cpta_cpbancaire`.`nom_cptbc`, `asso`.`nom_asso`");

  $tbl = new sqltable ("cpta_cptasso",
               "Comptes association",
               $req_sql,
               "./index.php",
               "id_cptasso",
               array("nom_cptbc" => "Compte bancaire",
              "nom_cptasso" => "Compte asso."
               ),
               array(),
               array(),
               array());

  $cts->add($tbl,true);

  $cts->add_paragraph("&nbsp;");

}

if ( $site->user->is_in_group("compta_admin") )
{

$cts->add_title(1,"Administration");

$req_sql = new requete ($site->db,
      "SELECT `cpta_classeur`.`id_classeur`,`cpta_classeur`.`nom_classeur`," .
      "`cpta_classeur`.`date_debut_classeur`,`cpta_classeur`.`date_fin_classeur`," .
      "`cpta_cpasso`.`id_cptasso`, `asso`.`nom_asso` AS `nom_cptasso`," .
      "`cpta_cpbancaire`.`nom_cptbc`,`cpta_cpbancaire`.`id_cptbc`," .
      "(SELECT " .
      "SUM(IF(`cpta_op_plcptl`.`type_mouvement` IS NULL,`cpta_op_clb`.`type_mouvement`,`cpta_op_plcptl`.`type_mouvement`)*`montant_op`) " .
      "FROM `cpta_operation` " .
      "LEFT JOIN `cpta_op_clb` ON `cpta_operation`.`id_opclb`=`cpta_op_clb`.`id_opclb` ".
      "LEFT JOIN `cpta_op_plcptl` ON `cpta_operation`.`id_opstd`=`cpta_op_plcptl`.`id_opstd` ".
      "WHERE `cpta_operation`.id_classeur=`cpta_classeur`.id_classeur)/100 AS `solde` " .
      "FROM `cpta_classeur` " .
      "INNER JOIN `cpta_cpasso` ON `cpta_cpasso`.`id_cptasso` = `cpta_classeur`.`id_cptasso` " .
      "INNER JOIN `cpta_cpbancaire` ON `cpta_cpbancaire`.`id_cptbc` = `cpta_cpasso`.`id_cptbc` " .
      "INNER JOIN `asso` ON `asso`.`id_asso`=`cpta_cpasso`.`id_asso` " .
      "WHERE `cpta_classeur`.`ferme`=0 " .
      "ORDER BY `cpta_cpbancaire`.`nom_cptbc`, `asso`.`nom_asso`");

$tbl = new sqltable ("lstclasseur",
               "Classeur ouverts",
               $req_sql,
               "index.php",
               "id_classeur",
               array(
            "nom_cptbc" => "Compte bancaire",
            "nom_cptasso" => "Compte asso.",
            "nom_classeur" => "Classeur",
               "solde"=>"Solde",
               "date_debut_classeur"=>"De",
               "date_fin_classeur"=>"Au"

             ),
               array(),
               array(),
               array());

$cts->add($tbl,true);

$req_sql = new requete ($site->db,
      "SELECT id_cptasso, asso.nom_asso as nom_cptasso, " .
      "cpta_cpbancaire.nom_cptbc, cpta_cpbancaire.id_cptbc " .
      "FROM `cpta_cpasso` " .
      "INNER JOIN asso ON asso.id_asso=cpta_cpasso.id_asso " .
      "INNER JOIN cpta_cpbancaire ON cpta_cpbancaire.id_cptbc=cpta_cpasso.id_cptbc " .
      "ORDER BY `cpta_cpbancaire`.`nom_cptbc`, `asso`.`nom_asso`");

$tbl = new sqltable ("cpta_cptasso",
             "Comptes association",
             $req_sql,
             "./index.php",
             "id_cptasso",
             array("nom_cptbc" => "Compte bancaire",
            "nom_cptasso" => "Compte asso."
             ),
             array(),
             array(),
             array());

$cts->add($tbl,true);


$req_sql = new requete ($site->db,
      "SELECT * FROM `cpta_cpbancaire` ORDER BY nom_cptbc");

$tbl = new sqltable ("cpta_cpbancaire",
               "Comptes bancaires",
               $req_sql,
               "./index.php",
               "id_cptbc",
               array(
               "nom_cptbc" => "nom du compte"),
               array(),
               array(),
               array());

$cts->add($tbl,true);

}

$site->add_contents ($cts);

$site->end_page ();

exit ();


?>
