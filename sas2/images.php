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
$GLOBALS['nosession'] = true;

$topdir="../";
require_once("include/sas.inc.php");
$site = new sas();

$site->allow_only_logged_users("sas");

$q = $_SERVER["argv"][0];
$q = htmlspecialchars ($q);

if ( ereg("^(.*)/([0-9]*).jpg$",$q,$regs) )
{
  $id_photo = intval($regs[2]);
  $mode = "";
}
else if ( ereg("^(.*)/([0-9]*).vignette.jpg$",$q,$regs) )
{
  $id_photo = intval($regs[2]);
  $mode = "vignette";
}
else if ( ereg("^(.*)/([0-9]*).diapo.jpg$",$q,$regs) )
{
  $id_photo = intval($regs[2]);
  $mode = "diapo";
}
else if ( ereg("^(.*)/([0-9]*).flv$",$q,$regs) )
{
  $id_photo = intval($regs[2]);
  $mode = "flv";
}
else if ( ereg("^/(.*)$",$q) )
{
  $path = $_SERVER["argv"][0];
}
else
{
  $site->error_not_found("sas");
  exit();
}

if ( $id_photo > 0 )
{
  $photo = new photo($site->db);
  $photo->load_by_id($id_photo);

  if ( !$photo->is_valid() || !$photo->is_right($site->user,DROIT_LECTURE) )
  {
    $site->return_simplefile( "pherror", "image/png", $topdir."images/actions/delete.png");
  }

  $abs_file = $photo->get_abs_path().$photo->id;

  if ( $mode == "flv" && $photo->type_media == MEDIA_VIDEOFLV )
  {
    header("Content-Disposition: file; filename=\"".$photo->id.".flv\"");
    $site->return_simplefile( "sasflv".$photo->id, "video/x-flv", $abs_file.".flv" );
  }

  if ( $mode == "vignette" )
    $abs_file.=".vignette.jpg";
  else if ( $mode == "diapo" )
    $abs_file.=".diapo.jpg";
  else
    $abs_file.=".jpg";

  $site->return_simplefile( "sas".$mode.$photo->id, "image/jpeg", $abs_file );
}

?>
