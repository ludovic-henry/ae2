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
require_once($topdir."include/site.inc.php");

require_once($topdir."sas2/include/cat.inc.php");
require_once($topdir."sas2/include/photo.inc.php");

define("SAS_NPP",60);

/**
 * @defgroup sas SAS 2.0
 */

/**
 * Version spécialisé du site pour le SAS
 * @ingroup sas
 * @author Julien Etelain
 */
class sas extends site
{

  function sas()
  {

    $this->site();

      if ( ($this->get_param("closed.sas",false) && !$this->user->is_in_group("root")) || !is_dir("/var/www/ae2/data/sas")	)
      $this->fatal_partial("sas");
    $this->set_side_boxes("right",array("monsas"), "sas2");

    if( $this->user->is_valid() )
    {

      $box = new contents("SAS");

      $lst = new itemlist("Le SAS et moi");

      $lst->add("<a href=\"complete.php?mode=userphoto\">Completer les noms sur mes photos</a>");
      $lst->add("<a href=\"droitimage.php\">Droit &agrave; l'image</a>");

      $sql = new requete($this->db,
        "SELECT COUNT(*) " .
        "FROM sas_personnes_photos " .
        "INNER JOIN sas_photos ON (sas_photos.id_photo=sas_personnes_photos.id_photo) " .
        "WHERE sas_personnes_photos.id_utilisateur=".$this->user->id." " .
        "AND sas_personnes_photos.accord_phutl='0' " .
        "AND (droits_acces_ph & 0x100) " .
        "ORDER BY sas_photos.id_photo");
      list($count) = $sql->get_row();

      if ( $count > 0 )
        $lst->add("<a href=\"droitimage.php?page=process\"><b>$count photo(s) en attente</b></a>");

      $box->add($lst,true);

      if( $this->user->is_in_group("sas_admin") )
      {
        $lst = new itemlist("Administration");

        $req = new requete($this->db, "SELECT COUNT(*) FROM `sas_cat_photos` WHERE `modere_catph`='0' ");
        list($ncat) = $req->get_row();
        $req = new requete($this->db, "SELECT COUNT(*) FROM `sas_photos` WHERE `modere_ph`='0'");
        list($nphoto) = $req->get_row();



        $msg = "";
        if ( $ncat > 0 )
          $msg .= $ncat." cat&eacute;gorie(s)";
        if ( $ncat > 0 && $nphoto > 0 )
          $msg .= " et ";
        if ($nphoto > 0 )
          $msg .= $nphoto." photo(s)";

        if ( empty($msg))
          $msg = "rien";

        $lst->add("<a href=\"modere.php\">$msg &agrave; mod&eacute;rer</a>");

        $req = new requete($this->db, "SELECT COUNT(*) FROM `sas_photos` WHERE `incomplet`='1'");
        list($nphoto) = $req->get_row();

        $lst->add("<a href=\"complete.php\">$nphoto photos &agrave; compl&eacute;ter</a>");


        $req = new requete($this->db,
              "SELECT COUNT(*) FROM (SELECT id_photo FROM sas_personnes_photos WHERE modere_phutl = '0' GROUP BY id_photo) as p");
        list($nnoms) = $req->get_row();

        if ( $nnoms > 0 )
          $lst->add("<a href=\"moderenoms.php\">$nnoms photos avec des noms à vérifier</a>");


        $box->add($lst,true);
      }
      else
      {
        $sql = new requete($this->db,
          "SELECT DISTINCT(id_groupe_admin) " .
          "FROM sas_cat_photos " .
          "WHERE id_groupe_admin IN (".$this->user->get_groups_csv().") ");

        while ( list($id_groupe) = $sql->get_row() )
        {
          $lst = new itemlist("Moderation ".$this->user->groupes[$id_groupe]);

          $req = new requete($this->db, "SELECT COUNT(*) FROM `sas_cat_photos` WHERE `modere_catph`='0' AND id_groupe_admin='$id_groupe'");
          list($ncat) = $req->get_row();
          $req = new requete($this->db, "SELECT COUNT(*) FROM `sas_photos` WHERE `modere_ph`='0' AND id_groupe_admin='$id_groupe'");
          list($nphoto) = $req->get_row();

          $msg = "";
          if ( $ncat > 0 )
            $msg .= $ncat." cat&eacute;gorie(s)";
          if ( $ncat > 0 && $nphoto > 0 )
            $msg .= " et ";
          if ($nphoto > 0 )
            $msg .= $nphoto." photo(s)";

          if ( empty($msg))
            $msg = "rien";

          $lst->add("<a href=\"modere.php?mode=adminzone&amp;id_groupe_admin=$id_groupe\">$msg &agrave; mod&eacute;rer</a>");

          $req = new requete($this->db, "SELECT COUNT(*) FROM `sas_photos` WHERE `incomplet`='1' AND id_groupe_admin='$id_groupe'");
          list($nphoto) = $req->get_row();

          $lst->add("<a href=\"complete.php?mode=adminzone&amp;id_groupe_admin=$id_groupe\">$nphoto photos &agrave; compl&eacute;ter</a>");
          $box->add($lst,true);
        }
      }
      $this->add_box("monsas",$box);
    }
  }


  function start_page ( $section, $title,$compact=false )
  {
    global $topdir;
    if ( $compact )
    {
      $this->set_side_boxes("left",array());
    }
    parent::start_page($section,$title,$compact);
  }

}

?>
