<?php
/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
 * - Remasterisation par Laurent COLNAT < laurent dot colnat at utbm dot fr >
 *
 * Ce fichier fait partie du site de l'Association des Ãtudiants de
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
$topdir = "../";
require_once($topdir. "include/site.inc.php");

$site = new site ();

if (!$site->user->is_in_group ("moderateur_site"))
  $site->error_forbidden("accueil");

$site->start_page ("accueil", "Planning / Photo de la semaine");

$cts = new contents("Attention");
$cts->add_paragraph("La validité d'une photo de la semaine, de même que celle du planning est de UNE ".
                    "semaine. Si leur contenu n'est pas mis à jour dans les 7 jours à compter de l'instant ".
                    "présent, la boite correspondante ne s'affichera plus sur le site");

$site->add_contents($cts);

$cts = new contents("Planning");

if ( is_dir("/var/www/var/img") && $_REQUEST["action"] == "setplanning" )
{
  $dest_small = "/var/www/var/img/com/planning-small.jpg";
  $dest_diapo = "/var/www/var/img/com/planning-diapo.jpg";
  $dest_full  = "/var/www/var/img/com/planning.jpg";
  if ( isset($_REQUEST['delete']) && file_exists($topdir."var/img/com/planning.jpg"))
  {
    if (!unlink($dest_small))
      $erreur = "Erreur lors de la suppression du planning miniature";
    elseif (!unlink($dest_diapo))
      $erreur = "Erreur lors de la suppression du planning en version diapo";
    elseif (!unlink($dest_full))
      $erreur = "Erreur lors de la suppression du planning en version HD";
    else
    {
      $cts->add_title(2,"Planning supprimé");
      $cts->add_paragraph("<p><img src=\"".$topdir."images/actions/done.png\">Le planning actuel a été correctement supprimé.</p>");
    }
  }
  else
  {
    if( isset($_FILES['file']) )
    {
      if ( !is_uploaded_file($_FILES['file']['tmp_name']) )
        $Erreur="Erreur d'upload (!!!)";
      else
      {
        $src = $_FILES['file']['tmp_name'];
        exec(escapeshellcmd("/usr/share/php5/exec/convert $src -thumbnail 140x100 -quality 95 \"$dest_small\""));
        exec(escapeshellcmd("/usr/share/php5/exec/convert $src -thumbnail 680x510 -quality 95 \"$dest_diapo\""));
        exec(escapeshellcmd("/usr/share/php5/exec/convert $src -quality 95 \"$dest_full\""));
        $cts->add_title(2,"Planning mis à jour");
        $cts->add_paragraph("<p><img src=\"".$topdir."images/actions/done.png\">Le planning a été correctement mis à jour.</p>");
      }
    }
  }
}
else
{
  if (file_exists("/var/www/var/img/com/planning.jpg"))
  {
    $cts->add_paragraph("<img src=\"".$topdir."var/img/com/planning-small.jpg\" />".
                        "<br/><br/>Fichier planning modifié pour la dernière fois le : " .
                        strftime("%A %d %B %G", filemtime($topdir."var/img/com/planning.jpg")),"center");
  }

  $frm = new form("setplanning","weekly_upload.php",true,"POST","Upload");
  $frm->add_hidden("action","setplanning");
  if ( $Erreur )
    $frm->error($Erreur);
  $frm->add_file_field("file","Nouveau Fichier Image (jpg,png...)");
  $frm->add_checkbox("delete","Supprimer le planning de la semaine courant");
  $frm->add_submit("valid","Envoyer");
  $cts->add($frm,true);
}
$site->add_contents($cts);

$cts = new contents("Photo de la semaine");

if ( is_dir("/var/www/var/img") && $_REQUEST["action"] == "setweekly_photo" )
{
  $dest_small = "/var/www/var/img/com/weekly_photo-small.jpg";
  $dest_diapo = "/var/www/var/img/com/weekly_photo-diapo.jpg";
  $dest_full  = "/var/www/var/img/com/weekly_photo.jpg";

  if ( isset($_REQUEST['delete']) && file_exists($topdir."var/img/com/weekly_photo.jpg"))
  {
    if (!unlink($dest_small))
      $erreur = "Erreur lors de la suppression de la photo miniature";
    elseif (!unlink($dest_diapo))
      $erreur = "Erreur lors de la suppression de la photo diapo";
    elseif (!unlink($dest_full))
      $erreur = "Erreur lors de la suppression de la photo HD";
    else
    {
      $cts->add_title(2,"Photo de la semaine actuelle supprimée");
      $cts->add_paragraph("<p><img src=\"".$topdir."images/actions/done.png\">La photo de la semaine actuelle a été correctement supprimée.</p>");
    }
  }
  else
  {
    if( isset($_FILES['file']) )
    {
      if ( !is_uploaded_file($_FILES['file']['tmp_name']) )
        $Erreur="Erreur d'upload (!!!)";
      else
      {
        $src = $_FILES['file']['tmp_name'];
        $dest_small = "/var/www/var/img/com/weekly_photo-small.jpg";
        $dest_diapo = "/var/www/var/img/com/weekly_photo-diapo.jpg";
        $dest_full  = "/var/www/var/img/com/weekly_photo.jpg";

        exec(escapeshellcmd("/usr/share/php5/exec/convert $src -thumbnail 140x100 -quality 95 \"$dest_small\""));
        exec(escapeshellcmd("/usr/share/php5/exec/convert $src -thumbnail 680x510 -quality 95 \"$dest_diapo\""));
        exec(escapeshellcmd("/usr/share/php5/exec/convert $src -quality 95 \"$dest_full\""));

        $cts->add_title(2,"Photo de la semaine mis à jour");
        $cts->add_paragraph("<p><img src=\"".$topdir."images/actions/done.png\">La photo de la semaine a été correctement mis à jour.</p>");

        $frm_edit_box = new form ("frm_edit_box","site.php",true,"post","Edition de la boite photo de la semaine");

        $req = new requete ($site->db, "SELECT `contenu_boite`, `nom_boite` FROM `site_boites` WHERE `nom_boite` = 'Weekly_Photo' LIMIT 1");
        $ct = $req->get_row ();
        $frm_edit_box->add_text_area ("frm_edit_box_ct[".$ct['nom_boite']."]","Contenu de la boite " .$ct['nom_boite'],$ct['contenu_boite']);
        $frm_edit_box->add_submit("frm_edit_box_submit", "Modifier");

        $site->add_contents($frm_edit_box);
      }
    }
  }
}
else
{
  if (file_exists("/var/www/var/img/com/weekly_photo.jpg"))
  {
    $cts->add_paragraph("<img src=\"".$topdir."var/img/com/weekly_photo-small.jpg\" />".
                        "<br/><br/>Fichier photo de la semaine modifié pour la dernière fois le : " .
                        strftime("%A %d %B %G", filemtime($topdir."var/img/com/weekly_photo.jpg")),"center");
  }
  $frm = new form("setweekly_photo","weekly_upload.php",true,"POST","Upload");
  $frm->add_hidden("action","setweekly_photo");
  if ( $Erreur )
    $frm->error($Erreur);
  $frm->add_file_field("file","Nouveau Fichier Image (jpg,png...)");
  $frm->add_checkbox("delete","Supprimer la photo de la semaine courante");
  $frm->add_submit("valid","Envoyer");
  $cts->add($frm,true);
}
$site->add_contents($cts);
$site->end_page ();
?>
