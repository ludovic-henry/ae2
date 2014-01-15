<?php
/* Copyright 2007
 * - Julien Etelain < julien at pmad dot net >
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
 * @file
 */

/**
 * Affiche un lien pour réagir sur le fourm au sujet d'un élément.
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class reactonforum extends stdcontents
{

  /**
   * Génère une instance de la classe
   * @param $db Lien vers la base de donnée (lecture seule)
   * @param $user utilisateur consultant le site
   * @param $titre Titre suggéré pour le message sur le forum
   * @param $params Tableau associatif définissant le contexte (peut être id_nouvelle, id_catph, id_sondage)
   * @param $id_asso Id de l'association du contexte (permet de cibler le forum de l'association ou club s'il existe)
   * @param $list Affiche la liste, ou simplement un lien vers le premier topic s'il y a
   */
  function reactonforum ( &$db, &$user, $titre, $params=array(), $id_asso=null, $list=true )
  {
    global $wwwtopdir;


    $conds = array();
    foreach ($params as $key => $value)
    {
      if ( is_null($value) )
        $conds []= "(`frm_sujet`.`" . $key . "` is NULL)";
      else
        $conds []= "(`frm_sujet`.`" . $key . "`='" . mysql_escape_string($value) . "')";
    }

    $sqlconds = implode(" AND ",$conds);

    if ( $user->is_valid() )
    {
      $grps = $user->get_groups_csv();
      $req = new requete($db,"SELECT id_sujet, titre_sujet ".
        "FROM frm_sujet ".
        "INNER JOIN frm_forum USING(`id_forum`) ".
        "WHERE ((droits_acces_forum & 0x1) OR " .
        "((droits_acces_forum & 0x10) AND id_groupe IN ($grps)) OR " .
        "(id_groupe_admin IN ($grps)) OR " .
        "((droits_acces_forum & 0x100) AND frm_sujet.id_utilisateur='".$user->id."')) ".
        "AND $sqlconds");
    }
    else
      $req = new requete($db,"SELECT id_sujet, titre_sujet ".
        "FROM frm_sujet ".
        "INNER JOIN frm_forum USING(`id_forum`) ".
        "WHERE (droits_acces_forum & 0x1) ".
        "AND $sqlconds");

    if ( $req->lines > 0 )
    {
      if ( $list )
      {
        $this->buffer = "<div class=\"reacts\">";
        $this->buffer .= "<h2>Réactions sur le forum</h2>";
        $this->buffer .= "<ul>";
        while ( $row = $req->get_row() )
        {
          $this->buffer .= "<li><a href=\"".$wwwtopdir."forum2/?id_sujet=".$row["id_sujet"]."\"><img src=\"".$wwwtopdir."images/icons/16/sujet.png\" class=\"icon\" /> ".$row["titre_sujet"]."</a></li>";

        }
        $this->buffer .= "</ul>";
        $this->buffer .= "</div>";
      }
      else
      {
        $row = $req->get_row();
        $this->buffer = "<p class=\"react\"><a href=\"".$wwwtopdir."forum2/?id_sujet=".$row["id_sujet"]."\"><img src=\"".$wwwtopdir."images/icons/16/sujet.png\" class=\"icon\" /> Réactions sur le forum</a></p>";
      }
      return;
    }

    $context = "";
    foreach ($params as $key => $value)
      $context .= " &amp;".$key."=".urlencode($value);

    $id_forum = 3;

    if ( !is_null($id_asso) )
    {
      $req = new requete($db,"SELECT id_forum FROM frm_forum WHERE id_asso='".mysql_escape_string($id_asso)."' AND categorie_forum=0");
      if ( $req->lines > 0 )
        list($id_forum) = $req->get_row();
    }

    $this->buffer = "<p class=\"react\"><a href=\"".$wwwtopdir."forum2/?page=post&amp;titre_sujet=".urlencode($titre)."&amp;id_forum=$id_forum".$context."\"><img src=\"".$wwwtopdir."images/icons/16/sujet.png\" class=\"icon\" /> Réagir sur le forum</a></p>";


  }





}


?>
