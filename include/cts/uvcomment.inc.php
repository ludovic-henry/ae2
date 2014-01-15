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

  if ($note == null)
    return "<b>non renseigné</b>";

  $str = "";

  for ($i = 0; $i < 4; $i++)
    {
      if ($i+1 <= $note)
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
class uvcomment_contents extends stdcontents
{

  function uvcomment_contents (&$comments,
			       &$db,
			       &$user,
			       $page = "uvs.php")
  {
    $author = new utilisateur($db);

    /* TODO : meme remarque concernant
     * l'éventuelle mise en place d'un groupe de modérateurs
     */
    $admin = $user->is_in_group("gestion_ae");

    $i = 0;
    for ($i = 0 ; $i < count($comments); $i++)
      {
	$comment = &$comments[$i];

	$parity = (($i %2) == 0);

	/* commentaire "abusé" */
	if ($comment->etat == 1)
	  $extra = "abuse";
	else if ($parity)
	  $extra = "pair";

	$this->buffer .= "<div class=\"uvcomment $extra\" id=\"cmt_".$comment->id."\">\n";

	$this->buffer .= "<div class=\"uvcheader\">\n";

	$this->buffer .= "<span class=\"uvcdate\"><b>Le ".
	  HumanReadableDate($comment->date). "\n";

	if ($comment->etat == 1)
	  $this->buffer .= "(Commentaire jugé abusif !)";

	$this->buffer .= "</b></span>\n";

	/* options (modération, ...) */
	$this->buffer .= "<span class=\"uvcoptions\">";
	$links = array();

	/* l'auteur peut toujours décider de supprimer son message */
	if ($user->id == $comment->id_commentateur)
	  {
	    $links[] = "<a href=\"".$page."?action=editcomm&id=".
	      $comment->id."\">Editer</a>";
	    $links[] = "<a href=\"".$page."?action=deletecomm&id=".
	      $comment->id."\">Supprimer</a>";
	  }
	/* sinon, n'importe qui peut signaler un abus */
	/* sous reserve que ce ne soit pas deja le cas ... */

	else if ($comment->etat != 1)
	  $links[] = "<a href=\"".$page."?action=reportabuse&id=".$comment->id."\">Signaler un abus</a>";

	/* mise en "quarantaine" par un admin
	 * (demande de modération)
	 */
	if (($admin) && ($user->id != $comment->id_commentateur))
	  $links[] = "<a href=\"".$page."?action=quarantine&id=".$comment->id."\">Mise en modération</a>";

	$this->buffer .= implode(" | ", $links);

	$this->buffer .= "</span>\n<br/>\n"; // fin span modération

	$author->load_by_id($comment->id_commentateur);

	$this->buffer .= "<span class=\"uvcauthor\"> Par ".
	  $author->get_html_extended_info() . "</span>";


	if ($comment->note_obtention != null)
	  {
	    if (($comment->note_obtention != 'F')
		&& ($comment->note_obtention != 'Fx'))
	      $this->buffer .= "<span class=\"uvcnote\">obtenu avec " .
		$comment->note_obtention;
	    else
	      $this->buffer .= "<span class=\"uvcnote\">échec (" .
		$comment->note_obtention . ")";
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

?>
