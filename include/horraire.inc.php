<?php
/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
 *
 * Ce fichier fait partie du site de l'Association des 0tudiants de
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

/**
 * @file
 */

define("HR_SDIMANCHE",  0x0001);
define("HR_SLUNDI",     0x0002);
define("HR_SMARDI",     0x0004);
define("HR_SMERCREDI",  0x0008);
define("HR_SJEUDI",     0x0010);
define("HR_SVENDREDI",  0x0020);
define("HR_SSAMEDI",    0x0040);

define("HR_TDIMANCHE",  0x0101);
define("HR_TLUNDI",     0x0202);
define("HR_TMARDI",     0x0404);
define("HR_TMERCREDI",  0x0808);
define("HR_TJEUDI",     0x1010);
define("HR_TVENDREDI",  0x2020);
define("HR_TSAMEDI",    0x4040);

define("HR_JOURSFERIES",0x0080);
define("HR_SCOLAIRE",   0x007F);
define("HR_VACANCES",   0x7F00);

$GLOBALS["hr_jours_semaine"] = array(
  HR_SDIMANCHE=>"dimanche",
  HR_SLUNDI=>"lundi",
  HR_SMARDI=>"mardi",
  HR_SMERCREDI=>"mercredi",
  HR_SJEUDI=>"jeudi",
  HR_SVENDREDI=>"vendredi",
  HR_SSAMEDI=>"samedi");

/**
 * Classe offrant des fonctions pour la gestion d'horraires.
 *
 * Elle offre la possibilité de gérer des horraires par jours de la semaine,
 * de prendre en compte les jours fériés et les vacances scolaires.
 * Il n'est pas nécessaire de l'instancier pour les fonctions n'ayant pas besoin
 * d'accéder à la base de données (et donc d'accéder au calendrier officiel)
 *
 * @ingroup pg2
 * @author Julien Etelain
 */
class horraire
{
  var $db;

  function horraire ( &$db )
  {
    $this->db = $db;
  }

  static function distinction_vacances ( $jours )
  {
    return ($jours & HR_SCOLAIRE) != (($jours & HR_VACANCES) >> 8);
  }

  static function _jours_to_text ( $jours )
  {
    $res = "";
    $n = 1;

    $prev=null;
    $first=null;

    while ( $n < HR_JOURSFERIES )
    {
      if ( $jours & $n )
      {
        if ( is_null($first) )
          $first=$n;

        $prev=$n;
      }
      else
      {
        if ( !is_null($first) )
        {
          if ( $res )
            $res .= ", ";

          if ( $prev == $first )
            $res .= $GLOBALS["hr_jours_semaine"][$prev];
          elseif ( $prev  == ($first << 1) )
            $res .= $GLOBALS["hr_jours_semaine"][$first].", ".$GLOBALS["hr_jours_semaine"][$prev];
          else
            $res .= $GLOBALS["hr_jours_semaine"][$first]." au ".$GLOBALS["hr_jours_semaine"][$prev];
        }
        $prev=null;
        $first=null;
      }

      $n = $n << 1;
    }

    if ( !is_null($first) )
    {
      if ( $res )
        $res .= ", ";

      if ( $prev == $first )
        $res .= $GLOBALS["hr_jours_semaine"][$prev];
      elseif ( $prev  == ($first << 1) )
        $res .= $GLOBALS["hr_jours_semaine"][$first].", ".$GLOBALS["hr_jours_semaine"][$prev];
      else
        $res .= $GLOBALS["hr_jours_semaine"][$first]." au ".$GLOBALS["hr_jours_semaine"][$prev];
    }

    if ( $jours & HR_JOURSFERIES )
    {
      if ( $res )
        $res .= " et ";
      $res .= "jours fériés";
    }

    return $res;
  }

  static function jours_to_text ( $jours )
  {
    if ( $jours == (HR_VACANCES|HR_SCOLAIRE|HR_JOURSFERIES) )
      return "7j/7";

    if ( horraire::distinction_vacances($jours) )
    {
      $res = "";

      if ( $jours & HR_JOURSFERIES )
      {
        $res .= "jours fériés";
        $jours &= ~HR_JOURSFERIES;
      }

      $j1 = $jours & HR_SCOLAIRE;

      if ( $j1 )
      {
        if ( $res )
          $res .= " et ";
        $res .= horraire::_jours_to_text($j1)." en période scolaire";
      }

      $j2 = ($jours & HR_VACANCES) >> 8;

      if ( $j2 )
      {
        if ( $res )
          $res .= " et ";
        $res .= horraire::_jours_to_text($j2)." lors des vacances scolaires";
      }
      return $res;
    }
    return horraire::_jours_to_text($jours & (HR_SCOLAIRE|HR_JOURSFERIES));
  }

  static function heures_to_text ( $heures )
  {
    $res = "";

    foreach ( $heures as $creneau )
    {
      if ( !empty($res) )
        $res .= " ";

      $h = floor($creneau[0]/3600);
      $m = ($creneau[0]/60)%60;

      if ( $m )
        $res .= $h."h".$m."-";
      else
        $res .= $h."h"."-";

      $h = floor($creneau[1]/3600);
      $m = ($creneau[1]/60)%60;

      if ( $m )
        $res .= $h."h".$m;
      else
        $res .= $h."h";
    }
    return $res;
  }

  static function horraires_to_text ( $horraires )
  {
    $horraires = horraire::optimiser_par_jours($horraires);

    $res = "";

    foreach ( $horraires as $jours => $heures )
    {
      if ( !empty($res) )
        $res .= " ";

      $res .= horraire::heures_to_text($heures)." ".horraire::jours_to_text($jours).".";
    }
    return $res;
  }

  static function _eclater ( $horraires )
  {
    $data=array();
    $mask=HR_SCOLAIRE|HR_JOURSFERIES;

    foreach ( $horraires as $jours => $hor )
    {
      if ( horraire::distinction_vacances($jours) )
        $mask=HR_VACANCES|HR_SCOLAIRE|HR_JOURSFERIES;
    }

    foreach ( $horraires as $jours => $heures )
    {
      $jours = $jours & $mask;
      $jour = 1;
      while ( $jour & $mask )
      {
        if ( $jours & $jour )
        {
          $j = $jour;
          if ( !($mask & HR_VACANCES) && $j < HR_JOURSFERIES )
            $j = $j | ($j << 8);

          if ( isset($data[$j]) )
            $data[$j] = array_merge($data[$j],$heures);
          else
            $data[$j] = $heures;
        }
        $jour = ($jour << 1);
      }
    }
    return $data;
  }

  static function optimiser_par_jours ( $horraires )
  {
    $horraires = horraire::_eclater($horraires);
    $data = array();
    foreach ( $horraires as $jour => $heures )
    {
      if ( count($data) == 0 )
        $data[$jour] = $heures;
      else
      {
        $fait=false;
        foreach ( $data as $jours => $heures2 )
        {
          if ( $heures2 == $heures )
          {
            $data[$jours|$jour] = $heures;
            unset($data[$jours]);
            $fait=true;
            break;
          }
        }
        if ( !$fait )
          $data[$jour] = $heures;
      }
    }
    return $data;
  }

  static function texte_to_horraires ( $texte )
  {
    $jours_liste = array("dimanche"=>0x101,"lundi"=>0x202,"mardi"=>0x404,"mercredi"=>0x808,"jeudi"=>0x1010,"vendredi"=>0x2020,"samedi"=>0x4040);
    $jours_etendu_liste = array_merge($jours_liste,array("jours fériés"=>0x80));

    $jours_pattern="dimanche|lundi|mardi|mercredi|jeudi|vendredi|samedi";
    $jours_etendu_pattern="dimanche|lundi|mardi|mercredi|jeudi|vendredi|samedi|jours fériés";

    $segments = preg_split("/([\\.]{1})/iu", $texte);
    $horraires=array();

    echo "\n\n======== $texte ========\n";

    foreach ( $segments as $segment )
    {
      $segment = trim($segment);

      if ( !empty($segment) )
      {
        echo "==== $segment ====\n";

        $tokens = explode(" ", $segment);

        $heures=array();
        $jours=0;
        $cumul="";


        foreach ( $tokens as $token )
        {
          $token = trim($token);

          $cumul=trim($cumul." ".$token);
          echo "=> $cumul\n";

          if ( $token == "24h/24" )
          {
            $heures[] = array(0,24*60*60);
            $cumul="";

            if ( $jours )
            {
              $horraires[$jours]=$heures;
              $heures=array();
              $jours=0;
            }

          }
          elseif ( $token == "7j/7" || $cumul == "7 jours/7" )
          {
            $jours = 0x7FFF;
            $cumul="";

            if ( count($heures) )
            {
              $horraires[$jours]=$heures;
              $heures=array();
              $jours=0;
            }
          }
          elseif ( preg_match("/semaine/ui",$token) )
          {
            $jours = 0x202|0x404|0x808|0x1010|0x2020;
            $cumul="";

            if ( count($heures) )
            {
              $horraires[$jours]=$heures;
              $heures=array();
              $jours=0;
            }
          }
          elseif ( preg_match("@([0-9]{1,2})h([0-9]*)(-|\\\\|/)([0-9]{1,2})h([0-9]*)@iu",$token,$match) )
          {
            $heures[] = array((($match[1]*60)+$match[2])*60,(($match[4]*60)+$match[5])*60);
            $cumul="";

            if ( $jours )
            {
              $horraires[$jours]=$heures;
            }
          }
          elseif ( preg_match("@([0-9]{1,2})h([0-9]*) à ([0-9]{1,2})h([0-9]*)@iu",$cumul,$match) )
          {
            $heures[] = array((($match[1]*60)+$match[2])*60,(($match[3]*60)+$match[4])*60);
            $cumul="";

            if ( $jours )
            {
              $horraires[$jours]=$heures;
            }
          }
          elseif ( preg_match("@($jours_pattern) au ($jours_pattern)@iu",$cumul,$match) )
          {
            $match[1]=mb_strtolower($match[1],"UTF-8");
            $match[2]=mb_strtolower($match[2],"UTF-8");
            $cumul="";
            $started=false;
            foreach ( $jours_liste as $jour => $key )
            {
              if ( $jour == $match[1] )
                $started=true;

              if ( $started )
                $jours |= $key;

              if ( $jour == $match[2] )
                $started=false;

            }
            if ( $started )
            {
              foreach ( $jours_liste as $jour => $key )
              {
                if ( $started )
                  $jours |= $key;

                if ( $jour == $match[2] )
                  $started=false;
              }
            }

            if ( count($heures) )
            {
              $horraires[$jours]=$heures;
              $heures=array();
              $jours=0;
            }
          }
          elseif ( preg_match("@($jours_etendu_pattern) et ($jours_etendu_pattern)@iu",$cumul,$match) )
          {
            $match[1]=mb_strtolower($match[1],"UTF-8");
            $match[2]=mb_strtolower($match[2],"UTF-8");
            $cumul="";
            $jours|=$jours_etendu_liste[$match[1]]|$jours_etendu_liste[$match[2]];
            if ( count($heures) )
            {
              $horraires[$jours]=$heures;
              $heures=array();
              $jours=0;
            }
          }
          elseif ( preg_match("@sauf (le|les) ($jours_etendu_pattern)(s?)@iu",$cumul,$match) )
          {
            $match[1]=mb_strtolower($match[1],"UTF-8");
            $jours&=~$jours_etendu_liste[$match[1]];
          }
          elseif ( preg_match("@($jours_etendu_pattern)@iu",$cumul,$match) )
          {
            $match[1]=mb_strtolower($match[1],"UTF-8");
            $jours|=$jours_etendu_liste[$match[1]];
          }
        }

        if ( $jours || count($heures) )
          $horraires[$jours]=$heures;
      }
    }

    echo "Resultat => ".horraire::horraires_to_text($horraires)."\n\n\n";
    return $horraires;
  }

}
/*
$testsuite=array("24h/24","24h/24 et 7j/7","7 jours/7 de 11h à 00h","7h/19h la semaine, 8h30/13h le dimanche et jours fériés","7j/7","7j/7 et 24h/24","9h-12h 14h-18h du lundi au vendredi",
"9h-15h lundi et mardi. Du mercredi au vendredi de 17h30 à 21h30. Samedi : 9h-15h. Journée complète le dimanche.");

foreach ( $testsuite as $test )
{
  horraire::texte_to_horraires($test);
}
*/
?>
