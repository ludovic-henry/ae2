<?
/*
 * @brief Classe de traçage de lieux, basé sur l'utilisation de la
 * clase imgcarto. Cette classe a par ailleurs pour objectif de lier
 * les différentes données hétérogènes sur les lieux (bases / tables
 * MySQL, Postgres ...).
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

/* Inclusions requises */
require_once($topdir . "include/mysql.inc.php");
require_once($topdir . "include/mysqlae.inc.php");
require_once($topdir . "include/pgsqlae.inc.php");
require_once($topdir . "include/cts/imgcarto.inc.php");


/* définition des niveaux */

/* niveaux génériques */
define("IMGLOC_WORLD", 1);
define("IMGLOC_CONTINENT", 2);
define("IMGLOC_COUNTRY", 3);

/* spécifique à la segmentation géographique francaise */
define("IMGLOC_REGIONFR", 4);
define("IMGLOC_DEPTFR", 5);

class imgloc
{
  /* un accès read-only à la base MySQL */
  var $mysqldb;

  /* un accès read-only à la base PostGreSQL */
  var $pgsqldb;

  /* une largeur d'image */
  var $width;

  /* un niveau (cf. au dessus pour la définition des niveaux) */
  var $imglevel;

  /*  une liste de lieux à afficher */
  var $locs = array();

  /* une liste de contextes */
  var $contexts = array();

  /* une liste de contextes spéciaux à hilighter */
  var $hlcontexts = array();

  /* une liste de lieux à relier (étapes) */
  var $steps = array();

  /* constructeur */
  function imgloc($width, $level, &$mysqldb, &$pgsqldb)
  {
    $this->width   = $width;
    $this->level   = $level;
    $this->mysqldb = $mysqldb;
    $this->pgsqldb = $pgsqldb;
  }


  function add_step_by_engname($name, $countryc = null)
  {
    $this->add_location_by_engname($name, $countryc);
    $this->steps[] = count($this->locs) - 1;
  }

  function add_step_by_coords($long, $lat, $srid = 4030, $name = "")
  {
    $this->add_location_by_coords($long, $lat, $srid, $name);
    $this->steps[] = count($this->locs) - 1;
  }

  function add_step_by_idville($id, $hilight = true, $red = false)
  {
    $this->add_location_by_idville($id, $hilight, $red);
    $this->steps[] = count($this->locs) - 1;
  }

  function add_step_by_object(&$myloc)
  {
    $this->add_location_by_object($myloc);
    $this->steps[] = count($this->locs) - 1;
  }

  /* Ajout d'un lieu via son nom anglais (recherche dans la table
   * pgsql://worldloc/).
   *
   * Note : Cette table est gigantesque, la fonction sera d'autant
   * plus efficace si le code pays est renseigné. Si vous ne souhaitez
   * gérer que des points situés en France métropolitaine, mieux vaut
   * utiliser les fonctions spécifiques à la France.
   *
   * Les données de cette table sont de type "POINT".
   */
  function add_location_by_engname ($name, $countryc = null)
  {
    $sql = "SELECT
                       the_geom AS coords
            FROM
                       worldloc
            WHERE
                        name_loc = '".pg_escape_string($name)."'";

    if ($countryc != null)
      $sql .= "\n AND countryc_loc = '".pg_escape_string($countryc)."'";

    $sql .= "\n LIMIT 1;";


    $pgreq = new pgrequete($this->pgsqldb,
			   $sql);


    $rs = $pgreq->get_all_rows();
    $rs = $rs[0];

    /* coordonnées globales */
    $coords['datas'] = $rs['coords'];
    $coords['srid']  = 4030;

    $this->_add_location($name, $coords);

    return true;
  }

  /* ajout d'un lieu par ses coordonnées */
  function add_location_by_coords($long, $lat, $srid = 4030, $name = "")
  {
    $convert = new pgrequete($this->pgsqldb, "SELECT GeomFromText('POINT(".$lng." ".$lat. ")', $srid) as datas;");
    $rs = $convert->get_all_rows();

    $coords['datas'] = $rs[0]['datas'];
    $coords['srid']  = $srid;

    $this->_add_location($name,  $coords);
    return true;
  }
  /* ajout d'une ville via idville */
  function add_location_by_idville($id, $hilight = true, $red = false)
  {
    $my = new requete($this->mysqldb, "SELECT
                                              id_ville,
                                              nom_ville,
                                              cpostal_ville,
                                              lat_ville,
                                              long_ville
                                       FROM
                                              loc_ville
                                       WHERE
                                              id_ville = " . intval($id));

    while ($rs = $my->get_row())
      {
	$lng = rad2deg($rs['long_ville']);
	$lat = rad2deg($rs['lat_ville']);
	$lng = str_replace(",", ".", $lng);
	$lat = str_replace(",", ".", $lat);


	$convert = new pgrequete($this->pgsqldb, "SELECT GeomFromText('POINT(".$lng." ".$lat. ")', 4030) as datas;");
	$pgrs = $convert->get_all_rows();

	$coords['datas'] = $pgrs[0]['datas'];
	$coords['srid']  = 4030;

	$this->_add_location($rs['nom_ville'],  $coords, $red);
	if ($hilight == true)
	  {
	    if (strlen($rs['cpostal_ville']) == 4)
	      $cp = "0" . substr($rs['cpostal_ville'], 0, 1);
	    else
	      $cp = substr($rs['cpostal_ville'], 0, 2);

	    $this->add_hilighted_context_fr($cp);
	  }
      }

  }

  /* ajout d'un lieu par objet
   */
  function add_location_by_object(&$myloc)
  {
    if (!$myloc)
      return false;

    $lng = rad2deg($myloc->long);
    $lat = rad2deg($myloc->lat);

    $lng = str_replace(',', '.', $lng);
    $lat = str_replace(',', '.', $lat);

    $convert = new pgrequete($this->pgsqldb, "SELECT GeomFromText('POINT(".$lng." ".$lat. ")', 4030) as datas;");
    $rs = $convert->get_all_rows();

    $coords['datas'] = $rs[0]['datas'];
    $coords['srid']  = 4030;

    $this->_add_location($myloc->nom,  $coords);
    return true;
  }

  function _add_location($nom, $coords, $red = false)
  {
    $this->locs[] = array($nom, $coords, count($this->locs), $red);
  }

  function add_hilighted_context_fr($identifier)
  {
    if (($this->level == 4) || ($this->level == 5))
      $srid = 27582;
    else
      $srid = 3395;

    $sql = "SELECT
                    AsText(Transform(the_geom, $srid)) AS points
            FROM
                    deptfr
            WHERE
                    nom_dept = '".pg_escape_string($identifier) . "'
            OR
                    code_dept = '".pg_escape_string($identifier) . "'
            OR
                    nom_region = '".pg_escape_string($identifier) . "';";

    $this->add_hl_context_by_sql($sql);
  }


  function add_hilighted_context($identifier)
  {
    /* n'a aucun sens pour la france métropolitaine */
    if ($this->france == true)
      return false;

    $sql = "SELECT
                    AsText(Transform(Simplify(the_geom, 0.2), 3395)) As points
            FROM
                    worldadmwgs
            WHERE
                    name = '".pg_escape_string($identifier) ."'
            OR
                    region = '".pg_escape_string($identifier) . "'";

    $this->add_hl_context_by_sql($sql);

  }


  function add_hl_context_by_sql($sql)
  {
    $pgreq = new pgrequete($this->pgsqldb, $sql);
    $rs = $pgreq->get_all_rows();

    $this->_add_hl_context ($rs);
  }

  function _add_hl_context ($datas)
  {
    $this->parse_polygons($datas, true);
  }
  /*
   * Ajoute le contexte du lieu en fonction du niveau défini dans le
   * constructeur.
   *
   */
  function add_context()
  {
    /* but de cette fonction : remplir le tableau $contexts de données géographiques (polygones)
     * en fonction du type d'échelle demandé.
     */
    if ($this->level == IMGLOC_WORLD)
      {

	/* on passe toutes les coordonnées au format "mondial" (SRID 3395) */

	$sql = "SELECT
                       AsText(Transform(Simplify(the_geom, 0.20), 3395)) AS points \n";
	if (count($this->locs))
	  {
	    $i =0;
	    foreach($this->locs as &$loc)
	      {
		$loc[2] = $i;
		/* note : les données pour le tracé des continents proviennent de la table
		 * worldadmgws dont les informations géographiques sont exprimées dans le SRID 4030
		 * Il va donc falloir convertir ces données si elles ne sont pas dans le bon référentiel.
		 */
		if ($loc[1]['srid'] != 4030)
		  {
		    $conds[] = " CONTAINS(the_geom, Transform('".$loc[1]['datas']."', 4030)) ";
		  }
		else
		  {
		    $conds[] = " CONTAINS(the_geom, '".$loc[1]['datas']."') ";
		  }
		/* par ailleurs, pour le tracé mondial, il nous faut une projection dans le SRID 3395 */
		$sql .= ", AsText(Transform('".$loc[1]['datas'] . "', 3395)) AS coords".$i."\n";

		$i++;
	      }
	  }


	$sql .= "FROM
                       worldadmwgs
                 WHERE
                       region != 'Antarctica';";

	$rq = new pgrequete($this->pgsqldb, $sql);

	$rs = $rq->get_all_rows();

	$this->get_locs_coords($rs[0]);

	$this->parse_polygons($rs);

	return;
      }
    else if ($this->level == IMGLOC_CONTINENT)
      {

       	$sql = "SELECT
                       AsText(Transform(Simplify(the_geom, 0.20), 3395)) AS points \n";

	if (count($this->locs))
	  {

	    /* on prépare une sous-requête qui ramènera le nom des régions concernées */
	    $subrq = "SELECT region FROM worldadmwgs WHERE ";

	    $i =0;
	    foreach($this->locs as &$loc)
	      {
		$loc[2] = $i;
		/* note : les données pour le tracé des continents proviennente de la table
		 * worldadmgws dont les informations géographiques sont exprimées dans le SRID 4030
		 * Il va donc falloir convertir ces données si elles ne sont pas dans le bon référentiel.
		 */
		if ($loc[1]['srid'] != 4030)
		  {
		    $conds[] = " CONTAINS(the_geom, Transform('".$loc[1]['datas']."', 4030)) ";

		  }
		else
		  {
		    $conds[] = " CONTAINS(the_geom, '".$loc[1]['datas']."') ";
		  }
		/* meme remarque que pour le tracé mondial */
		$sql .= ", AsText(Transform('".$loc[1]['datas'] . "', 3395)) AS coords".$i."\n";
		$i++;
	      }

	    $subrq .= implode(" OR ", $conds);
	  }
	$sql .= "FROM
                       worldadmwgs\n";
	if ($subrq)
	  $sql .="     WHERE
                              region IN (" . $subrq . ")";

	$rq = new pgrequete($this->pgsqldb, $sql);

	$rs = $rq->get_all_rows();

	$this->get_locs_coords($rs[0]);

	$this->parse_polygons($rs);

	return;
      }

    else if ($this->level == IMGLOC_COUNTRY)
      {

       	$sql = "SELECT
                       AsText(Transform(the_geom, 3395)) AS points \n";

	if (count($this->locs))
	  {

	    /* on prépare une sous-requête qui ramènera le nom des pays concernés */
	    $subrq = "SELECT name FROM worldadmwgs WHERE ";

	    $i =0;
	    foreach($this->locs as &$loc)
	      {
		$loc[2] = $i;
		/* note : les données pour le tracé des continents proviennent de la table
		 * worldadmgws dont les informations géographiques sont exprimées dans le SRID 4030
		 * Il va donc falloir convertir ces données si elles ne sont pas dans le bon référentiel.
		 */
		if ($loc[1]['srid'] != 4030)
		  {
		    $conds[] = " CONTAINS(the_geom, Transform('".$loc[1]['datas']."', 4030)) ";

		  }
		else
		  {
		    $conds[] = " CONTAINS(the_geom, '".$loc[1]['datas']."') ";
		  }
		/* meme remarque que pour le tracé mondial */
		$sql .= ", AsText(Transform('".$loc[1]['datas'] . "', 3395)) AS coords".$i."\n";
		$i++;
	      }

	    $subrq .= implode(" OR ", $conds);
	  }
	$sql .= "FROM
                       worldadmwgs\n";
	if ($subrq)
	  $sql .=" WHERE
                       name IN (" . $subrq . ")";

	$rq = new pgrequete($this->pgsqldb, $sql);

	$rs = $rq->get_all_rows();

	$this->get_locs_coords($rs[0]);

	$this->parse_polygons($rs);

	return;

      }


    else if ($this->level == IMGLOC_REGIONFR)
      {
	$sql = "SELECT
                         AsText(the_geom) AS points\n";
	if (count($this->locs))
	  {
	    $i = 0;
	    $subrq = "SELECT DISTINCT code_reg FROM deptfr WHERE ";
	    foreach ($this->locs as &$loc)
	      {
		/* on ajoute un identifiant de point */
		$loc[2] = $i;
		if ($loc[1]['srid'] != 27582)
		  {
		    $subconds[] = " Contains(the_geom, Transform('".$loc[1]['datas']."', 27582)) ";
		    $sql .= ", Contains(the_geom, Transform('".$loc[1]['datas']."', 27582)) AS contained".$i;
		    $sql .= ", AsText(Transform('".$loc[1]['datas']."', 27582)) AS coords".$i;
		  }
		else
		  {
		    $subconds[] = " Contains(the_geom, Transform('".$loc[1]['datas']."', 27582)) ";
		    $sql .= ", Contains(the_geom, '".$loc[1]['datas']."') AS contained".$i;
		    $sql .= ", AsText(Transform('".$loc[1]['datas']."')) AS coords".$i;
		  }
		$i++;
	      }
	    $subrq .= implode (' OR ', $subconds);
	  }
	$sql .="
                FROM
                       deptfr\n";
	if ($subrq)
	  $sql .= " WHERE
                       code_reg IN (".$subrq.");";

	$rq = new pgrequete($this->pgsqldb, $sql);

	$rs = $rq->get_all_rows();

	$this->get_locs_coords($rs[0]);

	$this->parse_polygons($rs);


	return;
      }
    else if ($this->level == IMGLOC_DEPTFR)
      {

	/* on supprime les points non concernés par la France Métropolitaine */
	$sql = "SELECT
                         AsText(the_geom) AS points\n";
	if (count($this->locs))
	  {
	    $i = 0;
	    $subrq = "SELECT DISTINCT code_dept FROM deptfr WHERE ";
	    foreach ($this->locs as &$loc)
	      {
		/* on ajoute un identifiant de point */
		$loc[2] = $i;
		if ($loc[1]['srid'] != 27582)
		  {
		    $sql .= ", Contains(the_geom, Transform('".$loc[1]['datas']."', 27582)) AS contained".$i;
		    $sql .= ", AsText(Transform('".$loc[1]['datas']."', 27582)) AS coords".$i;
		    $subconds[] = " Contains(the_geom, Transform('".$loc[1]['datas']."', 27582)) ";
		  }
		else
		  {
		    $subconds[] = " Contains(the_geom, Transform('".$loc[1]['datas']."', 27582)) ";
		    $sql .= ", Contains(the_geom, '".$loc[1]['datas']."') AS contained".$i;
		    $sql .= ", AsText('".$loc[1]['datas']."') AS coords".$i;
		  }
		$i++;
	      }
	    $subrq .= implode (' OR ', $subconds);
	  }
	$sql .="
                FROM
                       deptfr\n";
	if ($subrq)
	  $sql .= " WHERE
                       code_dept IN (".$subrq.");";

	$rq = new pgrequete($this->pgsqldb, $sql);

	$rs = $rq->get_all_rows();

	$this->get_locs_coords($rs[0]);
	$this->parse_polygons($rs);

	return;
      }
  }

  function get_locs_coords($datas)
  {
    if (count($datas) <= 0)
      return;

    /** on récupère les coordonnées projetées des lieux
     * dans le bon système
     */
    foreach ($this->locs as &$loc)
      {
	$coordspt = $datas['coords' . $loc[2]];
	$coordspt = str_replace('POINT(', '', $coordspt);
	$coordspt = str_replace(')', '', $coordspt);
	$coordspt = explode(' ', $coordspt);

	$loc[1]['long'] = $coordspt[0];
	$loc[1]['lat'] =  $coordspt[1];
      }

  }

  function parse_polygons($datas, $hl = false)
  {
    if ((!is_array($datas)) || (count($datas) <= 0))
      return;

    $numplg = 0;

    if ($hl == false)
      $arraysto = &$this->contexts;
    else
      $arraysto = &$this->hlcontexts;

    foreach($datas as $data)
      {
	$astext = $data['points'];
	$matched = array();

	preg_match_all("/\(([^)]*)\)/", $astext, $matched);


	foreach ($matched[1] as $polygon)
	  {
	    $polygon = str_replace("(", "", $polygon);
	    $points = explode(",", $polygon);

	    foreach ($points as $point)
	      {
		$coord = explode(" ", $point);
		$step = count($arraysto[$numplg]);

		/* premier point */
		if ($step == 0)
		  {
		    $arraysto[$numplg][] = $coord[0];
		    $arraysto[$numplg][] = $coord[1];
		  }
		/* points suivants : détection de la connerie */
		else if (checkcoords($arraysto[$numplg][$step - 2],
				     $arraysto[$numplg][$step - 1],
				     $coord[0],
				     $coord[1],
				     10000000)) // tolérance
		  {
		    $arraysto[$numplg][] = $coord[0];
		    $arraysto[$numplg][] = $coord[1];
		  }
	      } // points du polygone
	    $numplg++;
	  } // polygones
      } // lignes de résultat pgsql
  }

  /*
   * Génération de l'image
   *
   */
  function generate_img()
  {
    $myimg = new imgcarto($this->width, 10);

    $myimg->addcolor("red", 255, 0, 0);
    $myimg->addcolor("grey", 210, 210, 210);
    $myimg->addcolor("pgreen", 184, 255, 184);
    $myimg->addcolor("porange", 238, 172, 0);

    if (count($this->contexts))
      {
	foreach($this->contexts as $plg)
	  {
	    if (count($plg) >= 6)
	      {
		$myimg->addpolygon($plg, 'porange', true);
		$myimg->addpolygon($plg, 'grey',   false);
	      }
	  }
      }

    if (count($this->hlcontexts))
      {
	foreach($this->hlcontexts as $plg)
	  {
	    if (count($plg) >= 6)
	      {
		$myimg->addpolygon($plg, 'red', true);
		$myimg->addpolygon($plg, 'grey',   false);
	      }
	  }
      }

    if (count($this->locs))
      {
	foreach($this->locs as $loc)
	  {

	    if ($loc[3] == false)
	      {
		$myimg->addpointwithlegend($loc[1]['long'],
					   $loc[1]['lat'],
					   10,
					   "red",
					   12,
					   0,
					   $loc[0],
					   "black");
	      }
	    if ($loc[3] == true)
	      {
		$myimg->addpointwithlegend($loc[1]['long'],
					   $loc[1]['lat'],
					   10,
					   "red",
					   12,
					   0,
					   $loc[0],
					   "red");
	      }

	  }
      }
    if (count($this->steps) >= 2)
      {
	for($i = 1; $i < count($this->steps); $i++)
	  {
	    $loc  = &$this->locs[$this->steps[$i]];
	    $ploc = &$this->locs[$this->steps[$i-1]];
	    $myimg->addline($loc[1]['long'], $loc[1]['lat'],
			    $ploc[1]['long'], $ploc[1]['lat'],
			    'black');
	  }
      }

    $myimg->draw();
    return $myimg;

  }

}

function checkcoords($lx, $ly, $x, $y, $tolerance)
{
  if (sqrt(pow($x - $lx, 2) + pow($y - $ly, 2)) > $tolerance)
    return false;
  return true;
}

?>
