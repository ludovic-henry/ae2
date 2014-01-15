<?php
/* Copyright 2006
 * - Julien Etelian <julien CHEZ pmad POINT net>
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

define('FPDF_FONTPATH', $topdir . 'font/');

require_once($topdir . "include/lib/barcodefpdf.inc.php");

class pdfcarteae extends FPDF
{
  var $img_front;
  var $img_back;
  var $width;
  var $height;
  var $xmargin;
  var $ymargin;
  var $pos;
  var $npp;
  var $npl;

  function pdfcarteae()
  {
    global $topdir;


    $this->FPDF();

    $this->width = 80; // Largeur d'une carte
    $this->height = 50; // Hauteur d'une carte
    $this->xmargin = 25; // Marge X
    $this->ymargin = 25; // Marge Y
    $this->npp = 10; // Nombre par page
    $this->npl=2; // Nombre par ligne

    $this->img_front[1] = $topdir."images/carteae/front-A2011_normaux.png";
    $this->img_back[1] = $topdir."images/carteae/back-A2011_normaux.png";
    $this->img_front[2] = $topdir."images/carteae/front-A2011_autres.png";
    $this->img_back[2] = $topdir."images/carteaea/back-A2011_autres.png";
    $this->img_front[3] = $topdir."images/carteae/front-A2011_autres.png";
    $this->img_back[3] = $topdir."images/carteae/back-A2011_autres.png";
    $this->img_front[4] = $topdir."images/carteae/front-A2011_autres.png";
    $this->img_back[4] = $topdir."images/carteae/back-A2011_autres.png";

    /* ATTENTION
     * - l'égalité suivante doit être respectée :
     *   210=$this->npl*$this->width+2*$this->xmargin
     *   sinon les cartes seront mal aligné entre le recto et le verso
     *
     * - l'égalité suivante doit être respectée :
     *   290<$this->height*$this->npp/$this->npl+$this->ymargin
     *  sinon les cartes ne tiendrons pas dans la page
     */


    $this->pos[1] = array (
      "photo" => array ("x"=>4.4,"y"=>9.0,"w"=>25.1,"h"=>34.3),
      "cbar" => array ("x"=>6.7,"y"=>4,2,"w"=>67,"h"=>25),
      "front" =>
        array (
          "nom" => array ("x"=>41.5,"y"=>9.9,"w"=>27,"h"=>4),
          "prenom" => array ("x"=>46.5,"y"=>14.8,"w"=>27,"h"=>4.2),
          "surnom" => array ("x"=>46.5,"y"=>19.9,"w"=>27,"h"=>4.2),
          "semestres" => array ("x"=>49.0,"y"=>24.9,"w"=>27,"h"=>4.2)
        )
      );

    $this->pos[4] = $this->pos[3] = $this->pos[2] = $this->pos[1];

    $this->SetAutoPageBreak(false);


  }

  function render_front ( $x, $y, $infos )
  {
    global $topdir;

    $this->Image($this->img_front[$infos['type_carte']],$x,$y,$this->width,$this->height);

    $src = "../data/matmatronch/".$infos['id'].".identity.jpg";

    $this->Image($src,
        $x+$this->pos[$infos['type_carte']]['photo']['x'],
        $y+$this->pos[$infos['type_carte']]['photo']['y'],
        $this->pos[$infos['type_carte']]['photo']['w'],
        $this->pos[$infos['type_carte']]['photo']['h']);

    $this->SetFont('Arial','',8);

    foreach ( $this->pos[$infos['type_carte']]['front'] as $name => $pos )
    {
      $this->SetXY($x+$pos['x'],$y+$pos['y']);
      $this->Cell($pos['w'],$pos['h'],utf8_decode($infos[$name]));
    }
  }

  function render_back ( $x, $y, $infos )
  {
    $this->Image($this->img_back[$infos['type_carte']],$x,$y,$this->width,$this->height);

    $cbar = new PDF_C128AObject($this->pos[$infos['type_carte']]['cbar']['w'], $this->pos[$infos['type_carte']]['cbar']['h'],
            BCS_ALIGN_CENTER | BCS_DRAW_TEXT,
            $infos['cbar'],
            &$this,
            $x+$this->pos[$infos['type_carte']]['cbar']['x'],
            $y+$this->pos[$infos['type_carte']]['cbar']['y']
            );

    $cbar->DrawObject(0.25);

  }

  function render_page ( $users )
  {
    $n = count($users);

    $this->AddPage();
    for($i=0;$i<$n;$i++)
    {
      $x = $this->xmargin+($i % $this->npl)*$this->width;
      $y = $this->ymargin+intval($i/$this->npl)*$this->height;

      $this->render_front($x,$y,$users[$i]);
    }

    $this->AddPage();
    for($i=0;$i<$n;$i++)
    {
      $x = $this->xmargin+($this->npl-1-($i % $this->npl))*$this->width;
      $y = $this->ymargin+intval($i/$this->npl)*$this->height;

      $this->render_back($x,$y,$users[$i]);
    }
  }

  function semestre ( $time )
  {
    $d = date("d",$time);
    $m = date("m",$time);

    if ( $m <= 2 )
      return "A".sprintf("%02d",(date("y",$time)-1));

    if ( $m > 8 )
      return "A".date("y",$time);

    return "P".date("y",$time);
  }


  function render ( $req )
  {
    global $topdir;
    $i=0;
    $users = array();

    $acc=utf8_decode('ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËéèêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ');
        $noacc='AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn';

    while ( $row= $req->get_row())
    {
      if ($infos['type_cotis'] == 5)
        $type_carte = 2;
      elseif($infos['type_cotis'] == 6)
        $type_carte = 3;
      elseif($infos['type_cotis'] == 8)
        $type_carte = 3;
      else
        $type_carte = 1;

      $users[$i]["id"] = $row["id_utilisateur"];
      $users[$i]["nom"] = $row["nom_utl"];
      $users[$i]["prenom"] = $row["prenom_utl"];
      $users[$i]["surnom"] = $row["surnom_utbm"];
      $users[$i]["type_carte"] = $type_carte;

      $fsem = $this->semestre(strtotime($row["date_fin_cotis"]));
      $sem = $this->semestre(time());

      if ( $fsem != $sem )
        $users[$i]["semestres"] = $sem."-".$fsem;
      else
        $users[$i]["semestres"] = $fsem;

      $row["prenom_utl"] = utf8_encode(substr(strtr(utf8_decode($row["prenom_utl"]), $acc, $noacc), 0, 6));
      $row["nom_utl"] = utf8_encode(substr(strtr(utf8_decode($row["nom_utl"]), $acc, $noacc), 0, 6));
      $users[$i]["cbar"] = $row["id_carte_ae"]." ".$row["prenom_utl"].".".$row["nom_utl"];
      $users[$i]["cbar"] = strtoupper(str_replace("-", "", $users[$i]["cbar"]));

      $i++;

      if ( $i == $this->npp )
      {
        $this->render_page($users);

        $users = array();
        $i = 0;
      }

    }

    if ( $i != 0 )
      $this->render_page($users);

  }

}




?>
