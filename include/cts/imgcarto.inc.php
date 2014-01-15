<?
/*
 * @brief Classe de traçage d'objets géographiques.
 *
 */
/* Copyright 2007
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des étudiants de
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

class imgcarto
{
  /* objets graphiques à ajouter à l'image */
  var $texts  = array();

  var $lines = array();

  var $polygons = array();

  var $points = array();

  /* ressource image GD */
  var $imgres = null;
  /* Couleurs */
  var $colors = array();

  /* un facteur de division d'échelle */
  var $factor = 1.0;

  /* une valeur en pixels de décalage */
  var $offset = 10;

  /* points minis / maxi */
  var $minx, $miny;
  var $maxx, $maxy;

  /* dimensions */
  var $dimx, $dimy;

  var $errmsg;

  var $calculated = false;

  function imgcarto($dimx, $offset)
  {
    $this->dimx = $dimx;
    $this->offset = $offset;

    /* quelques couleurs de base */
    $this->addcolor("black", 0,0,0);
    $this->addcolor("red", 255, 0,0);
    $this->addcolor("blue", 0,0,255);
    $this->addcolor("white", 255,255,255);

    return;
  }

  function addcolor($def, $r,$g,$b)
  {
    /* on ne génère pas encore la couleur via imagecolorallocate()
     * étant donné que les dimensions de l'image ne sont pas encore
     * connues, et que l'image n'est pas encore créée.
     */

    $this->colors[$def] = array($r,$g,$b);
  }


  function addtext($size, $angle, $x, $y, $color, $text, $font = null, $pointed = null)
  {
    global $topdir;

    if ($font == null)
      $font =  $topdir . "font/verdana.ttf";

    if (!isset($this->minx))
      $this->minx = $x;
    if (!isset($this->miny))
      $this->miny = $y;


    if ($this->minx > $x)
      $this->minx = $x;
    if ($this->maxx < $x)
      $this->maxx = $x;

    if ($this->miny > $y)
      $this->miny = $y;
    if ($this->maxy < $y)
      $this->maxy = $y;


    $this->texts[] = array($size, $angle, $x, $y, $color, $font, $text, $pointed);

  }

  function addpointwithlegend($x, $y, $r, $pcolor, $size, $angle, $text, $tcolor, $font = null)
  {
    $this->addpoint($x, $y, $r, $color);
    $this->addtext($size, $angle, $x, $y, $tcolor, $text, $font, $r);
  }

  function addpoint($x, $y, $r, $color)
  {

    if (!isset($this->minx))
      $this->minx = $x;
    if (!isset($this->miny))
      $this->miny = $y;

    if ($this->minx > $x)
      $this->minx = $x;
    if ($this->maxx < $x)
      $this->maxx = $x;

    if ($this->miny > $y)
      $this->miny = $y;
    if ($this->maxy < $y)
      $this->maxy = $y;

    $this->points[] = array($x,$y,$r, $color);
  }

  function addline($x, $y, $fx, $fy, $color)
  {
    if (!isset($this->minx))
      $this->minx = $x;
    if (!isset($this->miny))
      $this->miny = $y;

    if ($this->minx > $x)
      $this->minx = $x;
    if ($this->maxx < $x)
      $this->maxx = $x;

    if ($this->miny > $y)
      $this->miny = $y;
    if ($this->maxy < $y)
      $this->maxy = $y;

    if ($this->minx > $fx)
      $this->minx = $fx;
    if ($this->maxx < $fx)
      $this->maxx = $fx;

    if ($this->miny > $fy)
      $this->miny = $fy;
    if ($this->maxy < $fy)
      $this->maxy = $fy;

    $this->lines[] = array($x,$y,$fx,$fy,$color);
  }

  /*
   * Précisions sur la variable facultative $mapdatas : contient
   * éventuellement un tableau associatif avec une clé id désignant
   * l'identifiant unique de "l'objet", ainsi qu'une URL pour l'action.
   */
  function addpolygon($plg, $color, $filled = false, $mapdatas = null)
  {
    if (count($plg) <= 0)
      return;

    for ($i = 0; $i < count($plg); $i +=2)
      {
	$x = $plg[$i];
	$y = $plg[$i+1];
	if (!isset($this->minx))
	  $this->minx = $x;
	if (!isset($this->miny))
	  $this->miny = $y;

	if ($this->minx > $x)
	  $this->minx = $x;
	if ($this->maxx < $x)
	  $this->maxx = $x;

	if ($this->miny > $y)
	  $this->miny = $y;
	if ($this->maxy < $y)
	  $this->maxy = $y;
      }

    $this->polygons[] = array($plg, $color, $filled, $mapdatas);
  }

  /* passe les coordonnées des objets en positif */
  function setpositivecoords()
  {
    /* pour passer en positif, il suffit de retrancher aux coordonnées
     * le minimum */

    /* textes */
    if (count($this->texts))
      {
	foreach ($this->texts as &$text)
	  {
	    $text[2] -= $this->minx;
	    $text[3] -= $this->miny;
	  }
      }
    /* points */
    if (count($this->points))
      {
	foreach($this->points as &$point)
	  {
	    $point[0] -= $this->minx;
	    $point[1] -= $this->miny;
	  }
      }
    /* lignes */
    if (count($this->lines))
      {
	foreach($this->lines as &$line)
	  {
	    $line[0] -= $this->minx;
	    $line[1] -= $this->miny;
	    $line[2] -= $this->minx;
	    $line[3] -= $this->miny;
	  }
      }
    /* polygones */
    if (count($this->polygons))
      {
	foreach ($this->polygons as &$polygon)
	  {
	    for ($i = 0; $i < count($polygon[0]); $i+=2)
	      {
		$polygon[0][$i] -= $this->minx;
		$polygon[0][$i+1] -= $this->miny;
	      }

	  }
      }
  }

  /* calcul des dimensions */
  function calculatedimensions($invert_y = true)
  {
    $this->factor = (($this->dimx - 2 * $this->offset) / ($this->maxx - $this->minx));
    $this->dimy = ($this->maxy - $this->miny) * $this->factor + 2 * $this->offset;
    /* on repasse en revue les objets afin de leur donner
     * une taille en adéquation avec l'image de sortie
     */
    /* textes */
    if (count($this->texts))
      {
	foreach ($this->texts as &$text)
	  {
	    $text[2] = $text[2] * $this->factor + $this->offset;
	    $text[3] = $text[3] * $this->factor + $this->offset;

	    /* point avec légende */
	    if ($text[7] != null)
	      {
		$text[2] = $text[2] + 1.5 * $text[7];
		$text[3] = $text[3] - $text[7] / 2;
	      }



	    if ($invert_y)
	      $text[3] = $this->dimy - $text[3];
	  }
      }
    /* points */
    if (count($this->points))
      {
	foreach($this->points as &$point)
	  {
	    $point[0] = $point[0] * $this->factor + $this->offset;
	    $point[1] = $point[1] * $this->factor + $this->offset;
	    if ($invert_y)
	      $point[1] = $this->dimy - $point[1];
	  }
      }
    /* lignes */
    if (count($this->lines))
      {
	foreach($this->lines as &$line)
	  {
	    $line[0] = $line[0] * $this->factor + $this->offset;
	    $line[1] = $line[1] * $this->factor + $this->offset;
	    $line[2] = $line[2] * $this->factor + $this->offset;
	    $line[3] = $line[3] * $this->factor + $this->offset;

	    if ($invert_y)
	      {
		$line[1] = $this->dimy - $line[1];
		$line[3] = $this->dimy - $line[3];
	      }
	  }
      }
    /* polygones */
    if (count($this->polygons))
      {
	foreach ($this->polygons as &$polygon)
	  {
	    for ($i = 0; $i < count($polygon[0]); $i+=2)
	      {
		$polygon[0][$i]    = $polygon[0][$i] * $this->factor + $this->offset;
		$polygon[0][$i+1]  = $polygon[0][$i+1] * $this->factor + $this->offset;

		if ($invert_y)
		  $polygon[0][$i+1] = $this->dimy - $polygon[0][$i+1];
	      }
	  }
      }
    $this->calculated = true;
  }

  function draw()
  {

    if ($this->calculated == false)
      {
	$this->setpositivecoords();
	$this->calculatedimensions();
      }

    $this->imgres = imagecreatetruecolor($this->dimx, $this->dimy);

    /* allocate colors */
    if (count($this->colors))
    {
      foreach ($this->colors as $key => $color)
      {
        $this->colors[$key]['gd'] = imagecolorallocate($this->imgres,
                                                       $color[0],
                                                       $color[1],
                                                       $color[2]);
      }
    }

    imagefill($this->imgres, 0,0, $this->colors['white']['gd']);

    /* draw polygons */
    if (count($this->polygons))
    {
      foreach ($this->polygons as $polygon)
      {
        if ($polygon[2] == false)
          imagepolygon($this->imgres, $polygon[0], count($polygon[0]) / 2, $this->colors[$polygon[1]]['gd']);
        else
          imagefilledpolygon($this->imgres, $polygon[0], count($polygon[0]) / 2, $this->colors[$polygon[1]]['gd']);
      }
    }

    /* draw lines */
    if (count($this->lines))
    {
      foreach ($this->lines as $line)
      {
        imageline ($this->imgres,
                   $line[0],
                   $line[1],
                   $line[2],
                   $line[3],
                   $this->colors[$line[4]]['gd']);
      }
    }
    /* draw points */
    if (count($this->points))
    {
      foreach ($this->points as $point)
      {
        imagefilledellipse ($this->imgres,
                            $point[0],
                            $point[1],
                            $point[2],
                            $point[2],
                            $this->colors[$point[3]]['gd']);

      }
    }
    /* draw texts */
    if (count($this->texts))
      {
	foreach ($this->texts as $text)
	  {
	    imagettftext ($this->imgres,
			  $text[0],
			  $text[1],
			  $text[2],
			  $text[3],
			  $this->colors[$text[4]]['gd'],
			  $text[5],
			  $text[6]);
	  }
      }
  }
  function saveas($path)
  {
    if ($this->imgres)
      imagepng($this->imgres, $path);
  }

  function output()
  {
    if ($this->imgres)
    {
      header("Content-Type: image/png");
      imagepng($this->imgres);
    }
  }

  function destroy()
  {
    if ($imgres)
      imagedestroy($imgres);
  }

  function map_area($mapname="map")
  {

    if ($this->calculated == false)
      {
	$this->setpositivecoords();
	$this->calculatedimensions();
      }

    if (count($this->polygons))
    {
      $map = "<map name=\"".$mapname."\">\n";
      $pol_n=0;
      foreach ($this->polygons as $polygon)
      {
        $map .="<area shape=\"poly\" coords=\"";

	$values = array();

	foreach ($polygon[0] as $elem)
	  $values[] = intval($elem);


	$map .= implode (",", $values);


	$map .= "\" href=\"".$polygon[3]['url']."\" alt=\"notset\" />\n";
        $pol_n++;

      }
      $map .= "</map>\n";

      return $map;
    }
    return "";
  }
}

?>
