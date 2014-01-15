<?php

/* Copyright 2007
 *
 * - Julien Etelain < julien at pmad dot net >
 *
 * "AE Recherche & Developpement" : Galaxy
 *
 * Ce fichier fait partie du site de l'Association des étudiants
 * de l'UTBM, http://ae.utbm.fr.
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

define("GALAXY_SCORE_1PTPHOTO",1);
define("GALAXY_SCORE_PARRAINAGE",15);
define("GALAXY_SCORE_2ANNEESACTIFASSO",15);
define("GALAXY_SCORE_2ANNEESASSO",5);
define("GALAXY_MINSCORE",10);

/**
 * @defgroup useless Les trucs inutiles
 */

/**
 * Gestionnaire de Galaxy
 *
 *
 * @ingroup useless
 * @author Julien Etelain
 */
class galaxy
{
  var $db;
  var $dbrw;

  var $width;
  var $height;

  var $done_pre_cycle;

  function galaxy ( &$db, &$dbrw )
  {
    $this->db = $db;
    $this->dbrw = $dbrw;
    $this->done_pre_cycle=false;
  }

  /**
   * Vérifie que des données puevent être montrés aux utilisateurs.
   * Pour cela vérifie qu'il existe des éléments avec des coordonnées en pixels.
   */
  function is_ready_public()
  {
    $req = new requete($this->db,"SELECT * FROM galaxy_star WHERE rx_star IS NOT NULL AND ry_star IS NOT NULL LIMIT 1");
    if ( $req->lines != 1 )
      return false;
    return true;
  }


  function scores ()
  {
    $liens = array();

    // 1- Cacul du score

    // a- Les photos : 1pt / n photo ensemble
    $req = new requete($this->db, "SELECT COUNT( * ) as c, p1.id_utilisateur as u1, p2.id_utilisateur as u2 ".
    "FROM `sas_personnes_photos` AS `p1` ".
    "JOIN `sas_personnes_photos` AS `p2` ON ( p1.id_photo = p2.id_photo ".
    "AND p1.id_utilisateur != p2.id_utilisateur ) ".
    "LEFT JOIN utilisateurs usr1 ON (p1.id_utilisateur = usr1.id_utilisateur) ".
    "LEFT JOIN utilisateurs usr2 ON (p2.id_utilisateur = usr2.id_utilisateur) ".
    "WHERE usr1.publique_utl != '0' ".
    "AND usr2.publique_utl != '0' ".
    "GROUP BY p1.id_utilisateur, p2.id_utilisateur");

    while ( $row = $req->get_row() )
    {
      $a = min($row['u1'],$row['u2']);
      $b = max($row['u1'],$row['u2']);

      $liens[$a][$b] = round($row['c']/GALAXY_SCORE_1PTPHOTO);
    }

    // b- Parrainage : n pt / relation parrain-fillot
    $req = new requete($this->db, "SELECT parrains.id_utilisateur as u1, id_utilisateur_fillot as u2 ".
    "FROM `parrains` ".
    "LEFT JOIN utilisateurs usr1 ON (parrains.id_utilisateur = usr1.id_utilisateur) ".
    "LEFT JOIN utilisateurs usr2 ON (id_utilisateur_fillot = usr2.id_utilisateur) ".
    "WHERE usr1.publique_utl != '0' ".
    "AND usr2.publique_utl != '0' ".
    "GROUP BY parrains.id_utilisateur, id_utilisateur_fillot");
    while ( $row = $req->get_row() )
    {
      $a = min($row['u1'],$row['u2']);
      $b = max($row['u1'],$row['u2']);

      if ( isset($liens[$a][$b]) )
        $liens[$a][$b] += GALAXY_SCORE_PARRAINAGE;
      else
        $liens[$a][$b] = GALAXY_SCORE_PARRAINAGE;
    }

    // c- associations et clubs : 1pt / n jours ensemble / assos
    $req = new requete($this->db,"SELECT a.id_utilisateur as u1,b.id_utilisateur as u2,
  SUM(DATEDIFF(LEAST(COALESCE(a.date_fin,NOW()),COALESCE(b.date_fin,NOW())),GREATEST(a.date_debut,b.date_debut))) AS together
  FROM asso_membre AS a
  JOIN asso_membre AS b ON
  (
  a.id_utilisateur < b.id_utilisateur
  AND a.id_asso = b.id_asso
  AND GREATEST(a.date_debut,b.date_debut) < LEAST(COALESCE(a.date_fin,NOW()),COALESCE(b.date_fin,NOW()))
  )
  LEFT JOIN utilisateurs usr1 ON (a.id_utilisateur = usr1.id_utilisateur)
  LEFT JOIN utilisateurs usr2 ON (b.id_utilisateur = usr2.id_utilisateur)
  WHERE usr1.publique_utl != '0'
  AND usr2.publique_utl != '0'
  AND NOT (a.role > 0 AND b.role > 0)
  GROUP BY a.id_utilisateur,b.id_utilisateur
  ORDER BY a.id_utilisateur,b.id_utilisateur");

    while ( $row = $req->get_row() )
    {
      $a = min($row['u1'],$row['u2']);
      $b = max($row['u1'],$row['u2']);

      if ( isset($liens[$a][$b]) )
        $liens[$a][$b] += round((1-exp(-$row['together']/365))*GALAXY_SCORE_2ANNEESASSO);
      else
        $liens[$a][$b] = round((1-exp(-$row['together']/365))*GALAXY_SCORE_2ANNEESASSO);
    }

  $req = new requete($this->db,"SELECT a.id_utilisateur as u1,b.id_utilisateur as u2,
  SUM(DATEDIFF(LEAST(COALESCE(a.date_fin,NOW()),COALESCE(b.date_fin,NOW())),GREATEST(a.date_debut,b.date_debut))) AS together
  FROM asso_membre AS a
  JOIN asso_membre AS b ON
  (
  a.id_utilisateur < b.id_utilisateur
  AND a.id_asso = b.id_asso
  AND GREATEST(a.date_debut,b.date_debut) < LEAST(COALESCE(a.date_fin,NOW()),COALESCE(b.date_fin,NOW()))
  )
  LEFT JOIN utilisateurs usr1 ON (a.id_utilisateur = usr1.id_utilisateur)
  LEFT JOIN utilisateurs usr2 ON (b.id_utilisateur = usr2.id_utilisateur)
  WHERE usr1.publique_utl != '0'
  AND usr2.publique_utl != '0'
  AND a.role > 0
  AND b.role > 0
  GROUP BY a.id_utilisateur,b.id_utilisateur
  ORDER BY a.id_utilisateur,b.id_utilisateur");

    while ( $row = $req->get_row() )
    {
      $a = min($row['u1'],$row['u2']);
      $b = max($row['u1'],$row['u2']);

      if ( isset($liens[$a][$b]) )
        $liens[$a][$b] += round((1-exp(-$row['together']/365))*GALAXY_SCORE_2ANNEESACTIFASSO);
      else
        $liens[$a][$b] = round((1-exp(-$row['together']/365))*GALAXY_SCORE_2ANNEESACTIFASSO);
    }

    // 2- On vire les liens pas significatifs
    foreach ( $liens as $a => $data )
    {
      foreach ( $data as $b => $score )
        if ( $score < GALAXY_MINSCORE )
          unset($liens[$a][$b]);
    }

    return $liens;
  }


  /**
   * Initialise une nouvelle galaxy, le "big bang" en quelque sorte
   */
  function init ( )
  {
    new requete($this->dbrw,"TRUNCATE `galaxy_link`");
    new requete($this->dbrw,"TRUNCATE `galaxy_star`");

    $liens = $this->scores();

    // 3- On crée les peronnes requises
    $stars = array();
    foreach ( $liens as $a => $data )
    {
      if ( !isset($stars[$a]) )
        $stars[$a] = $a;

      foreach ( $data as $b => $score )
        if ( !isset($stars[$b]) )
          $stars[$b] = $b;
    }

    $gx=0;
    $gy=0;

    $width = floor(sqrt(count($stars)));

    foreach ( $stars as $id )
    {
      new insert($this->dbrw,"galaxy_star",array( "id_star"=>$id, "x_star" => $gx, "y_star" => $gy ));
      $gx++;
      if ( $gx > $width )
      {
        $gx=0;
        $gy++;
      }
    }

    // 4- On crée les liens
    foreach ( $liens as $a => $data )
      foreach ( $data as $b => $score )
        new insert($this->dbrw,"galaxy_link",array( "id_star_a"=>$a, "id_star_b"=>$b, "tense_link" => $score ));

    //fixe_star
    new requete($this->dbrw, "UPDATE galaxy_star SET max_tense_star = ( SELECT MAX(tense_link) FROM galaxy_link WHERE id_star_a=id_star OR id_star_b=id_star )");
    new requete($this->dbrw, "UPDATE galaxy_star SET sum_tense_star = ( SELECT SUM(tense_link) FROM galaxy_link WHERE id_star_a=id_star OR id_star_b=id_star )");
    new requete($this->dbrw, "UPDATE galaxy_star SET nblinks_star = ( SELECT COUNT(*) FROM galaxy_link WHERE id_star_a=id_star OR id_star_b=id_star )");
    new requete($this->dbrw, "UPDATE galaxy_link SET max_tense_stars_link=( SELECT AVG(max_tense_star) FROM galaxy_star WHERE id_star=id_star_a OR id_star=id_star_b )");

    new requete($this->dbrw, "UPDATE galaxy_link SET ideal_length_link=0.25+((1-(tense_link/max_tense_stars_link))*30)");
    new requete($this->dbrw, "DELETE FROM galaxy_star WHERE nblinks_star = 0");

  }

  function update()
  {
    $liens = $this->scores();

    $stars = array();
    foreach ( $liens as $a => $data )
    {
      if ( !isset($stars[$a]) )
        $stars[$a] = $a;

      foreach ( $data as $b => $score )
        if ( !isset($stars[$b]) )
          $stars[$b] = $b;
    }

    $prev_stars = array();
    //$prev_liens = array();

    $req = new requete($this->dbrw, "SELECT id_star FROM galaxy_star");

    while ( list($id) = $req->get_row() )
      $prev_stars[$id] = $id;

    //$req = new requete($this->dbrw, "SELECT id_star_a, id_star_b, tense_link FROM galaxy_link");

    //while ( list($a,$b,$c) = $req->get_row() )
      //$prev_liens[$a][$b] = $c;

    // enlève les anciennes étoiles
    foreach ( $prev_stars as $id )
      if ( !isset($stars[$id]) )
        new delete($this->dbrw,"galaxy_star",array( "id_star"=>$id) );

    // enlève les anciens liens
    //foreach ( $prev_liens as $a => $data )
      //foreach ( $data as $b => $score )
        //if (!isset($liens[$a][$b]) )
          //new delete($this->dbrw,"galaxy_link",array( "id_star_a"=>$a, "id_star_b"=>$b));
    
    $req = new requete($this->dbrw, "DELETE FROM galaxy_link");

    if ( count($prev_stars) == 0 )
    {
      $x1=0;
      $y1=0;
      $cw=10;
    }
    else
    {
      list($x1,$y1,$x2,$y2) = $this->limits();
      $cw = max($x2-$x1,$y2-$y1);
    }

    // ajoute les nouvelles étoiles
    foreach ( $stars as $id )
      if ( !isset($prev_stars[$id]) )
      {
        list($nx,$ny) = $this->find_low_density_point($x1,$y1,$cw);
        new insert($this->dbrw,"galaxy_star",array( "id_star"=>$id, "x_star" => $nx, "y_star" => $ny ));
      }

    // ajoute les nouveaux liens
    foreach ( $liens as $a => $data )
      foreach ( $data as $b => $score )
        //if (!isset($prev_liens[$a][$b]) )
          new insert($this->dbrw,"galaxy_link",array( "id_star_a"=>$a, "id_star_b"=>$b, "tense_link" => $score ));

    // met à jour les anciens liens
    //foreach ( $liens as $a => $data )
    //  foreach ( $data as $b => $score )
    //    if ( isset($prev_liens[$a][$b]) && $prev_liens[$a][$b] != $score )
    //      new update($this->dbrw,"galaxy_link",array("tense_link"=>$score),array("id_star_a"=>$a,"id_star_b"=>$b));

    // met à jour les champs calculés
    new requete($this->dbrw, "UPDATE galaxy_star SET max_tense_star = ( SELECT MAX(tense_link) FROM galaxy_link WHERE id_star_a=id_star OR id_star_b=id_star )");
    new requete($this->dbrw, "UPDATE galaxy_star SET sum_tense_star = ( SELECT SUM(tense_link) FROM galaxy_link WHERE id_star_a=id_star OR id_star_b=id_star )");
    new requete($this->dbrw, "UPDATE galaxy_star SET nblinks_star = ( SELECT COUNT(*) FROM galaxy_link WHERE id_star_a=id_star OR id_star_b=id_star )");
    new requete($this->dbrw, "UPDATE galaxy_link SET max_tense_stars_link=( SELECT AVG(max_tense_star) FROM galaxy_star WHERE id_star=id_star_a OR id_star=id_star_b )");

    new requete($this->dbrw, "UPDATE galaxy_link SET ideal_length_link=0.25+((1-(tense_link/max_tense_stars_link))*30)");
    new requete($this->dbrw, "DELETE FROM galaxy_star WHERE nblinks_star = 0");

  }



  /**
   * Préalable à une série de cycles.
   * Efface les coordonnées en pixels de tous les éléments.
   */
  function pre_cycle ()
  {
    // s'assure que is_ready_public() renverra false pendant les calculs
    // se débloquera après un appel à pre_render() (causé par render() ou mini_render())
    new requete($this->dbrw,"UPDATE `galaxy_star` SET rx_star = NULL, ry_star = NULL");
    $this->done_pre_cycle=true;
  }

  /**
   * Cycle.
   * Produit les mouvements des objets par le calculs des contraintes aux quels ils sont soumis.
   * Attention: Il n'y a pas de notion d'accélération ou d'intertie.
   * Provoque un appel de pre_cycle() s'il n'a pas eu lieu.
   */
  function cycle ( $detectcollision=false )
  {
    if ( !$this->done_pre_cycle )
      $this->pre_cycle();

    new requete($this->dbrw,"UPDATE galaxy_link, galaxy_star AS a, galaxy_star AS b SET ".
    "vx_link = b.x_star-a.x_star, ".
    "vy_link = b.y_star-a.y_star  ".
    "WHERE a.id_star = galaxy_link.id_star_a AND b.id_star = galaxy_link.id_star_b");
    new requete($this->dbrw,"UPDATE galaxy_link SET length_link = SQRT(POW(vx_link,2)+POW(vy_link,2))");
    new requete($this->dbrw,"UPDATE galaxy_link SET dx_link=vx_link/length_link, dy_link=vy_link/length_link WHERE length_link != 0");
    new requete($this->dbrw,"UPDATE galaxy_link SET dx_link=0, dy_link=0 WHERE length_link = ideal_length_link");
    new requete($this->dbrw,"UPDATE galaxy_link SET dx_link=RAND(), dy_link=RAND() WHERE length_link != ideal_length_link AND dx_link=0 AND dy_link=0");

    $req = new requete($this->db,"SELECT MAX(length_link/ideal_length_link),AVG(length_link/ideal_length_link) FROM galaxy_link");

    $reducer=1000;

    if ( $req->lines > 0 )
    {
      list($max,$avg) = $req->get_row();
      if ( $max > 1000 )
      {
        echo "failed due to expension";
        exit();
      }
      if ( !is_null($max) && $max > 0 )
        $reducer = max(25,round($max)*3);
      //echo $max." ".$avg." (".$reducer.") - ";
    }

    new requete($this->dbrw,"UPDATE galaxy_link, galaxy_star AS a, galaxy_star AS b SET  ".
    "delta_link_a=(length_link-ideal_length_link)/ideal_length_link/$reducer, ".
    "delta_link_b=(length_link-ideal_length_link)/ideal_length_link/$reducer*-1 ".
    "WHERE a.id_star = galaxy_link.id_star_a AND b.id_star = galaxy_link.id_star_b");

    new requete($this->dbrw,"UPDATE galaxy_star SET ".
    "dx_star = COALESCE(( SELECT SUM( delta_link_a * dx_link ) FROM galaxy_link WHERE id_star_a = id_star ),0) + ".
      "COALESCE((SELECT SUM( delta_link_b * dx_link ) FROM galaxy_link WHERE id_star_b = id_star ),0), ".
    "dy_star = COALESCE(( SELECT SUM( delta_link_a * dy_link ) FROM galaxy_link WHERE id_star_a = id_star ),0) + ".
      "COALESCE((SELECT SUM( delta_link_b * dy_link ) FROM galaxy_link WHERE id_star_b = id_star ),0) WHERE fixe_star != 1");
    if ( $detectcollision )
    {
      new requete($this->dbrw,"UPDATE galaxy_star AS a, galaxy_star AS b SET a.dx_star=0, a.dy_star=0, b.dx_star=0, b.dy_star=0 WHERE a.id_star != b.id_star AND POW(a.x_star+a.dx_star-b.x_star-b.dx_star,2)+POW(a.y_star+a.dy_star-b.y_star-b.dy_star,2) < 0.05");
      new requete($this->dbrw,"UPDATE galaxy_star AS a, galaxy_star AS b SET a.dx_star=0, a.dy_star=0, b.dx_star=0, b.dy_star=0 WHERE a.id_star != b.id_star AND POW(a.x_star+a.dx_star-b.x_star-b.dx_star,2)+POW(a.y_star+a.dy_star-b.y_star-b.dy_star,2) < 0.05");
    }
    new requete($this->dbrw,"UPDATE galaxy_star SET x_star = x_star + dx_star, y_star = y_star + dy_star WHERE dx_star != 0 OR dy_star != 0 AND fixe_star != 1");


  }

  /**
   * Provoque un deplacement aleatoir de tous les éléments (+/- 5 sur x et y)
   */
  function rand()
  {
    new requete($this->dbrw, "UPDATE `galaxy_star` SET x_star = x_star+5-( RAND( ) *10 ), y_star = y_star+5-( RAND( ) *10) WHERE fixe_star != 1");
  }

  /**
   * Optimise le placement de certains éléments en les renvoyant dans des zones peu denses
   */
  function optimize()
  {

    $req = new requete($this->db,
    "SELECT a.id_star, b.x_star, b.y_star, l.ideal_length_link
     FROM galaxy_star AS a
     INNER JOIN galaxy_link AS l ON ( l.id_star_a = a.id_star )
     INNER JOIN galaxy_star AS b ON ( l.id_star_b = b.id_star )
     WHERE a.nblinks_star=1 AND b.nblinks_star > 1
     UNION
     SELECT b.id_star, a.x_star, a.y_star, l.ideal_length_link
     FROM galaxy_star AS b
     INNER JOIN galaxy_link AS l ON ( l.id_star_b = b.id_star )
     INNER JOIN galaxy_star AS a ON ( l.id_star_a = a.id_star )
     WHERE b.nblinks_star=1 AND a.nblinks_star > 1");

    while ( list($id,$cx,$cy,$l) = $req->get_row() )
    {
      list($nx,$ny) = $this->find_low_density_point($cx-$l,$cy-$l,$l*2,$id);
      $nx = sprintf("%.f",$nx);
      $ny = sprintf("%.f",$ny);
      //echo "MOVE $id to ($nx, $ny)<br/>\n";
      new requete ( $this->dbrw, "UPDATE galaxy_star set x_star=$nx, y_star=$ny WHERE id_star=$id AND fixe_star != 1");
    }

    list($x1,$y1,$x2,$y2) = $this->limits();
    $cw = max($x2-$x1,$y2-$y1);

    $req = new requete($this->db,
    "SELECT a.id_star, b.id_star, b.x_star, b.y_star
     FROM galaxy_star AS a
     INNER JOIN galaxy_link AS l ON ( l.id_star_a = a.id_star )
     INNER JOIN galaxy_star AS b ON ( l.id_star_b = b.id_star )
     WHERE a.nblinks_star=1 AND b.nblinks_star=1");

    while ( list($ida,$idb,$x,$y) = $req->get_row() )
    {
      $d = $this->get_density ( $x-1, $y-1, $x+1, $y+1, "$ida,$idb" );
      if ( $d > 5 )
      {
        list($nx,$ny) = $this->find_low_density_point($x1,$y1,$cw,"$ida,$idb");
        //echo "MOVE $ida,$idb to ($nx, $ny)<br/>\n";
        new requete ( $this->dbrw, "UPDATE galaxy_star set x_star=$nx, y_star=$ny WHERE id_star=$ida OR id_star=$idb fixe_star != 1");
      }
    }

  }

  function star_color ( $img, $i )
  {
    if ( $i > 800 )
      return imagecolorallocate($img, 255, 255, 255);

    if ( $i > 700 )
      return imagecolorallocate($img, (($i-700)*255/100), (($i-700)*255/100), 255);

    if ( $i > 400 )
      return imagecolorallocate($img, 255 -(($i-400)*255/300), 255 -(($i-400)*255/300), ($i-400)*255/300);

    if ( $i > 35 )
      return imagecolorallocate($img, 255, ($i-35)*255/365, 0);

    return imagecolorallocate($img, $i*255/36, 0, 0);
  }

  /**
   * Préalable au rendu: fixe les coordonnées en pixels de tous les éléments
   */
  function pre_render ($tx=10000)
  {
    $req = new requete($this->db, "SELECT MIN(x_star), MIN(y_star), MAX(x_star), MAX(y_star) FROM  galaxy_star");
    list($top_x,$top_y,$bottom_x,$bottom_y) = $req->get_row();

    $top_x = floor($top_x);
    $top_y = floor($top_y);
    $bottom_x = ceil($bottom_x);
    $bottom_y = ceil($bottom_y);

    $mult_x = floor(10000/($bottom_x-$top_x));
    $mult_y = floor(10000/($bottom_y-$top_y));

    $this->width = $tx;//($bottom_x-$top_x)*$tx;
    $this->height = $tx;//($bottom_y-$top_y)*$tx;

    $req=new requete($this->dbrw,"UPDATE galaxy_star SET rx_star = (x_star-($top_x)) * $mult_x, ry_star = (y_star-($top_y)) * $mult_y");
  }

  /**
   * Fait le rendu de la mignature de galaxy
   */
  function mini_render ( $mini_target="mini_galaxy_temp.png")
  {
    if ( empty($this->width) || empty($this->height) )
      $this->pre_render();

    $img = imagecreatetruecolor($this->width/50,$this->height/50);
    $bg = imagecolorallocate($img, 0, 0, 0);
    imagefill($img, 0, 0, $bg);
    $req = new requete($this->db, "SELECT FLOOR(rx_star/50),FLOOR(ry_star/50),SUM(sum_tense_star) FROM  galaxy_star GROUP BY FLOOR(rx_star/50),FLOOR(ry_star/50)");

    while ( list($x,$y,$d) = $req->get_row() )
      imagesetpixel($img,$x,$y,$this->star_color($img,$d));

    $img2 = imagecreatetruecolor($this->width/100,$this->height/100);
    imagecopyresampled($img2,$img,0,0,0,0,$this->width/100,$this->height/100,$this->width/50,$this->height/50);
    if(is_writable("/var/www/ae2/data/img/"))
      imagepng($img2,$mini_target);
    imagedestroy($img2);
    imagedestroy($img);
  }

  /**
   * Fait le rendu de l'image globale de galaxy
   */
  function render ($target="galaxy_temp.png")
  {
    if ( empty($this->width) || empty($this->height) )
      $this->pre_render();

    $img = imagecreatetruecolor($this->width,$this->height);

    if ( $img === false )
    {
      echo "failed imagecreatetruecolor($width,$height);";
      exit();
    }

    $bg = imagecolorallocate($img, 0, 0, 0);
    $textcolor = imagecolorallocate($img, 255, 255, 255);
    $wirecolor = imagecolorallocate($img, 32, 32, 32);

    imagefill($img, 0, 0, $bg);

    imagestring($img, 1, 0, 0, "AE R&D - GALAXY", $textcolor);

    for($i=0;$i<820;$i++)
    {
      imageline($img,$i,10,$i,20,$this->star_color($img,$i));

      if ( $i %100 == 0)
        imagestring($img, 1, $i, 22, $i, $textcolor);
    }

    $req = new requete($this->db, "SELECT ABS(length_link-ideal_length_link) as ex, ".
    "a.rx_star as x1, a.ry_star as y1, b.rx_star as x2, b.ry_star as y2 ".
    "FROM  galaxy_link ".
    "INNER JOIN galaxy_star AS a ON (a.id_star=galaxy_link.id_star_a) ".
    "INNER JOIN galaxy_star AS b ON (b.id_star=galaxy_link.id_star_b)");

    while ( $row = $req->get_row() )
    {
      imageline ($img, $row['x1'], $row['y1'], $row['x2'], $row['y2'], $wirecolor );
    }

    $req = new requete($this->db, "SELECT ".
    "rx_star, ry_star, sum_tense_star  ".
    "FROM  galaxy_star");

    while ( $row = $req->get_row() )
    {
      imagefilledellipse ($img, $row['rx_star'], $row['ry_star'], 5, 5, $this->star_color($img,$row['sum_tense_star']) );
    }

    $req = new requete($this->db, "SELECT ".
    "rx_star, ry_star, COALESCE(surnom_utbm, CONCAT(prenom_utl,' ',nom_utl), alias_utl) AS nom ".
    "FROM  galaxy_star ".
    "INNER JOIN utilisateurs ON (utilisateurs.id_utilisateur=galaxy_star.id_star)".
    "INNER JOIN `utl_etu_utbm` ON (`utl_etu_utbm`.`id_utilisateur` = `utilisateurs`.`id_utilisateur`)");

    while ( $row = $req->get_row() )
    {
      imagestring($img, 1, $row['rx_star']+5, $row['ry_star']-3,  utf8_decode($row['nom']), $textcolor);
    }

    if ( is_null($target) )
      imagepng($img);
    else
      imagepng($img,$target);

    imagedestroy($img);

  }

  /**
   * Fait le rendu d'une zone de galaxy
   */
  function render_area ( $tx, $ty, $w, $h, $target=null, $highlight=null )
  {
    $x1 = $tx-100; // Pour les textes
    $y1 = $ty-3;
    $x2 = $tx+$w+3;
    $y2 = $ty+$h+3;

    $img = imagecreatetruecolor($w,$h);

    $bg = imagecolorallocate($img, 0, 0, 0);
    $textcolor = imagecolorallocate($img, 255, 255, 255);
    $wirecolor = imagecolorallocate($img, 32, 32, 32);

    imagefill($img, 0, 0, $bg);

    $req = new requete($this->db, "SELECT ABS(length_link-ideal_length_link) as ex, ".
    "a.rx_star as x1, a.ry_star as y1, b.rx_star as x2, b.ry_star as y2 ".
    "FROM  galaxy_link ".
    "INNER JOIN galaxy_star AS a ON (a.id_star=galaxy_link.id_star_a) ".
    "INNER JOIN galaxy_star AS b ON (b.id_star=galaxy_link.id_star_b)");

    while ( $row = $req->get_row() )
    {
      imageline ($img, $row['x1']-$tx, $row['y1']-$ty, $row['x2']-$tx, $row['y2']-$ty, $wirecolor );
    }

    if ( is_null($highlight ) ) // Normal render
    {
      $req = new requete($this->db, "SELECT ".
      "rx_star, ry_star, sum_tense_star  ".
      "FROM  galaxy_star ".
      "WHERE rx_star >= $x1 AND rx_star <= $x2 AND ry_star >= $y1 AND ry_star <= $y2");

      while ( $row = $req->get_row() )
        imagefilledellipse ($img, $row['rx_star']-$tx, $row['ry_star']-$ty, 5, 5, $this->star_color($img,$row['sum_tense_star']) );

      $req = new requete($this->db, "SELECT ".
      "rx_star, ry_star, COALESCE(surnom_utbm, CONCAT(prenom_utl,' ',nom_utl), alias_utl) AS nom ".
      "FROM  galaxy_star ".
      "INNER JOIN utilisateurs ON (utilisateurs.id_utilisateur=galaxy_star.id_star) ".
      "LEFT JOIN `utl_etu_utbm` ON (`utl_etu_utbm`.`id_utilisateur` = `utilisateurs`.`id_utilisateur`)".
      "WHERE rx_star >= $x1 AND rx_star <= $x2 AND ry_star >= $y1 AND ry_star <= $y2" );

      while ( $row = $req->get_row() )
        imagestring($img, 1, $row['rx_star']+5-$tx, $row['ry_star']-3-$ty,  utf8_decode($row['nom']), $textcolor);

    }
    else
    {
      $ids = implode(",",$highlight);

      $wirecolor = imagecolorallocate($img, 64, 64, 64);

      $req = new requete($this->db, "SELECT ABS(length_link-ideal_length_link) as ex, ".
      "a.rx_star as x1, a.ry_star as y1, b.rx_star as x2, b.ry_star as y2 ".
      "FROM  galaxy_link ".
      "INNER JOIN galaxy_star AS a ON (a.id_star=galaxy_link.id_star_a) ".
      "INNER JOIN galaxy_star AS b ON (b.id_star=galaxy_link.id_star_b) ".
      "WHERE a.id_star = ".$highlight[0]." OR b.id_star = ".$highlight[0]."");

      while ( $row = $req->get_row() )
        imageline ($img, $row['x1']-$tx, $row['y1']-$ty, $row['x2']-$tx, $row['y2']-$ty, $wirecolor );

      $textcolor = imagecolorallocate($img, 128, 128, 128);

       $req = new requete($this->db, "SELECT ".
      "rx_star, ry_star, sum_tense_star  ".
      "FROM  galaxy_star ".
      "WHERE id_star NOT IN ($ids) AND rx_star >= $x1 AND rx_star <= $x2 AND ry_star >= $y1 AND ry_star <= $y2");

      while ( $row = $req->get_row() )
        imagefilledellipse ($img, $row['rx_star']-$tx, $row['ry_star']-$ty, 5, 5, $this->star_color($img,$row['sum_tense_star']) );

      $req = new requete($this->db, "SELECT ".
      "rx_star, ry_star, COALESCE(surnom_utbm, CONCAT(prenom_utl,' ',nom_utl), alias_utl) AS nom ".
      "FROM  galaxy_star ".
      "INNER JOIN utilisateurs ON (utilisateurs.id_utilisateur=galaxy_star.id_star) ".
      "LEFT JOIN `utl_etu_utbm` ON (`utl_etu_utbm`.`id_utilisateur` = `utilisateurs`.`id_utilisateur`)".
      "WHERE id_star NOT IN ($ids) AND rx_star >= $x1 AND rx_star <= $x2 AND ry_star >= $y1 AND ry_star <= $y2" );

      while ( $row = $req->get_row() )
        imagestring($img, 1, $row['rx_star']+5-$tx, $row['ry_star']-3-$ty,  utf8_decode($row['nom']), $textcolor);

      $textcolor = imagecolorallocate($img, 255, 255, 255);

       $req = new requete($this->db, "SELECT ".
      "rx_star, ry_star, sum_tense_star  ".
      "FROM  galaxy_star ".
      "WHERE id_star IN ($ids) AND rx_star >= $x1 AND rx_star <= $x2 AND ry_star >= $y1 AND ry_star <= $y2");

      while ( $row = $req->get_row() )
        imagefilledellipse ($img, $row['rx_star']-$tx, $row['ry_star']-$ty, 5, 5, $this->star_color($img,$row['sum_tense_star']) );

      $req = new requete($this->db, "SELECT ".
      "rx_star, ry_star, COALESCE(surnom_utbm, CONCAT(prenom_utl,' ',nom_utl), alias_utl) AS nom ".
      "FROM  galaxy_star ".
      "INNER JOIN utilisateurs ON (utilisateurs.id_utilisateur=galaxy_star.id_star) ".
      "LEFT JOIN `utl_etu_utbm` ON (`utl_etu_utbm`.`id_utilisateur` = `utilisateurs`.`id_utilisateur`)".
      "WHERE id_star IN ($ids) AND rx_star >= $x1 AND rx_star <= $x2 AND ry_star >= $y1 AND ry_star <= $y2" );

      while ( $row = $req->get_row() )
        imagestring($img, 1, $row['rx_star']+5-$tx, $row['ry_star']-3-$ty,  utf8_decode($row['nom']), $textcolor);

    }

    if ( is_null($target) )
      imagepng($img);
    else
      imagepng($img,$target);

    imagedestroy($img);

  }

  /**
   * Calcule le nombre d'objet dans la zone donnée.
   */
  function get_density ( $x1, $y1, $x2, $y2, $except=null )
  {
    $x1 = str_replace(",",".",sprintf("%.f",$x1));
    $y1 = str_replace(",",".",sprintf("%.f",$y1));
    $x2 = str_replace(",",".",sprintf("%.f",$x2));
    $y2 = str_replace(",",".",sprintf("%.f",$y2));

    //echo "get_density($x1,$y1,$x2,$y2) = ";

    if (is_null($except) )
      $req = new requete($this->db, "SELECT ".
        "COUNT(*)  ".
        "FROM  galaxy_star ".
        "WHERE x_star >= $x1 AND x_star < $x2 AND y_star >= $y1 AND y_star < $y2");
    else
      $req = new requete($this->db, "SELECT ".
        "COUNT(*)  ".
        "FROM  galaxy_star ".
        "WHERE id_star NOT IN (".$except.") AND x_star >= $x1 AND x_star < $x2 AND y_star >= $y1 AND y_star < $y2");

    list($count) = $req->get_row();

    //echo $count."<br/>\n";

    return $count;
  }

  /**
   * Cherche un point où la densité est faible dans la zone donnée.
   */
  function find_low_density_point ( $x, $y, $s, $except=null )
  {
    $ld = null;
    $lx = null;
    $ly = null;

    $cx = $x;
    $cy = $y;

    for($cx=$x;$cx<$x+$s;$cx+=$s/3)
    {
      for($cy=$y;$cy<$y+$s;$cy+=$s/3)
      {
        $d = $this->get_density($cx,$cy,$cx+($s/3),$cy+($s/3), $except);
        if ( $d == 0 )
          return array($cx+($s/6),$cy+($s/6));

        if ( is_null($ld) || $ld > $d )
        {
          $lx = $cx;
          $ly = $cy;
          $ld = $d;
        }
      }
    }

    if ( $s < 0.001 )
      return array($lx+($s/6),$ly+($s/6));

    return $this->find_low_density_point($lx,$ly,$s/3, $except);
  }

  function limits()
  {
    $req = new requete($this->db, "SELECT MIN(x_star), MIN(y_star), MAX(x_star), MAX(y_star) FROM  galaxy_star");
    return $req->get_row();
  }

  function get_size()
  {
    list( $min_x, $min_y, $max_x, $max_y ) = $this->limits();
    return max($max_x-$min_x, $max_y - $min_y);
  }

}




?>
