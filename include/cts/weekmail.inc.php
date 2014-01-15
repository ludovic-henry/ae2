<?php
/* Copyright 2008
 * - Simon Lopez <simon DOT lopez AT ayolo DOT org>
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
 * Weekmail
 * @ingroup display_cts_weekmail
 */
class weekmailcts extends stdcontents
{
  /**
   * Génère l'affichage d'un weekmail
   * @param $wm instance de la classe weekmail
   */
  function weekmailcts ($wm,$admin=false)
  {
    if(!is_null($wm->id) && ($wm->statut==1 || $admin=true))
    {
      $this->buffer ='<div id="weemail">';
      $this->buffer.='<div id="headerwm">';
//      $this->buffer.='<div id="headerwmimg">'.$wm->imgheader.'</div>';
      $this->buffer.='<div id="titrewm">'.$wm->title.'</div>';
      if($statut==1)
      {
        $date=list($annee, $mois, $jour) = explode("-", $wm->date);
        $date="Envoyé le : "$jour."/".$mois."/".$annee;
      }
      else
        $date='En attente';
      $this->buffer.='<div id="datewm">'.$date.'</div>';
      $this->buffer.='</div>';
      $this->buffer.='<div id="contentwm">';
      $this->puts(doku2xhtml($wm->content));
      $this->buffer.='</div>';
      $this->buffer.='</div>';
    }
    else
    {
      $this->title='Erreur');
      $this->add(new error('Weekmail not found!','Weekmail inconnu au bataillon moussaillon');
    }
  }

}

?>
