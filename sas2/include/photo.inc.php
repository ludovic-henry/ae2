<?php
/* Copyright 2004-2006
 * - Julien Etelain < julien at pmad dot net >
 * - Simon Lopez < simon dot lopez at ayolo dot org >
 * - Benjamin Collet < bcollet at oxynux dot org >
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
require_once($topdir.'/sas2/include/licence.inc.php');
require_once($topdir.'/sas2/include/cat.inc.php');
/**
 * @file
 */

define("MEDIA_PHOTO",0);
define("MEDIA_VIDEOFLV",1);

/**
 * Une photo du SAS
 * @ingroup sas
 * @author Julien Etelain
 * @author Simon Lopez
 * @author Benjamin Collet
 */
class photo extends basedb
{

  var $id_catph;
  var $id_utilisateur_photographe;
  var $date_prise_vue;
  var $commentaire;
  var $titre;
  var $supprime;

  var $incomplet;
  var $propose_incomplet;
  var $droits_acquis;
  var $couleur_moyenne;
  var $classification;

  var $meta_id_asso;
  var $date_ajout;
  var $id_licence;

  var $type_media;
  var $id_asso_photographe;

  /** Charge une photo par son ID
   * @param $id ID de la photo
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `sas_photos`
        WHERE `id_photo` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");
    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function _load($row)
  {
    $this->id = $row['id_photo'];
    $this->id_catph = $row['id_catph'];
    $this->id_utilisateur_photographe = $row['id_utilisateur_photographe'];
    $this->id_utilisateur_moderateur = $row['id_utilisateur_moderateur'];
    if ( is_null($row['date_prise_vue']))
    $this->date_prise_vue = null;
    else
    $this->date_prise_vue = strtotime($row['date_prise_vue']);
    $this->modere = $row['modere_ph'];
    $this->commentaire = $row['commentaire_ph'];
    $this->titre = $row['titre_ph'];
    $this->incomplet = $row['incomplet'];
    $this->propose_incomplet = $row['propose_incomplet'];
    $this->droits_acquis = $row['droits_acquis'];
    $this->couleur_moyenne = $row['couleur_moyenne'];
    $this->classification = $row['classification'];
    $this->supprime = $row['supprime_ph'];

    $this->type_media = $row['type_media_ph'];


    $this->id_utilisateur = $row['id_utilisateur'];
    $this->id_groupe = $row['id_groupe'];
    $this->id_groupe_admin = $row['id_groupe_admin'];
    $this->droits_acces = $row['droits_acces_ph'];

    $this->meta_id_asso = $row['meta_id_asso_ph'];

    $this->date_ajout = strtotime($row['date_ajout_ph']);
    $this->id_asso_photographe = $row['id_asso_photographe'];
    $this->id_licence = $row['id_licence'];

    //exif et puis nah ! :)
    $this->iso = $row["iso"];
    $this->focale = $row["focale"];
    $this->ouverture=$row["ouverture"];
    $this->flash = $row["flash"];
    $this->exposuretime = $row["exposuretime"];
    $this->aperture = $row["aperture"];
    $this->manufacturer = $row["manufacturer"];
    $this->model = $row["model"];

  }

  /**
   * Obtient le chemin absolu où est/sera stocké la photo
   */
  function get_abs_path ( )
  {
    return "/var/www/ae2/data/sas/".date("Y/m/d",$this->date_prise_vue)."/";
  }

  /**
   * Verifie si tous les droits sont acquis, et en cas de changement met à jour la base de donnés.
   */
  function update_droits_acquis ()
  {
    $oldvalue = $this->droits_acquis;

    if ( $this->incomplet )
      $this->droits_acquis = false;
    else
    {
      $req = new requete($this->db,
        "SELECT COUNT(*) " .
        "FROM `sas_personnes_photos` " .
        "WHERE `sas_personnes_photos`.`id_photo`='".$this->id."' AND `accord_phutl`='0' AND `modere_phutl`='1' " .
        "ORDER BY `nom_utilisateur`");

      list($nb_naccord) = $req->get_row();

      $this->droits_acquis = ($nb_naccord==0);
    }

    if ( $oldvalue != $this->droits_acquis )
    {
      $req = new update($this->dbrw, "sas_photos",
            array("droits_acquis"=>$this->droits_acquis),
            array("id_photo"=>$this->id));
    }
  }

  /**
   * Verifie si le repertoire de stockage de la photo existe, sinon le crée.
   */
  function make_path ( )
  {
    if ( !is_dir(  "/var/www/ae2/data/sas/".date("Y",$this->date_prise_vue)."/") )
      mkdir( "/var/www/ae2/data/sas/".date("Y",$this->date_prise_vue)."/");

    if ( !is_dir(  "/var/www/ae2/data/sas/".date("Y/m",$this->date_prise_vue)."/") )
      mkdir( "/var/www/ae2/data/sas/".date("Y/m",$this->date_prise_vue)."/");

    if ( !is_dir(  "/var/www/ae2/data/sas/".date("Y/m/d",$this->date_prise_vue)."/") )
      mkdir( "/var/www/ae2/data/sas/".date("Y/m/d",$this->date_prise_vue)."/");

  }

  /**
   * Ajoute une photo.
   * Vous DEVEZ avoir fait appel à herit et set_rights avant !
   * @param $tmp_filename Fichier temporaire source (ne sera pas modifié ni supprimé)
   * @param $id_catph Id de la catégorie
   * @param $commentaire Commentaire (facultatif)
   * @param $id_utilisateur_photographe Id du photographe (facultatif)
   * @param $nobody S'il n'y a personne sur la photo (si true alors incomplet=false,droits_acquis=true)
   */
  function add_photo (  $tmp_filename
                      , $id_catph
                      , $commentaire=""
                      , $id_utilisateur_photographe=null
                      , $nobody=false
                      , $meta_id_asso=NULL
                      , $titre=NULL
                      , $id_asso_photographe=NULL
                      , $id_licence=null)
  {
    $this->date_prise_vue=null;
    $this->iso=0;
    $this->focale=0;
    $this->ouverture=0;
    $this->exposuretime=0;
    $this->flash=-1;
    $this->aperture=0;
    $this->manufacturer=null;
    $this->model=null;
    $this->id_licence=$id_licence;

    $exif = @exif_read_data($tmp_filename, "IFDO", true);
    if ( $exif )
    {
      //EXIF
      if(isset($exif["EXIF"]))
      {
        $EXIF=$exif["EXIF"];
        // Date
        if ( $EXIF["DateTimeOriginal"] )
          $this->date_prise_vue = datetime_to_timestamp($EXIF["DateTimeOriginal"]);
        //Exposuretime
        if(isset($EXIF["ExposureTime"]))
          $this->exposuretime=$EXIF["ExposureTime"];

        //ISO
        if(isset($EXIF["ISOSpeedRatings"]))
          $this->iso=$EXIF["ISOSpeedRatings"];

        //Focale
        if(isset($EXIF["FocalLengthIn35mmFilm"]))
          $this->focale=$EXIF["FocalLengthIn35mmFilm"];

        //Ouverture
        if(isset($EXIF["FNumber"]))
          $this->ouverture=$EXIF["FNumber"];

        //Flash
        if(isset($EXIF["Flash"]))
        {
          $flash=(int)$EXIF["Flash"];
          $flash = decbin(intval($EXIF["Flash"]));
          $last=strlen($flash)-1;
          if($flash[$last]==1)
            $this->flash=1;
          else
            $this->flash=0;
        }
      }

      //COMPUTED
      if(isset($exif["COMPUTED"]))
      {
        $COMPUTED=$exif["COMPUTED"];
        //Aperture
        if(isset($COMPUTED["ApertureFNumber"]))
        {
          $this->aperture=explode("/",$COMPUTED["ApertureFNumber"]);
          if(count($at)==2)
            $at=$at[0]." ".$at[1];
          else
            $this->aperture=$COMPUTED["ApertureFNumber"];
        }
      }

      //IFDO
      if (isset($exif["IFD0"]))
      {
        $IFDO=$exif["IFD0"];

        // Si on a pas déjà la date
        if(is_null($this->date_prise_vue) && isset($IFDO["DateTime"]))
          $this->date_prise_vue = datetime_to_timestamp($IFD0["DateTime"]);

        //Fabricant
        if(isset($IFDO["Make"]))
          $this->manufacturer=$IFDO["Make"];

        //Boitier
        if(isset($IFDO["Model"]))
          $this->model=$IFDO["Model"];
      }
    }

    if ( $nobody )
    {
      $this->incomplet=false;
      $this->propose_incomplet=false;
      $this->droits_acquis=true;
    }
    else
    {
      $this->incomplet=true;
      $this->propose_incomplet=true;
      $this->droits_acquis=false;
    }
    $this->id_catph = $id_catph;
    $this->commentaire = $commentaire;
    $this->id_utilisateur_photographe = $id_utilisateur_photographe;
    $this->modere = false;
    $this->supprime = false;
    $this->classification = 0;
    $this->couleur_moyenne = null;
    $this->meta_id_asso = $meta_id_asso;
    $this->type_media = MEDIA_PHOTO;
    $this->titre = $titre;
    $this->id_asso_photographe = $id_asso_photographe;

    if ( is_null($this->id_utilisateur_photographe) )
      $this->id_utilisateur_photographe = $this->id_utilisateur;

    $sql = new insert ($this->dbrw,
      "sas_photos",
      array(
        "id_catph"=>$this->id_catph,
        "id_utilisateur_photographe"=>$this->id_utilisateur_photographe,
        "date_prise_vue"=>is_null($this->date_prise_vue)?null:date("Y-m-d H:i:s",$this->date_prise_vue),
        "modere_ph"=>$this->modere,
        "commentaire_ph"=>$this->commentaire,
        "incomplet"=>$this->incomplet,
        "propose_incomplet"=>$this->propose_incomplet,
        "droits_acquis"=>$this->droits_acquis,
        "couleur_moyenne"=>$this->couleur_moyenne,
        "classification"=>$this->classification,
        "supprime_ph"=>$this->supprime,
        "id_utilisateur"=>$this->id_utilisateur,
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_ph"=>$this->droits_acces,
        "meta_id_asso_ph"=>$this->meta_id_asso,
        "date_ajout_ph"=>date("Y-m-d H:i:s"),
        "type_media_ph"=>$this->type_media,
        "titre_ph"=>$this->titre,
        "id_asso_photographe"=>$this->id_asso_photographe,
        "iso"=>$this->iso,
        "focale"=>$this->focale,
        "ouverture"=>$this->ouverture,
        "flash"=>$this->flash,
        "exposuretime"=>$this->exposuretime,
        "aperture"=>$this->aperture,
        "manufacturer"=>$this->manufacturer,
        "model"=>$this->model,
        "id_licence"=>$this->id_licence
        )
      );

    if ( $sql->is_success() )
      $this->id = $sql->get_id();
    else
    {
      $this->id = null;
      return;
    }

    $this->make_path();

    $dest_hd = $this->get_abs_path().$this->id.".jpg";
    $dest_dip = $this->get_abs_path().$this->id.".diapo.jpg";
    $dest_vgt = $this->get_abs_path().$this->id.".vignette.jpg";

    list($w,$h) = getimagesize($tmp_filename);
    exec(escapeshellcmd("/usr/share/php5/exec/convert $tmp_filename -thumbnail 140x105 -quality 95 $dest_vgt"));
    if($w < 680 && $h < 510)
      exec(escapeshellcmd("/usr/share/php5/exec/convert $tmp_filename -thumbnail ".$w."x".$h." -quality 80 $dest_dip"));
    else
      exec(escapeshellcmd("/usr/share/php5/exec/convert $tmp_filename -thumbnail 680x510 -quality 80 $dest_dip"));
    if($w < 2400 && $h < 2400)
      exec(escapeshellcmd("/usr/share/php5/exec/convert $tmp_filename -thumbnail ".$w."x".$h." -quality 80 $dest_hd"));
    else
      exec(escapeshellcmd("/usr/share/php5/exec/convert $tmp_filename -thumbnail 2400x2400 -quality 80 $dest_hd"));

    //rotation automatique
    if(isset($IFDO['Orientation']))
    {
      switch($IFDO['Orientation'])
      {
        case 1: // nothing
          break;

        case 2: // horizontal flip
          $this->flip(1);
          break;

        case 3: // 180 rotate left
          $this->rotate(180);
          break;

        case 4: // vertical flip
          $this->flip(2);
          break;

        case 5: // vertical flip + 90 rotate right
          $this->flip(2);
          $this->rotate(90);
          break;

        case 6: // 90 rotate right
          $this->rotate(90);
          break;

        case 7: // horizontal flip + 90 rotate right
          $this->flip(1);
          $this->rotate(90);
          break;

        case 8: // 90 rotate left
          $this->rotate(-90);
          break;
      }
    }

    $this->_calcul_couleur_moyenne();
  }

  /**
   * Ajoute une video FLV.
   * Vous DEVEZ avoir fait appel à herit et set_rights avant !
   * @param $tmp_photo_filename Fichier image temporaire source (ne sera pas modifié ni supprimé)
   * @param $tmp_flv_filename Fichier video temporaire source (ne sera pas modifié ni supprimé)
   * @param $id_catph Id de la catégorie
   * @param $commentaire Commentaire (facultatif)
   * @param $id_utilisateur_photographe Id du photographe (facultatif)
   * @param $nobody S'il n'y a personne sur la photo (si true alors incomplet=false,droits_acquis=true)
   */
  function add_videoflv (  $tmp_photo_filename
                         , $tmp_flv_filename
                         , $id_catph
                         , $commentaire=""
                         , $id_utilisateur_photographe=null
                         , $nobody=false
                         , $meta_id_asso=NULL
                         , $titre=NULL
                         , $id_asso_photographe=NULL
                         , $id_licence=null)
  {
    $this->id_licence=$id_licence;
    $this->date_prise_vue=null;

    if ( $nobody )
    {
      $this->incomplet=false;
      $this->propose_incomplet=false;
      $this->droits_acquis=true;
    }
    else
    {
      $this->incomplet=true;
      $this->propose_incomplet=true;
      $this->droits_acquis=false;
    }

    $this->id_catph = $id_catph;
    $this->commentaire = $commentaire;
    $this->id_utilisateur_photographe = $id_utilisateur_photographe;
    $this->modere = false;
    $this->supprime = false;
    $this->classification = 0;
    $this->couleur_moyenne = null;
    $this->meta_id_asso = $meta_id_asso;
    $this->type_media = MEDIA_VIDEOFLV;
    $this->titre = $titre;
    $this->id_asso_photographe = $id_asso_photographe;

    if ( is_null($this->id_utilisateur_photographe) )
      $this->id_utilisateur_photographe = $this->id_utilisateur;

    $sql = new insert ($this->dbrw,
      "sas_photos",
      array(
        "id_catph"=>$this->id_catph,
        "id_utilisateur_photographe"=>$this->id_utilisateur_photographe,
        "date_prise_vue"=>is_null($this->date_prise_vue)?null:date("Y-m-d H:i:s",$this->date_prise_vue),
        "modere_ph"=>$this->modere,
        "commentaire_ph"=>$this->commentaire,
        "incomplet"=>$this->incomplet,
        "propose_incomplet"=>$this->propose_incomplet,
        "droits_acquis"=>$this->droits_acquis,
        "couleur_moyenne"=>$this->couleur_moyenne,
        "classification"=>$this->classification,
        "supprime_ph"=>$this->supprime,
        "id_utilisateur"=>$this->id_utilisateur,
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_ph"=>$this->droits_acces,
        "meta_id_asso_ph"=>$this->meta_id_asso,
        "date_ajout_ph"=>date("Y-m-d H:i:s"),
        "type_media_ph"=>$this->type_media,
        "titre_ph"=>$this->titre,
        "id_asso_photographe"=>$this->id_asso_photographe,
        "id_licence"=>$this->id_licence
        )
      );

    if ( $sql )
    {
      $this->id = $sql->get_id();
    }
    else
    {
      $this->id = null;
      return;
    }

    $this->make_path();

    $dest_hd = $this->get_abs_path().$this->id.".jpg";
    $dest_dip = $this->get_abs_path().$this->id.".diapo.jpg";
    $dest_vgt = $this->get_abs_path().$this->id.".vignette.jpg";
    $dest_flv = $this->get_abs_path().$this->id.".flv";

    exec(escapeshellcmd("/usr/share/php5/exec/convert $tmp_photo_filename -thumbnail 140x105 -quality 95 $dest_vgt"));
    exec(escapeshellcmd("/usr/share/php5/exec/convert $tmp_photo_filename -thumbnail 680x510 -quality 80 $dest_dip"));
    exec(escapeshellcmd("/usr/share/php5/exec/convert $tmp_photo_filename -thumbnail 2400x2400 -quality 80 $dest_hd"));

    copy($tmp_flv_filename,$dest_flv);

    $this->_calcul_couleur_moyenne();
  }


  /**
   * Calcule la couleur moyenne de la photo (en se basant sur la vignette).
   *
   * Utilisé par le module de génération de mosaiques. Nécessite en général 0.08 sec.
   * Script ressuscité de UBPT v1.
   */
  function _calcul_couleur_moyenne (  )
  {
    $vgt = $this->get_abs_path().$this->id.".vignette.jpg";
    $img = imagecreatefromjpeg($vgt);

    if ( !$img )
      return;

    $sR = 0;
    $sG = 0;
    $sB = 0;
    $n = 0;

    $W = imagesx($img);
    $H = imagesy($img);

    for ( $x=0; $x < $W; $x++ )
      for ( $y=0; $y < $H; $y++ ) {
        $rgb = imagecolorat($img,$x,$y);
        $sR += ($rgb >> 16) & 0xFF;
        $sG += ($rgb >> 8) & 0xFF;
        $sB += $rgb & 0xFF;
        $n++;
      }

    imagedestroy($img);

    $R = round($sR/$n);
    $G = round($sG/$n);
    $B = round($sB/$n);

    $this->couleur_moyenne = ($R << 16) | ($G << 8) | $B;

    $sql = new update ($this->dbrw, "sas_photos",
        array("couleur_moyenne"=>$this->couleur_moyenne),
        array("id_photo"=>$this->id )
      );

  }

  /**
   * Recalcule et met à jour les droits à l'image pour la photo
   * @private
   */
  function _update_droits_acquis()
  {
    if ( $this->incomplet )
      $droits_acquis = 0;
    else
    {
      $sql = new requete($this->db, "SELECT COUNT(*) FROM `sas_personnes_photos` " .
        "WHERE `id_photo`='".$this->id."' " .
        "AND `accord_phutl`='0' " .
        "AND `modere_phutl`='1'");
      list($nb) = $sql->get_row();
      $droits_acquis = ($nb == 0);
    }

    if ( $droits_acquis != $this->droits_acquis )
    {
      $this->droits_acquis = $droits_acquis;
      $sql = new update($this->dbrw,"sas_photos",array("droits_acquis"=>$this->droits_acquis),array("id_photo"=>$this->id) );
    }
  }

  /**
   * Ajoute une personne comme étant présente sur la photo.
   * Et prosséde aux mises à jours nécessaires.
   * @param $utl Instance de utilisateur correspondant à la personne
   * @param $modere Précise si cet ajout est une suggestion(=false) ou pas(=true)
   * @see class Utilisateur
   */
  function add_personne( $utl, $modere=true, $id_utl_propose = null )
  {
    if($modere)
      $modere=1;
    else
      $modere=0;
    $sql = null;
    if(is_null($id_utl_propose))
	    $sql = new insert ($this->dbrw,
	      "sas_personnes_photos",
	      array(
		"id_photo"=>$this->id,
		"id_utilisateur"=>$utl->id,
		"modere_phutl"=>$modere,
		"accord_phutl"=> ( $utl->droit_image || $utl->id == $this->id_utilisateur )
		)
	      );
    elseif( $modere )
	    $sql = new insert ($this->dbrw,
	      "sas_personnes_photos",
	      array(
		"id_photo"=>$this->id,
		"id_utilisateur"=>$utl->id,
		"modere_phutl"=>$modere,
		"accord_phutl"=> ( $utl->droit_image || $utl->id == $this->id_utilisateur ),
		"id_utl_modere"=> $id_utl_propose,
		"id_utl_propose"=> $id_utl_propose
		)
	      );
    else
	    $sql = new insert ($this->dbrw,
	      "sas_personnes_photos",
	      array(
		"id_photo"=>$this->id,
		"id_utilisateur"=>$utl->id,
		"modere_phutl"=>$modere,
		"accord_phutl"=> ( $utl->droit_image || $utl->id == $this->id_utilisateur ),
		"id_utl_propose"=> $id_utl_propose
		)
	      );


    $this->_update_droits_acquis();
  }

  /**
   * @param $id_utilisateur Id de la personne
   */
  function modere_personne($id_utilisateur, $id_modo = null)
  {
    $sql = null;
    if(is_null($id_modo))
      $sql = new update($this->dbrw,"sas_personnes_photos",array("modere_phutl"=>true),array(
        "id_photo"=>$this->id,
        "id_utilisateur"=>$id_utilisateur
          ));
    else
      $sql = new update($this->dbrw,"sas_personnes_photos",array("modere_phutl"=>true, "id_utl_modere"=> $id_modo),array(
        "id_photo"=>$this->id,
        "id_utilisateur"=>$id_utilisateur
          ));
    $this->_update_droits_acquis();
  }

  /**
   * Enlève une personne de la liste des présents sur la photo.
   * Et prosséde aux mises à jours nécessaires.
   * @param $id_utilisateur Id de la personne
   */
  function remove_personne ( $id_utilisateur )
  {
    $sql = new delete ($this->dbrw,
      "sas_personnes_photos",
      array(
        "id_photo"=>$this->id,
        "id_utilisateur"=>$id_utilisateur
        )
      );
    $this->_update_droits_acquis();
  }

  /**
   * Prends en compte l'accord pour la photo d'une personne présente
   * Et prosséde aux mises à jours nécessaires.
   * @param $id_utilisateur Id de la personne
   */
  function donne_accord($id_utilisateur)
  {
    $sql = new update($this->dbrw,"sas_personnes_photos",array("accord_phutl"=>true),array(
        "id_photo"=>$this->id,
        "id_utilisateur"=>$id_utilisateur
        ));
    $this->_update_droits_acquis();
  }

  /**
   * Définit si la liste des personnes sur la photo est incomplète ou non.
   * Et prosséde aux mises à jorus requises.
   * @param $incomplet Incomplet(=true) ou non (=false)
   */
  function set_incomplet($incomplet=true,$suggest = false)
  {
    if(!$suggest)
    {
	    $this->incomplet = $incomplet;
	    $this->propose_incomplet = $incomplet;
	    $sql = new update($this->dbrw,"sas_photos",array("incomplet"=>$this->incomplet, "propose_incomplet"=>$this->propose_incomplet ),array("id_photo"=>$this->id) );
	    $this->_update_droits_acquis();
    }
    else
    {
	    $this->propose_incomplet = $incomplet;
	    $sql = new update($this->dbrw,"sas_photos",array("propose_incomplet"=>$this->propose_incomplet),array("id_photo"=>$this->id) );
    }
  }

  function set_licence($id_licence)
  {
    if(is_null($id_licence))
      return false;
    $licence = new licence($this->db);
    if($licence->load_by_id($id_licence))
    {
      $this->id_licence=$licence->id;
      new update($this->dbrw,
                 "sas_photos",
                 array("id_licence"=>$this->id_licence),
                 array("id_photo"=>$this->id));
      return true;
    }
    else
      return false;
  }

  /**
   * Définit si la photo est modérée ou pas
   * @param $modere Modérée(=true) ou non (=false)
   * @param $id_utilisateur_moderateur Id du moderateur
   */
  function set_modere($modere=true,$id_utilisateur_moderateur=null)
  {
    $this->modere = $modere;
    $this->id_utilisateur_moderateur = $id_utilisateur_moderateur;
    $sql = new update($this->dbrw,"sas_photos",
    array("modere_ph"=>$this->modere,
    "id_utilisateur_moderateur"=>$this->id_utilisateur_moderateur),array("id_photo"=>$this->id) );
  }

  /**
   * Détermine si un utilisateur est sur la photo.
   * @param $id_utilisateur Id de l'utilisateur à tester.
   * @note Entraine une requête SQL.
   */
  function is_on_photo ( $id_utilisateur )
  {
    $req = new requete($this->db,
      "SELECT `id_utilisateur` " .
      "FROM `sas_personnes_photos` " .
      "WHERE `id_photo`='".$this->id."' " .
      "AND `id_utilisateur`='".$id_utilisateur."' " .
      "AND `modere_phutl`='1' LIMIT 1");

    return ($req->lines==1);
  }

  /**
   * Détermine si un utilisateur a vu la photo.
   * @param $id_utilisateur Id de l'utilisateur à tester.
   * @note Entraine une requête SQL.
   */
  function has_seen_photo ( $id_utilisateur )
  {
    $req = new requete($this->db,
      "SELECT `id_utilisateur` " .
      "FROM `sas_personnes_photos` " .
      "WHERE `id_photo`='".$this->id."' " .
      "AND `id_utilisateur`='".$id_utilisateur."' " .
      "AND `modere_phutl`='1' " .
      "AND `vu_phutl`='1' LIMIT 1");

    return ($req->lines==1);
  }

  /**
   * Définit la photo comme ayant été vue.
   * @param $id_utilisateur Id de l'utilisateur ayant vu la photo.
   * @note Uniquement pour les personnes présentes sur la photo.
   */
  function set_seen_photo ( $id_utilisateur )
  {
    $sql = new update($this->dbrw,"sas_personnes_photos",
      array("vu_phutl" => 1),
      array("id_utilisateur" => $id_utilisateur,
        "id_photo" => $this->id,
        "vu_phutl" => 0)
      );
  }

  /**
   * Définit le commentaire de la photo.
   * @param $comment Commentaire
   * @param $modere Définit si le commentaire est "modéré" (non implémenté)
   */
  function set_comment ( $comment, $modere=true)
  {
    $this->commentaire = $comment;
    $sql = new update($this->dbrw,"sas_photos",array("commentaire_ph"=>$this->commentaire),array("id_photo"=>$this->id) );
  }

  /**
   * Suppression de la photo
   */
   function remove_photo()
   {
    unlink($this->get_abs_path().$this->id.".jpg");
    unlink($this->get_abs_path().$this->id.".diapo.jpg");
    unlink($this->get_abs_path().$this->id.".vignette.jpg");

    if ( $this->type_media == MEDIA_VIDEOFLV )
      unlink($this->get_abs_path().$this->id.".flv");

    $this->set_tags_array(array());

    new delete($this->dbrw,"sas_photos",array("id_photo"=>$this->id) );
    new delete($this->dbrw,"sas_personnes_photos",array("id_photo"=>$this->id) );
   }



  function update_photo (  $date_prise_vue
                         , $commentaire=""
                         , $id_utilisateur_photographe=null
                         , $meta_id_asso=NULL
                         , $titre=NULL
                         , $id_asso_photographe=NULL
                         , $id_licence=null)
  {
    if(!is_null($id_licence))
      $this->id_licence=$id_licence;
    $this->date_prise_vue=$date_prise_vue;
    $this->commentaire = $commentaire;
    $this->id_utilisateur_photographe = $id_utilisateur_photographe;

    $this->titre = $titre;
    $this->id_asso_photographe = $id_asso_photographe;

    if ( $meta_id_asso )
      $this->meta_id_asso = $meta_id_asso;
    else
      $this->meta_id_asso=NULL;

    $sql = new update ($this->dbrw,
      "sas_photos",
      array(

        "id_utilisateur_photographe"=>$this->id_utilisateur_photographe,
        "date_prise_vue"=>is_null($this->date_prise_vue)?null:date("Y-m-d H:i:s",$this->date_prise_vue),
        "id_utilisateur"=>$this->id_utilisateur,
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_ph"=>$this->droits_acces,
        "meta_id_asso_ph"=>$this->meta_id_asso,
        "titre_ph"=>$this->titre,
        "id_asso_photographe"=>$this->id_asso_photographe,
        "id_licence"=>$this->id_licence
        ),
        array("id_photo"=>$this->id )
      );
  }

  function save_rights()
  {
    $sql = new update ($this->dbrw,
      "sas_photos",
      array(
        "id_groupe"=>$this->id_groupe,
        "id_groupe_admin"=>$this->id_groupe_admin,
        "droits_acces_ph"=>$this->droits_acces,
        "meta_id_asso_ph"=>$this->meta_id_asso
        ),
        array("id_photo"=>$this->id ));
  }


  function is_right ( &$user, $required )
  {
    if ( parent::is_right($user,$required) )
      return true;

    if ( $required != DROIT_LECTURE )
      return false;

    if ( $this->is_on_photo($user->id) )
      return true;

    if ( $this->id_utilisateur_photographe == $user->id )
      return true;

    // Droit de lecture de toutes les photos pour les utilisateurs qui ont déjà été à l'AE.
    $derniere_cotiz = false;
    if (($dernier_cotiz = $user->date_derniere_cotiz_a_lae ()) && $required == DROIT_LECTURE) {
      $date_derniere_cotiz = strtotime($dernier_cotiz);
      if ( $date_derniere_cotiz >= $date_debut)
        return true;
    }

    if ( $this->meta_id_asso )
      if ( $user->is_asso_role($this->meta_id_asso,ROLEASSO_MEMBREBUREAU) )
        return true;

    return false;
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

  function is_category()
  {
    return false;
  }

  function rotate ( $degrees=90 )
  {
    $src_hd = $this->get_abs_path().$this->id.".jpg";
    $dest_dip = $this->get_abs_path().$this->id.".diapo.jpg";
    $dest_vgt = $this->get_abs_path().$this->id.".vignette.jpg";

    exec(escapeshellcmd("/usr/share/php5/exec/convert $src_hd -rotate ".intval($degrees)." -quality 80 $src_hd"));
    exec(escapeshellcmd("/usr/share/php5/exec/convert $src_hd -thumbnail 140x105 -quality 95 $dest_vgt"));
    exec(escapeshellcmd("/usr/share/php5/exec/convert $src_hd -thumbnail 680x510 -quality 80 $dest_dip"));
  }

  function flip($flip=1)
  {
    $src_hd = $this->get_abs_path().$this->id.".jpg";
    $dest_dip = $this->get_abs_path().$this->id.".diapo.jpg";
    $dest_vgt = $this->get_abs_path().$this->id.".vignette.jpg";
    if($flip==1)//horizontal
      exec(escapeshellcmd("/usr/share/php5/exec/convert $src_hd -flop ".intval($degrees)." -quality 80 $src_hd"));
    if($flip==2)//vertical
      exec(escapeshellcmd("/usr/share/php5/exec/convert $src_hd -flip ".intval($degrees)." -quality 80 $src_hd"));
    exec(escapeshellcmd("/usr/share/php5/exec/convert $src_hd -thumbnail 140x105 -quality 95 $dest_vgt"));
    exec(escapeshellcmd("/usr/share/php5/exec/convert $src_hd -thumbnail 680x510 -quality 80 $dest_dip"));
  }

  function move_to ( $id_catph )
  {
    $this->id_catph = $id_catph;

    $sql = new update ($this->dbrw,
      "sas_photos",
      array("id_catph"=>$this->id_catph),
      array("id_photo"=>$this->id ));
  }
}

?>
