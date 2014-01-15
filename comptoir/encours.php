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
require_once($topdir."include/site.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");
require_once("include/defines.inc.php");
$site = new site();

if ( !$site->user->is_valid() )
  $site->error_forbidden("services");

if ( isset($_REQUEST['id_utilisateur']) )
{
  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST["id_utilisateur"]);
  if ( $user->id < 0 )
  {
    $site->error_not_found("services");
    exit();
  }
}
else
  $user = &$site->user;



if ( $_REQUEST["action"] == "retires")
{
  require_once("include/facture.inc.php");
  require_once("include/produit.inc.php");
  $fact = new debitfacture($site->db,$site->dbrw);
  $prod = new produit($site->db,$site->dbrw);
  foreach(  $_REQUEST["id_factprods"] as $id_factprod )
  {
    list($id_facture,$id_produit) = explode(",",$id_factprod);
    $fact->load_by_id($id_facture);
    $prod->load_by_id($id_produit);

    if (
        ( $site->user->is_in_group("gestion_ae") || $site->user->is_asso_role($prod->id_assocpt, 2))
        && $fact->id > 0 && $fact->id_utilisateur_client == $user->id )
      $fact->set_retire($id_produit);

  }
}

$site->start_page("services", $user->prenom . " " . $user->nom );

$cts = new contents("Commandes en cours/à retirer");

$req = new requete($site->db, "SELECT " .
      "CONCAT(`cpt_debitfacture`.`id_facture`,',',`cpt_produits`.`id_produit`) AS `id_factprod`, " .
      "`cpt_debitfacture`.`id_facture`, " .
      "`cpt_debitfacture`.`date_facture`, " .
      "`asso`.`id_asso`, " .
      "`asso`.`nom_asso`, " .
      "`cpt_vendu`.`a_retirer_vente`, " .
      "`cpt_vendu`.`a_expedier_vente`, " .
      "`cpt_vendu`.`quantite`, " .
      "`cpt_vendu`.`prix_unit`/100 AS `prix_unit`, " .
      "`cpt_vendu`.`prix_unit`*`cpt_vendu`.`quantite`/100 AS `total`," .
      "`cpt_produits`.`nom_prod`, " .
      "`cpt_produits`.`id_produit` " .
      "FROM `cpt_vendu` " .
      "INNER JOIN `asso` ON `asso`.`id_asso` =`cpt_vendu`.`id_assocpt` " .
      "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` =`cpt_vendu`.`id_produit` " .
      "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
      "WHERE `id_utilisateur_client`='".$user->id."' AND (a_retirer_vente='1' OR a_expedier_vente='1') " .
      "ORDER BY `cpt_debitfacture`.`date_facture` DESC");

$items=array();
$peut_retirer = false;
while ( $item = $req->get_row() )
{
  if ($site->user->is_in_group("gestion_ae") || $site->user->is_asso_role($item['id_asso'], 2))
    $peut_retirer = true;

  if ($user->id==$site->user->id || $site->user->is_in_group("gestion_ae") || $site->user->is_asso_role($item['id_asso'], 2))
  {
    if ( $item['a_retirer_vente'])
    {
      $noms=array();

      $req2 = new requete($site->db,
           "SELECT `cpt_comptoir`.`nom_cpt`
            FROM `cpt_mise_en_vente`
            INNER JOIN `cpt_comptoir` ON `cpt_comptoir`.`id_comptoir` = `cpt_mise_en_vente`.`id_comptoir`
            WHERE `cpt_mise_en_vente`.`id_produit` = '".$item['id_produit']."' AND `cpt_comptoir`.`type_cpt`!=1");

      if ( $req2->lines != 0 )
        while ( list($nom) = $req2->get_row() )
          $noms[] = $nom;

      $item["info"] = "A venir retirer à : ".implode(" ou ",$noms);
    }
    else if ( $item['a_expedier_vente'])
    {
      $item["info"] = "En preparation";
    }
    $items[]=$item;
  }
}

$cts->add(new sqltable(
  "listresp",
  "Achats", $items, "encours.php?id_utilisateur=".$user->id,
  "id_factprod",
  array(
    "nom_prod"=>"Produit",
    "quantite"=>"Quantité",
    "prix_unit"=>"Prix unitaire",
    "total"=>"Total",
    "info"=>""),
  /*($site->user->is_in_group("gestion_ae")&& ( $site->user->is_in_group("root") || $site->user->id != $user->id ))?array("delete"=>"Annuler la facture"):array()*/ array(),
  $peut_retirer?array("retires"=>"Marquer comme retiré"):array(),
  array( )
  ));

$site->add_contents($cts);

$site->end_page();
?>
