<?
/*
 *  Placement de points sur une carte de France - projet ultra
 *  expérimental
 *
 *  Le but est de lier des points sur la carte
 *
 *  Probleme : on n'a pas des pixels, mais des coordonnées GPS en degrés
 *
 *  Solution :
 *
 *  - Passer les degrés en X / Y coordonnées projetées
 *  selon un systeme de projection donné (il semblerait que celui
 *  utilisé par l'IGN soit le lambert II etendu, particulièrement
 *  adapté pour la France)
 *
 *  - Déterminer un rapport (échelle) entre ces coordonnées et celles
 *  de la carte en Pixel.
 *
 *  C'est de cette 2eme étape que vient mon probleme. J'arrive
 *  aisément à déterminer les coordonnées d'une ville en tracant 2
 *  cercles, en utilisant donc la distance à vol d'oiseau de 2 villes
 *  vers la ville d'arrivée, mais je ne comprends pas pourquoi un
 *  passage en coordonnées cartésiennes ne me donne pas les "bonnes
 *  coordonnées" x et y de la ville en pixels.
 *
 *  Résolution du probleme : Une échelle trop "précise" et inadaptée
 *  était la cause principale. Après simplification de l'échelle, on
 *  obtient une approximation tout à fait intéressante. L'ancienne
 *  provoquait, suite aux nombreux calculs, une erreur trop
 *  importante.
 *
 *  Derniere remarque :
 *
 *  La calibration de l'échelle risque de changer violemment en
 *  fonction de l'image. Et de la vient 90 % du travail de cette
 *  bibliothèque.
 *
 *                                            My 2 cents - pedrov
 */
/* Copyright 2006
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des Ã‰tudiants de
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

require_once ($topdir . "include/watermark.inc.php");


class carto
{
  /* une ressource GD de l'image de rendu */
  var $fond_carte;
  /* un tableau de points à lier
   * (le but premier est de fournir un trajet) */
  var $pts_link;
  /*
   * un tableau de couleurs indexé par un nom
   */
  var $color;

  /*
   * Constructeur
   */
  function carto ($link)
  {
    global $topdir;

    $this->pts_link = $link;
    $this->fond_carte = imagecreatefrompng($topdir . "images/france.png");
    $this->add_color("red", 255, 0, 0);
    $this->add_color("black", 0, 0, 0);

  }

  /*
   * utilisation du programme binaire externe proj
   *
   * voir le script /usr/share/php4/exec/proj.sh pour plus
   * d'explications. On utilise la projection Lambert II étendue
   *
   */
  function convert_gps_to_proj ($coords_deg)
  {
    /*
     * ATTENTION : l'INSEE fournit des longitudes négatives
     * pour les longitudes Ouest ...
     *
     * Il faut donc les passer en positif et indiquer qu'il s'agit
     * bien de longitudes Ouest (et non Est)
     */
    if ($coords_deg[1] < 0)
      $long = abs($coords_deg[1]) . "dW";
    else
      $long = $coords_deg[1] . "dE";

    /* ca, a priori, ca n'arrivera pas ;-)
     *
     * (pour ceux qui ne comprennent pas, jusqu'à preuve du contraire,
     * l'intégralité du territoire francais métropolitain est dans
     * l'hémisphère Nord ...)
     *
     */
    if ($coords_deg[0] < 0)
      $lat = abs($coords_deg[0]) . "dS";
    else
      $lat = $coords_deg[0] . "dN";

    $res = exec ("/usr/share/php5/exec/proj.sh $lat $long");
    $res = explode("\t", $res);
    return $res;
  }
  /*
   * Conversion coordonnées GPS -> Pixels
   *
   */
  function convert_gps_to_px ($coords)
  {
    /* echelle de la carte france.png
     * A CHANGER SI CHANGEMENT D'IMAGE !
     */

    /* a 0.5 pixels pour 1 km (px / m) */
    $echelle = 1.015 / 2000;
    /* on prendra Paris comme référence */
    /* coordonnees de Paris en degres (GPS) */
    $paris['deg'] = array ('48.866667','2.333333');
    /* coordonnees en pixels sur la carte */
    $paris['px'] = array ('280', '131');
    $paris['utm'] = $this->convert_gps_to_proj ($paris['deg']);
    $res['utm'] = $this->convert_gps_to_proj ($coords);

    $res['px'][0] = $paris['px'][0] + ($res['utm'][0] - $paris['utm'][0]) * $echelle;
    $res['px'][1] = $paris['px'][1] - ($res['utm'][1] - $paris['utm'][1]) * $echelle;

    return array(round($res['px'][0]), round($res['px'][1]));
  }

  /*
   * Calcul de la distance à "vol d'oiseau".
   *
   * retourne la distance (en m) relative entre
   * un premier point de coordonnées ($ref en degres),
   * et un point d'arrivee ($coords en degres)
   *
   *
   */
  function get_distance_from_gps ($ref, $coords)
  {
    list($x_ref, $y_ref) = $this->convert_gps_to_proj ($ref);
    list($x_crd, $y_crd) = $this->convert_gps_to_proj ($coords);

    $x = abs($x_ref - $x_crd);
    $y = abs($y_ref - $y_crd);

    $dist = sqrt (pow($x,2) + pow($y,2));
    return $dist;
  }
  /* Pixels ou autre (pas de projection) */
  function get_distance ($ref, $coords)
  {
    list($x_ref, $y_ref) = $ref;
    list($x_crd, $y_crd) = $coords;

    $x = abs($x_ref - $x_crd);
    $y = abs($y_ref - $y_crd);

    $dist = sqrt (pow($x,2) + pow($y,2));
    return $dist;
  }



  /*
   * Parsing des coordonnees du tableau
   *
   */
  function parse_links ()
  {
    $links = $this->pts_link;

    for ($i = 0; $i < count($links); $i++)
      $links[$i] = $this->convert_gps_to_px ($links[$i]);

    /* on trie les etapes dans l'ordre */
    for ($i = 1; $i < count($links) - 1; $i++)
      {
	$dist = $this->get_distance ($links[0], $links[$i]);
	$l_dists[$dist] = $links[$i];
      }

    $start = $links[0];
    $end = $links[count($links) -1];

    $links = array();
    if (is_array($l_dists))
      sort($l_dists);

    $links[0] = $start;
    foreach ($l_dists as $link)
      $links[] = $link;
    $links[] = $end;


    /* parcours deux fois de suite du meme tableau
     * afin d'eviter l'affichage des lignes sur les carres */
    /* trace de ligne */
    for ($i = 0; $i < count($links); $i++)
      {
	if ($i != 0)
	  $this->draw_line (array($links[$i - 1][0],
				  $links[$i - 1][1]),
			    array($links[$i][0],
				  $links[$i][1]));
      }
    /* tracage des pts depart / arrivee et etapes */
    for ($i = 0; $i < count ($links); $i++)
      {
	/* carre pt de depart ou  arrivee */
	if (($i == 0) || ($i == (count($links) - 1)))
	  $this->draw_circle (array($links[$i][0],
				    $links[$i][1]),
			      10);
	/* Carre d'etape */
	else
	  $this->draw_circle (array($links[$i][0],
				    $links[$i][1]),
			      5);
      }
  }

  /*
   * Ajout d'une couleur
   */
  function add_color ($name, $r, $g, $b)
  {
    $this->color[$name] = imagecolorallocate($this->fond_carte,
					     $r,
					     $g,
					     $b);
  }
  /*
   * Trace d'une ligne sur le fond de carte
   *
   */
  function draw_line ($begin, $end, $width = 4, $color = 'red')
  {
    /* les coordonnees de la ligne / rectangle
     * dependent de la position relative de $begin et $end
     */

    /* algo inspire de php.net */
    if ($width == 1)
      imageline($this->fond_carte,
		$begin[0],
		$begin[1],
		$end[0],
		$end[1],
		$this->color[$color]);

    $t = $width / 2 - 0.5;

    if ($begin[0] == $end[0] ||
	$begin[1] == $end[1])
      imagefilledrectangle($this->fond_carte,
			   round(min($x1, $x2) - $t),
			   round(min($y1, $y2) - $t),
			   round(max($x1, $x2) + $t),
			   round(max($y1, $y2) + $t),
			   $this->color[$color]);

    if ($end[0] != $begin[0])
      $k = ($end[1] - $begin[1]) / ($end[0] - $begin[0]);
    else
      $k = ($end[1] - $begin[1]) / (0.1);
    $a = $t / sqrt(1 + pow($k, 2));
    $rectangle = array(round($begin[0] - (1 + $k) * $a),
		    round($begin[1] + (1 - $k) * $a),
		    round($begin[0] - (1 - $k) * $a),
		    round($begin[1] - (1 + $k) * $a),
		    round($end[0] + (1 + $k) * $a),
		    round($end[1] - (1 - $k) * $a),
		    round($end[0] + (1 - $k) * $a),
		    round($end[1] + (1 + $k) * $a));

    imagefilledpolygon($this->fond_carte,
		       $rectangle,
		       4,
		       $this->color[$color]);
  }
  /*
   * tracé d'un cercle (cercle)
   */
  function draw_circle ($coords, $r = 4, $color = "black")
  {
    imagefilledellipse ($this->fond_carte,
			$coords[0],
			$coords[1],
			$r,
			$r,
			$this->color[$color]);
  }
  /*
   * tracé d'un point (carré)
   */
  function draw_square ($center, $width, $color = "black")
  {
    /* haut gauche */
    $topleft[0] =  $center[0] - ($width / 2);
    $topleft[1] =  $center[1] - ($width / 2);
    /* haut droit */
    $topright[0] = $center[0] + ($width / 2);
    $topright[1] = $center[1] - ($width / 2);
    /* bas droit */
    $botright[0] = $center[0] + ($width / 2);
    $botright[1] = $center[1] + ($width / 2);
    /* bas gauche */
    $botleft[0] = $center[0] - ($width / 2);
    $botleft[1] = $center[1] + ($width / 2);

    imagefilledpolygon ($this->fond_carte,
			array($topleft[0],
			      $topleft[1],
			      $topright[0],
			      $topright[1],
			      $botright[0],
			      $botright[1],
			      $botleft[0],
			      $botleft[1]),
			4,
			$this->color[$color]);
  }


  /*
   * Envoi au navigateur
   *
   */
  function output ($watermark = null)
  {
    header ("Content-Type: image/png");
    if (($watermark == null) || (! file_exists($watermark)))
      $img = new img_watermark ($this->fond_carte);
    else
      $img = new img_watermark ($this->fond_carte, $watermark);
    $img->output ();
    $img->destroy ();

  }
  /*
   * "enregistrer sous"
   */
  function saveas ($file)
  {
    @imagepng($this->fond_carte, $file);
  }
  /*
   * Liberation de la memoire
   */
  function destroy ()
  {
    @imagedestroy ($this->fond_carte);
  }
}


?>
