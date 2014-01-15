<?php

/* Copyright 2006,2008
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
 * Gestion des factures (et debits) des comptes AE et de l'eboutic.
 */

/**
 * Classe gérant les factures cartes AE/e-boutic. Elle permet le debit sur les comptes AE.
 * @see venteproduit
 * @ingroup comptoirs
 */
class debitfacture extends stdentity
{

  /** Id du client */
  var $id_utilisateur_client;
  /** Id du vendeur */
  var $id_utilisateur;
  /** Id cu comptoir où s'est déroulé la vente */
  var $id_comptoir;
  /** date de la vente */
  var $date;
  /** Mode de paiement AE ou SG */
  var $mode;
  /** montant en centimes */
  var $montant;
  /** si SG numéro de transaction */
  var $transacid;
  /** Etat */
  var $etat;


  /**
   * Charge la facture en fonction de son ID
   * @param $id Id de la facture
   */
  function load_by_id ( $id )
  {

    $req = new requete($this->db,"SELECT * FROM cpt_debitfacture WHERE id_facture='".intval($id)."'");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function _load ( $row )
  {
    $this->id = $row['id_facture'];
    $this->id_utilisateur_client = $row['id_utilisateur_client'];
    $this->id_utilisateur = $row['id_utilisateur'];
    $this->id_comptoir = $row['id_comptoir'];
    $this->date = strtotime($row['date_facture']);
    $this->mode = $row['mode_paiement'];
    $this->montant = $row['montant_facture'];
    $this->transacid = $row['transacid'];
    $this->etat = $row['etat_facture'];
  }

  /**
   * Procéde à un debit sur un compte AE
   * @param $client Instance d'utilisateur, le client qui va être débité
   * @param $vendeur Instance d'utilisateur, personne prenant la responsabilité de l'opération
   * @param $comptoir Instance de comptoir, lieu où s'est faite la vente
   * @param $panier Panier, tableau contenant des instances de venteproduit de la forme array(array(quatité,venteproduit))
   * @param $prix_barman Utilise le prix barman ou non (true:prix barman, false: prix publique)
   * @param $etat Etat initial de retrait/expedition. Si commande à expédier ETAT_FACT_A_EXPEDIER, sinon 0
   * @return false en cas de problème (solde insuffisent, erreur sql) sinon true
   * @see venteproduit
   */
  function debitAE ( $client, $vendeur, $comptoir, $panier, $prix_barman, $etat=0 )
  {
    $this->id_utilisateur_client = $client->id;
    $this->id_utilisateur = $vendeur->id;
    $this->id_comptoir = $comptoir->id;
    $this->date = time();
    $this->mode = "AE";
    $this->transacid = "";
    $this->etat = $etat;

    $this->montant = $this->calcul_montant($panier, $prix_barman,$client);

    if ( !$client->credit_suffisant($this->montant) )
      return false;

    $req = new insert ($this->dbrw,
           "cpt_debitfacture",
           array(
           "id_utilisateur_client" => $this->id_utilisateur_client,
           "id_utilisateur" => $this->id_utilisateur,
           "id_comptoir" => $this->id_comptoir,
           "date_facture" => date("Y-m-d H:i:s",$this->date),
           "mode_paiement" => $this->mode,
           "montant_facture" => $this->montant,
           "transacid" => $this->transacid,
           "etat_facture" => $this->etat
         ));

    if ( !$req )
      return false;

    $this->id = $req->get_id();

    $req2 = new requete($this->dbrw,"UPDATE `utilisateurs`
            SET `montant_compte` = `montant_compte` - ".$this->montant."
            WHERE `id_utilisateur` = '".$this->id_utilisateur_client."'");



    $this->traiter_panier($client,$vendeur,$panier, $prix_barman,true,($comptoir->type==1));

    return true;
  }

  /**
   * Enregistre et valide une vente effectue sur e-boutic
   * @param $client Instance d'utilisateur, le client
   * @param $vendeur Instance d'utilisateur, personne prenant la responsabilité de l'opération (en général le client)
   * @param $comptoir Instance de comptoir, lieu où s'est faite la vente
   * @param $panier Panier, tableau contenant des instances de venteproduit de la forme array(array(quatité,venteproduit))
   * @param $transacid Numéro de transaction sogenactif
   * @param $etat Etat initial de retrait/expedition. Si commande à expédier ETAT_FACT_A_EXPEDIER, sinon 0
   * @return false en cas de problème (erreur sql) sinon true
   * @see venteproduit
   */
  function debitSG ( $client, $vendeur, $comptoir, $panier, $transacid, $etat=0 )
  {

    $this->id_utilisateur_client = $client->id;
    $this->id_utilisateur = $vendeur->id;
    $this->id_comptoir = $comptoir->id;
    $this->date = time();
    $this->mode = "SG";
    $this->montant = $this->calcul_montant($panier,false,$client);
    $this->transacid = $transacid;
    $this->etat = $etat;

    $req = new insert ($this->dbrw,
           "cpt_debitfacture",
           array(
           "id_utilisateur_client" => $this->id_utilisateur_client,
           "id_utilisateur" => $this->id_utilisateur,
           "id_comptoir" => $this->id_comptoir,
           "date_facture" => date("Y-m-d H:i:s",$this->date),
           "mode_paiement" => $this->mode,
           "montant_facture" => $this->montant,
           "transacid" => $this->transacid,
           "etat_facture" => $this->etat
         ));

    if ( !$req )
      return false;

    $this->id = $req->get_id();

    $this->traiter_panier($client,$vendeur,$panier,false,false,($comptoir->type==1));

    return true;
  }

  /**
   * Calcule le montant d'un panier
   * @param $panier Panier, tableau contenant des instances de venteproduit de la forme array(array(quatité,venteproduit))
   * @param $prix_barman Utilise le prix barman ou non (true:prix barman, false: prix publique)
   * @return le montant en centimes
   */
  function calcul_montant ( $panier, $prix_barman, &$client )
  {
    $montant = 0;
    $montantBarman = 0;

    foreach ( $panier as $item )
    {
      list($quantite,$vp) = $item;

      $montantBarman += $quantite * $vp->produit->obtenir_prix($prix_barman,$client );

      if ($quantite > 0 && $vp->produit->plateau)
        $quantite -= floor ($quantite/6);

      $montant += $quantite * $vp->produit->obtenir_prix(false,$client );
    }

    return ($montantBarman<$montant)?$montantBarman:$montant;
  }

  /**
   * Modifie l'état de la facture (expedition/retrait)
   * @param $etat Nouvel etat de la facture
   */
  function set_etat ( $etat )
  {
    if ( $this->etat != $etat )
    {
      $this->etat = $etat;
      $req = new update ($this->dbrw,"cpt_debitfacture",array("etat_facture" => $this->etat),array("id_facture" => $this->id));
    }
  }

  /**
   * Procède à la "vente" de l'ensemble des produits (Usage strictement interne).
   *
   * - met à jours les stocks
   * - procède aux actions des produits (comme pour les cotisations)
   * - met à jour l'état de retrait/expedition de la facture [si $eboutic est à true]
   * - archive les produits vendus (dans la table cpt_vendu)
   * - marque les produits à retirer/à expédier [si $eboutic est à true]
   *
   * @param $client Utilisateur client (instance de utilisateur)
   * @param $client Utilisateur vendeur (instance de utilisateur) (premier barman, ou client si e-boutic)
   * @param $panier Panier, tableau contenant des instances de venteproduit de la forme array(array(quatité,venteproduit))
   * @param $prix_barman Utilise le prix barman ou non (true:prix barman, false: prix publique)
   * @param $asso_sum Met à jour la comme de contrôle des associations qui vendent les produits (seulement en mode carte AE) (obsolète)
   * @param $eboutic Vente sur un comptoir eboutic, procède aux mises à jour de l'état de retrait/expedition
   * @private
   */
  function traiter_panier ( $client,$vendeur, $panier, $prix_barman, $asso_sum, $eboutic )
  {
    foreach ( $panier as $item )
    {
      list($quantite,$vp) = $item;
      $a_expedier=NULL;
      $a_retirer=NULL;

      if ( $eboutic ) // Comptoir de type e-boutic
      {
        if (  ($this->etat & ETAT_FACT_A_EXPEDIER ) && $vp->produit->postable )
          $a_expedier = true;

        if ( $vp->produit->a_retirer )
        {
          if ( $this->etat & ETAT_FACT_A_RETIRER )
          {
            $this->set_etat( $this->etat| ETAT_FACT_A_RETIRER );
            $a_retirer = true;
          }
          // Auto detection du retrait si non postable, ou si commande non expédiée
          elseif ( !$vp->produit->postable || !( $this->etat & ETAT_FACT_A_EXPEDIER ) )
          {
            $a_retirer = true;
            $this->set_etat( $this->etat| ETAT_FACT_A_RETIRER );
          }
        }
      }

      $prix = $vp->produit->obtenir_prix(false,$client);
      $prixBarman = $vp->produit->obtenir_prix($prix_barman,$client);

      $sub_q = 0;
      if ($quantite > 0 && $vp->produit->plateau)
	      $sub_q = floor ($quantite / 6);

      $prixFinal = 0;
      if(($prix*($quantite-$sub_q))<($prixBarman*$quantite))
      {
	      $quantite -= $sub_q;
	      $prixFinal = $prix;
      }
      else
      {
	      $prixFinal = $prixBarman;
	      $sub_q = 0;
      }

      $req = new insert ($this->dbrw,
             "cpt_vendu",
             array(
               "id_facture" => $this->id,
               "id_produit" => $vp->produit->id,
               "id_assocpt" => $vp->produit->id_assocpt,
               "quantite" => $quantite,
               "prix_unit" => $prixFinal,
               "a_retirer_vente" => $a_retirer,
               "a_expedier_vente" => $a_expedier
             ));

      if ($sub_q > 0) {
        $req = new insert ($this->dbrw,
               "cpt_vendu",
               array(
                 "id_facture" => $this->id,
                 "id_produit" => $vp->produit->id,
                 "id_assocpt" => $vp->produit->id_assocpt,
                 "quantite" => $sub_q,
                 "prix_unit" => 0,
                 "a_retirer_vente" => $a_retirer,
                 "a_expedier_vente" => $a_expedier
              ));
      }

        /* Somme de controle utilise */
      if ( $asso_sum && $vp->produit->id_assocpt )
        $sql = new requete($this->dbrw,"UPDATE `cpt_association`
                          SET `montant_ventes_asso` = `montant_ventes_asso` + ".($prixFinal*$quantite)."
              WHERE `id_assocpt` = '" . $vp->produit->id_assocpt ."'");

      $vp->vendu_bloque($vendeur,$client,$prixFinal,$quantite);
    }
  }

  /**
   * Annule la facture actuelle
   * ATTENTION: n'annule pas les actions liées aux produits (rechargement carte AE, cotisation...)
   * @return true en cas de succès, false sinon
   */
  function annule_facture ( )
  {
    // Seul les paiements par carte AE peuvent être annulés
    if ( $this->mode != "AE" )
      return false;

    // Après la fin du mois, le paiement ne peut pas être annulé
    // car la véritable facture a du être établi
    if ( date("Y-m") != date("Y-m",$this->date) )
      return false;

    $sql = new requete($this->db,"SELECT `cpt_vendu`.*,`stock_global_prod`,`stock_local_prod` " .
        "FROM `cpt_vendu` " .
        "INNER JOIN `cpt_produits` ON `cpt_vendu`.`id_produit`=`cpt_produits`.`id_produit` " .
        "INNER JOIN `cpt_mise_en_vente` ON " .
          "(`cpt_vendu`.`id_produit`=`cpt_mise_en_vente`.`id_produit` " .
          "AND `cpt_mise_en_vente`.`id_comptoir`='".intval($this->id_comptoir)."') ".
        "WHERE `cpt_vendu`.`id_facture`='".intval($this->id)."'");

    while ( $row = $sql->get_row() )
    {
      if ( $row['stock_global_prod'] != -1 )
        $req = new requete($this->dbrw,
          "UPDATE `cpt_produits` ".
          "SET `stock_global_prod` = `stock_global_prod`+".$row['quantite']." ".
          "WHERE `id_produit` = '".$row['id_produit']."' " .
          "LIMIT 1");

      if ( $row['stock_local_prod'] != -1 )
        $req = new requete($this->dbrw,
          "UPDATE `cpt_mise_en_vente` ".
          "SET `stock_local_prod` = `stock_local_prod`+".$row['quantite']." ".
          "WHERE `id_produit` = '".$row['id_produit']."' ".
          "AND `id_comptoir` = '".intval($this->id_comptoir)."' " .
          "LIMIT 1");

      if ( $this->mode == "AE" )
      {
        $req = new requete($this->dbrw,"UPDATE `cpt_association`
            SET `montant_ventes_asso` = `montant_ventes_asso` + ".($row['prix_unit']*$row['quantite'])."
            WHERE `id_assocpt` = '" . $row['id_assocpt'] ."'");
      }
    }

    if ( $this->mode == "AE" )
    {
      $req2 = new requete($this->dbrw,"UPDATE `utilisateurs`
            SET `montant_compte` = `montant_compte` + ".intval($this->montant)."
            WHERE `id_utilisateur` = '".intval($this->id_utilisateur_client)."'");
    }

    $req = new delete ($this->dbrw,"cpt_vendu",array("id_facture" => $this->id));
    $req = new delete ($this->dbrw,"cpt_debitfacture",array("id_facture" => $this->id));

    return true;
  }

  /**
   * Marque un produit de la facture comme retiré
   * @param $id_produit Id du produit
   */
  function set_retire ( $id_produit)
  {
    $req = new update ($this->dbrw,"cpt_vendu",
      array("a_retirer_vente" => 0 ),
      array("id_facture" => $this->id,"id_produit"=>$id_produit));

    $this->recalcul_etat_retrait();
  }

  /**
   * Met à jour l'état de retrait de la facture
   * @private
   */
  function recalcul_etat_retrait()
  {
    $req = new requete($this->db, "SELECT COUNT(*) ".
      "FROM `cpt_vendu` " .
      "WHERE `id_facture`='".$this->id."' AND a_retirer_vente='1'");

    list($nb) = $req->get_row();

    if ( $nb == 0 )
      $this->set_etat( $this->etat & ~ETAT_FACT_A_RETIRER );
  }


}

/**@}*/
?>
