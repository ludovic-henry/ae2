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
require_once("include/comptoirs.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");
require_once($topdir."include/graph.inc.php");
$site = new sitecomptoirs();

if ( !$site->user->is_valid() )
{
  header("Location: ../403.php?reason=session");
  exit();
}

$comptoirs = array();
if ( $site->user->is_in_group("gestion_ae") )
{
  $comptoirs[0] = "-";
  $req = new requete($site->db,"SELECT `id_comptoir`,`nom_cpt` FROM `cpt_comptoir`");
}
else
  $req = new requete($site->db,"SELECT `cpt_comptoir`.`id_comptoir`,`cpt_comptoir`.`nom_cpt`
       FROM `cpt_comptoir`
       INNER JOIN `utl_groupe` ON `utl_groupe`.`id_groupe` = `cpt_comptoir`.`id_groupe`
       WHERE `utl_groupe`.`id_utilisateur` = '".intval($site->user)."'");

while ( list($id,$nom) = ($row = $req->get_row()) )
  $comptoirs[$id] = $nom;

if ( !count($comptoirs) && !$site->user->is_in_group("gestion_ae") )
  $site->error_forbidden("services");


$conds = array();
$comptoir = false;

if ( $_REQUEST["debut"] )
  $conds[] = "cpt_debitfacture.date_facture >= '".date("Y-m-d H:i:s",$_REQUEST["debut"])."'";

if ( $_REQUEST["fin"] )
  $conds[] = "cpt_debitfacture.date_facture <= '".date("Y-m-d H:i:s",$_REQUEST["fin"])."'";

if ( isset($comptoirs[$_REQUEST["id_comptoir"]]) && $_REQUEST["id_comptoir"] )
{
  $conds[] = "cpt_debitfacture.id_comptoir='".intval($_REQUEST["id_comptoir"])."'";
  $comptoir=true;
}

if ( $comptoir || $site->user->is_in_group("gestion_ae") )
{

  if ( $_REQUEST["id_assocpt"] )
    $conds[] = "cpt_vendu.id_assocpt='".intval($_REQUEST["id_assocpt"])."'";

  if ( $_REQUEST["id_typeprod"] )
    $conds[] = "cpt_produits.id_typeprod='".intval($_REQUEST["id_typeprod"])."'";

  if ( $_REQUEST["id_produit"] )
    $conds[] = "cpt_vendu.id_produit='".intval($_REQUEST["id_produit"])."'";
}

if ( $_REQUEST["mode"] == "day" )
  $decoupe = "DATE_FORMAT(`cpt_debitfacture`.`date_facture`,'%Y-%m-%d')";
elseif ( $_REQUEST["mode"] == "week" )
  $decoupe = "YEARWEEK(`cpt_debitfacture`.`date_facture`)";
elseif ( $_REQUEST["mode"] == "year" )
  $decoupe = "DATE_FORMAT(`cpt_debitfacture`.`date_facture`,'%Y')";
else
  $decoupe = "DATE_FORMAT(`cpt_debitfacture`.`date_facture`,'%Y-%m')";

$req = new requete($site->db, "SELECT " .
    "$decoupe AS `unit`, " .
    "SUM(`cpt_vendu`.`quantite`), " .
    "SUM(`cpt_vendu`.`prix_unit`*`cpt_vendu`.`quantite`) AS `total`," .
    "SUM(`cpt_produits`.`prix_achat_prod`*`cpt_vendu`.`quantite`) AS `total_coutant`" .
    "FROM `cpt_vendu` " .
    "INNER JOIN `asso` ON `asso`.`id_asso` =`cpt_vendu`.`id_assocpt` " .
    "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` =`cpt_vendu`.`id_produit` " .
    "INNER JOIN `cpt_type_produit` ON `cpt_produits`.`id_typeprod` =`cpt_type_produit`.`id_typeprod` " .
    "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
    "INNER JOIN `utilisateurs` AS `vendeur` ON `cpt_debitfacture`.`id_utilisateur` =`vendeur`.`id_utilisateur` " .
    "INNER JOIN `utilisateurs` AS `client` ON `cpt_debitfacture`.`id_utilisateur_client` =`client`.`id_utilisateur` " .
    "INNER JOIN `cpt_comptoir` ON `cpt_debitfacture`.`id_comptoir` =`cpt_comptoir`.`id_comptoir` " .
    "WHERE " .implode(" AND ",$conds)." " .
    "GROUP BY `unit` ".
    "ORDER BY `unit`");

$coords=array();
$tics=array();
$i=0;
$strip = round($req->lines/7);

while ( list($unit,$qte,$total,$coutant) = $req->get_row() )
{
  if ( $_REQUEST["mode"] == "day" )
    $unit = date("d/m/y",strtotime($unit));

  if ( $i%$strip && ($i != $req->lines-1) && $i != 0 )
    $tics[$i]="";
  else
    $tics[$i]=$unit;

  $coords[] = array('x'=>$i,'y'=>array($total/100,$qte,$coutant/100));
  $i++;
}

$grfx = new graphic ("Resultats",
        array("c.a.","qte","countant"),
        $coords,false,$tics);

$grfx->png_render();
$grfx->destroy_graph();


?>
