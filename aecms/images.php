<?php
/*
 * AECMS : CMS pour les clubs et activités de l'AE UTBM
 *
 * Copyright 2004-2007
 * - Julien Etelain < julien dot etelain at gmail dot com >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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

require_once("include/site.inc.php");
require_once($topdir."sas2/include/cat.inc.php");
require_once($topdir."sas2/include/photo.inc.php");

session_write_close(); // on n'a plus besoin de la session, liberons le semaphore...

if ( ereg("^(.*)/([0-9]*).jpg$",$_SERVER["argv"][0],$regs) )
{
  $id_photo = intval($regs[2]);
  $mode = "";
}
else if ( ereg("^(.*)/([0-9]*).vignette.jpg$",$_SERVER["argv"][0],$regs) )
{
  $id_photo = intval($regs[2]);
  $mode = "vignette";
}
else if ( ereg("^(.*)/([0-9]*).diapo.jpg$",$_SERVER["argv"][0],$regs) )
{
  $id_photo = intval($regs[2]);
  $mode = "diapo";
}
else if ( ereg("^(.*)/([0-9]*).flv$",$_SERVER["argv"][0],$regs) )
{
  $id_photo = intval($regs[2]);
  $mode = "flv";
}
else if ( ereg("^(.*)/([0-9]*)$",$_SERVER["argv"][0],$regs) )
{
  $id_photo = intval($regs[2]);
  $mode = "diapo";
}
else if ( ereg("^/(.*)$",$_SERVER["argv"][0]) )
{
  $path = $_SERVER["argv"][0];
}
else
{
  header("Location: index.php?name=404");
  exit();
}

if ( $id_photo > 0 )
{
  function renvoyer_image ( $file )
  {

    $lastModified = gmdate('D, d M Y H:i:s', filemtime($file) ) . ' GMT';
    $etag=md5($file.'#'.$lastModified);

    if ( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) )
    {
      $ifModifiedSince = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
      if ($lastModified == $ifModifiedSince)
      {
        header("HTTP/1.0 304 Not Modified");
        header('ETag: "'.$etag.'"');
        exit();
      }
    }

    if ( isset($_SERVER['HTTP_IF_NONE_MATCH']) )    {      if ( $etag == str_replace('"', '',stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) )
      {
        header("HTTP/1.0 304 Not Modified");
        header('ETag: "'.$etag.'"');
        exit();
      }
    }

    header("Cache-Control: must-revalidate");    header("Pragma: cache");
    header("Last-Modified: ".$lastModified);
    header("Cache-Control: public");
    header("Content-type: image/jpeg");
    header('Content-length: '.filesize($file));
    header('ETag: "'.$etag.'"');
    readfile($file);
    exit();
  }

  $photo = new photo($site->db);
  $photo->load_by_id($id_photo);

  if ( !$photo->is_valid() || !$photo->is_right($site->user,DROIT_LECTURE) )
  {
    renvoyer_image($topdir."images/actions/delete.png");
    exit();
  }

  $rootcat = new catphoto($site->db);
  $catpr = new catphoto($site->db);

  $rootcat->load_by_asso_summary($site->asso->id);

  if ( !$rootcat->is_valid() )
  {
    renvoyer_image($topdir."images/actions/delete.png");
    exit();
  }

  $catpr->load_by_id($photo->id_catph);

  while ( $catpr->is_valid() && $catpr->id != $rootcat->id )
    $catpr->load_by_id($catpr->id_catph_parent);

  if ( ($catpr->id != $rootcat->id) && ($site->asso->id != $photo->meta_id_asso)  )
  {
    renvoyer_image($topdir."images/actions/delete.png");
    exit();
  }

  $abs_file = $photo->get_abs_path().$photo->id;

  if ( $mode == "flv" && $photo->type_media == MEDIA_VIDEOFLV )
  {
    header('Content-length: '.filesize($abs_file.".flv"));
    header("Content-type: video/x-flv");
    header("Content-Disposition: file; filename=\"".$photo->id.".flv\"");
    readfile($abs_file.".flv");
    exit();
  }

  if ( $mode == "vignette" )
    $abs_file.=".vignette.jpg";
  else if ( $mode == "diapo" )
    $abs_file.=".diapo.jpg";
  else
    $abs_file.=".jpg";

  renvoyer_image($abs_file);

  exit();
}



?>
