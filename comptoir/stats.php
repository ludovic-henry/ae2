<?php

/* Copyright 2007
 * - Benjamin Collet < bcollet at oxynux dot org >
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
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/cts/user.inc.php");
require_once($topdir. "include/graph.inc.php");
require_once("include/comptoirs.inc.php");

$site = new sitecomptoirs();

if ( !$site->user->is_valid() )
{
  header("Location: /connexion.php?redirect_to=" . urlencode($_SERVER['REQUEST_URI']));
  exit();
}

$site->fetch_proprio_comptoirs();

$comptoirs = array(0=>"-") + $site->proprio_comptoirs;

if ( !count($site->proprio_comptoirs) && !$site->user->is_in_group("gestion_ae") )
  $site->error_forbidden("services");

$site->set_admin_mode();

$site->start_page("services","Statistiques de consommation");
$cts = new contents("Statistiques de consommation");

$cts->add_paragraph("<br />Hum... où en est le cours de la Vodka ce mois ci ?");

$frm = new form ("cptstats","stats.php",true,"POST","Critères de selection");
$frm->add_hidden("action","view");
$frm->add_datetime_field("debut","Date et heure de début");
$frm->add_datetime_field("fin","Date et heure de fin");
$frm->add_entity_select("id_assocpt", "Association", $site->db, "assocpt",$_REQUEST["id_assocpt"],true);
$frm->add_entity_select("id_typeprod", "Type", $site->db, "typeproduit",$_REQUEST["id_typeprod"],true);
$frm->add_select_field("id_comptoir","Lieu", $comptoirs, $_REQUEST["id_comptoir"]);
$frm->add_submit("valid","Voir");
$cts->add($frm,true);

if ( $_REQUEST["action"] == "view" )
{
  $conds = array();
  $comptoir = false;

  if ( $_REQUEST["debut"] && !empty($_REQUEST["debut"]) )
    $conds[] = "`cpt_debitfacture`.`date_facture` >= '".date("Y-m-d H:i:s",$_REQUEST["debut"])."'";

  if ( $_REQUEST["fin"] && !empty($_REQUEST["fin"]) )
    $conds[] = "`cpt_debitfacture`.`date_facture` <= '".date("Y-m-d H:i:s",$_REQUEST["fin"])."'";

  if ( isset($comptoirs[$_REQUEST["id_comptoir"]]) && $_REQUEST["id_comptoir"] )
  {
    $conds[] = "`cpt_debitfacture`.`id_comptoir`='".intval($_REQUEST["id_comptoir"])."'";
    $comptoir_rqt="INNER JOIN `cpt_mise_en_vente` ON `cpt_mise_en_vente`.`id_produit`=`cpt_produits`.`id_produit` ";
    $comptoir = true;
  }
  if ( $comptoir || $site->user->is_in_group("gestion_ae") )
  {
    if ( $_REQUEST["id_assocpt"] && !empty($_REQUEST["id_assocpt"]))
      $conds[] = "`cpt_produits`.`id_assocpt`='".intval($_REQUEST["id_assocpt"])."'";

    if ( $_REQUEST["id_typeprod"] && !empty($_REQUEST["id_typeprod"]) )
      $conds[] = "`cpt_produits`.`id_typeprod`='".intval($_REQUEST["id_typeprod"])."'";
  }

  if ( count($conds) )
  {
    $req = new requete($site->db,
      "SELECT nom_prod, id_produit, nom_asso, nom_typeprod, ventes, plateaux, 6 * plateaux AS verres_en_plateau, ventes - 6 * plateaux AS verres_hors_plateau " .
      "FROM ( " .
        "SELECT `cpt_produits`.`nom_prod`,`cpt_produits`.`id_produit`, " .
        "`asso`.`nom_asso`, " .
        "`cpt_type_produit`.`nom_typeprod`, " .
        "SUM(`cpt_vendu`.`quantite`) AS `ventes`, ".
        "SUM(IF(`cpt_vendu`.`prix_unit` = 0, 1, 0)) AS `plateaux` ".
        "FROM `cpt_produits` " .
        "INNER JOIN `cpt_vendu` ON `cpt_produits`.`id_produit` =`cpt_vendu`.`id_produit` " .
        "INNER JOIN `cpt_type_produit` ON `cpt_type_produit`.`id_typeprod`=`cpt_produits`.`id_typeprod` " .
        "INNER JOIN `asso` ON `asso`.`id_asso`=`cpt_produits`.`id_assocpt` " .
        //$comptoir_rqt .
        "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
        "WHERE " .implode(" AND ",$conds).
        "GROUP BY `cpt_produits`.`id_produit` " .
        "ORDER BY `ventes` DESC ) t");

    $tbl = new sqltable("products",
                        "Produits",
                        $req,
                        "",
                        "id_produit",
                        array("ventes"=>"Nombre de ventes",
                              "nom_typeprod"=>"Type",
                              "nom_prod"=>"Nom du produit",
                              "nom_asso"=>"Association",
                              "plateaux"=>"Plateaux",
                              "verres_hors_plateau"=>"Verres Hors Plateaux",
                              "verres_en_plateau"=>"Verres En Plateaux"),
                        array(),
                        array(),
                        array());
    $cts->add($tbl,true);

  }

}

$site->add_contents($cts);
$site->end_page();

?>
