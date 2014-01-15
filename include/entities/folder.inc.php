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
 * @file Gestion des repertoires virtuels (partie téléchargement).
 */

require_once($topdir."include/entities/fs.inc.php");
require_once($topdir."include/entities/files.inc.php");

/**
 * Classe de gestion des repertoires virtuels.
 *
 * La partie "fichier" est décrite par le dossier qui a id_asso=null et id_folder_parent=null.
 * Les repertoire pour chaque asso est décrit par le dossier ayant l'id de lasso et id_folder_parent=null.
 *
 * @ingroup aedrive
 * @author Julien Etelain
 */
class dfolder extends fs
{
  /** Nom de fichier du dossier (généré automatiquement) */
  var $nom_fichier;
  /** Titre du dossier */
  var $titre;
  /** Id du dossier parent, NULL si dossier racine */
  var $id_folder_parent;
  /** Description du dossier */
  var $description;
  /** Date d'ajout du dossier */
  var $date_ajout;

  var $date_modif;
  /** Dans le cas du dossier parent, donne l'association à qui est rattaché ce dossier parent, (NULL si section "fichiers").
   * Dans le cas général c'est une méta-donnée informant si l'association liée.
   */
  var $id_asso;

  /** Charge un dossier par son ID
   * @param $id ID du dossier
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `d_folder`
        WHERE `id_folder` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");
    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  /** Charge un dossier par son ID
   * @param $id_asso Id de l'asso
   */
  function load_root_by_asso ( $id_asso )
  {
    if ( is_null($id_asso) )
      $req = new requete($this->db, "SELECT * FROM `d_folder`
        WHERE `id_asso` IS NULL AND id_folder_parent IS NULL
        LIMIT 1");
    else
      $req = new requete($this->db, "SELECT * FROM `d_folder`
        WHERE `id_asso` = '" . mysql_real_escape_string($id_asso) . "' AND id_folder_parent IS NULL
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function load_or_create_root_by_asso ( &$asso )
  {
    if ( $this->load_root_by_asso($asso->id) )
      return true;

    $this->id_groupe_admin = $asso->get_bureau_group_id();
    $this->id_groupe = $asso->get_membres_group_id();
    $this->droits_acces = 0xDDD;
    $this->id_utilisateur = null;
    $this->add_folder ( $section, null, null, $asso->id );

    return true;
  }

  /** Charge un dossier par son titre et son dossier parent
   * @param $id_parent Id du dossier parent
   * @param $titre Titre du dossier
   */
  function load_by_titre ( $id_parent, $titre )
  {
    $req = new requete($this->db, "SELECT * FROM `d_folder`
        WHERE `titre_folder` = '" . mysql_real_escape_string($titre) . "' AND id_folder_parent ='".mysql_real_escape_string($id_parent)."'
        LIMIT 1");
    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function load_by_nom_fichier ( $id_parent, $nom_fichier )
  {
    if ( is_null($id_parent) || $id_parent === 0 )
      $req = new requete($this->db, "SELECT * FROM `d_folder` ".
          "WHERE `nom_fichier_folder` = '" . mysql_real_escape_string($nom_fichier) . "' ".
          "AND id_folder_parent IS NULL ".
          "LIMIT 1");
    else
      $req = new requete($this->db, "SELECT * FROM `d_folder` ".
          "WHERE `nom_fichier_folder` = '" . mysql_real_escape_string($nom_fichier) . "' ".
          "AND id_folder_parent ='".mysql_real_escape_string($id_parent)."' ".
          "LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function is_filename_avaible ($filename )
  {
    $req = new requete($this->db, "SELECT id_file FROM `d_file`
        WHERE `nom_fichier_file` = '" . mysql_real_escape_string($filename) . "'
        AND `id_folder` = '" . mysql_real_escape_string($this->id) . "'
        LIMIT 1");

    if ( $req->lines != 0 )
      return false;

    $req = new requete($this->db, "SELECT id_folder FROM `d_folder`
        WHERE `nom_fichier_folder` = '" . mysql_real_escape_string($filename) . "'
        AND `id_folder_parent` = '" . mysql_real_escape_string($this->id) . "'
        LIMIT 1");

    if ( $req->lines != 0 )
      return false;

    return true;
  }

  function get_child_by_nom_fichier( $filename )
  {
    $ent = new dfolder($this->db,$this->dbrw);
    if ( $ent->load_by_nom_fichier($this->id,$filename) )
      return $ent;

    $ent = new dfile($this->db,$this->dbrw);
    if ( $ent->load_by_nom_fichier($this->id,$filename) )
      return $ent;

    return null;
  }

  /**
   * Charge un dossier d'après une ligne de resultat SQL.
   * @param $row Ligne SQL
   */
  function _load ( $row )
  {
    $this->id = $row['id_folder'];
    $this->titre = $row['titre_folder'];
    $this->nom_fichier = $row['nom_fichier_folder'];
    $this->id_folder_parent = $row['id_folder_parent'];
    $this->description = $row['description_folder'];
    $this->date_ajout = strtotime($row['date_ajout_folder']);
    $this->date_modif = strtotime($row['date_modif_folder']);
    $this->id_asso = $row['id_asso'];

    $this->id_utilisateur = $row['id_utilisateur'];
    $this->id_groupe = $row['id_groupe'];
    $this->id_groupe_admin = $row['id_groupe_admin'];
    $this->droits_acces = $row['droits_acces_folder'];
    $this->modere = $row['modere_folder'];

    $this->auto_moderated = $row['auto_moderated'];
  }

  /**
   * Ajoute un dossier.
   * Vous DEVEZ avoir fait appel à herit et set_rights avant !
   * @param $titre Titre du dossier
   * @param $id_folder_parent Id du dossier parent (NULL si aucun)
   * @param $description Description (NULL si aucune)
   * @param $id_asso Association lié ou racine (NULL si aucune)
   */
  function add_folder ( $titre, $id_folder_parent, $description, $id_asso )
  {
    $this->titre = $titre;
    $this->id_folder_parent = $id_folder_parent;
    $this->description = $description;
    $this->id_asso = $id_asso;
    $this->date_ajout = time();
    $this->date_modif = time();
    $this->modere=(is_null($id_folder_parent) && !is_null($id_asso))?true:false;
    $this->auto_modere();

    $parent = $this->get_parent ();
    if (!is_null ($parent))
      $am = $parent->auto_moderated;
    else
      $am = 0;

    $this->_compute_nom_fichier();

    $sql = new insert ($this->dbrw,
      "d_folder",
      array(
        "titre_folder"=>$this->titre,
        "nom_fichier_folder"=>$this->nom_fichier,
        "id_folder_parent"=>$this->id_folder_parent,
        "description_folder"=>$this->description,
        "date_ajout_folder"=>date("Y-m-d H:i:s",$this->date_ajout),
        "date_modif_folder"=>date("Y-m-d H:i:s",$this->date_modif),
        "id_asso"=>$this->id_asso,
        "id_utilisateur"=>$this->id_utilisateur,
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_folder"=>$this->droits_acces,
        "modere_folder"=>$this->modere,
        "auto_moderated"=>$am
        )
      );
    if ( $sql )
      $this->id = $sql->get_id();
    else
    {
      $this->id = null;
      return;
    }
  }

  /**
   * met à jour les informations d'un dossier.
   * @param $titre Titre du dossier
   * @param $description Description (NULL si aucune)
   * @param $id_asso Association lié ou racine (NULL si aucune)
   */
  function update_folder ( $titre, $description, $id_asso )
  {
    $this->titre = $titre;
    $this->description = $description;
    $this->id_asso = $id_asso;

    $this->_compute_nom_fichier();


    $sql = new update ($this->dbrw,
      "d_folder",
      array(
        "titre_folder"=>$this->titre,
        "nom_fichier_folder"=>$this->nom_fichier,
        "description_folder"=>$this->description,
        "id_asso"=>$this->id_asso,

        "id_utilisateur"=>$this->id_utilisateur,
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_folder"=>$this->droits_acces,
        ),
      array("id_folder"=>$this->id)
      );

  }

  function create_copy_of ( &$source, $id_parent, $new_nom_fichier=null, $depth=-1 )
  {
    // 1- On s'assure que le copie ne vas pas se faire dans un dossier fils de la source
    $pfolder = new dfolder($this->db);
    $pfolder->load_by_id($id_folder);

    while ( $pfolder->is_valid() )
    {
      if ( $pfolder->id == $source->id ) return false; // On ne peut copier un dossier dans un dossier fils ou dans lui même
      $pfolder->load_by_id($pfolder->id_folder_parent);
    }

    // 2- Création du dossier
    $this->id_utilisateur = $source->id_utilisateur;
    $this->id_groupe = $source->id_groupe;
    $this->id_groupe_admin = $source->id_groupe_admin;
    $this->droits_acces = $source->droits_acces;
    $this->add_folder ( is_null($new_nom_fichier)?$source->titre:$new_nom_fichier, $id_parent, $source->description, $source->id_asso );

    if ( $depth == 0 )
      return true;

    if ( $depth > 0 )
      $depth--;

    // 3- Copie des sous-dossiers
    $fd = new dfolder($this->db);
    $nfd = new dfolder($this->db,$this->dbrw);
    $req = new requete($this->db,"SELECT * " .
        "FROM d_folder " .
        "WHERE " .
        "id_folder_parent='".$source->id."'");
    if ( $req->lines > 0 )
      while($row = $req->get_row())
      {
        $fd->_load($row);
        if ( !$nfd->create_copy_of($fd,$this->id,null,$depth) )
          return false;
      }

    // 4- Copie des fichiers
    $fl = new dfile($this->db);
    $nfl = new dfile($this->db,$this->dbrw);
    $req = new requete($this->db,"SELECT * " .
        "FROM d_file " .
        "WHERE " .
        "id_folder='".$source->id."'");
    if ( $req->lines > 0 )
      while($row = $req->get_row())
      {
        $fl->_load($row);
        $nfl->create_copy_of($fl,$this->id);
      }

    return true;
  }

  /**
   * Deplace le fichier dans un autre dossier
   * @param $id_folder Titre du dossier
   */
  function move_to ( $id_folder, $new_nom_fichier=null, $force = false )
  {
    if ( is_null($this->id_folder_parent) )
      return false;

    $pfolder = new dfolder($this->db);
    $pfolder->load_by_id($id_folder);

    $parent = $this->get_parent ();

    while ( $pfolder->is_valid() )
    {
      if ( $pfolder->id == $this->id ) return false; // On ne peut deplacer un dossier dans un dossier fils ou dans lui même
      $pfolder->load_by_id($pfolder->id_folder_parent);
    }

    $this->id_folder_parent = $id_folder;

    if ( !is_null($new_nom_fichier) )
      $this->titre = $new_nom_fichier;

    $this->_compute_nom_fichier();

    $sql = new update ($this->dbrw,
      "d_folder",
      array(
      "titre_folder"=>$this->titre,
      "nom_fichier_folder"=>$this->nom_fichier,
      "id_folder_parent"=>$this->id_folder_parent),
      array("id_folder"=>$this->id)
      );

    if (!is_null ($parent))
      if ($parent->auto_moderated && !$force) {
        $this->set_modere (false);
        $this->auto_modere ();
      }

    return true;
  }

  function _compute_nom_fichier()
  {
    if ( is_null($this->id_folder_parent) )
    {
      if ( is_null($this->id_asso) )
        $filename="public";
      else
      {
        $a = new asso($this->db);
        $a->load_by_id($this->id_asso);
        $filename = $a->nom_unix;
      }
    }
    else
      $filename = preg_replace("`([//\\\\\\:\\*\\?\"<>\\|]+)`", "", $this->titre);

    $this->nom_fichier= $this->get_free_filename($this->id_folder_parent,$filename,null,$this->id);
  }

  /** Liste les sous-dossiers que l'utilisateur peut voir
   * @param $user Instance de utilisateur
   * @param $select Champs SQL à récupéré
   * @return Une instance de requete avec les resultats
   */
  function get_folders ( $user, $select="*")
  {
    if ( is_null($this->id) || $this->id === 0 )
      $p="id_folder_parent IS NULL";
    else
      $p="id_folder_parent='".$this->id."'";

    if ( $this->is_admin( $user ) )
      return new requete($this->db,"SELECT $select " .
        "FROM d_folder " .
        "WHERE " .
        "$p " .
        "ORDER BY `titre_folder`");

    elseif ( !$user->is_valid() )
      return new requete($this->db,"SELECT $select " .
        "FROM d_folder " .
        "WHERE " .
        "$p AND " .
        "(droits_acces_folder & 0x1) " .
        "AND modere_folder='1' " .
        "ORDER BY `titre_folder`");

    else
      return new requete($this->db,"SELECT $select " .
        "FROM d_folder " .
        "WHERE " .
        "$p AND " .
        "((" .
          "(" .
            "(droits_acces_folder & 0x1) OR " .
            "((droits_acces_folder & 0x10) AND id_groupe IN (".$user->get_groups_csv()."))" .
          ") " .
          "AND modere_folder='1'" .
        ") OR " .
        "(id_groupe_admin IN (".$user->get_groups_csv().")) OR " .
        "((droits_acces_folder & 0x100) AND id_utilisateur='".$user->id."')) " .
        "ORDER BY `titre_folder`");

  }

  /** Liste les fichiers contenus dans le dossier que l'utilisateur peut voir
   * @param $user Instance de utilisateur
   * @param $select Champs SQL à récupéré
   * @return Une instance de requete avec les resultats
   */
  function get_files ( $user, $select="*")
  {
    if ( $this->is_admin( $user ) )
      return new requete($this->db,"SELECT $select " .
        "FROM d_file " .
        "WHERE " .
        "id_folder='".$this->id."' " .
        "ORDER BY `titre_file`");

    elseif ( !$user->is_valid() )
      return new requete($this->db,"SELECT $select " .
        "FROM d_file " .
        "WHERE " .
        "id_folder='".$this->id."' AND " .
        "(droits_acces_file & 0x1) " .
        "AND modere_file='1' " .
        "ORDER BY `titre_file`");

    else
      return new requete($this->db,"SELECT $select " .
        "FROM d_file " .
        "WHERE " .
        "id_folder='".$this->id."' AND " .
        "((" .
          "(" .
            "((droits_acces_file & 0x1) OR " .
            "((droits_acces_file & 0x10) AND id_groupe IN (".$user->get_groups_csv().")))" .
          ") " .
          "AND modere_file='1'" .
        ") OR " .
        "(id_groupe_admin IN (".$user->get_groups_csv().")) OR " .
        "((droits_acces_file & 0x100) AND id_utilisateur='".$user->id."')) " .
        "ORDER BY `titre_file`");

  }

  /**
   * Définit le status de modération du dossier
   * @param $modere true=modéré, false=non modéré
   */
  function set_modere($modere=true)
  {
    $this->modere=$modere;
    $sql = new update($this->dbrw,"d_folder",array("modere_folder"=>$this->modere),array("id_folder"=>$this->id));
  }

  function set_auto_moderated ($modere) {
    $this->auto_moderated = $modere;
    new update ($this->dbrw,"d_folder",array("auto_moderated"=>$modere),array("id_folder"=>$this->id));
  }

  /**
   * Supprime le dossier, ses sous-dossiers et ses fichiers
   */
  function delete_folder()
  {
    $fd = new dfolder($this->db,$this->dbrw);
    $req = new requete($this->db,"SELECT * " .
        "FROM d_folder " .
        "WHERE " .
        "id_folder_parent='".$this->id."'");

    if ( $req->lines > 0 )
      while($row = $req->get_row())
      {
        $fd->_load($row);
        $fd->delete_folder();
      }

    $fl = new dfile($this->db,$this->dbrw);
    $req = new requete($this->db,"SELECT * " .
        "FROM d_file " .
        "WHERE " .
        "id_folder='".$this->id."'");
    if ( $req->lines > 0 )
      while($row = $req->get_row())
      {
        $fl->_load($row);
        $fl->delete_file();
      }
    $sql = new delete($this->dbrw,"d_folder",array("id_folder"=>$this->id));
  }

  function delete()
  {
    $this->delete_folder();
  }

  function create_or_load ( $path, $id_asso=null )
  {

    $this->load_root_by_asso ( $id_asso );

    if ( !$this->is_valid() )
    {
      $this->id_groupe_admin = $asso->id + 20000; // asso-bureau
      $this->id_groupe = $asso->id + 30000; // asso-membres
      $this->droits_acces = 0xDDD;
      $this->id_utilisateur = null;
      $this->add_folder ( "Fichiers", null, null, $id_asso );
    }

    $tokens = explode("/",$path);

    $id_parent = $this->id;

    foreach( $tokens as $titre )
    {
      $this->load_by_titre ( $id_parent, $titre );
      if ( !$this->is_valid() )
      {
        $this->herit($this);
        $this->add_folder ( $titre, $id_parent, "", $id_asso );
      }
      $id_parent = $this->id;
    }
  }

  function create_or_load_asso ( $path, &$asso )
  {
    $this->load_or_create_root_by_asso ( $asso );

    $tokens = explode("/",$path);

    $id_parent = $this->id;

    foreach( $tokens as $titre )
    {
      $this->load_by_titre ( $id_parent, $titre );
      if ( !$this->is_valid() )
      {
        $this->herit($this);
        $this->add_folder ( $titre, $id_parent, "", $id_asso );
      }
      $id_parent = $this->id;
    }
  }

  function get_root_element()
  {
    $folder = new dfolder($this->db);
    $folder->load_root_by_asso(null);
    return $folder;
  }

  function get_parent()
  {
    if ( is_null($this->id_folder_parent) )
      return null;

    $folder = new dfolder($this->db);
    $folder->load_by_id($this->id_folder_parent);
    return $folder;
  }

  function get_childs(&$user)
  {
    $childs = array();

    $req = $this->get_folders ( $user);
    while ( $row = $req->get_row() )
    {
      $child = new dfolder($this->db);
      $child->_load($row);
      $childs[]=$child;
    }

    $req = $this->get_files ( $user);
    while ( $row = $req->get_row() )
    {
      $child = new dfile($this->db);
      $child->_load($row);
      $childs[]=$child;
    }

    return $childs;
  }

  function can_explore()
  {
    return true;
  }

  function is_admin ( &$user )
  {
    if ( $user->is_in_group("gestion_ae") )
      return true;

    return parent::is_admin($user);
  }

  /**
   * Procède à l'auto-modération du dossier si possible.
   * L'auto modération est possible sur les fichiers à accès "restreint".
   */
  function auto_modere()
  {
    if ( $this->modere )
      return;

    if ((DROIT_LECTURE & ($this->droits_acces)) == DROIT_LECTURE)
      return;

    $parent = $this->get_parent ();
    if (is_null ($parent));
      return;

    if (!$parent->auto_moderated)
      return;

    $this->modere = true;
  }


}

?>
