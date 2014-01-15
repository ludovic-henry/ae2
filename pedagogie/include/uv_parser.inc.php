<?php
/* Copyright 2011
 * - Antoine Tenart < antoine dot tenart at gmail dot com >
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

/**
    Parse le mail du SME contenant l'affectation aux groupes et UVs.
**/


require_once($topdir . 'include/mysql.inc.php');
require_once($topdir . 'pedagogie/include/pedagogie.inc.php');

class UVParser
{
  // --- Protected vars
  protected $is_hedt;
  protected $id;
  protected $text;
  protected $uv;
  protected $semester;
  protected $type;
  protected $group;
  protected $begin_hour;
  protected $end_hour;
  protected $day;
  protected $room;
  protected $frequency;

  protected $db;
  protected $_target = array();
  protected $_results = array();

  // Rules
  protected $_phrase;
  protected $_title;
  protected $_info;
  protected $_schedule;
  protected $_uv = '([A-Z]{2}[0-9]{2})';
  protected $_type = '(?:(C|TD|TP)([0-9]))';
  protected $_day = '(L|MA|ME|J|V|S)';
  protected $_frequency = '(\(1SEMAINE\/2\))';
  protected $_hour = '([0-2]?[0-9]H[0-5][0-9])';
  protected $_room = '(?:en([A-Z][0-9]{1,3}[A-Z]?))';


  // --- public functions
  // constructor
  function UVParser(&$db, $semester = SEMESTRE_NOW) {
    $this->db = &$db;
    $this->semester = $semester;

    $this->_schedule = "$this->_hour$this->_hour";

    $this->_title = "$this->_uv$this->_type?";
    $this->_info = "(?:$this->_day$this->_schedule$this->_frequency?$this->_room|(HORSEMPLOIduTEMPS))";

    $this->_phrase = "$this->_title$this->_info";
  }

  // load text & parse it
  public function load_by_text($txt, $load_next = false) {
    $txt = preg_replace('/(.+):(.+)et(.+)/', "$1$2\n$1$3", $txt); // life is easy
    $txt = str_replace(array(' ', ':', '-'), '', $txt);
    $txt = $this->get_real_uv($txt);
    $this->_target = explode("\n",$txt);

    $this->parse();

    if( $load_next )
      $this->load_next();
  }

  // load next parsed UV, if any (usefull in a loop)
  public function load_next() {
    $foo = current($this->_results);
    next($this->_results);

    if(!$foo)
      return false;

    if( !is_numeric($foo[1][3]) )
      $this->uv = get_real_uv($foo[1]);
    else
      $this->uv = $foo[1];

    $this->text = $foo[0];

    if(isset($foo[9])) {
      $this->hedt = true;

      $this->type = 'THE';
      $this->group = 1;
      $this->day = 0;
      $this->begin_hour = "00H00";
      $this->end_hour = "00H00";
      $this->room = null;
      $this->frequency = 1;
    }
    else {
      $days = array('L' => 1, 'MA' => 2, 'ME' => 3, 'J' => 4, 'V' => 5, 'S' => 6);
      $this->hedt = false;

      $this->type = $foo[2];
      $this->group = $foo[3];
      $this->day = $days[$foo[4]];
      $this->begin_hour = $foo[5];
      $this->end_hour = $foo[6];
      $this->room = $this->get_real_room($foo[8]);

      if($foo[7] == '')
        $this->frequency = 1;
      else
        $this->frequency = 2;
    }

    $this->id = $this->load_id_uv();

    return true;
  }

  public function get_id_uv() {
    return $this->id;
  }

  public function get_id_group() {
    $sql = "SELECT `id_groupe` FROM `pedag_groupe`";

    if( $this->is_hedt() ) {
      $sql .= " WHERE `id_uv` = ".$this->id." AND `type` = 'THE' AND `semestre` ='".$this->semester."' LIMIT 1";
    } else {
      $sql .= " WHERE `id_uv` = ".$this->id." AND `type` = '".$this->type."'";
      $sql .= " AND `debut` = '".str_replace('H', ':', $this->begin_hour)."' AND `jour` = ".$this->day." AND `salle` = '".$this->room."'";
      $sql .= " AND `semestre` = '".$this->semester."' LIMIT 1";
    }

    $req = new requete($this->db, $sql);

    if($req->is_success()) {
      $res = $req->get_row();
      return $res['id_groupe'];
    }

    return null;
  }

  public function get_uv() {
    if( !empty($this->uv) )
      return $this->uv;

    return null;
  }

  public function get_text() {
    return $this->text;
  }

  public function get_nice_print() {
    $plop = array( 'C' => 'Cours', 'TD' => 'Travaux dirigés', 'TP' => 'Travaux pratiques');
    $jours = array( 1 => 'lundi', 2 => 'mardi', 3 => 'mercredi', 4 => 'jeudi', 5 => 'vendredi', 6 => 'samedi');

    $ret = $plop[$this->type] . (preg_match('/^[A|E|U|I|O]$/', $this->uv[0]) ? ' d\'' : ' de ') . $this->uv;
    $ret .= ' le ' . $jours[$this->day] .' de ' . $this->begin_hour . ' à ' . $this->end_hour . ' en ' . $this->room . '.';

    return $ret;
  }

  public function get_info_add_group() {
    while(true) {
      $sql = "SELECT COUNT(*) as nb FROM pedag_groupe WHERE `type` = '".$this->type."' AND `num_groupe` = ".$this->group;
      $sql .= " AND `id_uv` = ".$this->id." AND `semestre` = '".$this->semester."'";

      $req = new requete($this->db, $sql);

      if( $req->is_success() ) {
        $res = $req->get_row();
        if( $res['nb'] > 0 ) {
          $this->group++;
          continue;
        }
      }

      break;
    }

    return array( $this->type,
                  $this->group,
                  $this->frequency,
                  $this->semester,
                  $this->day,
                  str_replace('H', ':', $this->begin_hour),
                  str_replace('H', ':', $this->end_hour),
                  $this->room
        );
  }

  public function is_weekly() {
    return ($this->frequency == 1 ? true : false);
  }

  public function is_hedt() {
    return $this->hedt;
  }


  // --- protected functions
  // parse text loaded
  protected function parse() {
    while( $foo = current($this->_target) ) {
      preg_match('/'.$this->_phrase.'/', $foo, $matches);

      if($matches)
        $this->_results[] = $matches;

      next($this->_target);
    }
  }

  protected function get_real_room($room) {
    $seek = array(); $destroy = array();  // ste blague
    $matches = array(
        '/H11/' => 'H011'
        );

    while(list($s,$d) = each($matches)) {
      $seek[] = $s;
      $destroy[] = $d;
    }

    return preg_replace($seek, $destroy, $room);
  }

  // to put in bdd
  protected function get_real_uv($uv) {
    $seek = array(); $destroy = array(); // st'humour de répétition
    $matches = array(
        '/MT1[A-Z]/' => 'MT11',
        '/MT2[A-Z]/' => 'MT12',
        '/PS1[A-Z]/' => 'PS11',
        '/PS2[A-Z]/' => 'PS12',
        '/ST1[A-Z]/' => 'ST10',
        '/YO1[A-Z]/' => 'LO11'
        );

    while(list($s, $d) = each($matches)) {
      $seek[] = $s;
      $destroy[] = $d;
    }

    return preg_replace($seek, $destroy, $uv);
  }

  protected function load_id_uv() {
    $sql = "SELECT id_uv FROM pedag_uv WHERE code='".$this->uv."' LIMIT 1";
    $req = new requete($this->db, $sql);

    if( $req->is_success() ) {
      $res = $req->get_row();
      return $res['id_uv'];
    }
    else
      return null;
  }

}

