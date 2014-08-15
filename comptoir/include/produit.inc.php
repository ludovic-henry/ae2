<?php


/** @file
 *
 * @brief déclaration de la classe produit
 */

/* Copyright 2005,2006,2007,2008
 * - Julien Etelain <julien CHEZ pmad POINT net>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Simon Lopez <simon POINT lopez CHEZ ayolo POINT org>
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

define ('NAMES_PATH', $topdir.'comptoir/include/nvdiplomes/names');
define ('FNAMES_PATH', $topdir.'comptoir/include/nvdiplomes/firstnames');

/**
 * Classe gérant un produit
 * @see venteproduit
 * @see comptoir
 * @see debitfacture
 * @ingroup comptoirs
 */
class produit extends stdentity
{

  /** Id du type de produit */
  var $id_type;
  /** Id du compte association qui sera crédité = Id association vendant le produit */
  var $id_assocpt;
  /** Nom du produit */
  var $nom;
  /** Prix de vente barman (en centimes) */
  var $prix_vente_barman;
  /** Prix de vente public (en centimes) */
  var $prix_vente;
  /** Prix d'achat (à titre indicatif) (en centimes) */
  var $prix_achat;
  /** Paramètre de l'action associée au produit */
  var $meta;
  /** Action associée au produit */
  var $action;
  /** Code barre du produit */
  var $code_barre;
  /** Stock global du produit, -1 si non limité */
  var $stock_global;
  /** Id du fichier utilisé pour la vignette du produit */
  var $id_file;
  /** Description succinte du produit */
  var $description;
  /** Description complète du porduit */
  var $description_longue;
  /** Id du groupe au quel la vente de ce produit est restreint (null si aucun) */
  var $id_groupe;
  /** Date de fin de vente du produit (timestamp) @todo à implémenter */
  var $date_fin;
  /** Id du produit parent (null si aucun) si non null, alors ce produit est une
   *  déclinaisaon du produit parent */
  var $id_produit_parent;
  /** A venir retirer */
  var $a_retirer;
  /** Infos sur les modalités du retrait */
  var $a_retirer_info;
  /** Payable par carte bancaire */
  var $cb;
  /** Envoyable par la poste (booléen) (non disponible pour le moment) */
  var $postable;
  /** Frais de port de l'objet en centimes (non disponible pour le moment) */
  var $frais_port;
  /** Possibilite d'acheter un plateau (6 pour le prix de 5) */
  var $plateau;
  /** etat d'un produit hors commerce, gardé pour archive */
  var $archive;
  /** limite du nombre d'achats par personne */
  var $limite_utilisateur;

  /** le produit peut il être vendu à un mineur */
  /* 0  => tout le monde
   * 16 => 16ans ou plus
   * 18 => majeurs uniquement
   */
  var $mineur=0;

  /** Cache de l'instance de la classe associée
   * @see get_prodclass
   * @private
   */
  var $cl;

  /* Garde en mémoire les noms et prénoms respectivement
   * des nouveaux diplomés pour certains produits
   */
  var $names = null;
  var $fnames = null;

  /* Class "amies" pouvant modifier les instances
    - VenteProduit
  */

  /**
   * Charge un produit en fonction de son id
   * En cas d'erreur, l'id est définit à null
   * @param $id id du produit
   * @return true en cas du succès, false sinon
   */
  function load_by_id ($id)
  {
    $req = new requete ($this->db, "SELECT * FROM `cpt_produits`
                                    WHERE `id_produit`='".mysql_real_escape_string($id)."'");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }
    $this->id = null;
    return false;
  }

  /**
   * Charge un produit en fonction de son code barre
   * En cas d'erreur, l'id est définit à null
   * @param $code_barre code barre du produit
   * @return true en cas du succès, false sinon
   */
  function charge_par_code_barre ($code_barre)
  {

    $req = new requete($this->db, "SELECT * FROM `cpt_produits`
                                   WHERE `cbarre_prod` = '".mysql_real_escape_string($code_barre)."'
                                   AND `prod_archive`!='1'");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  /**
   * Crée un produit
   *
   * Voir documentation des champs de la classe pour la signification des
   * paramètres.
   *
   * @return true si succès, false sinon
   */
  function ajout ($id_typeprod,
      $id_assocpt,
      $nom,
      $prix_vente_barman,
      $prix_vente,
      $prix_achat,
      $meta,
      $action,
      $code_barre,
      $stock_global,
      $id_file,
      $description,
      $description_longue,
      $a_retirer,
      $a_retirer_info,
      $cb,
      $postable,
      $frais_port,
      $plateau,
      $id_groupe=null,
      $date_fin=null,
      $id_produit_parent=null,
      $mineur=0,
      $limite_utilisateur=-1 )
  {

    $this->id_type = $id_typeprod;
    $this->id_assocpt = $id_assocpt;
    $this->nom = $nom;
    $this->prix_vente_barman = intval($prix_vente_barman);
    $this->prix_vente = intval($prix_vente);
    $this->prix_achat = intval($prix_achat);
    $this->meta = $meta;
    $this->action = intval($action);
    $this->code_barre = $code_barre;
    $this->stock_global = intval($stock_global);
    $this->archive = 0;
    $this->id_file = $id_file;
    $this->description = $description;
    $this->description_longue = $description_longue;

    $this->a_retirer = $a_retirer?1:0;
    $this->a_retirer_info = $a_retirer_info;
    $this->cb = $cb?1:0;
    $this->postable = $postable?1:0;
    $this->frais_port = intval(frais_port);
    $this->plateau = $plateau?1:0;

    $this->id_groupe = $id_groupe?$id_groupe:null;
    $this->date_fin = $date_fin?$date_fin:null;
    $this->id_produit_parent = $id_produit_parent;
    $this->mineur=$mineur;
    $this->limite_utilisateur = $limite_utilisateur;

    $req = new insert ($this->dbrw,
           "cpt_produits",
           array("id_typeprod" => $this->id_type,
           "id_assocpt" => $this->id_assocpt,
           "nom_prod" => $this->nom,
           "prix_vente_barman_prod" => $this->prix_vente_barman,
           "prix_vente_prod" => $this->prix_vente,
           "prix_achat_prod" => $this->prix_achat,
           "meta_action_prod" => $this->meta,
           "action_prod" => $this->action,
           "cbarre_prod" => $this->code_barre,
           "stock_global_prod" => $this->stock_global,
           "prod_archive" => $this->archive,
           "id_file" => $this->id_file,
           "description_prod" => $this->description,
           "description_longue_prod" => $this->description_longue,

           'frais_port_prod' => $this->frais_port,
           'postable_prod' => $this->postable,
           'a_retirer_prod'=> $this->a_retirer,
           'a_retirer_info'=> $this->a_retirer_info,
           'cb' => $this->cb,
           'plateau' => $this->plateau,

           'id_groupe'=>$this->id_groupe,
           'date_fin_produit'=>is_null($this->date_fin)?null:date("Y-m-d H:i:s",$this->date_fin),
           'id_produit_parent'=> $this->id_produit_parent,
           'mineur'=>$this->mineur,
           'limite_utilisateur'=>$this->limite_utilisateur


            ));

    if ( !$req )
      return false;

    $this->id = $req->get_id();

    return true;
  }

  /**
   * Modifie le produit
   *
   * Voir documentation des champs de la classe pour la signification des
   * paramètres.
   *
   * @return true si succès, false sinon
   */
  function modifier ($id_typeprod,
         $nom,
         $prix_vente_barman,
         $prix_vente,
         $prix_achat,
         $meta,
         $action,
         $code_barre,
         $stock_global,
         $id_file,
         $description,
         $description_longue,
         $id_assocpt,
         $a_retirer,
         $a_retirer_info,
         $cb,
         $postable,
         $frais_port,
         $plateau,
         $id_groupe=null,
         $date_fin=null,
         $id_produit_parent=null,
         $mineur=0,
         $limite_utilisateur=-1
         )
  {

    $this->id_type = $id_typeprod;
    $this->nom = $nom;
    $this->prix_vente_barman = intval($prix_vente_barman);
    $this->prix_vente = intval($prix_vente);
    $this->prix_achat = intval($prix_achat);
    $this->meta = $meta;
    $this->action = intval($action);
    $this->code_barre = $code_barre;
    $this->stock_global = intval($stock_global);
    $this->id_file = $id_file;
    $this->description = $description;
    $this->description_longue = $description_longue;
    $this->id_assocpt = $id_assocpt;

    $this->a_retirer = $a_retirer?1:0;
    $this->a_retirer_info = $a_retirer_info;
    $this->cb = $cb?1:0;
    $this->postable = $postable?1:0;
    $this->frais_port = intval(frais_port);
    $this->plateau = $plateau?1:0;

    $this->id_groupe = $id_groupe?$id_groupe:null;
    $this->date_fin = $date_fin?$date_fin:null;
    $this->id_produit_parent = $id_produit_parent;
    $this->mineur = $mineur;
    $this->limite_utilisateur = $limite_utilisateur;

    $req = new update ($this->dbrw,
           "cpt_produits",
           array("id_typeprod" => $this->id_type,
           "id_assocpt" => $this->id_assocpt,
           "nom_prod" => $this->nom,
           "prix_vente_barman_prod" => $this->prix_vente_barman,
           "prix_vente_prod" => $this->prix_vente,
           "prix_achat_prod" => $this->prix_achat,
           "meta_action_prod" => $this->meta,
           "action_prod" => $this->action,
           "cbarre_prod" => $this->code_barre,
           "stock_global_prod" => $this->stock_global,
           "id_file" => $this->id_file,
           "description_prod" => $this->description,
           "description_longue_prod" => $this->description_longue,

           'frais_port_prod' => $this->frais_port,
           'postable_prod' => $this->postable,
           'a_retirer_prod'=> $this->a_retirer,
           'a_retirer_info'=> $this->a_retirer_info,
           'cb' => $this->cb,
           'plateau' => $this->plateau,

           'id_groupe'=>$this->id_groupe,
           'date_fin_produit'=>is_null($this->date_fin)?null:date("Y-m-d H:i:s",$this->date_fin),
           'id_produit_parent'=> $this->id_produit_parent,
           'mineur'=>$this->mineur,
           'limite_utilisateur'=>$this->limite_utilisateur
            ),
         array("id_produit" => $this->id));

    if ( !$req )
      return false;

    return true;
  }

  /**
   * Modifie le type du produit
   * @param $id_typeprod Id du type de produit
   * @return true si succès, false sinon
   */
  function modifier_typeprod ($id_typeprod)
  {

    $this->id_type = $id_typeprod;

    $req = new update ($this->dbrw,
           "cpt_produits",
           array("id_typeprod" => $this->id_type
            ),
         array("id_produit" => $this->id));

    if ( !$req )
      return false;

    return true;
  }

  /**
   * Change la date d'expiration du produit
   * @param $date nouvel date d'expiration
   * @return true si succès, false sinon
   */
  function modifier_date_expiration ($date)
  {
      $this->date_fin = $date;
      $req = new update ($this->dbrw, 'cpt_produits',
                         array('date_fin_produit' => $this->date_fin),
                         array('id_produit' => $this->id));

      if (!$req)
          return false;

      return true;
  }

  /**
   * Supprime le produit (s'il n'a jamais été vendu)
   * @return true si succès, false sinon
   */
  function supprimer ()
  {
    if ( $this->determine_deja_vendu() )
      return false;

    new delete($this->dbrw,"cpt_produits",array("id_produit" => $this->id));
    new delete($this->dbrw,"cpt_mise_en_vente",array("id_produit" => $this->id));

    return false;
  }

  /**
   * Archivage d'un produit :
   * - le retire de la vente dans tous les comptoirs
   * - le marque comme archivé
   *
   * @return true si succès, false sinon
   */
  function archiver ()
  {

    $req = new update ($this->dbrw,
           "cpt_produits",
           array(
           "prod_archive" => 1
            ),
         array("id_produit" => $this->id));
    if ( !$req )
      return false;

    $this->archive = 1;

    $req = new delete($this->dbrw,"cpt_mise_en_vente",array("id_produit" => $this->id));

    return true;
  }

  /**
   * De-archivage d'un produit : enlève le marquage "archivé"
   *
   * @return true si succès, false sinon
   */
  function dearchiver ()
  {

    $req = new update ($this->dbrw,
           "cpt_produits",
           array(
           "prod_archive" => 0
            ),
         array("id_produit" => $this->id));

    if ( !$req )
      return false;

    $this->archive = 0;

    return true;
  }

  function determine_deja_vendu ()
  {
    $req = new requete ($this->db, "SELECT count(id) FROM `cpt_vendu`
                                    WHERE id_produit='".$this->id."'");

    list($count) = $req->get_row();

    return $count != 0;
  }

  function _load ($row)
  {
    $this->id = $row['id_produit'];
    $this->id_type = $row['id_typeprod'];
    $this->id_assocpt = $row['id_assocpt'];
    $this->nom = $row['nom_prod'];
    $this->prix_vente_barman = $row['prix_vente_barman_prod'];
    $this->prix_vente = $row['prix_vente_prod'];

    $this->prix_achat = $row['prix_achat_prod'];
    $this->meta = $row['meta_action_prod'];
    $this->action = $row['action_prod'];
    $this->code_barre = $row['cbarre_prod'];
    $this->stock_global = $row['stock_global_prod'];
    $this->archive = $row['prod_archive'];

    $this->id_file = $row['id_file'];
    $this->description = $row['description_prod'];
    $this->description_longue = $row['description_longue_prod'];

    $this->a_retirer = $row['a_retirer_prod'];
    $this->a_retirer_info = $row['a_retirer_prod_info'];
    $this->cb = $row['cb'];
    $this->postable = $row['postable_prod'];
    $this->frais_port = $row['frais_port_prod'];
    $this->plateau = $row['plateau'];

    $this->id_groupe = $row['id_groupe'];
    $this->date_fin = is_null($row['date_fin_produit'])?null:strtotime($row['date_fin_produit']);
    $this->id_produit_parent = $row['id_produit_parent'];
    $this->mineur = $row['mineur'];
    $this->limite_utilisateur = $row['limite_utilisateur'];
  }

  /**
   * Determine le prix de vente pour un utilisateur
   *
   * @param $prix_barman true si l'utilisateur a droit au prix barman, false sinon
   * @param $user utilisateur à qui le pdoruit va être vendu (intsance de utilisateur)
   * @return le prix (en centimes d'euros)
   */
  function obtenir_prix ($barman,$user=false)
  {
    return $barman ? $this->prix_vente_barman : $this->prix_vente;
  }

  function escape_name ($iname)
  {
      $iname = ereg_replace("(é|è|ê|ë|É|È|Ê|Ë)","e",$iname);
      $iname = ereg_replace("(à|â|ä|À|Â|Ä)","a",$iname);
      $iname = ereg_replace("(ï|î|Ï|Î)","i",$iname);
      $iname = ereg_replace("(ç|Ç)","c",$iname);
      $iname = ereg_replace("(Ò|ò|ô|Ô)","o",$iname);
      $iname = ereg_replace("(ù|ü|û|Ü|Û|Ù)","u",$iname);
      $iname = ereg_replace("(ñ|Ñ)","n",$iname);

      return $iname;
  }

  function is_nouveau_diplome ($user)
  {
      if ($this->names == null)
          $this->names = unserialize (file_get_contents (NAMES_PATH));
      if ($this->fnames == null)
          $this->fnames = unserialize (file_get_contents (FNAMES_PATH));

      return array_key_exists (strtoupper ($this->escape_name ($user->nom)), $this->names)
          && array_key_exists (strtoupper ($this->escape_name ($user->prenom)), $this->fnames);
  }

  /**
   * Détermine si le produit peut être vendu à un utilisateur.
   * Verifie que l'utilisateur fait partie du groupe cible (si définit).
   * Fait appel à la classe associée au produit si disponible.
   *
   * @param $user Utilisateur (instance de utilisateur)
   * @return true si le produit peut être vendu à $user, false sinon
   * @see get_prodclass
   */
  function can_be_sold ( &$user )
  {
    if (!is_null($this->id_groupe)) {
        // 42 = nouveaux diplomes (les gens chiants)
        if ($this->id_groupe == 42) {
            if (!$this->is_nouveau_diplome ($user))
                return false;
        } else if (!$user->is_in_group_id($this->id_groupe) ) {
            return false;
        }
    }

    if ( $this->action == ACTION_CLASS )
    {
      $this->get_prodclass($user);
      return $this->cl->can_be_sold($user);
    }

    if($this->mineur>0)
    {
      $naiss=$user->date_naissance;
      $today = mktime();
      $secondes = ($today > $naiss)? $today - $naiss : 0;
      $age = date('Y', $secondes) - 1970;
      if($age<$this->mineur)
        return false;
    }

    if ($this->limite_utilisateur >= 0)
    {
      $req = new requete($this->db,
        "SELECT SUM(quantite) nb_achetes FROM `cpt_debitfacture`
        INNER JOIN `cpt_vendu` USING(`id_facture`)
        WHERE `id_utilisateur_client`='".intval($user->id)."'
        AND `id_produit`='".intval($this->id)."'");

      $row = $req->get_row();

      // Le nombre renvoyé doit être >= 0 !
      $val = max(0, $this->limite_utilisateur - $row["nb_achetes"]);
      return $val;
    }

    // Les putains d'Ecocup
    if($this->id == 1151)
    {
      $req = new requete($this->db,
	"SELECT  SUM(cpt_produits.prix_vente_prod*cpt_vendu.quantite)/100 nb_consigne FROM cpt_vendu
        INNER JOIN cpt_produits ON cpt_vendu.id_produit = cpt_produits.id_produit
	INNER JOIN cpt_debitfacture ON cpt_debitfacture.id_facture = cpt_vendu.id_facture
	WHERE cpt_debitfacture.id_utilisateur_client = '".intval($user->id)."' 
	AND (cpt_produits.id_produit = 1152 OR cpt_produits.id_produit = 1151)");

      $row = $req->get_row();

      // Le nombre renvoyé doit être >= 0 !
      $val = max(0, intval($row["nb_consigne"]));
      return $val;


    }

    return -1;
  }

  /**
   * Renvoie une instance de la classe associée au produit (lorsque l'action
   * associée est ACTION_CLASS).
   *
   * Permet entre autre de réaliser des traitements spécifiques suite à la vente
   * d'un produit. Comme par exemple les cotisations.
   *
   * Les classes associables sont stockées dans comptoir/include/class.
   *
   * La classe associée est définit par le champ meta lorsque l'action associée
   * au produit est ACTION_CLASS. Le formalisme du champ est
   * "nomdelaclasse(paramètre dans un format quelquonque)"
   *
   * Le fichier chargée est comptoir/include/class/nomdelaclasse.inc.php
   *
   * Le nom de la classe est forcément composé de lettre en minuscules.
   *
   * Le constructeur de la classe est appelé avec quatres paramètre : les liens
   * à la base de données, le pramètre selui définit dans la base et
   * le client passé à cette fonction.
   *
   * Les classes doivent implémenter un certain nombre de fonctions :
   * - vendu($user,$prix_unit) appelé lorsque le produit est vendu à $user au
   *   prix $prix_unit
   * - get_info() renvoie les informations complémentaires sur le produit
   * - get_once_sold_cts($user) renvoie un stdcontents lorsque le produit a été
   *   vendu à $user
   * - can_be_sold($user) determine le nombre d'occurences du produit qui
   *   peuvent être vendues à $user
   * - is_compatible($cl) determine si le produit peut être vendu en même temps
   *   qu'un produit dont $cl est une instance de la classe associée
   * Exemple:  cotisationae (comptoir/include/class/cotisationae.inc.php)
   *
   * @param $user Client pour ce produit (instance de utilisateur)
   * @return une instance de la classe associée ou null si aucune
   * @see cotisationae
   */
  function get_prodclass(&$user)
  {
    global $topdir;

    if ( $this->cl )
      return $this->cl;

    if ( $this->action != ACTION_CLASS )
      return NULL;

    $regs=null;

    if ( !ereg("^([a-z]+)\((.*)\)$",$this->meta,$regs))
      return NULL;

    $class = $regs[1]; // que des lettes minuscules
    $param = $regs[2]; //

    if ( !class_exists($class))
    {
      if ( !file_exists($topdir."comptoir/include/class/".$class.".inc.php") )
        return NULL;

      include($topdir."comptoir/include/class/".$class.".inc.php");
    }

    $this->cl = new $class ( $this->db, $this->dbrw, $param, $user );

    return $this->cl;
  }

  /**
   * Determine les informations complémentaires sur le produit en appelant
   * la classe associée au produit.
   *
   * @param $user Client pour ce produit (instance de utilisateur)
   * @return le texte d'information complémentaire (vide si aucun)
   * @see get_prodclass
   */
  function get_extra_info (&$user)
  {
    if ( $this->action == ACTION_CLASS )
    {
      $this->get_prodclass($user);
      return $this->cl->get_info();
    }
    return "";
  }
}

?>
