<?php

/* Copyright 2005,2006,2008
 * - Julien Etelain <julien CHEZ pmad POINT net>
 *
 * Ce fichier fait partie du site de l'Association des étudiants de
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

/**
 * @file
 * Interface de vente par carte AE sur des comptoirs de type "classique" (bar).
 * Remarque: Ce fichier ne contient que les spécificités de ce type de comptoir.
 *
 * L'id du comptoir doit être définit en GET ou en POST : id_comptoir
 *
 * La salle est vérifiée par ce script : l'id de la salle du poste client
 * est démandée à get_localisation(), si elle différe de id_salle du comptoir,
 * l'accès est bloqué.
 *
 * Ce script ajoute la boite latérale pour la connexion des barmens, et prends
 * en charge les opérations liées.
 *
 * @see comptoir/frontend.inc.php
 * @see comptoir
 * @see sitecomptoirs
 * @see get_localisation
 */

$topdir="../";
require_once("include/comptoirs.inc.php");
require_once($topdir. "include/cts/user.inc.php");
require_once($topdir. "include/localisation.inc.php");

$site = new sitecomptoirs(true );

if (! $site->comptoir->ouvrir($_REQUEST["id_comptoir"]))
  $site->error_not_found("services");

if ( !$site->comptoir->is_valid() )
  $site->error_not_found("services");

if ( get_localisation() != $site->comptoir->id_salle )
  $site->error_forbidden("services","wrongplace");

if ( $site->comptoir->type != 0 )
  $site->error_forbidden("services","invalid");

if ( $_REQUEST["action"] == "logoperateur" )
{
  $op = new utilisateur($site->db);

  if ( $_REQUEST["code_bar_carte"] )
    $op->load_by_carteae($_REQUEST["code_bar_carte"]);
  else
    $op->load_by_email($_REQUEST["adresse_mail"]);

  if ( !$op->is_valid() || !$op->is_password($_REQUEST["password"]) )
      $opErreur = "Etudiant inconnu / mauvais mot de passe";
      // restons vague sur les details de l'erreur

  else
  {
    foreach($site->comptoir->operateurs as $_op)
    {
      if($_op->id == $op->id)
      {
        $op=null;
        break;
      }
    }
    if(!is_null($op))
      if ( !$site->comptoir->ajout_operateur($op) )
        $opErreur = "Refusé";
  }

}
/*
  Loggage d'un barman
*/
else if ( $_REQUEST["action"] == "unlogoperateur" )
{

  $site->comptoir->enleve_operateur($_REQUEST["id_operateur"]);


}

// Boite sur le coté
$cts = new contents("Comptoir");

$cts->add_paragraph("<a href=\"index.php\">Autre comptoirs</a>");

if ($site->comptoir->rechargement)
  $cts->add_paragraph("<a href=\"caisse.php?action=new&id_comptoir=".$site->comptoir->id."\">Faire un relevé de caisse</a>");

$lst = new itemlist();
foreach( $site->comptoir->operateurs as $op )
{
  $oplog = true;
  $lst->add(
    "<a href=\"comptoir.php?id_comptoir=".$site->comptoir->id."&amp;".
    "action=unlogoperateur&amp;id_operateur=".$op->id."\">". $op->prenom.
    " ".$op->nom."</a>");
}
$cts->add($lst);

$frm = new form ("logoperateur","comptoir.php?id_comptoir=".$site->comptoir->id);
if ( $opErreur )
  $frm->error($opErreur);
$frm->add_hidden("action","logoperateur");
$frm->add_text_field("adresse_mail","Adresse email","prenom.nom@utbm.fr");
$frm->add_text_field("code_bar_carte","Carte AE");
$frm->add_password_field("password","Mot de passe");
$frm->add_submit("valid","valider");
$cts->add($frm);

$site->add_box("comptoir",$cts);
unset($cts);


//Clickage baguettes
if ( $_REQUEST["action"] == "clickbaguettes" && $_REQUEST["id_comptoir"] == 2 )
{
  require_once("include/facture.inc.php");
  require_once("include/produit.inc.php");
  $fact = new debitfacture($site->db,$site->dbrw);
  $prod = new produit($site->db,$site->dbrw);

  list($id_facture,$id_produit) = explode(",",$_REQUEST["id_factprod"]);
  $fact->load_by_id($id_facture);
  $prod->load_by_id($id_produit);

  if ($oplog)
    $fact->set_retire($id_produit);
}

// Boite pour les baguettes au foyer.
if($_REQUEST["id_comptoir"] == 2 && $oplog)
{

    $req = new requete($site->db, "SELECT " .
    "CONCAT(`cpt_debitfacture`.`id_facture`,',',`cpt_produits`.`id_produit`) AS `id_factprod`, " .
    "`cpt_debitfacture`.`id_facture`, " .
    "`cpt_debitfacture`.`date_facture`, " .
    "`cpt_debitfacture`.`id_utilisateur_client`, " .
    "`cpt_debitfacture`.`date_facture`, " .
    "`asso`.`id_asso`, " .
    "`asso`.`nom_asso`, " .
    "`cpt_vendu`.`a_retirer_vente`, " .
    "`cpt_produits`.`a_retirer_info`, " .
    "`cpt_vendu`.`a_expedier_vente`, " .
    "`cpt_vendu`.`quantite`, " .
    "`cpt_vendu`.`prix_unit`/100 AS `prix_unit`, " .
    "`cpt_vendu`.`prix_unit`*`cpt_vendu`.`quantite`/100 AS `total`," .
    "`cpt_produits`.`nom_prod`, " .
    "`cpt_produits`.`id_produit`, " .
    "`utilisateurs`.`nom_utl`, " .
    "`utilisateurs`.`prenom_utl`, " .
    "`utl_etu_utbm`.`surnom_utbm` " .
    "FROM `cpt_vendu` " .
    "INNER JOIN `asso` ON `asso`.`id_asso` =`cpt_vendu`.`id_assocpt` " .
    "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` =`cpt_vendu`.`id_produit` " .
    "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
    "INNER JOIN `utilisateurs` ON `utilisateurs`.`id_utilisateur`=`cpt_debitfacture`.`id_utilisateur_client` " .
    "INNER JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`cpt_debitfacture`.`id_utilisateur_client` " .
    "WHERE `cpt_produits`.`id_produit`='766' ".
    "AND (`cpt_vendu`.`a_retirer_vente`='1' OR `cpt_vendu`.`a_expedier_vente`='1') " .
    "ORDER BY `cpt_debitfacture`.`date_facture` DESC");

  $cts = new contents("Baguettes");
  $lst = "";

  while ( $item = $req->get_row() )
  {
      $lst = $lst . "<a href=\"" . $topdir . "comptoir/comptoir.php?id_comptoir=" . $site->comptoir->id . "&amp;"."action=clickbaguettes&amp;id_factprod=" . $item['id_factprod'] . "\">" . $item['prenom_utl'] . " " . $item['nom_utl'] . " (" . $item['surnom_utbm'] . ") : " . $item['quantite'] . "</a><br />";
  }
  $cts->add_paragraph($lst);
  $site->add_box("baguettes",$cts);
  unset($cts);
}

include("frontend.inc.php");

?>
