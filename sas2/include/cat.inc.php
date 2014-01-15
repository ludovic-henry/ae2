<?php
/* Copyright 2004-2006
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
require_once($topdir."include/entities/basedb.inc.php");
require_once($topdir."include/entities/asso.inc.php");

define("CATPH_MODE_NORMAL",0);
define("CATPH_MODE_META_ASSO",1);

$GLOBALS['catph_modes'] =
  array(
    CATPH_MODE_NORMAL=>"Catégorie normale",
    CATPH_MODE_META_ASSO=>"Catégorie sommaire association"
  );

/**
 * Catégorie du SAS
 * @ingroup sas
 * @author Julien Etelain
 */
class catphoto extends basedb
{

  var $id_catph_parent;/*id_catph_parent*/
  var $id_photo;/*id_photo*/
  var $nom;/*nom_catph*/
  var $date_debut;/*date_debut_catph*/
  var $date_fin;/*date_fin_catph*/
  /*modere_catph*/

  var $meta_id_asso;
  var $meta_mode;

  var $meta_cat;

  var $id_lieu;

  /** Charge une catégorie par son ID
   * @param $id ID de l'association
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `sas_cat_photos`
        WHERE `id_catph` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");
    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function load_by_asso_summary($id_asso)
  {
    $req = new requete($this->db, "SELECT * FROM `sas_cat_photos` " .
        "WHERE `meta_id_asso_catph` = '" . mysql_real_escape_string($id_asso) . "' " .
        "AND `meta_mode_catph`='".CATPH_MODE_META_ASSO."' " .
        "LIMIT 1");
    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }


  function load_by_name ( $id_parent, $name )
  {
    if ( $id_parent )
      $req = new requete($this->db, "SELECT * FROM `sas_cat_photos`
          WHERE `id_catph_parent` = '" . mysql_real_escape_string($id_parent) . "'
          AND `nom_catph` = '" . mysql_real_escape_string($name) . "'
          LIMIT 1");
    else
      $req = new requete($this->db, "SELECT * FROM `sas_cat_photos`
          WHERE `id_catph_parent` IS NULL
          AND `nom_catph` = '" . mysql_real_escape_string($name) . "'
          LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function load_by_path ( $path )
  {
    $tokens = explode("/",$path);

    if ( $tokens[0] == "" ) unset($tokens[0]);

    $this->id_catph_parent = null;

    foreach ( $tokens as $name )
    {
      if(  $name != "" )
      {
        $this->load_by_name($this->id_catph_parent,$name);
        if ( !$this->is_valid() ) return false;
      }
    }

    return true;
  }

  function _load($row)
  {
    $this->id = $row['id_catph'];
    $this->id_catph_parent = $row['id_catph_parent'];
    $this->id_photo = $row['id_photo'];
    $this->nom = $row['nom_catph'];

    if ( is_null($row['date_debut_catph']) )
    {
      $this->date_debut = null;
      $this->date_fin = null;
    }
    else
    {
      $this->date_debut = strtotime($row['date_debut_catph']);
      $this->date_fin = strtotime($row['date_fin_catph']);
    }

    $this->modere = $row['modere_catph'];

    $this->id_utilisateur = $row['id_utilisateur'];
    $this->id_groupe = $row['id_groupe'];
    $this->id_groupe_admin = $row['id_groupe_admin'];
    $this->droits_acces = $row['droits_acces_catph'];

    $this->meta_id_asso = $row['meta_id_asso_catph'];
    $this->meta_mode = $row['meta_mode_catph'];

    $this->id_lieu = $row['id_lieu'];
  }

  function get_photos ( $id_cat, $user, $grps, $select="sas_photos.*", $limit="")
  {
    if ( isset($this->meta_cat) && $this->meta_cat->meta_id_asso != $this->meta_id_asso )
      $filter = " AND (`meta_id_asso_ph`='".$this->meta_cat->meta_id_asso."' ".
                       "OR `id_asso_photographe`='".$this->meta_cat->meta_id_asso."')";
    else
      $filter = "";

    if ( $this->is_admin( $user ) )
      return new requete($this->db,"SELECT $select " .
        "FROM sas_photos " .
        "WHERE " .
        "id_catph='".$id_cat."'$filter ".
        "ORDER BY type_media_ph DESC, date_prise_vue " .
        "$limit");
    else
      return new requete($this->db,"SELECT $select " .
        "FROM sas_photos " .
        "LEFT JOIN sas_personnes_photos ON " .
          "(sas_personnes_photos.id_photo=sas_photos.id_photo " .
          "AND sas_personnes_photos.id_utilisateur='".$user->id."' " .
          "AND sas_personnes_photos.modere_phutl='1') " .
        "LEFT JOIN `asso_membre` ON ".
          "(`asso_membre`.`id_asso` = `sas_photos`.`meta_id_asso_ph` ".
          "AND `asso_membre`.`id_utilisateur` = '" . $user->id."' ".
          "AND `asso_membre`.`date_fin` is NULL AND `asso_membre`.`role` >= '".ROLEASSO_MEMBREBUREAU."') ".
        "WHERE " .
        "id_catph='".$id_cat."'$filter AND " .
        "((((droits_acces_ph & 0x1) OR " .
        "((droits_acces_ph & 0x10) AND ".$user->get_grps_authorization_fragment('date_prise_vue', $grps, 'id_groupe').")) " .
          "AND droits_acquis='1' AND modere_ph='1' ) OR " .
        "(id_groupe_admin IN ($grps)) OR " .
        "((droits_acces_ph & 0x100) AND sas_photos.id_utilisateur='".$user->id."') OR " .
        "((droits_acces_ph & 0x100) AND sas_personnes_photos.id_utilisateur IS NOT NULL) ) " .
        "ORDER BY type_media_ph DESC, date_prise_vue " .
        "$limit");

  }

  function get_categories ( $id_cat, $user, $grps, $select="*")
  {
    if ( $this->is_admin( $user ) )
      return new requete($this->db,"SELECT $select " .
        "FROM sas_cat_photos " .
        "WHERE " .
        "id_catph_parent='$id_cat' " .
        "ORDER BY `date_debut_catph` DESC,`nom_catph`");
    else
      return new requete($this->db,"SELECT $select " .
        "FROM sas_cat_photos " .
        "WHERE " .
        "id_catph_parent='$id_cat' AND " .
        "((droits_acces_catph & 0x1) OR " .
        "((droits_acces_catph & 0x10) AND ".$user->get_grps_authorization_fragment('date_debut_catph', $grps, 'id_groupe').") OR " .
        "(id_groupe_admin IN ($grps)) OR " .
        "((droits_acces_catph & 0x100) AND id_utilisateur='".$user->id."')) " .
        "ORDER BY `date_debut_catph` DESC,`nom_catph`");
  }

  /**
   * Retourne toutes les sous-catégories que l'utilisateur peut voir.
   * Retourne un tableau ou chaque entrée est un tableau associatif contenant :
   * - id_catph
   * - nom_catph
   * - id_photo
   * - id_catph_parent
   * - nom_catph_parent
   * - date_debut_catph
   * - id_utilisateur
   * - id_groupe
   * - id_groupe_admin
   * - droits_acces_catph
   * - meta_mode_catph
   * - meta_id_asso
   * Attention: Si id_catph_parent != $this->id, alors il s'agit d'une méta sous-catégorie
   */
  function get_all_categories ( &$user )
  {
    $cats = array();
    $grps = $user->get_groups_csv();

    if ( $this->is_admin( $user ) )
      $query = "SELECT id_catph, nom_catph, id_photo, id_catph_parent, NULL AS nom_catph_parent, date_debut_catph, id_utilisateur, id_groupe, id_groupe_admin, droits_acces_catph, meta_mode_catph, meta_id_asso_catph   " .
        "FROM sas_cat_photos " .
        "WHERE " .
        "id_catph_parent='".$this->id."' ";
    else
      $query = "SELECT id_catph, nom_catph, id_photo, id_catph_parent, NULL AS nom_catph_parent, date_debut_catph, id_utilisateur, id_groupe, id_groupe_admin, droits_acces_catph, meta_mode_catph, meta_id_asso_catph " .
        "FROM sas_cat_photos " .
        "WHERE " .
        "id_catph_parent='".$this->id."' AND " .
        "((droits_acces_catph & 0x1) OR " .
        "((droits_acces_catph & 0x10) AND ".$user->get_grps_authorization_fragment('date_debut_catph', $grps, 'id_groupe').") OR " .
        "(id_groupe_admin IN ($grps)) OR " .
        "((droits_acces_catph & 0x100) AND id_utilisateur='".$user->id."')) ";

    if ( $this->meta_mode == CATPH_MODE_META_ASSO )
    {
      $query .= "UNION ".
        "SELECT DISTINCT(sas_cat_photos.id_catph) AS `id_catph`, ".
        "sas_cat_photos.nom_catph, ".
        "NULL AS id_photo, ".
        "sas_cat_photos.id_catph_parent, ".
        "parent.nom_catph AS nom_catph_parent, ".
        "sas_cat_photos.date_debut_catph,".
        "NULL AS id_utilisateur, ".
        "NULL AS id_groupe, ".
        "NULL AS id_groupe_admin, ".
        "NULL AS droits_acces_catph, " .
        "NULL AS meta_mode_catph, " .
        "NULL AS meta_id_asso_catph " .
        "FROM `sas_photos` ".
        "INNER JOIN sas_cat_photos ON ( sas_photos.id_catph = sas_cat_photos.id_catph ) " .
        "LEFT JOIN sas_cat_photos as parent ON ( parent.id_catph = sas_cat_photos.id_catph_parent ) ".
        "WHERE ".
          "(sas_photos.meta_id_asso_ph='".$this->meta_id_asso."' ".
          "OR sas_photos.id_asso_photographe='".$this->meta_id_asso."') " .
        "AND sas_cat_photos.id_catph!='".$this->id."' ".
        "AND sas_cat_photos.id_catph_parent!='".$this->id."' ".
        "AND parent.id_catph_parent!='".$this->id."' ".
        "AND (sas_cat_photos.meta_id_asso_catph!='".$this->meta_id_asso."' OR sas_cat_photos.meta_id_asso_catph IS NULL)";

      $query .= "UNION ".

        "SELECT sas_cat_photos.id_catph, ".
        "sas_cat_photos.nom_catph, ".
        "sas_cat_photos.id_photo, ".
        "sas_cat_photos.id_catph_parent, ".
        "parent.nom_catph AS nom_catph_parent, ".
        "sas_cat_photos.date_debut_catph,".
        "NULL AS id_utilisateur, ".
        "NULL AS id_groupe, ".
        "NULL AS id_groupe_admin, ".
        "NULL AS droits_acces_catph, " .
        "NULL AS meta_mode_catph, " .
        "NULL AS meta_id_asso_catph " .
        "FROM sas_cat_photos ".
        "LEFT JOIN sas_cat_photos as parent ON ( parent.id_catph = sas_cat_photos.id_catph_parent ) ".
        "WHERE sas_cat_photos.meta_id_asso_catph='".$this->meta_id_asso."' ".
        "AND sas_cat_photos.id_catph!='".$this->id."' ".
        "AND sas_cat_photos.id_catph_parent!='".$this->id."' ".
        "AND parent.id_catph_parent!='".$this->id."' ".
        "AND sas_cat_photos.meta_mode_catph!='".CATPH_MODE_META_ASSO."'";
    }

    $query .= "ORDER BY  `date_debut_catph` DESC,`nom_catph`";

    $cats = array();

    $req = new requete($this->db,$query);

    while ( $row = $req->get_row() )
    {
      if ( is_null($row['id_photo']) || $row['id_photo'] <= 0 )
      {
        if ( $row['id_catph_parent'] != $this->id_catph_parent )
          $req2 = new requete($this->db,
            "SELECT id_photo FROM `sas_photos` ".
            "WHERE meta_id_asso_ph='".$this->meta_id_asso."' " .
            "AND id_catph='".$row['id_catph']."' " .
            "AND droits_acquis =1 " .
            "AND (droits_acces_ph & 1) = 1 " .
            "ORDER BY date_prise_vue");
        else
          $req2 = new requete($this->db,
            "SELECT id_photo FROM `sas_photos` ".
            "WHERE id_catph='".$row['id_catph']."' " .
            "AND droits_acquis =1 " .
            "AND (droits_acces_ph & 1) = 1 " .
            "ORDER BY date_prise_vue");

        if ( $req2->lines > 0 )
          list($row['id_photo']) = $req2->get_row();
      }

      $cats[] = $row;
    }

    return $cats;
  }



  function get_recent_photos_categories ($user, $grps )
  {
      // pas tres beau, mais ca marche...  <- NO SHIT
      /*return new requete($this->db, "SELECT ( SELECT sas_photos.id_photo ".
        "FROM sas_photos " .
        "LEFT JOIN sas_personnes_photos ON " .
          "(sas_personnes_photos.id_photo=sas_photos.id_photo " .
          "AND sas_personnes_photos.id_utilisateur='".$user->id."' " .
          "AND sas_personnes_photos.modere_phutl='1') " .
        "LEFT JOIN `asso_membre` ON ".
          "(`asso_membre`.`id_asso` = `sas_photos`.`meta_id_asso_ph` ".
          "AND `asso_membre`.`id_utilisateur` = '" . $user->id."' ".
          "AND `asso_membre`.`date_fin` is NULL AND `asso_membre`.`role` >= '".ROLEASSO_MEMBREBUREAU."') ".
        "WHERE " .
        " sas_photos.id_catph = sas_cat_photos.id_catph AND " .
        "((((droits_acces_ph & 0x1) OR " .
        "((droits_acces_ph & 0x10) AND sas_photos.id_groupe IN ($grps))) " .
          "AND droits_acquis='1') OR " .
        "(sas_photos.id_groupe_admin IN ($grps)) OR " .
        "((droits_acces_ph & 0x100) AND sas_photos.id_utilisateur='".$user->id."') OR " .
        "((droits_acces_ph & 0x100) AND sas_personnes_photos.id_utilisateur IS NOT NULL) ) ".
        "ORDER BY sas_photos.id_photo DESC LIMIT 1 ), ".
        "sas_cat_photos.* " .
        "FROM sas_cat_photos WHERE" .
        "((droits_acces_catph & 0x1) OR " .
        "((droits_acces_catph & 0x10) AND ".$user->get_grps_authorization_fragment('date_debut_catph', $grps, 'id_groupe').") OR " .
        "(sas_cat_photos.id_groupe_admin IN ($grps)) OR " .
        "((droits_acces_catph & 0x100) AND sas_cat_photos.id_utilisateur='".$user->id."')) " .
        "ORDER BY 1 DESC " .
        "LIMIT 4");*/
    
    // On va tenter de faire une requete qui attaque pas
    return $req = new requete ($this->db,
        "SELECT `id_catph`, `nom_catph`, `id_photo`
         FROM `sas_cat_photos`
         WHERE
         (
          (`droits_acces_catph` & 0x1) OR
          ((`droits_acces_catph` & 0x10) AND ".$user->get_grps_authorization_fragment ('date_debut_catph', $grps, 'id_groupe').") OR
          (`id_groupe_admin` IN ($grps)) OR
          ((droits_acces_catph & 0x100) AND sas_cat_photos.id_utilisateur='".$user->id."')
         )
         ORDER BY `id_catph` DESC
         LIMIT 5");
  }



  function add_catphoto ( $id_catph_parent, $nom, $debut, $fin, $meta_id_asso=NULL, $meta_mode=CATPH_MODE_NORMAL, $id_lieu=NULL )
  {
    $this->nom = $nom;
    $this->id_catph_parent = $id_catph_parent;
    $this->id_photo = null;

    $this->date_debut = $debut;
    $this->date_fin = $fin;
    $this->modere=0;
    $this->meta_id_asso=$meta_id_asso;
    $this->meta_mode=$meta_mode;

    $this->id_lieu=$id_lieu;


    $sql = new insert ($this->dbrw,
      "sas_cat_photos",
      array(
        "id_catph_parent"=>$this->id_catph_parent,
        "id_photo"=>$this->id_photo,
        "nom_catph"=>$this->nom,
        "date_debut_catph"=>is_null($this->date_debut)?null:date("Y-m-d H:i:s",$this->date_debut),
        "date_fin_catph"=>is_null($this->date_debut)?null:date("Y-m-d H:i:s",$this->date_fin),
        "modere_catph"=>$this->modere,
        "id_utilisateur"=>$this->id_utilisateur,
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_catph"=>$this->droits_acces,

        "meta_id_asso_catph"=>$this->meta_id_asso,
        "meta_mode_catph"=>$this->meta_mode,

        "id_lieu"=>$this->id_lieu
        )
      );

    if ( $sql )
      $this->id = $sql->get_id();
    else
      $this->id = null;
  }

  function set_photo ( $id_photo )
  {
    $this->id_photo = $id_photo;

    $sql = new update ($this->dbrw,
      "sas_cat_photos",
      array(
        "id_photo"=>$this->id_photo
        ),
      array("id_catph"=>$this->id )
      );


  }

  function update_catphoto ( &$user, $id_catph_parent, $nom, $debut, $fin, $meta_id_asso=NULL, $meta_mode=CATPH_MODE_NORMAL, $id_lieu=NULL )
  {
    $this->nom = $nom;
    $this->id_catph_parent = $id_catph_parent;
    $this->date_debut = $debut;
    $this->date_fin = $fin;
    if ( !$this->is_admin( $user ) )
      $this->modere=0;
    $this->meta_id_asso=$meta_id_asso;
    $this->meta_mode=$meta_mode;

    $this->id_lieu=$id_lieu;

    $sql = new update ($this->dbrw,
      "sas_cat_photos",
      array(
        "id_catph_parent"=>$this->id_catph_parent,
        "id_photo"=>$this->id_photo,
        "nom_catph"=>$this->nom,
        "date_debut_catph"=>is_null($this->date_debut)?null:date("Y-m-d H:i:s",$this->date_debut),
        "date_fin_catph"=>is_null($this->date_debut)?null:date("Y-m-d H:i:s",$this->date_fin),
        "modere_catph"=>$this->modere,
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_catph"=>$this->droits_acces,

        "meta_id_asso_catph"=>$this->meta_id_asso,
        "meta_mode_catph"=>$this->meta_mode,

        "id_lieu"=>$this->id_lieu

        ),
        array("id_catph"=>$this->id)
      );


  }

  /**
   * Définit si la catégorie est modérée ou pas
   * @param $modere Modérée(=true) ou non (=false)
   */
  function set_modere($modere=true)
  {
    $this->modere = $modere;
    $sql = new update($this->dbrw,"sas_cat_photos",array("modere_catph"=>$this->modere),array("id_catph"=>$this->id) );
  }


  function set_meta ( &$meta_cat)
  {
    $this->meta_cat = &$meta_cat;
  }

  function remove_cat(&$user)
  {
    $req = new requete($this->db,
        "SELECT * FROM sas_photos " .
        "WHERE id_catph='".$this->id."'");

    $ph = new photo($this->db,$this->dbrw);
    while ( $row = $req->get_row() )
    {
      $ph->_load($row);
      $ph->remove_photo();
    }


    $req = new requete($this->db,
        "SELECT * FROM sas_cat_photos " .
        "WHERE id_catph_parent='".$this->id."'");
    $cat = new catphoto($this->db,$this->dbrw);
    while ( $row = $req->get_row() )
    {
      $cat->_load($row);
      $cat->remove_cat($site);
    }

    $sql = new delete($this->dbrw,"sas_cat_photos",array("id_catph"=>$this->id) );
    $this->id=null;
    _log($this->dbrw,'Suppression catégorie sas', 'Potentiel boulet a supprimé :'.$this->nom, 'SAS', $user);
  }

  /**
   * Détermine si l'utilisateur est administrateur de l'élèment
   * @param $user Instance de utilisateur
   */
  function is_admin ( &$user )
  {
    if ( $user->is_in_group("sas_admin")) return true;
    return parent::is_admin($user);
  }

  function move_to ( $id_catph_parent )
  {
    $this->id_catph_parent = $id_catph_parent;

    $sql = new update ($this->dbrw,
      "sas_cat_photos",
      array("id_catph_parent"=>$this->id_catph_parent),
      array("id_catph"=>$this->id ));
  }

  function get_semestre ()
  {
    if ( is_null($this->date_debut) )
      return null;

    $y = date("Y",$this->date_debut);
    $m = date("m",$this->date_debut);

    if ( $m >= 2 && $m < 9)
      return "Printemps ".$y;
    else if ( $m >= 9 )
      return "Automne ".$y;
    else
      return "Automne ".($y-1);
  }

  function get_short_semestre ()
  {
    if ( is_null($this->date_debut) )
      return null;

    $y = date("Y",$this->date_debut);
    $m = date("m",$this->date_debut);

    if ( $m >= 2 && $m < 9)
      return "P".$y;
    else if ( $m >= 9 )
      return "A".$y;
    else
      return "A".($y-1);
  }

  function is_right (&$user, $required)
  {
    $result = parent::is_right($user, $required);

    // Le test a réussi vraisemblablement parce que le mec est seulement dans
    // le bureau de l'asso. Dans ce cas restreindre l'accès aux vrais gradés.
    if ($meta_id_asso != NULL && $required == DROIT_ECRITURE)
      if ($result && !$user->is_in_group("sas_admin") && $user->is_in_group_id($this->id_groupe_admin))
        if (!$user->is_asso_role($meta_id_asso, ROLEASSO_PRESIDENT) && !$user->is_asso_role($meta_id_asso, ROLEASSO_RESPCOM))
          return false;

    // Droit de lecture de toutes les catégories pour les utilisateurs qui ont déjà été à l'AE.
    $derniere_cotiz = false;
    if (!$result && ($dernier_cotiz = $user->date_derniere_cotiz_a_lae ()) && ($required == DROIT_LECTURE) && ($this->id_groupe == 10000) && (($required & ($this->droits_acces >> 4)) == $required)) {
      $date_derniere_cotiz = strtotime($dernier_cotiz);
      if ($date_derniere_cotiz >= $date_debut)
        return true;
    }

    return $result;
  }

  function get_photos_search ( $user, $filter, $joins="", $select="*", $limit="", $order="type_media_ph DESC, date_prise_vue")
  {

    if ( $this->is_admin( $user ) )
      return new requete($this->db,"SELECT $select " .
        "FROM sas_photos " .
        "$joins ".
        "WHERE " .
        "($filter) ".
        "ORDER BY $order " .
        "$limit");

    $grps = $user->get_groups_csv();

    return new requete($this->db,"SELECT $select " .
        "FROM sas_photos " .
        "$joins ".
        "LEFT JOIN sas_personnes_photos AS p1 ON " .
          "(p1.id_photo=sas_photos.id_photo " .
          "AND p1.id_utilisateur='".$user->id."' " .
          "AND p1.modere_phutl='1') " .
        "LEFT JOIN `asso_membre` ON ".
          "(`asso_membre`.`id_asso` = `sas_photos`.`meta_id_asso_ph` ".
          "AND `asso_membre`.`id_utilisateur` = '" . $user->id."' ".
          "AND `asso_membre`.`date_fin` is NULL AND `asso_membre`.`role` >= '".ROLEASSO_MEMBREBUREAU."') ".
        "WHERE " .
        "($filter) AND " .
        "((((droits_acces_ph & 0x1) OR " .
        "((droits_acces_ph & 0x10) AND id_groupe IN ($grps))) " .
          "AND droits_acquis='1' AND modere_ph='1' ) OR " .
        "(id_groupe_admin IN ($grps)) OR " .
        "((droits_acces_ph & 0x100) AND sas_photos.id_utilisateur='".$user->id."') OR " .
        "((droits_acces_ph & 0x100) AND p1.id_utilisateur IS NOT NULL) ) " .
        "ORDER BY $order " .
        "$limit");

  }

}



?>
