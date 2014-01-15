<?

/** @file
 * Generation d'images watermarkees AE
 *
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

class img_watermark
{

  /* un ressource image */
  var $img;
  /* une ressource image pour le watermark */
  var $res_wm;

  /* la taille de l'image */
  var $size;

  /* des coordonnees de destination */
  var $dest_x;
  var $dest_y;

  /* reglage de l'opacite */
  var $opacity;

  function img_watermark ($image_to_mark,
                          $watermark = "/data/img/ae_watermark.png",
                          $opacity = 9)
  {
    $this->opacity = $opacity;
    /* watermark temporaire */
    /* on ne supporte que le JPG et le PNG */
    switch (exif_imagetype($watermark))
    {
      case IMAGETYPE_JPEG:
        $temp_wm = imagecreatefromjpeg ($watermark);
        break;
      case IMAGETYPE_PNG:
        $temp_wm = imagecreatefrompng($watermark);
        break;
      default:
        die ("Format Watermark non supporte");
      }
    /* taille du watermark temporaire */
    $temp_wm_dim = getimagesize($watermark);

    /* affectation ressource image a marquer */
    $this->img = $image_to_mark;
    /* dimensions image a watermarker */
    $this->size[0] = imagesx($this->img);
    $this->size[1] = imagesy($this->img);

    /* le watermark doit rester carre */
    /* on prend donc la dimension la plus petite de l'image d'arrivee */
    $size = $this->size[0] > $this->size[1] ? $this->size[1] : $this->size[0];

    /* creation du watermark a la taille de l'image a marquer */
    $this->res_wm = imagecreatetruecolor($this->size[0],
           $this->size[1]);
    $fond = imagecolorallocate($this->res_wm, 255, 255, 255);
    imagefilledrectangle($this->res_wm,0,0,$this->size[0],$this->size[1],$fond);
    /* calcul du positionnement dans l'image */
    $dest['x'] = ($this->size[0] - $size) / 2;
    $dest['y'] = ($this->size[1] - $size) / 2;

    /* copie watermark original vers watermark taille finale */
    imagecopyresized($this->res_wm,
                     $temp_wm,
                     $dest['x'],
                     $dest['y'],
                     0,
                     0,
                     $size,
                     $size,
                     $temp_wm_dim[0],
                     $temp_wm_dim[1]);


    /* merging a proprement parler */
    imagecopymerge($this->img,
                   $this->res_wm,
                   0,
                   0,
                   0,
                   0,
                   $this->size[0],
                   $this->size[1],
                   $this->opacity);
    imagedestroy ($this->res_wm);
    imagedestroy ($temp_wm);
  }
  function save_image($dest)
  {
    imagepng($this->img, $dest);
  }

  function saveas($dest) { $this->save_image($dest); }

  function output ()
  {
    header ("Content-Type: image/png");
    imagepng ($this->img);
  }
  function destroy ()
  {
    imagedestroy($this->img);
  }
}

?>
