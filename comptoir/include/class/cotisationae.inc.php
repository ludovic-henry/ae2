<?php
/* Copyright 2006,2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
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
require_once($topdir. "include/entities/cotisation.inc.php");

class cotisationae
{
  var $enddate;
  var $prevdate;

  var $db;
  var $dbrw;

  function cotisationae($db,$dbrw,$param,&$user)
  {
    $this->db = $db;
    $this->dbrw = $dbrw;

    $year  = date("Y");
    $month = date("m");

    if ( $user->ae )
    {
      $req = new requete($this->db,
        "SELECT date_fin_cotis ".
        "FROM `ae_cotisations` " .
        "WHERE `id_utilisateur`='".$user->id."' " .
        "ORDER BY `date_fin_cotis` DESC LIMIT 1");
      if ( $req->lines == 1 )
      {
        list($curend) = $req->get_row();
        $this->prevdate=strtotime($curend);
        $year  = date("Y",$this->prevdate);
        $month = date("m",$this->prevdate);
      }
    }

    if ( $month < 2 ) // janvier => aout année -1
    {
      $year--;
      $month = 8;
    }
    else if ( $month < 7 ) // février, mars, avril, mai, juin => février année
    {
      $month = 2;
    }
    else // juillet, aout, sept, octobre, novembre, décembre => aout année
    {
      $month = 8;
    }

    $month += intval($param);

    if ( $month > 12 )
    {
      $year++;
      $month -= 12;
    }

    $this->enddate = mktime ( 2, 0, 0, $month, 15 , $year );


  }

  function vendu($user,$prix_unit)
  {
    $cotisation = new cotisation($this->db,$this->dbrw);
    $cotisation->add ( $user->id, $this->enddate, 5, $prix_unit, 1 );
  }

  function get_info()
  {
    return "Cotisation à l'AE jusqu'au ".date("d/m/Y",$this->enddate);
  }

  function get_once_sold_cts($user)
  {
   // On affiche la date "précédente", vu que la cotisation a déjà été fait, $this->enddate corresponderait à une nouvelle cotisation
    $cts = new contents("Vous venez de cotiser à l'AE jusqu'au ".date("d/m/Y",$this->prevdate));
    $cts->add_paragraph("Pensez à venir retirer votre cadeau et votre carte AE au bureau de l'AE.");
    $cts->add_paragraph("Assurez vous d'avoir une photo d'identité dans votre profil pour que votre carte puisse être imprimée.");
    $cts->add_paragraph("Pensez à mettre à jour votre profil dans le matmatronch.");
    $cts->add_paragraph("Merci d'avoir cotisé à l'AE.");
    return $cts;
  }

  function can_be_sold($user)
  {
    if ( !$user->utbm )
      return false;

    return true;
  }

  function is_compatible($cl)
  {
    if ( get_class($cl) == "cotisationae" )
      return false;

    return true;
  }

}

?>
