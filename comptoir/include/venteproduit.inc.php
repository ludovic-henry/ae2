<?php


/** @file
 *
 * @brief définition de la classe venteproduit
 *
 */
/* Copyright 2005-2008
 * - Julien Etelain <julien CHEZ pmad POINT net>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Simon Lopez <simon POINT lopez CHEZ ayolo POINT org>
 *
 * Ce fichier fait partie du site de l'Association des Ã©tudiants de
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

require_once("defines.inc.php");
require_once("produit.inc.php");
require_once("comptoir.inc.php");


/**
 * Classe gérant la mise en vente des produit
 * @ingroup comptoirs
 * @author Julien Etelain
 * @author Pierre Mauduit
 * @author Simon Lopez
 */
class venteproduit extends stdentity
{

  var $produit;
  var $comptoir;

  var $stock_local;

  /** @brief Met en vente un produit dans un comptoir
   *
   * @param produit le produit vendu
   * @param comptoir le comptoir en question
   * @param stock_local l'état du stock local
   *
   * @return true si succès, false sinon
   *
   */
  function nouveau ($produit, $comptoir, $stock_local)
  {
    $this->produit = &$produit;
    $this->comptoir = &$comptoir;
    $this->stock_local = intval($stock_local);

    $req = new insert ($this->dbrw,
           "cpt_mise_en_vente",
           array("id_produit" => $this->produit->id,
             "id_comptoir" => $this->comptoir->id,
           "stock_local_prod" => $this->stock_local,
           "date_mise_en_vente" => date("Y-m-d H:i:s")
           ));
    if ( !$req )
      return false;

    return true;
  }

  /** @brief Modifie le stock d'un produit dans un comptoir
   *
   * @param stock_local le stock local
   *
   */
  function modifier ($stock_local)
  {

    $this->stock_local = intval($stock_local);

    $req = new requete($this->dbrw,
           "UPDATE `cpt_mise_en_vente` ".
           "SET `stock_local_prod` = '".$this->stock_local."' ".
           "WHERE `id_produit` = '".intval($this->produit->id)."' ".
           "AND `id_comptoir` = '".intval($this->comptoir->id)."' ".
           "LIMIT 1");
    if ($req->lines < 0)
      return false;
    return true;
  }


  function load_by_id ( $id_produit, $id_comptoir=0, $force=false )
  {

    $req = new requete($this->db,
           "SELECT `cpt_mise_en_vente`.`stock_local_prod`
         FROM `cpt_mise_en_vente`
         WHERE `id_produit` = '".intval($id_produit)."'
         AND `id_comptoir` = '".intval($id_comptoir)."'
         LIMIT 1");

    if ( $req->lines != 1 && !$force )
      return false;

    $this->produit = new produit($this->db);
    $this->comptoir = new comptoir($this->db);

    $this->produit->load_by_id($id_produit);
    if ( !$this->produit->is_valid() )
      return false;

    $this->comptoir->load_by_id($id_comptoir);
    if ( !$this->comptoir->is_valid() )
      return false;

    if ( $req->lines == 1 )
      list($this->stock_local) = $req->get_row();
    else
      $this->stock_local = -1;

    return true;
  }

  /**
   * Non supporté
   */
  function _load($row)
  {

  }

  /** Enlève la mise en vente
   *
   */
  function supprime ()
  {
    $req = new delete ($this->dbrw, "cpt_mise_en_vente",
           array(
           "id_produit" =>  $this->produit->id,
           "id_comptoir" =>  $this->comptoir->id,
           ));

    return true;
  }


  /** @brief Charge l'objet à l'aide d'un produit (objet)
   *  et d'un comptoir (objet) et verifie la disponiblité en stock
   *
   * @param produit une référence vers un objet produit (?)
   * @param comptoir une référence vers un objet comptoir (?)
   *
   * @return true si succès, false si le produit n'est pas en vente on n'est plus en stock
   *
   */
  function charge ( $produit, $comptoir )
  {
    if ( !$this->_charge(&$produit,&$comptoir) )
      return false;
    if ($produit->action == ACTION_VSTOCKLIM || $produit->action == ACTION_PASS )
      if ($this->stock_local == 0 || $produit->stock_global == 0)
      {
        unset($this->produit);
        unset($this->comptoir);
        return false;
      }

    return true;
  }

  /** @brief Charge l'objet à l'aide d'un produit (objet)
   *  et d'un comptoir (objet)
   *
   * @param produit une référence vers un objet produit
   * @param comptoir une référence vers un objet comptoir
   *
   * @return true si succès, false sinon
   *
   */
  function _charge ( $produit, $comptoir )
  {

    $req = new requete($this->db,
           "SELECT `cpt_produits`.`stock_global_prod`, `cpt_mise_en_vente`.`stock_local_prod` ".
           "FROM `cpt_mise_en_vente` ".
           "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` = `cpt_mise_en_vente`.`id_produit` " .
           "WHERE `cpt_mise_en_vente`.`id_produit` = '".intval($produit->id)."' ".
           "AND `cpt_mise_en_vente`.`id_comptoir` = '".intval($comptoir->id)."' ".
           "AND (`cpt_produits`.date_fin_produit IS NULL OR `cpt_produits`.date_fin_produit>NOW()) ".
           "LIMIT 1");

    if ($req->lines < 1)
      return false;

    list($produit->stock_global,$this->stock_local) = $req->get_row();

    $this->produit = &$produit;
    $this->comptoir = &$comptoir;

    return true;
  }


  /** Incremente le stock
   * @private
   */
  function _increment ( $qte=1 )
  {

    if ($this->produit->stock_global != -1 )
    {
      $req = new requete($this->dbrw,
         "UPDATE `cpt_produits` ".
         "SET `stock_global_prod` = `stock_global_prod`+$qte ".
         "WHERE `id_produit` = '".intval($this->produit->id)."' ".
         "LIMIT 1");
      $this->produit->stock_global--;
    }

    if ($this->stock_local != -1 )
    {
      $req = new requete($this->dbrw,
         "UPDATE `cpt_mise_en_vente` ".
         "SET `stock_local_prod` = `stock_local_prod`+$qte ".
         "WHERE `id_produit` = '".intval($this->produit->id)."' ".
         "AND `id_comptoir` = '".intval($this->comptoir->id)."' ".
         "LIMIT 1");
      $this->stock_local--;
    }

  }

  /** Decremente le stock
   * @return true s'il y a eu decrementation, sinon false
   * @private
   */
  function _decrement ( $qte=1 )
  {
    $altered = false;

    if ($this->produit->stock_global >= $qte)
    {
      $req = new requete($this->dbrw,
         "UPDATE `cpt_produits` ".
         "SET `stock_global_prod` = `stock_global_prod` - $qte ".
         "WHERE `id_produit` = '".intval($this->produit->id)."' ".
         "LIMIT 1");
      $this->produit->stock_global--;
      $altered = true;
    }

    if ($this->stock_local >= $qte)
    {
      $req = new requete($this->dbrw,
         "UPDATE `cpt_mise_en_vente` ".
         "SET `stock_local_prod` = `stock_local_prod`-$qte ".
         "WHERE `id_produit` = '".intval($this->produit->id)."' ".
         "AND `id_comptoir` = '".intval($this->comptoir->id)."' ".
         "LIMIT 1");
      $this->stock_local--;
      $altered = true;
    }

    return $altered;
  }



  /** Reserve un produit un utilisateur
   *
   * @return false si le produit n'est pas en stock, true si disponible
   */
  function bloquer ( $client, $qte=1, $cot=FALSE )
  {

    if ($produit->action == ACTION_VSTOCKLIM || $produit->action == ACTION_PASS)
      if ( ($this->stock_local < $qte && $this->stock_local != -1) ||
          ($produit->stock_global < $qte && $produit->stock_global != -1) )
        return false;


    if ($this->_decrement($qte))
      $this->_delta_verrou($client, $qte, $cot);

    return true;
  }

  /** (usage interne UNIQUEMENT)
   * Fait varier le nombre de produits sur le comptoir réservé pour le client
   * @param $delta variation de la réservation (+1 pour réserver un produit, -1 pour enlever la reservation d'un produit)
   * @return variation effective
   * @private
   */
  function _delta_verrou ( $client, $delta )
  {
    $id_client = $client->id;

    if ( !$client->is_valid() )
      $id_client = 0;

    $req = new requete($this->dbrw,
           "SELECT `quantite` FROM `cpt_verrou` ".
           "WHERE `id_produit` = '".intval($this->produit->id)."' ".
           "AND `id_comptoir` = '".intval($this->comptoir->id)."' ".
           "AND `id_utilisateur` = '".intval($id_client)."' ");

    if ( $req->lines )
    {
      list($qte) = $req->get_row();

      if ( $qte+$delta <= 0 )
      {
        $req = new requete($this->dbrw,
             "DELETE FROM `cpt_verrou` ".
             "WHERE `id_produit` = '".intval($this->produit->id)."' ".
             "AND `id_comptoir` = '".intval($this->comptoir->id)."' ".
             "AND `id_utilisateur` = '".intval($id_client)."'");

        return -($qte);
      }
      else
      {
        $req = new requete($this->dbrw,
             "UPDATE `cpt_verrou` SET " .
             "`quantite` = `quantite` + $delta, `date_res` = NOW() ".
             "WHERE `id_produit` = '".intval($this->produit->id)."' ".
             "AND `id_comptoir` = '".intval($this->comptoir->id)."' ".
             "AND `id_utilisateur` = '".intval($id_client)."'");
        return $delta;
      }

    }
    elseif ( $delta > 0 )
    {
      $req = new insert ($this->dbrw,
                    "cpt_verrou",
                    array(
                      "id_produit" => $this->produit->id,
                      "id_comptoir" => $this->comptoir->id,
                      "id_utilisateur" => $id_client,
                      "date_res" => date("Y-m-d H:i:s"),
                      "quantite"=>$delta
                    )
                    );
      return $delta;
    }
    return 0;
  }

  /**
   * Enleve la reservation sur le produit
   * A utiliser pour annuler un blocage
   */
  function debloquer ( $client, $qte=1, $cot=FALSE )
  {
    $res = $this->_delta_verrou($client, -$qte);

    if ( $res != 0 )
      $this->_increment(-$res);
  }

  /**
   * Procéde aux actions suite à la vente effective d'un produit qui a été bloqué avec bloquer()
   */
  function vendu_bloque ( $operateur, $client, $prix, $qte=1, $cot=FALSE )
  {
    $this->_delta_verrou($client, -$qte);

    for($i=0;$i<$qte;$i++)
      $this->_action( $operateur, $client, $prix);

  }

  /** Procède aux actions suite à la vente d'un produit
   * !!Attention ne s'occupe pas du stock!!
   * @private
   */
  function _action ( $operateur, $client, $prix )
  {

    if ( $this->produit->action == ACTION_BON )
    {
      /* on ne credite pas si provenant
                     d'un eboutic de test            */
      global $topdir;
      require_once($topdir . "/e-boutic/include/e-boutic.inc.php");
      if ((STO_PRODUCTION == false)
          && ($this->comptoir->id == CPT_E_BOUTIC))
        return;

      $client->crediter ($operateur->id,
             PAIE_BONSITE,
             0,
             $prix,
             $this->produit->id_assocpt,
             $this->comptoir->id);
    }
    else if ( $this->produit->action == ACTION_CLASS )
    {
      $this->produit->dbrw = $this->dbrw;
      if ( $cl = $this->produit->get_prodclass($client) )
        $cl->vendu($client,$prix);
    }
  }

}
?>
