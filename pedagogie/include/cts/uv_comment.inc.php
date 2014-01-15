<?php

/** @file
 *
 * @brief contents pour les commentaires sur les UVs
 *
 */

/* Copyright 2007
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Manuel Vonthron <manuel DOT vonthron AT acadis DOT org>
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
 * along with this program; if not, write to the Free Sofware
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

function p_stars($note)
{

  if ($note == -1)
    return "<b>non renseigné</b>";

  $str = "";
  for ($i = 1; $i <= 4; $i++){
     if ($i <= $note)
      $str .= "<img src=\"$topdir/images/icons/16/star.png\" alt=\"star\" />\n";
    else
      $str .= "<img src=\"$topdir/images/icons/16/unstar.png\" alt=\"unstar\" />\n";
  }

  return $str;
}

/**
 * Affiche un commentaire sur une UV
 *
 * @author Pierre Mauduit
 * @author Manuel Vonthron
 * @ingroup display_cts
 */
class uvcomment extends stdcontents
{

  public function __construct(&$comments, &$db, &$user, $page = "uvs.php")
  {
    $author = new pedag_user($db);

    /* TODO : meme remarque concernant
     * l'éventuelle mise en place d'un groupe de modérateurs
     */
    $admin = $user->is_in_group("gestion_ae");

    $i = 0;
    for ($i = 0; $i < count($comments); $i++)
    {
      $comment = &$comments[$i];

      $parity = (($i %2) == 0);

      /* commentaire "abusé" */
      if ($comment->valid == 0)
        $extra = "abuse";
      else if ($parity)
        $extra = "pair";

      $this->buffer .= "<div class=\"uvcomment $extra\" id=\"cmt_".$comment->id."\">\n";

      $this->buffer .= "<div class=\"uvcheader\">\n";

      $date = split("[-: ]", $comment->date);
      $timestamp = mktime($date[3], $date[4], $date[5], $date[1], $date[2], $date[0]);
      $this->buffer .= "<span class=\"uvcdate\"><b>Le ".strftime("%A %e %B %Y à %Hh%M", $timestamp)."\n";

      if ($comment->etat == 1)
        $this->buffer .= "(Commentaire jugé abusif !)";

      $this->buffer .= "</b></span>\n";

      /* options (modération, ...) */
      $this->buffer .= "<span class=\"uvcoptions\">";
      $links = array();

      /* l'auteur peut toujours décider de supprimer son message */
      if ($admin || ($user->id == $author->id)){
          $links[] = "<a href=\"".$page."?action=editcomm&id=".
            $comment->id."\">Editer</a>";
          $links[] = "<a href=\"".$page."?action=deletecomm&id=".
            $comment->id."\">Supprimer</a>";
        }
      /* sinon, n'importe qui peut signaler un abus */
      /* sous reserve que ce ne soit pas deja le cas ... */

      else if (($comment->valid == 1) && !$admin)
        $links[] = "<a href=\"".$page."?action=reportabuse&id_commentaire=".$comment->id."\">Signaler un abus</a>";

      if ($comment->valid == 0)
      {
        if ($admin)
          $links[] = "<a href=\"".$page."?action=validcomm&id_commentaire=".$comment->id."\">Valider le commentaire</a>";
        else
          $links[] = "Ce commentaire a été signalé";
      }
      else if ($comment->valid == 2)
        $links[] = "Ce commentaire a été validé";

      /* mise en "quarantaine" par un admin
       * (demande de modération)
       */
      /* Commenté par matou : et concrètement, ça sert à quoi ?
      if (($admin) && ($user->id != $comment->id_utilisateur))
        $links[] = "<a href=\"".$page."?action=quarantine&id=".$comment->id."\">Mise en modération</a>";
      */

      $this->buffer .= implode(" | ", $links);

      $this->buffer .= "</span>\n<br/>\n"; // fin span modération

      $author->load_by_id($comment->id_utilisateur);

      $this->buffer .= "<span class=\"uvcauthor\"> Par ".
        $author->get_html_extended_info() . "</span>";


      if ($comment->note_obtention != null){
        if (($comment->note_obtention != 'F') && ($comment->note_obtention != 'Fx'))
          $this->buffer .= "<span class=\"uvcnote\">obtenu avec " . $comment->note_obtention;
        else
          $this->buffer .= "<span class=\"uvcnote\">échec (" . $comment->note_obtention . ")";

        $this->buffer .= "</span>";
      }

      $this->buffer .= "</div><br/>"; // fin du header
      $this->buffer .= "<div class=\"uvleftbloc\" style=\"width: 235px;\">";
      $this->buffer .= "<table class=\"uvtable\">";
      $this->buffer .= "<tr>";
        $this->buffer .= "<td>Intérêt :</td>";
        $this->buffer .= "<td>".p_stars($comment->interet)."</td>";
      $this->buffer .= "</tr>";
      $this->buffer .= "<tr>";
        $this->buffer .= "<td>Utilité :</td>";
        $this->buffer .= "<td>".p_stars($comment->utilite)."</td>";
      $this->buffer .= "</tr>";
      $this->buffer .= "<tr>";
        $this->buffer .= "<td>Charge de travail :</td>";
        $this->buffer .= "<td>".p_stars($comment->charge_travail)."</td>";
      $this->buffer .= "</tr>";
      $this->buffer .= "<tr>";
        $this->buffer .= "<td>Qualité de l'enseignement :</td>";
        $this->buffer .= "<td>".p_stars($comment->qualite_ens)."</td>";
      $this->buffer .= "</tr>";
      $this->buffer .= "<tr>";
        $this->buffer .= "<td><b>Note globale</b></td>";
        $this->buffer .= "<td>".p_stars($comment->note)."</td>";
      $this->buffer .= "</tr>";
      $this->buffer .= "</table>";
      $this->buffer .= "</div>";

      $this->buffer .= "<div class=\"uvrightbloc\">";
      $this->buffer .= doku2xhtml($comment->comment);
      $this->buffer .= "</div>";

      $this->buffer .= "<div class=\"clearboth\"></div>";



      $this->buffer .= "</div>\n"; // fin du commentaire

    }
  }
}




/* refaisage */

class uv_comment_box extends stdcontents
{
  public function __construct($comment, $uv, $user, $author)
  {
    static $n = 0;
    $parity = ($n++ % 2);

    /* TODO : meme remarque concernant
     * l'éventuelle mise en place d'un groupe de modérateurs
     */
    $admin = $user->is_in_group("gestion_ae");

    if ($comment->valid == 0)
      $extra = "abuse";
    else if ($parity)
      $extra = "pair";

    $this->buffer .= "<div class=\"uvcomment $extra\" id=\"cmt_".$comment->id."\">\n";

    $this->buffer .= "<div class=\"uvcheader\">\n";

    $date = split("[-: ]", $comment->date);
    $timestamp = mktime($date[3], $date[4], $date[5], $date[1], $date[2], $date[0]);
    $this->buffer .= "<span class=\"uvcdate\"><b>Le ".strftime("%A %e %B %Y à %Hh%M", $timestamp)."\n";

    if ($comment->etat == 1)
      $this->buffer .= "(Commentaire jugé abusif !)";

    $this->buffer .= "</b></span>\n";

    /* options (modération, ...) */
    $this->buffer .= "<span class=\"uvcoptions\">";
    $links = array();

    /* l'auteur peut toujours décider de supprimer son message */
    if ($admin || ($user->id == $author->id)){
        $links[] = "<a href=\"".$page."?view=editcomm&id_commentaire=".
          $comment->id."\">Editer</a>";
        $links[] = "<a href=\"".$page."?action=deletecomm&id_commentaire=".
          $comment->id."\">Supprimer</a>";
      }
    /* sinon, n'importe qui peut signaler un abus */
    /* sous reserve que ce ne soit pas deja le cas ... */

    else if (($comment->valid == 1) && !$admin)
      $links[] = "<a href=\"?action=reportabuse&id_commentaire=".$comment->id."\">Signaler un abus</a>";

    if ($comment->valid == 0)
    {
      if ($admin)
        $links[] = "<a href=\"".$page."?action=validcomm&id_commentaire=".$comment->id."\">Valider le commentaire</a>";
      else
        $links[] = "Ce commentaire a été signalé";
    }
    else if ($comment->valid == 2)
      $links[] = "Ce commentaire a été validé";

    /* mise en "quarantaine" par un admin
     * (demande de modération)
     */
    /* Commenté par matou : et concrètement, ça sert à quoi ?
    if (($admin) && ($user->id != $comment->id_utilisateur))
      $links[] = "<a href=\"?action=quarantine&id=".$comment->id."\">Mise en modération</a>";
    */

    $this->buffer .= implode(" | ", $links);

    $this->buffer .= "</span>\n<br/>\n"; // fin span modération

    $this->buffer .= "<span class=\"uvcauthor\"> Par ".$author->get_html_extended_info() . "</span>";

    $note = $author->get_uv_result($uv->id);
    global $_RESULT;
    if ($note !== false){
      if ($note <= RESULT_E)
        $this->buffer .= "<span class=\"uvcnote\">obtenu avec " . $_RESULT[$note]['long'];
      else if ($note == RESULT_F || $note == RESULT_FX)
        $this->buffer .= "<span class=\"uvcnote\">échec (" . $_RESULT[$note]['long'] . ")";
      else
        $this->buffer .= "<span class=\"uvcnote\"> ".$_RESULT[$note]['long']." ";

      $this->buffer .= "</span>";
    }

    $this->buffer .= "</div><br/>"; // fin du header
    $this->buffer .= "<div class=\"uvleftbloc\" style=\"width: 200px;\">";
    $this->buffer .= "<table class=\"uvtable\">";
    $this->buffer .= "<tr>";
      $this->buffer .= "<td>Intérêt :</td>";
      $this->buffer .= "<td>".p_stars($comment->note_interet)."</td>";
    $this->buffer .= "</tr>";
    $this->buffer .= "<tr>";
      $this->buffer .= "<td>Utilité :</td>";
      $this->buffer .= "<td>".p_stars($comment->note_utilite)."</td>";
    $this->buffer .= "</tr>";
    $this->buffer .= "<tr>";
      $this->buffer .= "<td>Charge de travail :</td>";
      $this->buffer .= "<td>".p_stars($comment->note_travail)."</td>";
    $this->buffer .= "</tr>";
    $this->buffer .= "<tr>";
      $this->buffer .= "<td>Enseignement :</td>";
      $this->buffer .= "<td>".p_stars($comment->note_enseignement)."</td>";
    $this->buffer .= "</tr>";
    $this->buffer .= "<tr>";
      $this->buffer .= "<td><b>Note globale</b></td>";
      $this->buffer .= "<td>".p_stars($comment->note_generale)."</td>";
    $this->buffer .= "</tr>";
    $this->buffer .= "</table>";
    $this->buffer .= "</div>";

    $this->buffer .= "<div class=\"uvrightbloc\">";
    $this->buffer .= doku2xhtml($comment->content);
    $this->buffer .= "</div>";

    $this->buffer .= "<div class=\"clearboth\"></div>";



    $this->buffer .= "</div>\n"; // fin du commentaire
  }
}

?>
