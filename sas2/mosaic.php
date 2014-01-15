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
$topdir="../";
require_once("include/sas.inc.php");
require_once("include/mosaic.inc.php");

$site = new sas();
if ( !$site->user->is_in_group("sas_admin") )
  $site->error_forbidden("sas","group","sas_admin");

$site->allow_only_logged_users("sas");

$site->start_page("sas","Stock à Souvenirs");

$cts = new contents("Mosaic");
$frm = new form("mosaic","mosaic.php");
$frm->add_hidden("action","mosaic");
$frm->add_text_field("url","Url de l'image à traiter");
$frm->add_submit("go","Générer mosaic");
$cts->add($frm);

$site->add_contents($cts);

if ( $_REQUEST["action"] == "generatepal" )
{
  $Mosaic = new ImageMosaic($site->dbrw,3);
  $Mosaic->generate_palette();
  $Mosaic->store_palette();

  $cts = new contents("log");
  $cts->add(new itemlist(false,false,explode("\n",$Mosaic->log)));
  $site->add_contents($cts);
}
elseif ( $_REQUEST["action"] == "mosaic" )
{
  $Mosaic = new ImageMosaic($site->db,3);
  $Mosaic->load_palette();

  $img = $_REQUEST["url"];
  $infos = @getimagesize($img);

  if ( $infos === false )
  {
    $cts->add_paragraph("Une erreur c'est produite lors de la lecture de l'image","error");
  }
  else
  {
    $w=$infos[0];
    $h=$infos[1];

    if ( $w > 100 )
    {
      $h = $h*100/$w;
      $w = 100;
    }

    if ( $h > 100 )
    {
      $w = $w*100/$h;
      $h = 100;
    }

    $Mosaic->load_image($w,$h,$img);

    $cts = new contents("Resultat");
    $cts->add($Mosaic->output_stdcontents());

    $cts->add_paragraph("Lien vers cette page : http://".$_SERVER["HTTP_HOST"].$_SERVER["SCRIPT_NAME"]."?url=".rawurlencode($_REQUEST["url"])."&amp;action=mosaic");
    $site->add_contents($cts);

    $cts = new contents("log");
    $cts->add(new itemlist(false,false,explode("\n",$Mosaic->log)));
    $site->add_contents($cts);


  }
}

$site->end_page ();



?>
