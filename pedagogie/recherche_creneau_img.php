<?php
/**
 * Copyright 2008
 * - Manuel Vonthron  <manuel DOT vonthron AT acadis DOT org>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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

$topdir = "../";

require_once($topdir . "include/site.inc.php");
require_once("include/pedagogie.inc.php");
require_once("include/pedag_user.inc.php");
require_once("include/cts/edt_render.inc.php");

$site = new site();
$site->add_js("pedagogie/pedagogie.js");
$site->allow_only_logged_users("services");

$site->start_page("services", "AE Pédagogie");
$user = new pedag_user($site->db);
$id_utls = $_REQUEST['id_utilisateurs'];


$horraires = array();
foreach(array('08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19') as $h)
  foreach(array('00', '15', '30', '45') as $m)
    $horraires[] = $h.':'.$m;

$jours = array('Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi');
$lines = array();
$oqp = array();
foreach($jours as $jour)
{
  $oqp[$jour]=array();
  foreach($horraires as $horraire)
    $oqp[$jour][$horraire]=array('A'=>0,'B'=>0);
}
foreach($id_utls as $id_utl)
{
  $user->load_by_id($id_utl);
  if($user->is_valid())
  {
    if(in_array(SEMESTER_NOW, $user->get_edt_list()))
    {
      $groups = $user->get_groups_detail(SEMESTER_NOW);
      if(!empty($groups))
      {
        foreach($groups as $group)
        {
          if ($group['jour'] > 0)
            $jour  = get_day($group['jour']);
          $debut = substr($group['debut'], 0,5);
          $fin   = substr($group['fin'], 0, 5);
          $sem   = $group['semaine'];
          $add   = 0;
          foreach($horraires as $horraire)
          {
            if($horraire==$debut)
            {
              $add = 1;
            }
            if($horraire==$fin)
            {
              $add = 0;
              break;
            }
            if(is_null($sem))
            {
              $oqp[$jour][$horraire]['A']+=$add;
              $oqp[$jour][$horraire]['B']+=$add;
            }
            else
              $oqp[$jour][$horraire][$sem]+=$add;
          }
        }
      }
    }
  }
}
$free = array();
foreach($oqp as $jour => $_horraires)
{
  $startA = false;
  $startB = false;
  $lastA = false;
  $lastB = false;
  foreach($_horraires as $horraire => $_oqp)
  {
    if((int)$_oqp['A']==0)
    {
      $lastA=$horraires[array_search($horraire, $horraires)+1];
      if(!$startA)
        $startA=$horraire;
    }
    if((int)$_oqp['B']==0)
    {
      $lastB=$horraires[array_search($horraire, $horraires)+1];
      if(!$startB)
        $startB=$horraire;
    }
    if((int)$_oqp['B']>0)
    {
      if($startB && $lastB && $startB!=$lastB)
      {
        $free[] = array("semaine_seance" =>'B',
                        "hr_deb_seance"  => $startB,
                        "hr_fin_seance"  => $lastB,
                        "jour_seance"    => $jour,
                        "type_seance"    => '',
                        "grp_seance"     => 0,
                        "nom_uv"         => '',
                        "salle_seance"   => '');
      }
      $startB = false;
      $lastB = false;
    }
    if((int)$_oqp['A']>0)
    {
      if($startA && $lastA && $startA!=$lastA)
      {
        $free[] = array("semaine_seance" =>'A',
                        "hr_deb_seance"  => $startA,
                        "hr_fin_seance"  => $lastA,
                        "jour_seance"    => $jour,
                        "type_seance"    => '',
                        "grp_seance"     => 0,
                        "nom_uv"         => '',
                        "salle_seance"   => '');
      }
      $startA = false;
      $lastA = false;
    }
  }
  if($startB)
  {
    $free[] = array("semaine_seance" =>'B',
                    "hr_deb_seance"  => $startB,
                    "hr_fin_seance"  => $horraires[count($horraires)-1],
                    "jour_seance"    => $jour,
                    "type_seance"    => '',
                    "grp_seance"     => 0,
                    "nom_uv"         => '',
                    "salle_seance"   => '');
  }
  if($startA)
  {
    $free[] = array("semaine_seance" =>'A',
                    "hr_deb_seance"  => $startA,
                    "hr_fin_seance"  => $horraires[count($horraires)-1],
                    "jour_seance"    => $jour,
                    "type_seance"    => '',
                    "grp_seance"     => 0,
                    "nom_uv"         => '',
                    "salle_seance"   => '');
  }
}
$edt = new edt_img('Créneaux disponibles', $free,false,false,false);
$edt->generate(false);
exit;

?>
