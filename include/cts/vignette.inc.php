<?php
/* Copyright 2006 - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Hautement inspire de la fiche matmatronch dont le code revient a
 * Julien Etelain
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
 * Vignette pour e-boutic
 * @deprecated
 * @ingroup display_cts_eboutic
 */
class vignette extends stdcontents
{
  /**
   * Génère une fiche produit pour e-boutic
   * @param $id l'identifiant de l'article
   * @param $title titre
   * @param $id_file le chemin vers l'image
   * @param $desc la description
   * @param $prix le prix (en centimes)
   * @param $stock le stock disponible
   * @param $admin (optionnel) fonctionnalite d'administration sur l'objet
   */
  function vignette ($id,
		     $title,
		     $id_file,
		     $desc,
		     $prix,
		     $stock,
		     $cat,
		     $admin = false)
  {
    global $topdir;
    $this->title = $title;

    $this->buffer .= "<div class=\"userinfo\">\n";

    $regs=null;

    if ( !is_null($id_file) )
      $this->buffer .= "<img src=\"" . $topdir . "/d.php?id_file=" . $id_file .
        "&amp;action=download&amp;download=thumb\" alt=\"\" class=\"fiche_image\" />\n";
    else
      $this->buffer .= "<img src=\"" . $topdir . "images/comptoir/eboutic/prod-unknown.png".
	"\" alt=\"\" class=\"fiche_image\" />\n";
    $this->buffer .= "<p><b>". $title . "</b><br/><br/>";
    $this->buffer .= "<i>". $desc . "</i><br/><br/>";

    $this->buffer .= "<b>Prix : " .
      sprintf("%.2f Euros",$prix /100) . "</b><br/>";


	if ($stock != -1)
	{
	    $stck = $stock;

	    if ($stock == 0)
	      $stck = "Epuise";

	    $this->buffer .= "<br/>Stock : ".$stck ."";
	}


    if ($stock != 0)
      $this->buffer .= "<br/><a class=\"eb_addcart\" href=\"./".
	"?act=add&amp;item=$id&amp;cat=$cat\">Ajouter au panier</a></p>\n";

    $this->buffer .= "<div class=\"clearboth\"></div>\n";
    $this->buffer .= "</div>\n";
  }

}

/**
 * Vignette pour e-boutic
 * @deprecated
 * @ingroup display_cts_eboutic
 */
class vignette2 extends stdcontents
{
  /**
   * Génère une fiche catégorie pour e-boutic
   * @param $id l'identifiant de la catégorie
   * @param $title titre
   * @param $id_file le chemin vers l'image
   * @param $desc la description
   */
  function vignette2 ($id,
                     $title,
                     $id_file,
                     $desc)
  {
    global $topdir;
    $this->title = $title;

    $this->buffer .= "<div class=\"userinfo\">\n";

    $regs=null;

    $this->buffer .= "<p><b>". $title . "</b></p>\n";

		$this->buffer .= "<a href=\"./?cat=$id\">";

    if ( !is_null($id_file) )
      $this->buffer .= "<img src=\"" . $topdir . "/d.php?id_file=" . $id_file .
        "&amp;action=download&amp;download=thumb\" alt=\"\" class=\"fiche_image\" border=\"0\" />\n";
    else
      $this->buffer .= "<img src=\"" . $topdir . "images/comptoir/eboutic/prod-unknown.png".
        "\" alt=\"\" class=\"fiche_image\" border=\"0\" />\n";

    $this->buffer .= "</a>";

		$this->buffer .= "<p><br/>\n";
    $this->buffer .= "<i>". $desc . "</i>\n";
    $this->buffer .= "</p>\n";
		$this->buffer .= "<div class=\"clearboth\"></div>\n";
		$this->buffer .= "<p><a class=\"eb_addcart\" href=\"./?cat=$id\">Voir les articles</a></p>\n";

    $this->buffer .= "<div class=\"clearboth\"></div>\n";
    $this->buffer .= "</div>\n";
  }

}

?>
