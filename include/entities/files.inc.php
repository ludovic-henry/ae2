<?php
/* Copyright 2006,2007
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
 * @file Gestion des fichier des repertoires virtuels (partie téléchargement).
 */
require_once($topdir."include/entities/fs.inc.php");
require_once($topdir."include/entities/folder.inc.php");


/**
 * @defgroup aedrive Section fichiers
 *
 */

/**
 * Classe de gestion des fichiers des repertoires virtuels
 * @ingroup aedrive
 * @author Julien Etelain
 */
class dfile extends fs
{

  /** Nom du fichier avec l'extention, c'est ce qui sera communiqué au navigateur lors du téléchargement */
  var $nom_fichier;
  /** Titre du fichier, c'est ce qui sera affiché */
  var $titre;
  /** Id du dossier dans le quel le fichier se trouve */
  var $id_folder;
  var $id_folder_parent; // alias pour simplicité

  /** Description du fichier */
  var $description;
  /** date de l'ajout du fichier */
  var $date_ajout;
  /** date de modification du fichier */
  var $date_modif;
  /** Id de l'association lié (méta-donnée) (n'a pas de rapport avec l'id_asso du dossier racine, @see dfolder)*/
  var $id_asso;
  /** Nombre de téléchargements */
  var $nb_telechargement;
  /** Mime type du fichier */
  var $mime_type;
  /** Taille en octets du fichier */
  var $taille;

  /** ID de la revision en cours */
  var $id_rev_file=0;
  /** utilisateur ayant fait cette révision */
  var $id_utilisateur_rev_file;
  /** Date de la revision */
  var $date_rev_file;


  /**
   * Charge un fichier par son ID
   * @param $id ID du fichier
   * @return false en cas d'erreur, sinon true
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `d_file` ".
        "INNER JOIN d_file_rev ON ".
          "(d_file.id_file=d_file_rev.id_file ".
          "AND d_file_rev.id_rev_file=d_file.id_rev_file_last ) ".
        "WHERE d_file.`id_file` = '" . mysql_real_escape_string($id) . "' ".
        "LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  /**
   * Charge un fichier par son ID et sa revision
   * @param $id ID du fichier
  * @param $rev Revision du fichier
   * @return false en cas d'erreur, sinon true
   */
  function load_by_id_and_rev ( $id, $rev )
  {
    $req = new requete($this->db, "SELECT * FROM `d_file` ".
        "INNER JOIN d_file_rev ON ".
          "(d_file.id_file=d_file_rev.id_file ".
          "AND d_file_rev.id_rev_file='".mysql_real_escape_string($rev)."' ) ".
        "WHERE d_file.`id_file` = '".mysql_real_escape_string($id)."' ".
        "LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  /**
   * Charge un fichier par son nom "logique"
   * @param $id_parent Id du dossier parent
   * @param $nom_fichier Nom du fichier
   * @return false en cas d'erreur, sinon true
   */
  function load_by_nom_fichier ( $id_parent, $nom_fichier )
  {
    $req = new requete($this->db, "SELECT * FROM `d_file` ".
        "INNER JOIN d_file_rev ON ".
          "(d_file.id_file=d_file_rev.id_file ".
          "AND d_file_rev.id_rev_file=d_file.id_rev_file_last ) ".
        "WHERE `nom_fichier_file` = '" . mysql_real_escape_string($nom_fichier) . "' ".
        "AND id_folder ='".mysql_real_escape_string($id_parent)."' ".
        "LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }
  /**
   * Charge un fichier d'après une ligne de resultat SQL
   */
  function _load ( $row )
  {
    // File
    $this->id = $row['id_file'];
    $this->nom_fichier = $row['nom_fichier_file'];
    $this->titre = $row['titre_file'];
    $this->id_folder = $row['id_folder'];
    $this->id_folder_parent = $row['id_folder'];
    $this->description = $row['description_file'];
    $this->date_ajout = strtotime($row['date_ajout_file']);
    $this->date_modif = strtotime($row['date_modif_file']);
    $this->id_asso = $row['id_asso'];
    $this->nb_telechargement = $row['nb_telechargement_file'];
    /*$this->mime_type = $row['mime_type_file'];
    $this->taille = $row['taille_file'];*/

    // BaseDB
    $this->id_utilisateur = $row['id_utilisateur'];
    $this->id_groupe = $row['id_groupe'];
    $this->id_groupe_admin = $row['id_groupe_admin'];
    $this->droits_acces = $row['droits_acces_file'];
    $this->modere = $row['modere_file'];

    // Revision
    $this->id_rev_file = $row['id_rev_file'];
    $this->id_utilisateur_rev_file = $row['id_utilisateur_rev_file'];
    $this->date_rev_file = $row['date_rev_file'];
    $this->mime_type = $row['mime_type_rev_file'];
    $this->taille = $row['filesize_rev_file'];

  }

  /**
   * Ajoute un fichier.
   * Vous DEVEZ avoir fait appel à herit et set_rights avant !
   * @param $file Un élément de $_FILES
   * @param $titre Titre du dossier
   * @param $id_folder Id du dossier parent (NULL si aucun)
   * @param $description Description (NULL si aucune)
   * @param $id_asso Association lié (NULL si aucune)
   */
  function add_file ( $file, $titre, $id_folder, $description, $id_asso )
  {
    if ( !is_uploaded_file($file['tmp_name']) )
      return;

    $this->titre = $titre;
    $this->id_folder = $id_folder;
    $this->description = $description;
    $this->id_asso = $id_asso;
    $this->date_ajout = time();
    $this->date_modif = time();
    $this->modere=false;
    $this->auto_modere();

    $this->nom_fichier= $this->get_free_filename($id_folder,$file['name']);
    $this->taille=$file['size'];
    $this->mime_type=$file['type']; // ou mime_content_type($file['tmp_name']);

    $this->nb_telechargement=0;

    $sql = new insert ($this->dbrw,
      "d_file",
      array(
        "titre_file"=>$this->titre,
        "id_folder"=>$this->id_folder,
        "description_file"=>$this->description,
        "date_ajout_file"=>date("Y-m-d H:i:s",$this->date_ajout),
        "date_modif_file"=>date("Y-m-d H:i:s",$this->date_modif),
        "id_asso"=>$this->id_asso,

        "nom_fichier_file"=>$this->nom_fichier,
        "taille_file"=>$this->taille,
        "mime_type_file"=>$this->mime_type,
        "nb_telechargement_file"=>$this->nb_telechargement,

        "id_utilisateur"=>$this->id_utilisateur,
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_file"=>$this->droits_acces,
        "modere_file"=>$this->modere
        )
      );

    if ( $sql )
      $this->id = $sql->get_id();
    else
    {
      $this->id = null;
      return;
    }

    $this->_new_revision ( $this->id_utilisateur, $file['size'], $file['type'], "Created" );

    move_uploaded_file ( $file['tmp_name'], $this->get_real_filename() );
    $this->generate_thumbs();

  }

  /**
   * Ajoute un fichier.
   * Vous DEVEZ avoir fait appel à herit et set_rights avant !
   * @param $localfile Un fichier local
   * @param $filename Nom de fichier
   * @param $filesize Taille en octets
   * @param $mime_type Type MIME du contenu
   * @param $dateajout Date d'ajout
   * @param $modere Modéré
   * @param $nbdownlds Nombre de téléchargements
   * @param $titre Titre du dossier
   * @param $id_folder Id du dossier parent (NULL si aucun)
   * @param $description Description (NULL si aucune)
   * @param $id_asso Association lié (NULL si aucune)
   */
  function import_file ( $localfile, $filename, $filesize, $mime_type, $dateajout, $modere, $nbdownlds, $titre, $id_folder, $description, $id_asso )
  {


    $this->titre = $titre;
    $this->id_folder = $id_folder;
    $this->id_folder_parent = $id_folder;
    $this->description = $description;
    $this->id_asso = $id_asso;
    $this->date_ajout = $dateajout;
    $this->date_modif = $dateajout;
    $this->modere=$modere;
    $this->auto_modere();

    $this->nom_fichier= $this->get_free_filename($id_folder,$filename);
    $this->taille=$filesize;
    $this->mime_type=$mime_type; // ou mime_content_type($file['tmp_name']);

    $this->nb_telechargement=$nbdownlds;

    $sql = new insert ($this->dbrw,
      "d_file",
      array(
        "titre_file"=>$this->titre,
        "id_folder"=>$this->id_folder,
        "description_file"=>$this->description,
        "date_ajout_file"=>date("Y-m-d H:i:s",$this->date_ajout),
        "date_modif_file"=>date("Y-m-d H:i:s",$this->date_modif),
        "id_asso"=>$this->id_asso,

        "nom_fichier_file"=>$this->nom_fichier,
        "taille_file"=>$this->taille,
        "mime_type_file"=>$this->mime_type,
        "nb_telechargement_file"=>$this->nb_telechargement,

        "id_utilisateur"=>$this->id_utilisateur,
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_file"=>$this->droits_acces,
        "modere_file"=>$this->modere
        )
      );

    if ( $sql )
      $this->id = $sql->get_id();
    else
    {
      $this->id = null;
      return;
    }

    $this->_new_revision ( $this->id_utilisateur, $filesize, $mime_type, "Import/Copy" );

    copy ( $localfile, $this->get_real_filename() );

    $this->generate_thumbs();

  }

  /**
   * Génère, si possible, les miniatures
   *
   */
  function generate_thumbs()
  {
    if ( ereg("image/(.*)",$this->mime_type) )
    {
      $f = $this->get_thumb_filename();

      if ( file_exists($f) )
        unlink($f);

      exec(escapeshellcmd("/usr/share/php5/exec/convert ".$this->get_real_filename()." -thumbnail 128x128 -quality 95 $f"));

      $f = $this->get_screensize_filename();

      if ( file_exists($f) )
        unlink($f);

      exec(escapeshellcmd("/usr/share/php5/exec/convert ".$this->get_real_filename()." -thumbnail 500x1000 -quality 95 $f"));
    }
  }


  /**
   * Met à jour les informations d'un fichier.
   * @param $titre Titre du dossier
   * @param $description Description (NULL si aucune)
   * @param $id_asso Association lié (NULL si aucune)
   */
  function update_file ( $titre, $description, $id_asso )
  {

    $this->titre = $titre;
    $this->description = $description;
    $this->id_asso = $id_asso;

    $sql = new update ($this->dbrw,
      "d_file",
      array(
        "titre_file"=>$this->titre,
        "description_file"=>$this->description,
        "id_asso"=>$this->id_asso,
        "id_utilisateur"=>$this->id_utilisateur,
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_file"=>$this->droits_acces
        ),
      array("id_file"=>$this->id)
      );

  }

  /**
   * Insère une nouvelle révision du fichier à partir d'un élément de $_FILES
   * Fonction à usage interne
   * @param $id_utilisateur Utilisateur faisant la révision
   * @param $filesize Taille du nouveau fichier
   * @param $mime_type Type MIME du nouveau fichier
   * @param $comment Commentaire
   */
  function _new_revision ( $id_utilisateur, $filesize, $mime_type, $comment="" )
  {
    $this->id_utilisateur_rev_file = $id_utilisateur;
    $this->date_rev_file = time();
    $this->taille=$filesize;
    $this->mime_type=$mime_type;

    $sql = new insert($this->dbrw, "d_file_rev", array(
      "id_file"=>$this->id,
      "id_utilisateur_rev_file"=>$this->id_utilisateur_rev_file,
      "date_rev_file"=>date("Y-m-d H:i:s",$this->date_rev_file),
      "filesize_rev_file"=>$this->taille,
      "mime_type_rev_file"=>$this->mime_type,
      "comment_rev_file"=>$comment));

    $this->id_rev_file = $sql->get_id();
    $this->date_modif=time();
    $this->modere=false;
    $this->auto_modere();

    $sql = new update ($this->dbrw,
      "d_file",
      array(
        "mime_type_file"=>$this->mime_type, // LEGACY SUPPORT
        "date_modif_file"=>date("Y-m-d H:i:s",$this->date_modif),
        "taille_file"=>$this->taille, // LEGACY SUPPORT
        "nb_telechargement_file"=>$this->nb_telechargement,
        "modere_file"=>$this->modere,
        "id_rev_file_last"=>$this->id_rev_file
        ),
      array("id_file"=>$this->id)
      );

  }

  /**
   * Insère une nouvelle révision du fichier à partir d'un élément de $_FILES
   * @param $file Element de $_FILES
   * @param $user Utilisateur faisant la révision
   * @param $comment Commentaire
   * @return false en cas d'erreur, sinon true
   */
  function new_revision ( &$file, &$user, $comment="" )
  {
    if ( $this->is_locked($user) )
      return false;

    if ( !is_uploaded_file($file['tmp_name']) )
      return false;

    $this->_new_revision($user->id,$file['size'],$file['type'],$comment);

    move_uploaded_file ( $file['tmp_name'], $this->get_real_filename() );
    $this->generate_thumbs();
    return true;
  }


  function create_copy_of ( &$source, $id_parent, $new_nom_fichier=null, $depth=0 )
  {
    $this->id_utilisateur = $source->id_utilisateur;
    $this->id_groupe = $source->id_groupe;
    $this->id_groupe_admin = $source->id_groupe_admin;
    $this->droits_acces = $source->droits_acces;
    $this->import_file (
      $source->get_real_filename(),
      is_null($new_nom_fichier)?$source->nom_fichier:$new_nom_fichier,
      $source->taille,
      $source->mime_type,
      time(),
      0,
      0,
      $source->titre,
      $id_parent,
      $source->description,
      $source->id_asso );
    return true;
  }

  function create_empty ( $id_folder, $filename, $filesize, $mime_type )
  {
    $this->titre = $filename;
    $this->id_folder = $id_folder;
    $this->description = "";
    $this->date_ajout = time();
    $this->date_modif = time();
    $this->modere=false;
    $this->auto_modere();
    $this->nom_fichier= $this->get_free_filename($id_folder,$filename);
    $this->taille=$filesize;
    $this->mime_type=$mime_type; // ou mime_content_type($file['tmp_name']);

    $this->nb_telechargement=0;

    $sql = new insert ($this->dbrw,
      "d_file",
      array(
        "titre_file"=>$this->titre,
        "id_folder"=>$this->id_folder,
        "description_file"=>$this->description,
        "date_ajout_file"=>date("Y-m-d H:i:s",$this->date_ajout),
        "date_modif_file"=>date("Y-m-d H:i:s",$this->date_modif),
        "id_asso"=>$this->id_asso,

        "nom_fichier_file"=>$this->nom_fichier,
        "taille_file"=>$this->taille,
        "mime_type_file"=>$this->mime_type,
        "nb_telechargement_file"=>$this->nb_telechargement,

        "id_utilisateur"=>$this->id_utilisateur,
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_file"=>$this->droits_acces,
        "modere_file"=>$this->modere
        )
      );

    if ( $sql )
      $this->id = $sql->get_id();
    else
    {
      $this->id = null;
      return;
    }

    $this->_new_revision ( $this->id_utilisateur, $filesize, $mime_type, "Created" );

  }



  /**
   * Deplace le fichier dans un autre dossier
   * @param $id_folder Titre du dossier
   */
  function move_to ( $id_folder, $new_nom_fichier=null, $force = false )
  {
    $parent = $this->get_parent ();

    $this->id_folder = $id_folder;
    $this->id_folder_parent = $id_folder;

    if ( is_null($new_nom_fichier) )
      $this->nom_fichier= $this->get_free_filename($id_folder,$this->nom_fichier);
    else
      $this->nom_fichier= $this->get_free_filename($id_folder,$new_nom_fichier);

    $sql = new update ($this->dbrw,
      "d_file",
      array("nom_fichier_file"=>$this->nom_fichier,"id_folder"=>$this->id_folder),
      array("id_file"=>$this->id)
      );

    if (!is_null ($parent))
      if ($parent->auto_moderated && !$force) {
        $this->set_modere (false);
        $this->auto_modere ();
      }

    return true;
  }

  /**
   * Donne le nom du fichier sur le serveur.
   * Les fichiers ne doivent pas être accessibles depuis l'exterieur.
   */
  function get_real_filename()
  {
    global $topdir;
    return $topdir."data/files/".$this->id.".".$this->id_rev_file;
  }

  /**
   * Donne le nom de l'aperçu sur le serveur.
   * Les fichiers ne doivent pas être accessibles depuis l'exterieur.
   */
  function get_thumb_filename()
  {
    global $topdir;
    if( $this->mime_type=="image/png" )
      return $topdir."data/files/thumb/".$this->id.".png";
    else
      return $topdir."data/files/thumb/".$this->id.".jpg";
  }

  /**
   * Donne l'url du fichier.
   */
  function get_url()
  {
    global $wwwtopdir;
    return $wwwtopdir."d.php?id_file=".$this->id."&amp;action=download";
  }
  /**
   * Donne l'url de l'aperçu.
   */
  function get_preview_url()
  {
    global $wwwtopdir;
    return $wwwtopdir."d.php?id_file=".$this->id."&amp;action=download&amp;download=preview";
  }
  /**
   * Donne l'url de l'aperçu.
   */
  function get_thumb_url()
  {
    global $wwwtopdir;
    return $wwwtopdir."d.php?id_file=".$this->id."&amp;action=download&amp;download=thumb";
  }

  /**
   * Donne le nom de la version écran sur le serveur.
   * Les fichiers ne doivent pas être accessibles depuis l'exterieur.
   */
  function get_screensize_filename()
  {
    global $topdir;
    if( $this->mime_type=="image/png" )
      return $topdir."data/files/preview/".$this->id.".png";
    else
      return $topdir."data/files/preview/".$this->id.".jpg";
  }

  /**
   * Donne le nom de l'iconne à utiliser pour le fichier
   */
  function get_icon_name()
  {
    if ( ereg("image/(.*)",$this->mime_type) ) return "image.png";

    if ( ereg("video/(.*)",$this->mime_type) ) return "video.png";

    if ( ereg("audio/(.*)",$this->mime_type) ) return "sound.png";

    if ( $this->mime_type == "application/pdf" || $this->mime_type == "application/x-pdf" )
      return "pdf.png";

    if ( $this->mime_type == "text/richtext" || $this->mime_type == "application/msword"
        || $this->mime_type == "application/vnd.oasis.opendocument.text" )
      return "doc.png";

    if ( ereg("text/(.*)",$this->mime_type) ) return "txt.png";

    return "file.png";
  }

  /** Extention de basedb, informe que le fichier n'est pas une catégorie (comportement par défaut).
   * @return false
   */
  function is_category()
  {
    return false;
  }

  /**
   * Définit le status de modération du fichier
   * @param $modere true=modéré, false=non modéré
   */
  function set_modere($modere=true)
  {
    $this->modere=$modere;
    $sql = new update($this->dbrw,"d_file",array("modere_file"=>$this->modere),array("id_file"=>$this->id));
  }

  /**
   * Supprime le fichier
   */
  function delete_file()
  {
    $req = new requete($this->db,"SELECT id_rev_file ".
      "FROM d_file_rev ".
      "WHERE id_file='".mysql_real_escape_string($this->id)."'");
    while ( $row = $req->get_row() )
    {
      $this->id_rev_file = $row["id_rev_file"];
      $f = $this->get_real_filename();
      if ( file_exists($f))
        unlink($f);
    }
    $f = $this->get_thumb_filename();
    if ( file_exists($f))
      unlink($f);

    $f = $this->get_screensize_filename();
    if ( file_exists($f))
      unlink($f);

    $this->set_tags_array(array());

    new delete($this->dbrw,"d_file",array("id_file"=>$this->id));
    new delete($this->dbrw,"d_file_lock",array("id_file"=>$this->id));
    new delete($this->dbrw,"d_file_rev",array("id_file"=>$this->id));
  }

  /**
   * Supprime la révision du fichier
   */
  function delete_file_rev()
  {
    $req = new requete($this->db,"SELECT id_rev_file ".
      "FROM d_file_rev ".
      "WHERE id_file='".mysql_real_escape_string($this->id)."'");

    if ($req->lines <= 1)
      return $this->delete_file();

    new delete($this->dbrw,"d_file_rev",array("id_file"=>$this->id, "id_rev_file"=>$this->id_rev_file));

    $req = new requete($this->db,"SELECT MAX(id_rev_file) max_id_rev_file ".
      "FROM d_file_rev ".
      "WHERE id_file='".mysql_real_escape_string($this->id)."' ".
      "GROUP BY id_file");

    $row = $req->get_row();

    /* Si le fichier a pu être modifié c'est qu'il était modéré dans sa
     * version précédente (ou que c'est un modérateur qui l'a modifié)
     */
    $this->modere=false;
    $sql = new update ($this->dbrw, "d_file",
      array("id_rev_file_last"=>$row['max_id_rev_file'], "modere_file"=>"1"),
      array("id_file"=>$this->id));

      $f = $this->get_real_filename();
      if ( file_exists($f))
        unlink($f);

    $this->load_by_id($this->id);
    $this->generate_thumbs();
  }

  /**
   * Supprime le fichier. fs support.
   */
  function delete()
  {
    $this->delete_file();
  }

  /**
   * Incremente le compteur de téléchargements
   */
  function increment_download()
  {
    $sql = new requete($this->dbrw,"UPDATE `d_file` SET nb_telechargement_file=nb_telechargement_file+1 WHERE id_file='".$this->id."'");
    $this->nb_telechargement++;
  }

  function get_root_element()
  {
    $folder = new dfolder($this->db);
    $folder->load_root_by_asso(null);
    return $folder;
  }

  function get_parent()
  {
    $folder = new dfolder($this->db);
    $folder->load_by_id($this->id_folder);
    return $folder;
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

  function is_locked(&$user)
  {
    $req = new requete($this->db,
      "SELECT id_utilisateur FROM d_file_lock ".
      "WHERE `id_file` = '" . mysql_real_escape_string($this->id) . "' ".
      "LIMIT 1");

    if ( $req->lines == 0 )
    {
      return false;
    }

    list($uid) = $req->get_row();

    if ( $uid == $user->id )
      return false;

    return $uid;
  }

  function is_moderated() {
    if( isset($this->modere) && $this->modere == 1 )
      return true;
    return false;
  }

  function get_lock()
  {
    $req = new requete($this->db,
      "SELECT id_utilisateur, time_file_lock FROM d_file_lock ".
      "WHERE `id_file` = '" . mysql_real_escape_string($this->id) . "' ".
      "LIMIT 1");

    if ( $req->lines == 0 )
    {
      return false;
    }

    return $req->get_row();
  }

  function lock(&$user)
  {
    $this->unlock($user); // Supprime d'eventuels vieux verrous...
    new insert($this->dbrw,"d_file_lock",
      array(
        "id_file"=>$this->id,
        "id_utilisateur"=>$user->id,
        "time_file_lock"=>date("Y-m-d H:i:s")));
  }

  function unlock(&$user)
  {
    new delete($this->dbrw,"d_file_lock",
      array(
        "id_file"=>$this->id,
        "id_utilisateur"=>$user->id
        ));
  }

  function force_unlock()
  {
    new delete($this->dbrw,"d_file_lock",array("id_file"=>$this->id));
  }


  /**
   * Procède à l'auto-modération du fichier si possible.
   * L'auto modération est possible sur les fichiers à accès "restreint".
   */
  function auto_modere()
  {
    if ( $this->modere )
      return;

    if ( (DROIT_LECTURE & ($this->droits_acces)) == DROIT_LECTURE )
      return;

    $parent = $this->get_parent ();
    if (is_null ($parent))
      return;

    if (!$parent->auto_moderated)
      return;

    $this->modere = true;
  }


}

?>
