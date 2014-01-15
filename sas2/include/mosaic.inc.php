<?
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

function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

/**
 * Classe gérant une palette de couleurs
 *
 * Tout droit sorti de UBPT d'où le conding style
 *
 * Dans un souci de performance, les appels SQL ne passent pas par la classe d'abstration utilisée dans le reste du site.
 * @ingroup sas
 * @author Julien Etelain
 * @see ImageMosaic
 */
class Palette
{
  var $db;

  var $Colors;
  var $Cache;

  function Palette ($db)
  {
    $this->db = $db;
    $this->Cache = array();
    $this->Colors = array();
  }

  function clear ()
  {
    $req = new requete($this->db,"delete from sas_palette");
  }

  function reload_cache()
  {
    $req = new requete($this->db,"select idx,r<<16|g<<8|b from sas_palette");
    while ( list($idx,$rgb) = $req->get_row() )
    {
      $this->Cache[$rgb] = $Idx;
      $this->Colors[$Idx] = $rgb;
    }
  }

  function SetColor ( $Idx, $rgb )
  {
    $req = new requete($this->db,"insert into sas_palette (r,g,b,idx) values (".($rgb >> 16).",".(($rgb >> 8) & 0xFF).",".($rgb & 0xFF).",$Idx)");
    $this->Cache[$rgb] = $Idx;
    $this->Colors[$Idx] = $rgb;
  }

  function FindColsest ( $rgb ) {

    if ( isset($this->Cache[$rgb]) ) return $this->Cache[$rgb];

    $req = new requete($this->db,
      "select idx, ".
      "(pow(r-".($rgb >> 16).",2) + pow(g-".(($rgb >> 8) & 0xFF).",2) + pow(b-".($rgb & 0xFF).",2)) as dist ".
      "from sas_palette ".
      "order by dist ".
      "limit 0, 1");

    list($idx,$dist) = $req->get_row();

    $this->Cache[$rgb] = $idx;

    return $idx;
  }

  function FindClosestExcept ( $rgb, $exceptIdx ) {

    $req = new requete($this->db,
      "select idx, ".
      "(pow(r-".($rgb >> 16).",2) + pow(g-".(($rgb >> 8) & 0xFF).",2) + pow(b-".($rgb & 0xFF).",2)) as dist ".
      "from sas_palette ".
      "where idx!='$exceptIdx' ".
      "order by dist ".
      "limit 0, 1");

    list($idx,$dist) = $req->get_row();

    return $idx;
  }

  function RemoveIndex ( $idx )
  {
    $req = new requete($this->db,"delete from sas_palette where idx='$idx'");
  }

}

/**
 * Générateur d'une mosaique à partir de photos du SAS
 *
 * Tout droit sorti de UBPT d'où le conding style
 *
 * @ingroup sas
 * @author Julien Etelain
 */
class ImageMosaic
{

  var $db;

  var $Pal;
  var $Photos;

  var $ApproxMask;
  var $RealWidth;
  var $RealHeight;

  var $Image;
  var $ImageRGB;

  var $log;

  /**
   * Construit un générateur de mosaique.
   * @param $db Lien à la base de donnés
   * @param $approx Approximation en bits de poids faible à ignorer (2 est une valeur correcte, 4 est un maximum pour un resultat potable, 8 fera une image aléatoire)
   */
  function ImageMosaic ( $db, $approx )
  {

    $this->db = $db;
    $this->ApproxMask = (((0xFF << $approx) & 0xFF) << 16) |
      (((0xFF << $approx) & 0xFF) << 8) |
      ((0xFF << $approx) & 0xFF);


    $this->log = "AE R&D - Mosaic\n";
  }

  function merge_color_indexes ( $rgb2,  $idx1, $idx2 )
  {
    $this->Photos[$idx1] = array_merge($this->Photos[$idx1],$this->Photos[$idx2]);

    unset($this->Photos[$idx2]);

    while ( ($rgb = array_search($idx2, $this->Pal->Cache)) !== FALSE )
      $this->Pal->Cache[$rgb] = $idx1;

    $this->Pal->RemoveIndex($idx2);
  }

  /**
   * Diversifie les photos : fait en sorte qu'il y ai au moins 2 photos pour chaque couleur de la palette
   */
  function make_palette_diverse ( )
  {
    $st = microtime_float();
    foreach ( $this->Photos as $idx2 => $ids )
    {
      if ( count($ids) < 2 )
      {
        $rgb2 = $this->Pal->Colors[$idx2];
        $idx1 = $this->Pal->FindClosestExcept($rgb2,$idx2);
        $this->merge_color_indexes($rgb2,$idx1,$idx2);
      }
    }
    $this->log .= "make_palette_diverse: ".(microtime_float()-$st)." sec\n";
  }

  /**
   * Sauvgarde la palette, pour éviter de devoir la générer à chaque appel
   */
  function store_palette()
  {
    $st = microtime_float();

    $req = new requete($this->db, "delete from sas_palette_photos");

     foreach( $this->Photos as $idx => $photos )
       foreach ( $photos as $id_photo )
         new insert ($this->db, "sas_palette_photos", array("idx"=>$idx,"id_photo"=>$id_photo));

    $this->log .= "store_palette: ".(microtime_float()-$st)." sec\n";

  }

  /**
   * Charge la palette, ou la re-génre si aucune palette n'a été préalabelment sauvgardée
   */
  function load_palette()
  {
    $st = microtime_float();

    $this->Pal = new Palette($this->db);
    $this->Photos = array();

    $this->Pal->reload_cache();

    $req = new requete($this->db, "SELECT idx,id_photo FROM `sas_palette_photos`");

    while ( list($idx,$id_photo) = $req->get_row() )
      $this->Photos[$idx][] = $id_photo;

    $this->log .= "load_palette: ".(microtime_float()-$st)." sec\n";

    if ( count($this->Photos) == 0 )
    {
      $this->generate_palette();
      $this->store_palette();
    }
  }

  /**
   * Génère la palette, et la diversifie
   */
  function generate_palette ()
  {
    $st = microtime_float();

    $this->Pal = new Palette($this->db);
    $this->Pal->clear();

    $this->Photos = array();

    $req = new requete($this->db,
    "SELECT id_photo,(couleur_moyenne & $this->ApproxMask) " .
    "FROM `sas_photos` ".
    "WHERE couleur_moyenne IS NOT NULL AND droits_acquis='1' AND (droits_acces_ph & 1) " .
    "ORDER BY `couleur_moyenne`");

    $nidx = 0;

    while ( list($Id,$rgb) = $req->get_row() )
    {
      if ( !isset($this->Pal->Cache[$rgb]) )
      {
        $this->Pal->SetColor($nidx,$rgb);
        $idx = $nidx;
        $nidx++;
      } else
        $idx = $this->Pal->Cache[$rgb];

      $this->Photos[$idx][] = $Id;
    }

    $this->log .= "generate_palette: ".(microtime_float()-$st)." sec\n";

    $this->make_palette_diverse();
  }


  function load_image ( $Width, $Height, $File )
  {
    $st = microtime_float();

    if ( !isset($this->Pal) )
      $this->load_palette();

    unset($this->Image);

    $this->RealWidth = round($Width * 3 / 4);
    $this->RealHeight = $Height;


    if ( !(list($width, $height, $type, $attr) = @getimagesize($File)) )
      return false;

    if ( $type == 2 )
      $SrcImg = imagecreatefromjpeg($File);
    else if ( $type == 3 )
      $SrcImg = imagecreatefrompng($File);
    else
      return false;

    $this->log .= "load_image/part1: ".(microtime_float()-$st)." sec\n";

    $NvlImg = @imagecreatetruecolor($this->RealWidth,$this->RealHeight);
    imagecopyresampled($NvlImg,$SrcImg,0,0,0,0,$this->RealWidth,$this->RealHeight,ImageSX($SrcImg),ImageSY($SrcImg));
    imagedestroy($SrcImg);

    for ( $y=0; $y < $this->RealHeight; $y++ ) {
      for ( $x=0; $x < $this->RealWidth; $x++ ) {

        $rgb = imagecolorat($NvlImg,$x,$y) & $this->ApproxMask;

        $idx = $this->Pal->FindColsest($rgb);

        if ( !(list($k,$id) = each($this->Photos[$idx])) ) {
          reset($this->Photos[$idx]);
          list($k,$id) = each($this->Photos[$idx]);
        }
        $this->Image[$y][$x] = $id;
        $this->ImageRGB[$y][$x] = $rgb;
      }
    }
    imagedestroy($NvlImg);

    $this->log .= "load_image/total: ".(microtime_float()-$st)." sec";
    return true;
  }

  function output_html ()
  {
    echo "<p>";

    for ( $y=0; $y < $this->RealHeight; $y++ )
    {
      for ( $x=0; $x < $this->RealWidth; $x++ )
        echo "<img src=\"/sas2/images.php?/".$this->Image[$y][$x].".vignette.jpg\" width=\"8\" height=\"6\" />";
      echo "<br/>\n";
    }

    echo "</p>";
  }

  function output_stdcontents ()
  {
    $cts = new stdcontents();
    $cts->buffer .= "<p>";
    for ( $y=0; $y < $this->RealHeight; $y++ )
    {
      for ( $x=0; $x < $this->RealWidth; $x++ )
        $cts->buffer .= "<img src=\"/sas2/images.php?/".$this->Image[$y][$x].".vignette.jpg\" width=\"8\" height=\"6\" />";
      $cts->buffer .= "<br/>\n";
    }
    $cts->buffer .= "</p>";
    return $cts;
  }

  function output_image ( $pxHeight, $File, $cheat=false )
  {
    $st = microtime_float();
    $ph = new photo($this->db);


    $pxWidth = round($pxHeight * 4 / 3);

    $Width  = $this->RealWidth  * $pxWidth;
    $Height = $this->RealHeight * $pxHeight;

    $Image = imagecreatetruecolor($Width,$Height);
    if ( !$Image ) return false;
    imagealphablending($Image,true);

    $ImgCache = array();
    $spotY = 0;

    for ( $y=0; $y < $this->RealHeight; $y++ ) {
      $spotX = 0;
      for ( $x=0; $x < $this->RealWidth; $x++ ) {
        $id = $this->Image[$y][$x];
        if ( !isset($ImgCache[$id]) ) {
          $ph->load_by_id($id);
          $tmp = imagecreatefromjpeg($ph->get_abs_path().$ph->id.".vignette.jpg");
          $ImgCache[$id] = $timg = imagecreatetruecolor($pxWidth,$pxHeight);
          imagecopyresampled($timg,$tmp,0,0,0,0,$pxWidth,$pxHeight,ImageSX($tmp),ImageSY($tmp));
          imagedestroy($tmp);
        } else
          $timg = $ImgCache[$id];

        imagecopy($Image,$timg,$spotX,$spotY,0,0,$pxWidth,$pxHeight);

        // un soupcon de triche pour améliorer le rendu
        if ( $cheat )
          imagefilledrectangle ($Image,$spotX, $spotY, $spotX+$pxWidth-1, $spotY+$pxHeight-1, $this->ImageRGB[$y][$x] | (0x60) << 24 );

        $spotX += $pxWidth;
      }
      $spotY += $pxHeight;
    }

    foreach ( $ImgCache as $Img )
      imagedestroy($Img);

    imagejpeg($Image, $File);

    imagedestroy($Image);

    $this->log .= "output_image: ".(microtime_float()-$st)." sec\n";

    return true;
  }

}

?>
