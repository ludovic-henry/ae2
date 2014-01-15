<?php

/**
 * @file
 */

/*
 *  Classe comptoir.
*/

/* Copyright 2005,2006,2008,2010
 * - Julien Etelain <julien CHEZ pmad POINT net>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Cyrille Platteau <6pour5 CHEZ gmail POINT com>
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

require_once($topdir . "comptoir/include/facture.inc.php");
require_once($topdir . "comptoir/include/cts/product.inc.php");

/**
 * Renvoie le premier élément d'un tableau
 * @param $array Tableau
 * @return le premier élément de $array
 */
function first ( $array )
{
  reset($array);
  return current($array);
}


/**
 * Classe gérant un comptoir et des sessions de vente.
 *
 * Cette classe peut gérer des sessions de ventes, qui doivent se dérouler
 * de la manière qui suit :
 * 1 - Connecter le ou les barmens
 * 2 - Connecter le client
 * 3 - Ajouter les produits un à un dans le pannier
 * 4 - Valider le panier, procède à la vente
 * 5 - Retourner à 2...
 *
 * Cette classe prend en charge le mode "book" qui permet de mettre des livres
 * dans le panier. ATTENTION: cette classe en prend pas en charge le pret de ces
 * livres (ex: fait par frontend.inc.php)
 *
 * Ces différentes opérations peuvent se faire dans des appels de page différents.
 * Voir frontend.inc.php pour l'usage de cette fonction.
 *
 * Un comptoir peut être de plusieurs types :
 * 0 "Comptoir classique"
 *    Pour les bars, plusieurs opérateurs peuvent se connecter.
 * 1 "Bureau"
 *    Pour les ventes dans les bureaux de l'AE. Un seul opérateur est connecté
 *    il s'agit normalement de l'utilisateur connecté au site.
 * 2 "E-boutic"
 *    Comptoir pour la vente en ligne. Aucun operateur n'est connecté, c'est
 *    le client qui est considéré comme opérateur. Cette classe NE PEUT PAS être
 *    utilsié pour réaliser des ventes sur des comptoirs de ce type.
 *    frontend.inc.php ne peut donc pas être utilisés avec ces comptoirs.
 *    La vente se fait directement avec debitfacture
 *
 * @see debitfacture
 * @see produit
 * @see venteproduit
 * @see comptoir/frontend.inc.php
 * @ingroup comptoirs
 */
class comptoir extends stdentity
{
  /* Informations comptoir */
  /** Nom du comptoir */
  var $nom;
  /** Id compte association (obsolète)
   * @deprecated
   */
  var $id_assocpt;
  /** Id du groupe barmens */
  var $groupe_vendeurs;
  /** Id du groupe administrateur */
  var $groupe_admins;
  /** Type de comptoir (bar, bureau, e-boutic) */
  var $type;
  /** Id de la salle où se trouve le comptoir */
  var $id_salle;

  /* Informations de session */
  /** Barmen connectés (array(instance utilisateur)) */
  var $operateurs;
  /** Panier [si un client est connecté] */
  var $panier;
  /** Client connecté (instance utilisateur) */
  var $client;
  /** Le client a droit au prix barman */
  var $prix_barman;
  /** Mode du comptoir ("book" ou null/"") */
  var $mode;
  /** Rechargement activé ou pas */
  var $rechargement;


  /**
   * Charge un comptoir en fonction de son id
   * En cas d'erreur, l'id est défini à null
   * @param $id id du comptoir
   * @return true en cas du succès, false sinon
   */
  function load_by_id ($id)
  {

    $req = new requete($this->db,"SELECT *
               FROM `cpt_comptoir`
               WHERE `id_comptoir`='".intval($id)."'");

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
    $this->id = $row['id_comptoir'];
    $this->nom = $row['nom_cpt'];
    $this->id_assocpt = $row['id_assocpt'];
    $this->groupe_vendeurs = $row['id_groupe_vendeur'];
    $this->groupe_admins = $row['id_groupe'];
    $this->type = $row['type_cpt'];
    $this->id_salle = $row['id_salle'];
    $this->rechargement = $row['rechargement'];
    $this->archive = $row['archive'];
  }

  /**
   * Ajout d'un comptoir
   *
   * @param $nom le nom générique du comptoir
   * @param $id_assocpt l'id de l'association concernée (obsolète)
   * @param $groupe_vendeurs l'id du groupe désignant les vendeurs
   * @param $groupe_admins l'id du groupe désignant les admins du comptoir
   * @param $id_salle id de la salle où se trouve le comptoir
   * @return true en cas de succès, false sinon
   */
  function ajout ($nom, $id_assocpt, $groupe_vendeurs, $groupe_admins, $type, $id_salle, $rechargement = 1)
  {

    $this->nom = $nom;
    $this->id_assocpt = $id_assocpt;
    $this->groupe_vendeurs = $groupe_vendeurs;
    $this->groupe_admins = $groupe_admins;
    $this->type = $type;
    $this->id_salle = $id_salle>0?$id_salle:null;
    $this->rechargement = $rechargement;

    $req = new insert ($this->dbrw,
           "cpt_comptoir",
           array("nom_cpt" => $this->nom,
           "id_assocpt" => $this->id_assocpt,
           "id_groupe_vendeur" => $this->groupe_vendeurs,
           "id_groupe" => $this->groupe_admins,
           "type_cpt"=>$this->type,
           "id_salle"=>$this->id_salle,
           "rechargement"=>$this->rechargement
            ));

    if ( !$req )
      return false;

    $this->id = $req->get_id();

    return true;
  }

  /**
   * Modification d'un comptoir
   *
   * @param $nom le nom générique du comptoir
   * @param $id_assocpt l'id de l'association concernée (obsolète)
   * @param $groupe_vendeurs l'id du groupe désignant les vendeurs
   * @param $groupe_admins l'id du groupe désignant les admins du comptoir
   * @param $id_salle id de la salle où se trouve le comptoir
   * @return true en cas de succès, false sinon
   */
  function modifier ($nom, $id_assocpt, $groupe_vendeurs, $groupe_admins,$type,$id_salle,$rechargement = 1)
  {

    $this->nom = $nom;
    $this->id_assocpt = $id_assocpt;
    $this->groupe_vendeurs = $groupe_vendeurs;
    $this->groupe_admins = $groupe_admins;
    $this->type = $type;
    $this->id_salle = $id_salle>0?$id_salle:null;
    $this->rechargement = $rechargement;
    $sql = new update($this->dbrw,
          "cpt_comptoir",
          array("nom_cpt" => $nom,
          "id_assocpt" => $id_assocpt,
          "id_groupe_vendeur" => $groupe_vendeurs,
          "id_groupe" => $groupe_admins,
           "type_cpt"=>$this->type,
           "id_salle"=>$this->id_salle,
           "rechargement"=>$this->rechargement
           ),
          array("id_comptoir" => $this->id));

    return ($sql->lines == 1) ? true : false;
  }

  /**
   * "Ouvre" le comptoir.
   * Recharge la "session" du comptoir si disponible/
   *
   * @param id l'identifiant du comptoir
   * @return true en cas de succès, false sinon
   */
  function ouvrir ($id)
  {
    $this->load_by_id($id);

    if ( $this->archive )
      return false;

    if ( !$this->is_valid() )
      return false;

    $this->operateurs = array();
    $this->panier = array();

    $this->client = new utilisateur($this->db,$this->dbrw);

    /* Par sécurité */
    $this->client->id = null;
    /* par dèfaut on paye au prix normal */
    $this->prix_barman = false;
    /* Si il n'y a pas d'entrée, on a fini */
    if (!isset($_SESSION["Comptoirs"][$this->id]))
      return true;

    /* chargement des opérateurs */
    if( isset($_SESSION["Comptoirs"][$this->id]["operateurs"]) )
    foreach($_SESSION["Comptoirs"][$this->id]["operateurs"] as $uid)
    {
      $Op = new utilisateur ($this->db,$this->dbrw);
      $Op->load_by_id ($uid);
      if (($Op->is_valid()) && ($Op->is_in_group_id($this->groupe_vendeurs) || $Op->is_in_group_id(9)))
      {
        $this->operateurs[] = $Op;

        // Met à jour l'entrée de tracking de chaque barmen
        $req = new requete ($this->dbrw,
           "UPDATE `cpt_tracking` SET `activity_time`='".date("Y-m-d H:i:s")."'
            WHERE `activity_time` >= '".date("Y-m-d H:i:s",time()-intval(ini_get("session.gc_maxlifetime")))."'
            AND `closed_time` IS NULL
            AND `id_utilisateur` = '".mysql_real_escape_string($Op->id)."'
            AND `id_comptoir` = '".mysql_real_escape_string($this->id)."'");

        if ( $req->lines == 0 ) // rien n'a été affecté, donc on re-crée une entrée
        {
	  // On refait une requete pour verifier
	  $req = new requete($this->db, "SELECT * from cpt_tracking WHERE closed_time IS NULL 
		AND `id_utilisateur` = '".mysql_real_escape_string($Op->id)."'
		AND `id_comptoir` = '".mysql_real_escape_string($this->id)."'");
          if($req->lines == 0)
          $req = new insert ($this->dbrw,
             "cpt_tracking",
             array(
             "id_utilisateur" => $Op->id,
             "id_comptoir" => $this->id,
             "logged_time" => date("Y-m-d H:i:s"),
             "activity_time" => date("Y-m-d H:i:s"),
             "closed_time" => null
              ));
        }

      }
    }

    /* Si il n'y a pas client, on a fini */
    if (!$_SESSION["Comptoirs"][$this->id]["client"])
      return true;

    /* chargement du client */
    $this->client->load_by_id($_SESSION["Comptoirs"][$this->id]["client"]);

    /* L'utilisatveur n'existe pas... probablement une erreur
     * de passage de paramètre */
    if (!$this->client->is_valid())
      return false;


    if ( isset($_SESSION["Comptoirs"][$this->id]["mode"]) ) // On est en mode spécial
    {
      $this->mode = $_SESSION["Comptoirs"][$this->id]["mode"];

      if ( $this->mode == "book" )
      {
        if ( count($_SESSION["Comptoirs"][$this->id]["panier"]) > 0 )
        {
          $arrayWithNewKeys = array();
          foreach($_SESSION["Comptoirs"][$this->id]["panier"] as $pid)
          {
            $bk = new livre($this->db);
            $bk->load_by_id($pid);
            if ( $bk->is_valid() && $bk->id_salle == $this->id_salle )
            {
              $arrayWithNewKeys[] = $pid;
              $this->panier[] = $bk;
            }
          }

          $_SESSION["Comptoirs"][$this->id]["panier"] = $arrayWithNewKeys;
        }
      }

      return true;
    }

    /* vérification pour la tarification */
    if ($_SESSION["Comptoirs"][$this->id]["prix_barman"])
      $this->verifie_prix_barman ();

    /* on parse le panier du client */
    if ( count($_SESSION["Comptoirs"][$this->id]["panier"]) > 0 )
    {
      $arrayWithNewKeys = array();
      foreach($_SESSION["Comptoirs"][$this->id]["panier"] as $pid)
      {
        $Prod = new produit ($this->db);
        $Prod->load_by_id ($pid);
        if ($Prod->is_valid())
        {
          $VenteProd = new venteproduit ($this->db,$this->dbrw);
          if ($VenteProd->charge ($Prod,$this))
          {
            $arrayWithNewKeys[] = $pid;
            $this->panier[] = $VenteProd;
          }
        }
      }
      $_SESSION["Comptoirs"][$this->id]["panier"] = $arrayWithNewKeys;
    }
    return true;
  }

  /**
   * Fermeture d'un comptoir
   */
  function fermer ()
  {
    unset($_SESSION["Comptoirs"][$this->id]);
  }

  /**
   * Connecte un operateur(=barman) : l'ajoute dans la liste des opérateurs
   * en cours du comptoir. Doit être membre du groupe vendeurs.
   *
   * @param $user Utilisateur (instance de la classe utilisateur)
   * @return true si succès, false sinon
   */
  function ajout_operateur ($user)
  {
    if (!$user->is_valid())
      return false;

    /* Vente autorisée pour gestion_ae et bureau de l'assoc (en principe) */
    if (!$user->is_in_group_id($this->groupe_vendeurs) && !$user->is_in_group_id(9))
      return false;

    $this->operateurs[] = $user;

    $_SESSION["Comptoirs"][$this->id]["operateurs"][] = $user->id;

    // On ferme toute entree precedente
    $req = new requete ($this->dbrw,
       "UPDATE `cpt_tracking` SET `closed_time`='".date("Y-m-d H:i:s")."'
        WHERE `closed_time` IS NULL
        AND `id_utilisateur` = '".mysql_real_escape_string($user->id)."'
        AND `id_comptoir` = '".mysql_real_escape_string($this->id)."'");

    if ( $req->lines != 0 ) // Ca ne devrait pas arriver
        _log($this->dbrw, "Reconnexion de barman", serialize(debug_backtrace()), "Comptoir", $user);
    // crée l'entrée de tracking pour le barman
    $req = new insert ($this->dbrw,
           "cpt_tracking",
           array(
           "id_utilisateur" => $user->id,
           "id_comptoir" => $this->id,
           "logged_time" => date("Y-m-d H:i:s"),
           "activity_time" => date("Y-m-d H:i:s"),
           "closed_time" => null
            ));

    return true;
  }

  /**
   * Définit un unique operateur(=barman) du comptoir.
   * L'utilisateur doit être membre du groupe vendeurs.
   * Doit être appelè à chaque instanciation.
   *
   * @param $user Utilisateur (instance de la classe utilisateur)
   * @return true si succès, false sinon
   */
  function set_operateur ($user)
  {
    if (!$user->is_valid())
      return false;

    /* Vente autorisée pour gestion_ae et bureau de l'assoc (en principe) */
    if (!$user->is_in_group_id($this->groupe_vendeurs) && !$user->is_in_group_id(9))
      return false;

    $this->operateurs = array($user);

    return true;
  }

  /**
   * Enlève un operateur (=barman) de la liste des opérateurs
   *  du comptoir.
   *
   * @param $id_utilisateur Id de l'utilisateur
   * @return true si succès, false sinon
   */
  function enleve_operateur ($id_utilisateur)
  {

    $id_utilisateur = intval($id_utilisateur); // On est jamais trop prudent, même si c'est inutile

    if(!empty($this->operateurs))
      foreach ( $this->operateurs as $key => $op )
        if ( $id_utilisateur == $op->id )
          unset($this->operateurs[$key]);

    if(isset($_SESSION["Comptoirs"][$this->id]["operateurs"]) &&
      !empty($_SESSION["Comptoirs"][$this->id]["operateurs"]))
      foreach ( $_SESSION["Comptoirs"][$this->id]["operateurs"] as $key => $id_op )
        if ( $id_utilisateur == $id_op )
          unset($_SESSION["Comptoirs"][$this->id]["operateurs"][$key]);

    // met à jour l'entrée de tracking du barman

    $req = new requete ($this->dbrw,
           "UPDATE `cpt_tracking` SET `closed_time`='".date("Y-m-d H:i:s")."'
            WHERE `activity_time` > '".date("Y-m-d H:i:s",time()-intval(ini_get("session.gc_maxlifetime")))."'
            AND `closed_time` IS NULL
            AND `id_utilisateur` = '".mysql_real_escape_string($id_utilisateur)."'
            AND `id_comptoir` = '".mysql_real_escape_string($this->id)."'");

    return true;
  }

  /**
   * "Ouvre" le panier : connecte un client
   *
   * @param client un objet de type client
   * @param flag_prix_barman (optionel) true si le prix barman est demandé,
   *        false sinon
   *
   * @return true si succès, false sinon
   */
  function ouvre_pannier ($client, $flag_prix_barman = true)
  {
    /* si identifiant client invalide */
    if ( !$client->is_valid() )
      return false;

    if ( !$client->cotisant )
      return false;

    if ( $client->is_in_group("cpt_bloque") )
      return false;

    /* si pas d'opérateur sur le comptoir */
    if (!count($this->operateurs))
      return false;

    $this->client = $client;
    $_SESSION["Comptoirs"][$this->id]["client"] = $this->client->id;

    /* vérification du droit au prix barman */
    if ($flag_prix_barman)
      $this->verifie_prix_barman();
    else
      $this->prix_barman = false;

    $_SESSION["Comptoirs"][$this->id]["prix_barman"] = $this->prix_barman;

    return true;
  }

  /**
   * Annule et vide le panier, deconnecte le client
   *
   * @return true si succès, false sinon
   */
  function annule_pannier ()
  {
    if (!count($this->operateurs))
      return false;

    if ( !$this->client->is_valid() )
      return false;

    if ( $this->mode != "book" )

    foreach ($this->panier as $vp)
    {
      $vp->debloquer($this->client,1);
    }

    $this->vider_pour_vente();
    return true;
  }

  /**
   * Enlève le dernier produit ajouté au panier
   * en mode "book" le dernier livre ajouté
   *
   * @return true si succès, false sinon
   */
  function annule_dernier_produit ()
  {
    if (!count($this->operateurs))
      return false;

    if (!$this->client->is_valid())
      return false;

    if ( count($this->panier) == 0 )
      return false;

    $last = count($this->panier) - 1;

    if ( $this->mode != "book" )
      $this->panier[$last]->debloquer($this->client,1);

    unset($this->panier[$last]);
    unset($_SESSION["Comptoirs"][$this->id]["panier"][$last]);

    return true;
  }

  /**
   * Ajoute un article dans le panier
   * en mode "book" ajoute un libre
   *
   * @param prod un objet de type produit ou livre (en mode "book")
   * @param error the error message
   * @return true si succès, false sinon
   */
  function ajout_pannier ($prod, &$error)
  {
    if (!count($this->operateurs))
    {
      $error = "Pas de vendeur connecté";
      return false;
    }

    if ( !$this->client->is_valid() < 0)
    {
      $error = "Client non valide";
      return false;
    }

    if ( $this->mode == "book" )
    {
      if ( $prod->id <= 0 || $prod->id_salle != $this->id_salle )
      {
        $error = "Produit invalide ou utilisé dans le mauvais lieux";
        return false;
      }

      $this->panier[] = $prod;
      $_SESSION["Comptoirs"][$this->id]["panier"][] = $prod->id;

      return true;
    }

    $max = $prod->can_be_sold($this->client);
    if ($max >= 0){
      if (isset($_SESSION["Comptoirs"][$this->id]["panier"]))
      {
          foreach($_SESSION["Comptoirs"][$this->id]["panier"] as $id)
            if ($id == $prod->id)
                $max --;
      }
      if ( $max <= 0 )
      {
        $error = "Limite de vente par personne atteinte";
        return false;
      }
    }

    $ttot = 0;

    if ($prod->plateau && !$this->prix_barman)
      foreach ($this->panier as $tvp)
        if ($tvp->produit->id == $prod->id)
          $ttot ++;

    if (($ttot + 1) % 6 != 0 && !$this->client->credit_suffisant($this->calcule_somme () + $prod->obtenir_prix ($this->prix_barman,$this->client)))
    {
      $error = "Solde insuffisant";
      return false;
    }

    $vp = new venteproduit($this->db,$this->dbrw);

    if (!$vp->charge($prod,$this))
    {
      $error = "Stock insuffisant";
      return false;
    }

    $vp->bloquer($this->client);

    $this->panier[] = $vp;

    $_SESSION["Comptoirs"][$this->id]["panier"][] = $prod->id;

    return true;
  }

  /**
   * Enlève un article du panier
   * en mode "book" enleve un livre
   *
   * @param prod un objet de type produit ou livre (en mode "book")
   * @return true si succès, false sinon
   */
  function enleve_panier ($prod)
  {
    if (!count($this->operateurs))
      return false;

    if (!$this->client->is_valid())
      return false;

    if ( count($this->panier) == 0 )
      return false;

    $key = array_search($prod->id, $_SESSION["Comptoirs"][$this->id]["panier"]);

    if ($key!==FALSE)
    {
      if ( $this->mode != "book" )
        $this->panier[$key]->debloquer($this->client,1);

      unset($this->panier[$key]);
      unset($_SESSION["Comptoirs"][$this->id]["panier"][$key]);
    }

    return $key!==FALSE;
  }

  /**
   * Procède à la vente du contenu du panier au client
   *
   * @return un tableau associatif de type
   * ([0] => objet client,
   *  [1] => tableau d'articles vendus),
   * false sinon
   *
   */
  function vendre_panier ()
  {
    if (!count($this->operateurs))
      return false;

    if ($this->client->id < 0)
      return false;

    if ( $this->mode == "book" )
      return false;

    if (!$this->client->credit_suffisant($this->calcule_somme()))
      return false;

    $vendeur = first($this->operateurs);
    $client = $this->client;
    $ancien_panier = $this->panier;
    $panier = array();

    foreach ($ancien_panier as $vp)
    {
      $panier[$vp->produit->id][0]++;
      $panier[$vp->produit->id][1] = $vp;
    }

    $debfact = new debitfacture($this->db,$this->dbrw);

    if ( !$debfact->debitAE ( $client, $vendeur, $this, $panier, $this->prix_barman ) )
      return false;

    $this->vider_pour_vente();

    return array($client,$ancien_panier);
  }

  /**
   * Rechargement d'un compte
   *
   * @param client un objet de type client
   * @param type_paiement le type de paiement
   * @param banque la banque
   * @param valeur le montant du rechargement (en centimes)
   * @param association l'identifiant de l'association concernée
   * @return true en cas de succès, false sinon
   */
  function recharger_compte ($client,
           $type_paiement,
           $banque,
           $valeur,
           $association)
  {

    if ( !$client->cotisant )
      return false;

    if ( $client->is_in_group("cpt_bloque") )
      return false;

    if (!count($this->operateurs))
      return false;

    $operateur = first($this->operateurs);
    /* on passe à la fonction membre de client pour le rechargement */
    return $client->crediter ($operateur->id,
            $type_paiement,
            $banque,
            $valeur,
            $association->id,
            $this->id);
  }

  /**
   *  Détermine si le client a droit au prix barman
   *  @private
   */
  function verifie_prix_barman ()
  {
    $this->prix_barman = false;

    foreach ($this->operateurs as $Op)
      if ($this->client->id == $Op->id)
      {
        $this->prix_barman = true;
        return;
      }
  }

  /**
   * Vidage effectif du panier
   * @private
   */
  function vider_pour_vente ()
  {
    $this->panier = array();
    $this->client = new utilisateur($this->db);
    $this->client->id = null;
    $this->prix_barman = false;
    $this->mode = null;
    unset($_SESSION["Comptoirs"][$this->id]["panier"]);
    unset($_SESSION["Comptoirs"][$this->id]["prix_barman"]);
    unset($_SESSION["Comptoirs"][$this->id]["client"]);
    unset($_SESSION["Comptoirs"][$this->id]["mode"]);
  }

  /**
   * Calcule de la somme du panier.
   * prix_barman et client doivent être définits correctement.
   * @return la somme (en centimes)
   */
  function calcule_somme ( )
  {
    $Somme = 0;
    $SommeBarman = 0;
    $track = array ();
    foreach ( $this->panier as $VenteProd )
    {
      $track[$VenteProd->produit->id] ++;
      $SommeBarman += $VenteProd->produit->obtenir_prix($this->prix_barman,$this->user);
      if ($VenteProd->produit->plateau && $track[$VenteProd->produit->id] % 6 == 0)
	      continue;
      $Somme += $VenteProd->produit->obtenir_prix(false,$this->user);
    }
    return $Somme<$SommeBarman?$Somme:$SommeBarman;
  }

  /**
   * Passe le comptoir dans une monde "special"
   * Annule le panier.
   * Seul le mode "book" est supporté.
   * @param $mode Mode
   */

  function switch_to_special_mode ( $mode )
  {
    unset($_SESSION["Comptoirs"][$this->id]["panier"]);
    $this->panier = array();
    $this->mode = $mode;
    $_SESSION["Comptoirs"][$this->id]["mode"] = $mode;
  }

  /**
   * Récupère tous les produits disponibles à la vente dans ce comptoir.
   */
  function getAvailableProducts ($user = false)
  {
    $strRequest = "SELECT `cpt_produits`.`id_produit` ".
           "FROM `cpt_mise_en_vente` ".
           "INNER JOIN `cpt_produits` ON `cpt_produits`.`id_produit` = `cpt_mise_en_vente`.`id_produit` " .
           "WHERE `cpt_mise_en_vente`.`id_comptoir` = '".intval($this->id)."' ".
	   "AND cpt_produits.cbarre_prod > '' ".
           "AND (`cpt_produits`.`date_fin_produit` IS NULL OR `cpt_produits`.`date_fin_produit`>NOW()) ".
           "ORDER BY `cpt_produits`.`id_typeprod`, `cpt_produits`.`nom_prod`";

    $req = new requete($this->db, $strRequest);

    $products = array();

    for ($i=0; $i<$req->lines; $i++)
    {
      $product = new produit($this->db,$this->dbrw);
      $row = $req->get_row();
      $product->load_by_id($row['id_produit']);
      if (!$user || $product->can_be_sold($user))
      {
        $products[] = $product;
      }
    }

    return $products;
  }
}

?>
