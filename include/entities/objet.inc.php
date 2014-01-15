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

/**
 * @file
 * Gestion de l'inventaire
 */

/**
 * Type d'objet
 * @ingroup inventaire
 */
class objtype extends stdentity
{
  var $nom;
  var $prix;
  var $caution;
  var $prix_emprunt;
  var $code;
  var $empruntable;
  var $notes;


  /** Charge un type d'objet en fonction de son id
   * $this->id est égal à -1 en cas d'erreur
   * @param $id id de la fonction
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `inv_type_objets`
        WHERE `id_objtype` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function load_by_code ( $code )
  {
    $req = new requete($this->db, "SELECT * FROM `inv_type_objets`
        WHERE `code_objtype` = '" . mysql_real_escape_string($code) . "'
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
    $this->id            = $row['id_objtype'];
    $this->nom           = $row['nom_objtype'];
    $this->prix          = $row['prix_objtype'];
    $this->caution       = $row['caution_objtype'];
    $this->prix_emprunt  = $row['prix_emprunt_objtype'];
    $this->code          = $row['code_objtype'];
    $this->empruntable   = $row['empruntable_objtype'];
    $this->notes         = $row['notes_objtype'];
  }

  function add ( $nom, $prix, $caution, $prix_emprunt, $code, $empruntable, $notes )
  {
    $this->nom = $nom;
    $this->prix = $prix;
    $this->caution = $caution;
    $this->prix_emprunt = $prix_emprunt;
    $this->code = $code;
    $this->empruntable = $empruntable;
    $this->notes = $notes;

    $sql = new insert ($this->dbrw,
      "inv_type_objets",
      array(
        "nom_objtype" => $this->nom,
        "prix_objtype" => $this->prix,
        "caution_objtype" => $this->caution,
        "prix_emprunt_objtype" => $this->prix_emprunt,
        "code_objtype" => $this->code,
        "empruntable_objtype" => $this->empruntable,
        "notes_objtype" => $this->notes
        )
      );

    if ( $sql )
      $this->id = $sql->get_id();
    else
      $this->id = null;

  }

  function save ( $nom, $prix, $caution, $prix_emprunt, $code, $empruntable, $notes )
  {
    $this->nom = $nom;
    $this->prix = $prix;
    $this->caution = $caution;
    $this->prix_emprunt = $prix_emprunt;
    $this->code = $code;
    $this->empruntable = $empruntable;
    $this->notes = $notes;

    $sql = new update ($this->dbrw,
      "inv_type_objets",
      array(
        "nom_objtype" => $this->nom,
        "prix_objtype" => $this->prix,
        "caution_objtype" => $this->caution,
        "prix_emprunt_objtype" => $this->prix_emprunt,
        "code_objtype" => $this->code,
        "empruntable_objtype" => $this->empruntable,
        "notes_objtype" => $this->notes
        ),
      array(
        "id_objtype" => $this->id
        )
      );
  }

}


define("OEVENT_ABIME",1);
define("OEVENT_NONUTILISABLE",2);
define("OEVENT_SORTIE_INVENTAIRE",4);
define("OEVENT_VOLE",4);
/**
 * Objet dans l'inventaire
 * @ingroup inventaire
 */
class objet extends stdentity
{

  var $id_asso;
  var $id_asso_prop;
  var $id_salle;
  var $id_objtype;
  var $id_op;
  var $id_photo;

  var $nom;
  var $num;
  var $cbar;
  var $num_serie;
  var $date_achat;
  var $prix;
  var $caution;
  var $prix_emprunt;
  var $empruntable;
  var $en_etat;
  var $archive;
  var $notes;

  var $_is_book;
  var $_is_jeu;

  /** Charge un objet en fonction de son id
   * $this->id est égal à -1 en cas d'erreur
   * @param $id id de la fonction
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `inv_objet`
        WHERE `id_objet` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function load_by_cbar ( $cbar )
  {
    $req = new requete($this->db, "SELECT * FROM `inv_objet`
        WHERE `cbar_objet` = '" . mysql_real_escape_string($cbar) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function load_by_num ( $id_objtype, $num )
  {
    $req = new requete($this->db, "SELECT * FROM `inv_objet`
        WHERE `id_objtype` = '" . mysql_real_escape_string($id_objtype) . "'
        AND `num_objet` = '" . mysql_real_escape_string($num) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function get_display_name()
  {
    if ( $this->nom )
      return $this->nom." (".$this->num.")";
    else
      return $this->num;
  }

  function _load ( $row )
  {
    $this->id           = $row['id_objet'];
    $this->id_asso      = $row['id_asso'];
    $this->id_asso_prop = $row['id_asso_prop'];
    $this->id_salle     = $row['id_salle'];
    $this->id_objtype   = $row['id_objtype'];
    $this->id_op        = $row['id_op'];
    $this->id_photo     = $row['id_photo'];
    $this->nom          = $row['nom_objet'];
    $this->num          = $row['num_objet'];
    $this->cbar         = $row['cbar_objet'];
    $this->num_serie    = $row['num_serie'];
    $this->prix         = $row['prix_objet'];
    $this->caution      = $row['caution_objet'];
    $this->prix_emprunt = $row['prix_emprunt_objet'];
    $this->empruntable  = $row['objet_empruntable'];
    $this->en_etat      = $row['en_etat'];
    $this->archive      = $row['archive_objet'];
    $this->notes        = $row['notes_objet'];
    $this->date_achat   = strtotime($row['date_achat']);
  }

  function _determine_special()
  {
    if ( !$this->is_valid() )
      return;

    if ( !isset($this->_is_book) )
    {
      $req = new requete($this->db, "SELECT id_objet FROM `bk_book`
        WHERE `id_objet` = '" . mysql_real_escape_string($this->id) . "'
        LIMIT 1");

      $this->_is_book = $req->lines == 1;

      $req = new requete($this->db, "SELECT id_objet FROM `inv_jeu`
        WHERE `id_objet` = '" . mysql_real_escape_string($this->id) . "'
        LIMIT 1");

      $this->_is_jeu = $req->lines == 1;
    }

  }

  /**
   * Determine si par ailleurs cet objet est un livre
   * @see livre
   */
  function is_book()
  {
    $this->_determine_special();
    return $this->_is_book;
  }

  /**
   * Determine si par ailleurs cet objet est un jeu
   * @see jeu
   */
  function is_jeu()
  {
    $this->_determine_special();
    return $this->_is_jeu;
  }

  function add ( $id_asso, $id_asso_prop, $id_salle, $id_objtype, $id_op, $id_photo, $nom,
        $code_objtype, $num_serie, $prix, $caution, $prix_emprunt, $empruntable,
        $en_etat, $date_achat, $notes )
  {

    $sql = new requete ( $this->db, "SELECT MAX(`num_objet`) FROM `inv_objet` " .
        "WHERE `id_objtype`='".intval($id_objtype)."'" );

    if ( $sql->lines == 1 )
      list($pnum) = $sql->get_row();
    else
      $pnum = 0;

    $this->num = $pnum + 1;

    $this->id_asso    = $id_asso;
    $this->id_asso_prop  = $id_asso_prop;
    $this->id_salle    = $id_salle;
    $this->id_objtype  = $id_objtype;
    $this->id_op      = $id_op;
    $this->id_photo   = $id_photo;
    $this->nom      = $nom;
    $this->cbar      = sprintf("%s%04d",$code_objtype,$this->num);
    $this->num_serie    = $num_serie;
    $this->prix      = $prix;
    $this->caution    = $caution;
    $this->prix_emprunt  = $prix_emprunt;
    $this->empruntable  = $empruntable;
    $this->en_etat    = $en_etat;
    $this->archive    = false;
    $this->notes      = $notes;
    $this->date_achat  = $date_achat;

    $sql = new insert ($this->dbrw,
      "inv_objet",
      array(
        "id_asso" => $this->id_asso,
        "id_asso_prop" => $this->id_asso_prop,
        "id_salle" => $this->id_salle,
        "id_objtype" => $this->id_objtype,
        "id_op" => $this->id_op,
        "id_photo" => $this->id_photo,
        "nom_objet" => $this->nom,
        "num_objet" => $this->num,
        "cbar_objet" => $this->cbar,
        "num_serie" => $this->num_serie,
        "prix_objet" => $this->prix,
        "caution_objet" => $this->caution,
        "prix_emprunt_objet" => $this->prix_emprunt,
        "objet_empruntable" => $this->empruntable==true,
        "en_etat" => $this->en_etat==true,
        "archive_objet"=>$this->archive,
        "date_achat"=> date("Y-m-d",$this->date_achat),
        "notes_objet" => $this->notes
        )
      );

    if ( $sql )
      $this->id = $sql->get_id();
    else
      $this->id = null;

  }

  function save_objet ( $id_asso, $id_asso_prop, $id_salle, $id_objtype, $id_op, $id_photo, $nom,
        $num_serie, $prix, $caution, $prix_emprunt, $empruntable,
        $en_etat, $date_achat, $notes,$cbar, $archive )
  {

    $this->id_asso    = $id_asso;
    $this->id_asso_prop  = $id_asso_prop;
    $this->id_salle    = $id_salle;
    $this->id_objtype  = $id_objtype;
    $this->id_op    = $id_op;
    $this->id_photo = $id_photo;
    $this->nom      = $nom;
    $this->cbar      = $cbar;
    $this->num_serie  = $num_serie;
    $this->prix      = $prix;
    $this->caution    = $caution;
    $this->prix_emprunt  = $prix_emprunt;
    $this->empruntable  = $empruntable;
    $this->en_etat    = $en_etat;
    $this->notes    = $notes;
    $this->date_achat  = $date_achat;
    $this->archive      = $archive;

    $sql = new update ($this->dbrw,
      "inv_objet",
      array(
        "id_asso" => $this->id_asso,
        "id_asso_prop" => $this->id_asso_prop,
        "id_salle" => $this->id_salle,
        "id_objtype" => $this->id_objtype,
        "id_op" => $this->id_op,
        "id_photo" => $this->id_photo,
        "nom_objet" => $this->nom,
        "num_objet" => $this->num,
        "cbar_objet" => $this->cbar,
        "num_serie" => $this->num_serie,
        "prix_objet" => $this->prix,
        "caution_objet" => $this->caution,
        "prix_emprunt_objet" => $this->prix_emprunt,
        "objet_empruntable" => $this->empruntable==true,
        "en_etat" => $this->en_etat==true,
        "archive_objet"=>$this->archive,
        "date_achat"=> date("Y-m-d",$this->date_achat),
        "notes_objet" => $this->notes
        ),
      array ("id_objet"=>$this->id)
      );

  }


  function set_cbar ( $cbar)
  {
    $this->cbar      = $cbar;
    $sql = new update ($this->dbrw,
      "inv_objet",
      array(
        "cbar_objet" => $this->cbar
        ),
      array ("id_objet"=>$this->id)
      );

  }


  function event ( $id_emprunt, $id_utilisateur, $type, $date, $notes )
  {
    $sql = new insert ($this->dbrw,
      "inv_objet_evenement",
      array(
        "id_objet" => $this->id,
        "id_emprunt" => $id_emprunt,
        "id_utilisateur" => $id_utilisateur,
        "type_objeven" => $type,
        "date_even" => date("Y-m-d H:i",$date),
        "notes_even" => $notes
        )
      );
  }

  function is_avaible ( $from, $to )
  {


    $req = new requete($this->db,"SELECT * FROM inv_emprunt_objet ".
      "INNER JOIN inv_emprunt ON inv_emprunt.id_emprunt=inv_emprunt_objet.id_emprunt ".
      "WHERE ".
      "(( inv_emprunt.date_debut_emp < '".date("Y-m-d H:i:s",$to)."' ) AND ".
      "( inv_emprunt.date_fin_emp > '".date("Y-m-d H:i:s",$from)."') ) ".
      "AND inv_emprunt_objet.id_objet=".$this->id." ".
      "AND inv_emprunt_objet.retour_effectif_emp IS NULL");

    return ($req->lines==0);
  }  //!(date_debut_emp > $to || (r).date_fin_emp < $from)

  /**
   * Supprime l'objet de l'inventaire, à utiliser en cas d'erreur de saisie ou autre.
   * En aucun cas à utiliser pour un objet manquant ou détruit : das ce cas il faut l'archiver.
   */
  function delete_objet()
  {
    new delete($this->dbrw,"inv_objet",array("id_objet" => $this->id));
    new delete($this->dbrw,"inv_objet_evenement",array("id_objet" => $this->id));

    // Nettoyage des extentions
    new delete($this->dbrw,"inv_jeu",array("id_objet" => $this->id));
    new delete($this->dbrw,"bk_book",array("id_objet" => $this->id));
    new delete($this->dbrw,"bk_livre_auteur",array("id_objet" => $this->id));

    $this->id=null;
  }

  /**
   * Marque l'etat d'archive ou non de l'objet
   * @param $archive Etat d'archivage (true=archivé, false=actif)
   */
  function set_archive($archive=true)
  {
    $this->archive=$archive;
    $req = new update($this->dbrw,"inv_objet",array("archive_objet"=>$this->archive),array("id_objet" => $this->id));
  }

}

/**
 * @defgroup empruntmod Etats d'un pret/une reservation de matériel
 * @ingroup inventaire
 * @{
 */
define("EMPRUNT_RESERVATION",0);
define("EMPRUNT_MODERE",1);
define("EMPRUNT_PRIS",2);
define("EMPRUNT_RETOURPARTIEL",3);
define("EMPRUNT_RETOUR",4);

$EmpruntObjetEtats = array (
EMPRUNT_RESERVATION => "En attente de modération",
EMPRUNT_MODERE => "Réservé",
EMPRUNT_PRIS => "Pris",
EMPRUNT_RETOURPARTIEL => "Retourné en partie",
EMPRUNT_RETOUR => "Retourné"
);

/**
 * @}
 */

/**
 * Reservation de matériel / Pret de matériel
 * @ingroup inventaire
 */
class emprunt extends stdentity /*inv_emprunt*/
{
  var $id_utilisateur;
  var $id_asso;
  var $id_utilisateur_op;
  var $date_demande;/*date_demande_emp */
  var $date_prise;/*date_prise_emp */
  var $date_retour;/*date_retour_emp */
  var $date_debut;/*date_debut_emp */
  var $date_fin;/*date_fin_emp */
  var $caution;/*caution_emp */
  var $prix_paye;/*prix_paye_emp */
  var $emprunteur_ext;  /*emprunteur_ext */
  var $notes;/*notes_emprunt */
  var $etat;/*etat_emprunt */



  /* inv_emprunt_objet
   id_objet
   id_emprunt
   retour_effectif_emp
   */


  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `inv_emprunt`
        WHERE `id_emprunt` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  /** Charge un emprunt en cours en fonction d'un objet
   * $this->id est égal à null en cas d'erreur
   * @param $id_objet id de l'objet
   */
  function load_by_objet ( $id_objet )
  {
    $req = new requete($this->db, "SELECT inv_emprunt.* FROM `inv_emprunt_objet`
        INNER JOIN inv_emprunt ON inv_emprunt.id_emprunt=inv_emprunt_objet.id_emprunt
        WHERE inv_emprunt_objet.`id_objet` = '" . mysql_real_escape_string($id_objet) . "'
        AND inv_emprunt_objet.retour_effectif_emp IS NULL
        AND inv_emprunt.date_prise_emp IS NOT NULL
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

    $this->id      = $row['id_emprunt'];
    $this->id_utilisateur  = $row['id_utilisateur'];
    $this->id_asso    = $row['id_asso'];
    $this->id_utilisateur_op= $row['id_utilisateur_op'];
    $this->date_demande  = strtotime($row['date_demande_emp']);
    $this->date_prise  = strtotime($row['date_prise_emp']);
    $this->date_retour  = strtotime($row['date_retour_emp']);
    $this->date_debut  = strtotime($row['date_debut_emp']);
    $this->date_fin    = strtotime($row['date_fin_emp']);
    $this->caution    = $row['caution_emp'];
    $this->prix_paye    = $row['prix_paye_emp'];
    $this->emprunteur_ext  = $row['emprunteur_ext'];
    $this->notes      = $row['notes_emprunt'];
    $this->etat      = $row['etat_emprunt'];
  }

  function add_emprunt ( $id_utilisateur, $id_asso, $emprunteur_ext, $date_debut, $date_fin )
  {
    $this->id_utilisateur = $id_utilisateur;
    $this->id_asso = $id_asso;
    $this->emprunteur_ext = $emprunteur_ext;
    $this->date_debut = $date_debut;
    $this->date_fin = $date_fin;
    $this->etat = EMPRUNT_RESERVATION;
    $this->date_demande = time();

    $sql = new insert($this->dbrw,"inv_emprunt",
          array (
            "id_utilisateur"=>$this->id_utilisateur,
            "id_asso"=>$this->id_asso,
            "emprunteur_ext"=>$this->emprunteur_ext,
            "date_debut_emp"=>date("Y-m-d H:i:s",$this->date_debut),
            "date_fin_emp"=>date("Y-m-d H:i:s",$this->date_fin),
            "etat_emprunt"=>$this->etat,
            "date_demande_emp"=>date("Y-m-d H:i:s",$this->date_demande)
          ) );
    if ( $sql )
      $this->id = $sql->get_id();
    else
      $this->id = null;
  }

  function modere ( $id_utilisateur_op, $caution, $prix_paye, $notes )
  {
    $this->id_utilisateur_op = $id_utilisateur_op;
    $this->caution = $caution;
    $this->prix_paye = $prix_paye;
    $this->notes = $notes;
    $this->etat = EMPRUNT_MODERE;

    $sql = new update($this->dbrw,"inv_emprunt",
          array (
            "id_utilisateur_op"=>$this->id_utilisateur_op,
            "caution_emp"=>$this->caution,
            "prix_paye_emp"=>$this->prix_paye,
            "notes_emprunt"=>$this->notes,
            "etat_emprunt"=>$this->etat
          ),array("id_emprunt"=>$this->id) );
  }

  function retrait ( $id_utilisateur_op, $caution, $prix_paye, $notes)
  {
    $this->id_utilisateur_op = $id_utilisateur_op;
    $this->caution = $caution;
    $this->prix_paye = $prix_paye;
    $this->notes = $notes;
    $this->etat = EMPRUNT_PRIS;
    $this->date_prise = time();

    $sql = new update($this->dbrw,"inv_emprunt",
          array (
            "id_utilisateur_op"=>$this->id_utilisateur_op,
            "caution_emp"=>$this->caution,
            "prix_paye_emp"=>$this->prix_paye,
            "notes_emprunt"=>$this->notes,
            "etat_emprunt"=>$this->etat,
            "date_prise_emp"=>date("Y-m-d H:i:s",$this->date_prise)
          ),array("id_emprunt"=>$this->id) );

  }

  function add_object($id_objet)
  {
    $sql = new insert($this->dbrw,"inv_emprunt_objet",
          array (
            "id_emprunt"=>$this->id,
            "id_objet"=>$id_objet
          ) );
  }

  function remove_object($id_objet)
  {
    $sql = new delete($this->dbrw,"inv_emprunt_objet",
          array (
            "id_emprunt"=>$this->id,
            "id_objet"=>$id_objet
          ) );
    $sql = new requete($this->db, "SELECT * FROM inv_emprunt_objet WHERE id_emprunt = $this->id");
    if($sql->is_success() && $sql->lines == 0)
	$this->remove_emp();
  }

  function remove_emp()
  {
    $sql = new delete($this->dbrw,"inv_emprunt_objet",
          array (
            "id_emprunt"=>$this->id,
          ) );
    $sql = new delete($this->dbrw,"inv_emprunt",
          array (
            "id_emprunt"=>$this->id,
          ) );

  }

  function full_back ()
  {
    $sql = new update($this->dbrw,"inv_emprunt_objet",
          array("retour_effectif_emp"=>date("Y-m-d H:i:s")),
          array (
            "id_emprunt"=>$this->id
          ) );

    $this->etat = EMPRUNT_RETOUR;
    $this->date_retour = time();
    $sql = new update($this->dbrw,"inv_emprunt",
            array (
              "date_retour_emp"=>date("Y-m-d H:i:s",$this->date_retour),
              "etat_emprunt"=>$this->etat
            ),array("id_emprunt"=>$this->id) );
  }


  function back_objet($id_objet)
  {
    $sql = new update($this->dbrw,"inv_emprunt_objet",
          array("retour_effectif_emp"=>date("Y-m-d H:i:s")),
          array (
            "id_emprunt"=>$this->id,
            "id_objet"=>$id_objet
          ) );

    $req = new requete($this->db,"SELECT COUNT(*) FROM `inv_emprunt_objet` " .
        "WHERE `id_emprunt`='".$this->id."' AND `retour_effectif_emp` IS NULL");

    list($left) = $req->get_row();

    if ( $left == 0 )
    {
      $this->etat = EMPRUNT_RETOUR;
      $this->date_retour = time();
      $sql = new update($this->dbrw,"inv_emprunt",
            array (
              "date_retour_emp"=>date("Y-m-d H:i:s",$this->date_retour),
              "etat_emprunt"=>$this->etat
            ),array("id_emprunt"=>$this->id) );
    }
    elseif ( $this->etat != EMPRUNT_RETOURPARTIEL )
    {
      $this->etat = EMPRUNT_RETOURPARTIEL;
      $sql = new update($this->dbrw,"inv_emprunt",
            array (
              "etat_emprunt"=>$this->etat
            ),array("id_emprunt"=>$this->id) );
    }
  }


}

?>
