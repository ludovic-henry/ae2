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
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/user.inc.php");
require_once($topdir. "include/localisation.inc.php");

$site = new site();

$cts = new contents("Baguettes");

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

  while ( $item = $req->get_row() )
  {
      //date('l', strtotime($item['date_facture']))
      $cts->add_paragraph($item['date_facture'] . " : " . $item['prenom_utl'] . " " . $item['nom_utl'] . " (" . $item['surnom_utbm'] . ") : " . $item['quantite'] );
  }

$site->add_contents($cts);

$site->end_page();

?>
