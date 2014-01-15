<?php

/** @file Gestion des associations et clubs
 *
 */

/* Copyright 2005-2007
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

define("ROLEASSO_PRESIDENT",10);
define("ROLEASSO_VICEPRESIDENT",9);
define("ROLEASSO_TRESORIER",7);
define("ROLEASSO_RESPCOM",5);
define("ROLEASSO_SECRETAIRE",4);
define("ROLEASSO_RESPINFO",3);
define("ROLEASSO_MEMBREBUREAU",2);
define("ROLEASSO_MEMBREACTIF",1);
define("ROLEASSO_MEMBRE",0);


$GLOBALS['ROLEASSO'] = array
(
  ROLEASSO_PRESIDENT=>"Responsable/président",
  ROLEASSO_VICEPRESIDENT=>"Vice-responsable/Vice-président",
  ROLEASSO_TRESORIER=>"Trésorier",
  ROLEASSO_RESPCOM=>"Responsable communication",
  ROLEASSO_SECRETAIRE=>"Secrétaire",
  ROLEASSO_RESPINFO=>"Responsable informatique",
  ROLEASSO_MEMBREBUREAU=>"Membre du bureau/de l'équipe",
  ROLEASSO_MEMBREACTIF=>"Benevole, membre actif",
  ROLEASSO_MEMBRE=>"Membre, adepte ou curieux"
);

$GLOBALS['ROLEASSO100'] = array
 (
  (ROLEASSO_PRESIDENT+100)=>"Président",
  (ROLEASSO_VICEPRESIDENT+100)=>"Vice-président",
  (ROLEASSO_TRESORIER+100)=>"Trésorier",
  (ROLEASSO_RESPCOM+100)=>"Responsable communication",
  (ROLEASSO_SECRETAIRE+100)=>"Secrétaire",
  (ROLEASSO_RESPINFO+100)=>"Responsable informatique",
  (ROLEASSO_MEMBREBUREAU+100)=>"Membre du bureau/de l'équipe",
  (ROLEASSO_MEMBREACTIF+100)=>"Benevole, membre actif",
  (ROLEASSO_MEMBRE+100)=>"Membre, adepte ou curieux",
  ROLEASSO_PRESIDENT=>"Responsable",
  ROLEASSO_VICEPRESIDENT=>"Vice-responsable",
  ROLEASSO_TRESORIER=>"Trésorier",
  ROLEASSO_RESPCOM=>"Responsable communication",
  ROLEASSO_SECRETAIRE=>"Secrétaire",
  ROLEASSO_RESPINFO=>"Responsable informatique",
  ROLEASSO_MEMBREBUREAU=>"Membre du bureau/de l'équipe",
  ROLEASSO_MEMBREACTIF=>"Benevole, membre actif",
  ROLEASSO_MEMBRE=>"Membre, adepte ou curieux"
);

class asso extends stdentity
{

  /* table asso */
  var $id_parent;
  var $nom;
  var $nom_unix;
  var $adresse_postale;

  var $email;
  var $siteweb;

  var $login_email;
  var $passwd_email;
  /*
   *  L'objectif est de conserver les mots de passe des boites
   * mails des clubs (er pourquoi pas d'y acceder en imap).
   * Cependant conserver en clair les mots de passe dans la bdd
   * ça craint, faudrait une méthode de pseudo cryptage, pour
   * que le stockage ne se fasse pas en clair...
   */

  var $hidden;

  var $mailings_lists=null;

  public $distinct_benevole=false;


  /** Charge une association par son ID
   * @param $id ID de l'association
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `asso`
        WHERE `id_asso` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");
    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  /** Charge une association par son nom unix
   * @param $name Nom unix de l'association
   */
  function load_by_unix_name ( $name )
  {
    $req = new requete($this->db, "SELECT * FROM `asso`
        WHERE `nom_unix_asso` = '" . mysql_real_escape_string($name) . "'
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
    $this->id = $row['id_asso'];
    $this->id_parent = $row['id_asso_parent'];
    $this->nom = $row['nom_asso'];
    $this->nom_unix = $row['nom_unix_asso'];
    $this->adresse_postale = $row['adresse_postale'];

    $this->email = $row['email_asso'];
    $this->siteweb = $row['siteweb_asso'];
    $this->login_email = $row['login_email'];
    $this->passwd_email = $row['passwd_email'];

    $this->distinct_benevole = $row['distinct_benevole_asso'];

    $this->hidden = $row['hidden'];
  }

  /** Crée une nouvelle association
   * @param $nom      Nom  de l'association
   * @param $nom_unix    Nom UNIX de l'association
   * @param $id_parent  ID de l'association parent, false si non applicable
   */
  function add_asso ( $nom, $nom_unix, $id_parent = null, $adresse_postale="", $email="", $siteweb="", $login_email="", $passwd_email="", $distinct_benevole=false, $hidden=0  )
  {
    if ( is_null($this->dbrw) ) return; // "Read Only" mode

    $this->nom = $nom;
    $this->nom_unix = $nom_unix;
    $this->id_parent = $id_parent;
    $this->adresse_postale = $adresse_postale;

    $this->email = $email;
    $this->siteweb = $siteweb;
    $this->login_email = $login_email;
    $this->passwd_email = $passwd_email;
    $this->hidden = $hidden;

    $sql = new insert ($this->dbrw,
      "asso",
      array(
        "id_asso_parent" => $this->id_parent,
        "nom_asso" => $this->nom,
        "nom_unix_asso" => $this->nom_unix,
        "adresse_postale"=>$this->adresse_postale,

        "email_asso"=>$this->email,
        "siteweb_asso"=>$this->siteweb,
        "login_email"=>$this->login_email,
        "passwd_email"=>$this->passwd_email,

        "distinct_benevole_asso" => $this->distinct_benevole,

        "hidden"=>$this->hidden,

        )
      );

    if ( $sql )
      $this->id = $sql->get_id();
    else
      $this->id = null;

    if ( $this->nom_unix && $this->is_mailing_allowed() )
    {
      if ( !is_null($this->id_parent) )
        $this->_ml_create($this->dbrw,$this->nom_unix.".membres",$this->email);

      if ( $this->distinct_benevole )
        $this->_ml_create($this->dbrw,$nom_unix.".benevoles",$this->email);

      $this->_ml_create($this->dbrw,$this->nom_unix.".bureau",$this->email);
    }
  }

  /** Modifie l'association
   * @param $nom      Nom  de l'association
   * @param $nom_unix    Nom UNIX de l'association
   * @param $id_parent  ID de l'association parent, false si non applicable
   */
  function update_asso ( $nom, $nom_unix, $id_parent = null, $adresse_postale="", $email=null, $siteweb=null, $login_email=null, $passwd_email=null, $distinct_benevole=false, $hidden=0 )
  {
    if ( is_null($this->dbrw) ) return; // "Read Only" mode

    $old_allow = $this->is_mailing_allowed();
    $old_id_parent = $this->id_parent;
    $this->id_parent = $id_parent;

    if ( $this->is_mailing_allowed() )
    {
      if ( !$old_allow || ($nom_unix && !$this->nom_unix) )
      {
        if ( $nom_unix )
        {
          if ( !is_null($id_parent) )
            $this->_ml_create($this->dbrw,$nom_unix.".membres",$this->email);

          if ( $distinct_benevole )
            $this->_ml_create($this->dbrw,$nom_unix.".benevoles",$this->email);

          $this->_ml_create($this->dbrw,$nom_unix.".bureau",$this->email);
        }
      }
      elseif ( $this->nom_unix )
      {
        if ( is_null($old_id_parent) && !is_null($id_parent) )
          $this->_ml_create($this->dbrw,$this->nom_unix.".membres",$this->email);

        if ( $distinct_benevole && !$this->distinct_benevole )
          $this->_ml_create($this->dbrw,$this->nom_unix.".benevoles",$this->email);
      }
    }

    global $topdir;
    require_once ($topdir. 'include/cts/fsearchcache.inc.php');
    fsearch_revalidate_cache_for ($this->nom);
    fsearch_revalidate_cache_for ($nom);

    $this->nom = $nom;
    $this->nom_unix = $nom_unix;
    $this->adresse_postale = $adresse_postale;

    if ( !is_null($email) )
      $this->email = $email;

    if ( !is_null($siteweb) )
      $this->siteweb = $siteweb;

    if ( !is_null($login_email) )
      $this->login_email = $login_email;

    if ( !is_null($passwd_email) )
      $this->passwd_email = $passwd_email;

    $this->distinct_benevole = $distinct_benevole;
    $this->hidden = $hidden;

    $sql = new update ($this->dbrw,
      "asso",
      array(
        "id_asso_parent" => $this->id_parent,
        "nom_asso" => $this->nom,
        "nom_unix_asso" => $this->nom_unix,
        "adresse_postale"=>$this->adresse_postale,
        "email_asso"=>$this->email,
        "siteweb_asso"=>$this->siteweb,
        "login_email"=>$this->login_email,
        "passwd_email"=>$this->passwd_email,
        "distinct_benevole_asso" => $this->distinct_benevole,
        "hidden" => $this->hidden,
        ),
      array ( "id_asso" => $this->id )

      );

  }


  /* table asso_membre */

  /** Ajoute un membre actuel à l'association
   * Si l'utilisateur est déjà membre, passe sa participation
   *  précédente comme ancienne
   * @param $id_utl    ID de l'utilisateur
   * @param $date_debut  Date de début (timestamp unix)
   * @param $role      Role
   * @param $description  Description du role (vice président, vpi ...)
  */
  function add_actual_member ( $id_utl, $date_debut, $role, $description )
  {
    if ( is_null($this->dbrw) ) return; // "Read Only" mode

    if ( !$date_debut )
      $date_debut = time();

    $prevrole = $this->member_role($id_utl);

    if ( is_null($prevrole) )
      $this->_ml_all_subscribe_user($id_utl,$role);
    elseif ( $role == $prevrole )
      return;
    else
    {
      $this->make_former_member($id_utl,$date_debut,true);
      $this->_ml_all_delta_user($id_utl,$prevrole,$role);
    }

    // Boulet-proof
    // Enlève toute participation qui est après la date de debut
    new requete($this->dbrw,"DELETE FROM asso_membre ".
      "WHERE date_debut >= '".strftime("%Y-%m-%d", $date_debut)."' ".
      "AND id_utilisateur='".mysql_real_escape_string($id_utl)."' ".
      "AND id_asso='".mysql_real_escape_string($this->id)."'");

    $sql = new insert ($this->dbrw,
      "asso_membre",
      array(
        "id_asso" => $this->id,
        "id_utilisateur" => $id_utl,
        "date_debut" => strftime("%Y-%m-%d", $date_debut),
        "role" => $role,
        "desc_role" => $description
        )
      );
  }

  /** Ajoute un ancien membre de l'association
   * @param $id_utl    ID de l'utilisateur
   * @param $date_debut  Date de début (timestamp unix)
   * @param $date_fin    Date de fin (timestamp unix)
   * @param $role      Role
   * @param $description  Description du role (vice président, vpi ...)
   */
  function add_former_member ( $id_utl, $date_debut, $date_fin, $role, $description )
  {
    if ( is_null($this->dbrw) )
      return; // "Read Only" mode

    if ( is_null($date_fin))
      return;

    if ( $date_fin <= $date_debut ) // Boulet proof
      return;

    // Boulet-proof
    // Enlève toute participation qui est après la date de debut et avant la date de fin
    new requete($this->dbrw,"DELETE FROM asso_membre ".
      "WHERE date_debut >= '".strftime("%Y-%m-%d", $date_debut)."' ".
      "AND date_debut < '".strftime("%Y-%m-%d", $date_fin)."' ".
      "AND id_utilisateur='".mysql_real_escape_string($id_utl)."' ".
      "AND id_asso='".mysql_real_escape_string($this->id)."'");

    $sql = new insert ($this->dbrw,
      "asso_membre",
      array(
        "id_asso" => $this->id,
        "id_utilisateur" => $id_utl,
        "date_debut" => strftime("%Y-%m-%d", $date_debut),
        "date_fin" => strftime("%Y-%m-%d", $date_fin),
        "role" => $role,
        "desc_role" => $description
        )
      );
  }

  /** Passe un membre actuel de l'association comme ancien
   * @param $id_utl  ID de l'utilisateur
   * @param $date_fin  Date de fin (timestamp unix)
   */
  function make_former_member ( $id_utl, $date_fin, $ignore_ml=false )
  {
    if ( is_null($this->dbrw) ) return; // "Read Only" mode

    if ( !$date_fin )
      $date_fin = time();

    if ( !$ignore_ml )
      $this->_ml_all_unsubscribe_user($id_utl);

    // Boulet-proof
    // Verifie que l'action ne gènère par une durée nulle ou négative
    $req = new requete($this->db,"SELECT date_debut FROM asso_membre ".
      "WHERE date_fin IS NULL ".
      "AND id_utilisateur='".mysql_real_escape_string($id_utl)."' ".
      "AND id_asso='".mysql_real_escape_string($this->id)."'");
    if ( $req->lines == 1 )
    {
      list($date_debut) = $req->get_row();
      $date_debut = strtotime($date_debut);

      if ( $date_debut >= $date_fin ) // un debut après la fin ?
      {
        // On oublie la "précédente" participation
        new delete ($this->dbrw,
          "asso_membre",
          array(
            "id_asso" => $this->id,
            "id_utilisateur" => $id_utl,
            "date_fin" => NULL
            )
          );
        return;
      }
    }

    $sql = new update ($this->dbrw,
      "asso_membre",
      array(
        "date_fin" => strftime("%Y-%m-%d", $date_fin)
        ),
      array(
        "id_asso" => $this->id,
        "id_utilisateur" => $id_utl,
        "date_fin" => NULL
        )
      );

  }

  /** Determine si un utilisteur est actuellemnt membre de l'association
   * @param $id_utl  ID de l'utilisateur
   * @return true si vrai, false sinon
   */
  function is_member ( $id_utl )
  {
    if ( is_null($id_utl) )
      return false;

    $req = new requete($this->db, "SELECT * FROM `asso_membre`
          WHERE `id_asso` = '" . mysql_real_escape_string($this->id) . "'
          AND `id_utilisateur` = '" . mysql_real_escape_string($id_utl) . "'
          AND `date_fin` is NULL
          LIMIT 1");

    return ($req->lines == 1);
  }

  /** Determine si un utilisteur est actuellemnt membre de l'association et occupe un poste spécial
   * @param $id_utl  ID de l'utilisateur
   * @param $role  Role minimum à occuper
   * @return true si vrai, false sinon
   */
  function is_member_role ( $id_utl, $role )
  {
    if ( is_null($id_utl) )
      return false;

    $req = new requete($this->db, "SELECT * FROM `asso_membre`
        WHERE `id_asso` = '" . mysql_real_escape_string($this->id) . "'
        AND `id_utilisateur` = '" . mysql_real_escape_string($id_utl) . "'
        AND `date_fin` is NULL AND `role` >= '".mysql_real_escape_string($role)."'
        LIMIT 1");

    return ($req->lines == 1);
  }

  function member_role ( $id_utl )
  {
    if ( is_null($id_utl) )
      return NULL;

    $req = new requete($this->db, "SELECT role FROM `asso_membre`
        WHERE `id_asso` = '" . mysql_real_escape_string($this->id) . "'
        AND `id_utilisateur` = '" . mysql_real_escape_string($id_utl) . "'
        AND `date_fin` is NULL
        LIMIT 1");

    if ( $req->lines != 1 )
      return NULL;

    list($role) = $req->get_row();

    return $role;
  }


  /** Enlève une 'participation' d'un membre de l'association actuelle
   * @param $id_utl    ID de l'utilisateur
   * @param $date_debut  Date de debut de la 'participation' (timestamp unix)
   */
  function remove_member ( $id_utl, $date_debut )
  {
    if ( is_null($this->dbrw) ) return; // "Read Only" mode

    $prevrole = $this->member_role($id_utl);

    $sql = new delete ($this->dbrw,
      "asso_membre",
      array(
        "id_asso" => $this->id,
        "id_utilisateur" => $id_utl,
        "date_debut" => strftime("%Y-%m-%d", $date_debut)
        )
      );

    $newrole = $this->member_role($id_utl);
    if ( is_null($newrole) && !is_null($prevrole) )
      $this->_ml_all_unsubscribe_user($id_utl,$prevrole);
    elseif ( $newrole != $prevrole && !is_null($prevrole) && !is_null($newrole) )
      $this->_ml_all_delta_user($id_utl,$prevrole,$newrole);
  }

  function get_member_for_role ($role)
  {
      $sql = 'SELECT id_utilisateur FROM `asso_membre` WHERE id_asso='.$this->id.' AND role = '.$role.' ORDER BY date_debut DESC LIMIT 1';
      $req = new requete ($this->db, $sql);
      $row = $req->get_row ();

      return $row['id_utilisateur'];
  }

  function get_tabs($user)
  {
    $tabs = array(array("info","asso.php?id_asso=".$this->id, "Informations"));

    if ( $user->is_in_group("gestion_ae")|| $this->is_member_role($user->id,ROLEASSO_MEMBREBUREAU) )
    {
      $tabs[] = array("tools","asso/index.php?id_asso=".$this->id,"Outils");
      $tabs[] = array("inv","asso/inventaire.php?id_asso=".$this->id,"Inventaire");
      $tabs[] = array("res","asso/reservations.php?id_asso=".$this->id,"Reservations");
      $tabs[] = array("mebs","asso/membres.php?id_asso=".$this->id,"Membres");
      $tabs[] = array("slds","asso/ventes.php?id_asso=".$this->id,"Ventes");
      $tabs[] = array("cpg","asso/campagne.php?id_asso=".$this->id,"Campagne");
    }
    else
    {
      $tabs[] = array("mebs","asso/membres.php?id_asso=".$this->id,"Membres");
    }
    $tabs[] = array("files","d.php?id_asso=".$this->id,"Fichiers");


    $req = new requete($this->db, "SELECT id_catph FROM `sas_cat_photos` " .
        "WHERE `meta_id_asso_catph` = '" . mysql_real_escape_string($this->id) . "' " .
        "AND `meta_mode_catph`='1' LIMIT 1");

    if ( $req->lines == 1 )
    {
      $enr = $req->get_row();
      $tabs[] = array("photos","sas2/?id_catph=".$enr["id_catph"],"Photos");
    }

    $req = new requete($this->db, "SELECT CONCAT(asso_parent.nom_unix_asso,':',asso.nom_unix_asso) AS path
                                   FROM asso
                                   LEFT JOIN asso AS asso_parent ON asso.id_asso_parent=asso_parent.id_asso
                                   WHERE asso.id_asso='".$this->id."'
                                   AND CONCAT(asso_parent.nom_unix_asso,':',asso.nom_unix_asso) IS NOT NULL
                                   AND asso.id_asso_parent <> '1'");
    if ( $req->lines == 1 )
    {
      list($path) = $req->get_row();
      $tabs[] = array("wiki2","wiki2/?name=".$path,"Wiki");
    }

    return $tabs;
  }

  function get_membres_group_id()
  {
   return $this->id+30000;
  }

  function get_bureau_group_id()
  {
   return $this->id+20000;
  }


  function get_html_path()
  {
    global $wwwtopdir;
    $path = $this->get_html_link();
    $parent = new asso($this->db);
    $parent->load_by_id($this->id_parent);
    while ( $parent->is_valid() )
    {
      $path = $parent->get_html_link()." / ".$path;
      $parent->load_by_id($parent->id_parent);
    }
    return $path;
  }

  function prefer_list()
  {
    return true;
  }

  function _ml_all_subscribe_user ( $id_utl, $role=null )
  {
    if ( !$this->is_mailing_allowed() )
      return;

    $user = new utilisateur($this->db);
    $user->load_by_id($id_utl);

    if ( !$user->is_valid() )
      return;

    if ( !$this->nom_unix )
      return;

    if ( is_null($role) )
      $role = $this->member_role($user->id);

    if ( !is_null($this->id_parent) )
      $this->_ml_subscribe($this->dbrw,$this->nom_unix.".membres",$user->email);

    if ( $this->distinct_benevole && $role >= ROLEASSO_MEMBREACTIF )
      $this->_ml_subscribe($this->dbrw,$this->nom_unix.".benevoles",$user->email);

    if ( $role > ROLEASSO_MEMBREACTIF )
      $this->_ml_subscribe($this->dbrw,$this->nom_unix.".bureau",$user->email);
  }

  function _ml_all_unsubscribe_user ( $id_utl, $role=null )
  {
    if ( !$this->is_mailing_allowed() )
      return;

    $user = new utilisateur($this->db);
    $user->load_by_id($id_utl);

    if ( !$user->is_valid() )
      return;

    if ( !$this->nom_unix )
      return;

    if ( is_null($role) )
      $role = $this->member_role($user->id);

    if ( !is_null($this->id_parent) )
      $this->_ml_unsubscribe($this->dbrw,$this->nom_unix.".membres",$user->email);

    if ( $this->distinct_benevole && $role >= ROLEASSO_MEMBREACTIF )
      $this->_ml_unsubscribe($this->dbrw,$this->nom_unix.".benevoles",$user->email);

    if ( $role > ROLEASSO_MEMBREACTIF )
      $this->_ml_unsubscribe($this->dbrw,$this->nom_unix.".bureau",$user->email);
  }

  function _ml_all_delta_user ( $id_utl, $oldrole, $newrole )
  {
    if ( !$this->is_mailing_allowed() )
      return;

    $user = new utilisateur($this->db);
    $user->load_by_id($id_utl);

    if ( !$user->is_valid() )
      return;

    if ( !$this->nom_unix )
      return;

    if ( $this->distinct_benevole )
    {
      if ( $oldrole >= ROLEASSO_MEMBREACTIF && $newrole < ROLEASSO_MEMBREACTIF )
        $this->_ml_unsubscribe($this->dbrw,$this->nom_unix.".benevoles",$user->email);
      elseif ( $oldrole < ROLEASSO_MEMBREACTIF && $newrole >= ROLEASSO_MEMBREACTIF )
        $this->_ml_subscribe($this->dbrw,$this->nom_unix.".benevoles",$user->email);
    }


    if ( $oldrole > ROLEASSO_MEMBREACTIF && $newrole <= ROLEASSO_MEMBREACTIF )
      $this->_ml_unsubscribe($this->dbrw,$this->nom_unix.".bureau",$user->email);
    elseif ( $oldrole <= ROLEASSO_MEMBREACTIF && $newrole > ROLEASSO_MEMBREACTIF )
      $this->_ml_subscribe($this->dbrw,$this->nom_unix.".bureau",$user->email);

  }

  static function _ml_subscribe ( $db, $ml, $email )
  {
    if ( !$email )
      return;
    //echo "SUBSCRIBE $ml $email<br/>";
    new insert($db,"ml_todo",array("action_todo"=>"SUBSCRIBE","ml_todo"=>strtolower($ml),"email_todo"=>$email));
  }

  static function _ml_unsubscribe ( $db, $ml, $email )
  {
    if ( !$email )
      return;
    //echo "UNSUBSCRIBE $ml $email<br/>";
    new insert($db,"ml_todo",array("action_todo"=>"UNSUBSCRIBE","ml_todo"=>strtolower($ml),"email_todo"=>$email));
  }

  static function _ml_create ( $db, $ml, $owner="" )
  {
    if ( empty($owner) )
      $owner = "ae@utbm.fr";
    //echo "CREATE $ml $owner<br/>";
    new insert($db,"ml_todo",array("action_todo"=>"CREATE","ml_todo"=>strtolower($ml),"email_todo"=>$owner));
  }

  static function _ml_rename ( $db, $old, $new )
  {
    //TODO: rename mailing $old to $new
    //echo "MOVE $old TO $new<br/>";
    //>>> MAIL ADMIN
  }

  static function _ml_remove ( $db, $ml )
  {
    //TODO: destroy $ml
    //echo "DESTROY $ml<br/>";
    //>>> MAIL ADMIN
  }


  function is_mailing_allowed()
  {
    if ( $this->id == 1 )
     return true;

    if ( !is_null($this->id_parent) && $this->id_parent != 3 )
     return true;

    return false;
  }

  function get_pending_unmod_mail()
  {
    if (strlen($this->nom_unix) <= 0)
      return 0;

    $path = '/var/lib/mailman/data/';
    $dir = scandir ($path);
    $count = 0;
    foreach ($dir as $entry) {
        if (strpos ($entry, $this->nom_unix . ".membres") !== false)
            $count++;
    }
    return $count;
  }

  function get_exist_ml()
  {
    if ($mailings_lists != null)
      return $mailings_lists;

    $path = '/var/lib/mailman/lists/';
    $dir = scandir ($path);
    $mailings_lists = array ();
    foreach ($dir as $entry) {
        if (strpos ($entry, $this->nom_unix) !== false)
            $mailings_lists[] = $entry;
    }

    return $mailings_lists;
  }

  function get_subscribed_email($mailing_list)
  {
    exec(escapeshellcmd("/usr/lib/mailman/bin/list_members ".$mailing_list), $emails, $ret);
    if ($ret != 0)
      $emails = array();

    return $emails;
  }
}



?>
