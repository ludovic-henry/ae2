<?php

/* Copyright 2005,2006
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
 * @author Julien Etelain
 */

require_once($topdir . "include/entities/asso.inc.php");

/**
 * Compte bancaire
 *
 * Il s'agit de traiter un compte bancaire réel, celui ci se déclinant en
 * "comptes associations" correspondant aux différentes activités et associations
 * partageant le même compte bancaire (pour traiter le cas TI).
 *
 * Cette classe contient des routines pour pouvoir récupérer les relevès de
 * comptes electroniques pour permettre aux responsable d'activités et trésoriers
 * de connaitre l'état du compte sans avoir accès au site de la banque.
 * Cette fonctionalités n'est pas encore terminée.
 *
 * @ingroup compta
 * @see compte_asso
 */
class compte_bancaire extends stdentity
{
  /** Nom du cmpte */
  var $nom;
  /** Solde du compte lors du dernier relevé (en centimes) */
  var $solde;
  /** Date du dernier relevé (timestamp) */
  var $date_releve;
  /** Numéro de compte */
  var $num;

  /** Charge un compte bancaire en fonction de son id
   * En cas d'erreur, l'id est défini à null
   * @param $id id du compte bancaire
   * @return true en cas de succès, false sinon
   */
  function load_by_id ( $id_cptbc )
  {
    $req = new requete ($this->db, "SELECT * FROM `cpta_cpbancaire`
              WHERE id_cptbc='".intval($id_cptbc)."'");

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
    $this->id = $row['id_cptbc'];
    $this->nom = $row['nom_cptbc'];

    $this->solde = $row['solde_cptbc'];
    $this->date_releve = is_null($row['date_releve_cptbc'])?null:strtotime($row['date_releve_cptbc']);
    $this->num = $row['num_cptbc'];
  }

  /**
   * Crée un compte bancire dans la base
   * Si le compte est crée avec succès, alors id est mis à jour.
   * @param $nom Nom du compte
   * @param $num Numéro de compte
   * @return true en cas de succès, false sinon
   */
  function create ( $nom, $num=null )
  {
    $this->nom = $nom;
    $this->num = compte_bancaire::standardize_account_number($num);
    $this->solde = null;
    $this->date_releve = null;


    $req = new insert ($this->dbrw,
           "cpta_cpbancaire",
           array(
             "nom_cptbc" => $this->nom,
             "num_cptbc" => $this->num,
             "solde_cptbc" => $this->solde,
             "date_releve_cptbc" => $this->date_releve
           ));

    if (!$req)
      return false;

    $this->id = $req->get_id();

    return true;
  }

  /**
   * Met à jour les informations sur le compte bancaire
   * @param $nom Nom du compte
   * @param $num Numéro de compte
   * @return true en cas de succès, false sinon
   */
  function update ( $nom, $num )
  {
    $this->nom = $nom;
    $this->num = compte_bancaire::standardize_account_number($num);

    $req = new update ($this->dbrw,
           "cpta_cpbancaire",
           array(
             "nom_cptbc" => $this->nom,
             "num_cptbc" => $this->num
           ),
           array("id_cptbc" => $this->id));

    if ( !$req )
      return false;

    return true;
  }

  /**
   * Importe un relevè de compte au format progeliance CSV
   * @param $data Données brutes du fichier progeliance au format CSV
   */
  function import_csv_progeliance ( $data )
  {
    $lines = explode("\n",$data);

    $ignore_before = $this->date_releve;

    ereg("^Solde au;([0-9\/]*)$",$lines[3],$regs);
    $this->date_releve=datetime_to_timestamp($regs[1]);

    ereg("^Solde;([0-9\, ]*);EUR$",$lines[4],$regs);
    $this->solde=get_prix($regs[1]);

    $req = new update ($this->dbrw,
           "cpta_cpbancaire",
           array(
             "solde_cptbc" => $this->solde,
             "date_releve_cptbc" => date("Y-m-d",$this->date_releve)
           ),
           array("id_cptbc" => $this->id));

    $row = null;

    for ($i=7;$i<count($lines);$i++)
    {
      $cols = explode(";",$lines[$i]);

      if ( count($cols) == 7 )
      {
        if ( !is_null($row) )
          $req = new insert ($this->dbrw,"cpta_cpbancaire_lignes",$row);

        $time = datetime_to_timestamp($cols[0]);

        if ( is_null($ignore_before) || $ignore_before < $time )
          $row=array(
            "id_cptbc"=>$this->id,
            "date_ligne_cptbc"=>date("Y-m-d",$time),
            "date_valeur_ligne_cptbc"=>date("Y-m-d",datetime_to_timestamp($cols[5])),
            "libelle_ligne_cptbc"=>trim($cols[1]),
            "montant_ligne_cptbc"=>$cols[2]?get_prix($cols[2]):get_prix($cols[3]),
            "devise_ligne_cptbc"=>$cols[4],
            "libbanc_ligne_cptbc"=>trim($cols[6])
          );
         else
           $row=null;
      }
      elseif ( !is_null($row) )
      {
        if ( isset($row["commentaire_ligne_cptbc"]) )
          $row["commentaire_ligne_cptbc"] .= "\n".trim($cols[1]);
        else
          $row["commentaire_ligne_cptbc"] = trim($cols[1]);
      }
    }

    if ( !is_null($row) )
      $req = new insert ($this->dbrw,"cpta_cpbancaire_lignes",$row);
  }

  /**
   * Permet de normaliser un numéro de compte
   * @param $num Un numéro de compte dans un format quelquonque
   * @return le numéro de compte normalisé
   */
  static function standardize_account_number ( $num )
  {
    return ereg_replace("[^0-9]","",$num);
  }

}

/**
 * Compte association
 * Permet d'associer une association/activité à un compte bancaire tout en
 * permettant le partage d'un compte bancaire entre plusieures activités
 * pour traiter le cas de la TI.
 * @ingroup compta
 * @see compte_bancaire
 */
class compte_asso extends stdentity
{
  /** Id de l'association/activité possédant ce compte */
  var $id_asso;
  /** Id du compte bancaire concerné */
  var $id_cptbc;
  /** Nom du compte */
  var $nom;

  /** Charge un compte association en fonction de son id
   * En cas d'erreur, l'id est défini à null
   * @param $id id du compte association
   * @return true en cas de succès, false sinon
   */
  function load_by_id ( $id )
  {
    $req = new requete ($this->db, "SELECT *
              FROM `cpta_cpasso` " .
              "INNER JOIN `asso` ON `asso`.`id_asso`=`cpta_cpasso`.`id_asso`
              WHERE `cpta_cpasso`.`id_cptasso`='".intval($id)."'");

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
    $this->id = $row['id_cptasso'];
    $this->id_asso = $row['id_asso'];
    $this->id_cptbc = $row['id_cptbc'];
    $this->nom = $row['nom_asso'];
  }


  /** Ajoute un compte association
   * Si le compte est crée avec succès, alors id est mis à jour.
   * @param $id_asso Id de l'association
   * @param $id_cptbc Id du compte bancaire
   * @return true en cas de succès, false sinon
   */
  function ajouter ( $id_asso, $id_cptbc )
  {
    $this->id_asso = $id_asso;
    $this->id_cptbc = $id_cptbc;

    $dbrw = new mysqlae ('rw');
    $req = new insert ($dbrw,
      "cpta_cpasso",
      array(
        "id_asso" => $this->id_asso,
        "id_cptbc" => $this->id_cptbc
        )
      );

    if ( !$req )
      return false;

    $this->id = $req->get_id();

    return true;
  }

}

/**
 * Classeur de compta (relatif à un seul compte association)
 * @ingroup compta
 * @see compte_asso
 */
class classeur_compta extends stdentity
{
  /** Id du compte association concerné par ce classeur */  var $id_cptasso;
  /** Date de début de la période couverte par le classeur */  var $date_debut_classeur;
  /** Date de fin de la période couverte par le classeur */  var $date_fin_classeur;
  /** Nom du classeur */  var $nom;
  /** Etat de fermeture du classeur (0:ouvert,1:fermé) */  var $ferme;

  /** Charge un classeur de compta en fonction de son id
   * En cas d'erreur, l'id est défini à null
   * @param $id id du classeur de compta
   * @return true en cas de succès, false sinon
   */
   function load_by_id ( $id_classeur )
  {
    $req = new requete ($this->db, "SELECT *
              FROM `cpta_classeur`
              WHERE id_classeur='".intval($id_classeur)."'");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  /**
   * Charge le classeur ouvert d'un compte association
   * @param $id_cptasso Id du compte association
   * @param $not Id du classeur à ne pas charger (si plusieurs sont ouverts)
   */
   function load_opened ( $id_cptasso, $not=-1 )
  {
    $req = new requete ($this->db, "SELECT *
              FROM `cpta_classeur`
              WHERE id_cptasso='".intval($id_cptasso)."' AND ferme='0' AND id_classeur!='$not'
              ORDER BY `date_debut_classeur` DESC
              LIMIT 1");

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
    $this->id = $row['id_classeur'];
    $this->id_cptasso = $row['id_cptasso'];
    $this->date_debut_classeur = strtotime($row['date_debut_classeur']);
    $this->date_fin_classeur = strtotime($row['date_fin_classeur']);
    $this->nom = $row['nom_classeur'];
    $this->ferme = $row['ferme'];
  }

  /**
   * Crée un classeur dans la base de données.
   * Si le compte est crée avec succès, alors id est mis à jour.
   * @param $id_cptasso Compte association concerné
   * @param $date_debut_classeur Date de début de la période couverte par le classeur
   * @param $date_fin_classeur Date de fin de la période couverte par le classeur
   * @param $nom_classeur Nom du classeur
   * @return true en cas de succès, false sinon
   */
  function ajouter ( $id_cptasso, $date_debut_classeur,
             $date_fin_classeur, $nom_classeur )
  {
    $this->id_cptasso = $id_cptasso;
    $this->date_debut_classeur = $date_debut_classeur;
    $this->date_fin_classeur = $date_fin_classeur;
    $this->nom = $nom_classeur;
    $this->ferme = false;


    $req = new insert ($this->dbrw,
      "cpta_classeur",
      array(
        "id_cptasso" => $this->id_cptasso,
        "date_debut_classeur" => date("Y-m-d",$this->date_debut_classeur),
        "date_fin_classeur" => date("Y-m-d",$this->date_fin_classeur),
        "nom_classeur" => $this->nom,
        "ferme" => $this->ferme
        )
      );

    if ( !$req )
      return false;

    $this->id = $req->get_id();

    return true;
  }

  /**
   * Met à jour le classeur dans la base de données.
   * @param $date_debut_classeur Date de début de la période couverte par le classeur
   * @param $date_fin_classeur Date de fin de la période couverte par le classeur
   * @param $nom_classeur Nom du classeur
   */
   function update ( $date_debut_classeur, $date_fin_classeur, $nom_classeur )
  {

    $this->date_debut_classeur = $date_debut_classeur;
    $this->date_fin_classeur = $date_fin_classeur;
    $this->nom = $nom_classeur;

    $req = new update ($this->dbrw,
      "cpta_classeur",
      array(
        "date_debut_classeur" => date("Y-m-d",$this->date_debut_classeur),
        "date_fin_classeur" => date("Y-m-d",$this->date_fin_classeur),
        "nom_classeur" => $this->nom
        ),
      array(
        "id_classeur"=>$this->id
        )
      );
  }

  /** Ferme le classeur
   * @param $ferme Etat du fermeture
   */
  function fermer($ferme=true)
  {
    $this->ferme = $ferme;

    $req = new update ($this->dbrw,
      "cpta_classeur",
      array(
        "ferme" => $this->ferme
        ),
      array(
        "id_classeur"=>$this->id
        )
      );

  }

}

?>
