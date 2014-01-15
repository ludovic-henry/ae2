<?
/** @file
 *
 * @brief Generation d'images watermarkees
 */
/* Copyright 2006
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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


$topdir = "../../../";
require_once ($topdir . "include/watermark.inc.php");

/*
 * Explications : Watermarking d'images pour eboutic.
 *
 * Les images a watermarker doivent etre dans le repertoire courant
 * (/var/www/ae/www/images/comptoir/eboutic/)
 *
 * Les images servant de watermark doivent etre dans le $imgpath
 * defini apres.
 */

$imgpath = "/var/www/ae/www/images/";

/* a surveiller ... */
$in = basename ($_GET['in']);
$wm = $imgpath . basename ($_GET['wm']);

if (!file_exists($in) || !file_exists($wm))
  die("Fichier(s) introuvable(s)");

$exif_in = exif_imagetype ($in);
$exif_wm = exif_imagetype ($wm);

if (($exif_in != IMAGETYPE_JPEG)
  && ($exif_in != IMAGETYPE_PNG))
  die ("Type fichier entree refuse");

if (($exif_wm != IMAGETYPE_JPEG)
  && ($exif_wm != IMAGETYPE_PNG))
  die ("Type fichier watermark refuse");

switch ($exif_in)
{
 case IMAGETYPE_JPEG:
   $img_in = imagecreatefromjpeg ($in);
   break;
 case IMAGE_PNG:
   $img_in = imagecreatefrompng ($in);
   break;
}

if (isset($_GET['op']))
  $op = intval($_GET['op']);
else $op = 9;

$img = new img_watermark ($img_in,
			  $wm,
			  $op);
$img->output ();
$img->destroy ();






?>
