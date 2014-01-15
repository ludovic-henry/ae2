<?php

/* Copyright 2010
 *
 * - Cyrille Platteau < cyrille dot platteau at utbm dot fr >
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

require_once($topdir. "include/cts/special.inc.php");

/**
 * @file
 */

/**
 * Conteneur de fiche d'information sur un utilisateur
 * @ingroup display_cts
 * @author Cyrille Platteau
 */
class productinfo extends stdcontents
{
  /**
   * Génère une fiche pour un produit
   * @param $product instance de la classe produit
   * @param $barman affiche le prix barman à la place du prix normal
   * @param $sales affiche un formulaire de vente
   */

  /**
   * Génère une fiche pour un produit
   */
  function productinfo ( $product, $barman=false, $sales=false)
  {
    global $topdir;

    $prix = $product->obtenir_prix(false);
    $prixBarman = $product->obtenir_prix(true);

    $this->title = $product->nom;
    $this->buffer .= "<a href=\"#\" title=\"Ajouter 1 ".$product->nom." au panier\" onclick=\"return addToCart('$product->code_barre', '".addslashes($product->nom)."', $prix, "." $prixBarman, ".(($product->plateau) ? '1' : '0').', '.(($barman) ? '1' : '0').");\">"."\n";
    $this->buffer .= "<div id=\"product".$product->id."\" class=\"productinfo\">\n";

      $this->buffer .= "<h3>". $product->nom . "</h3>\n";
      $this->buffer .= "<div class=\"clearboth\"></div>\n";
      $this->buffer .= "<hr />\n";
      $this->buffer .= "<div class=\"clearboth\"></div>\n";

      $this->buffer .= "<div class=\"photo\" style=\"float: left;\">";

      if ($product->id_file)
      {
          $this->buffer .= "<img src=\"".$wwwtopdir."../d.php?id_file=".$product->id_file."&amp;action=download&amp;download=preview\" alt=\"\" class=\"fiche_image\" title=\"".$produit->nom."\" alt=\"".$produit->nom."\"/>\n";
      }
      else
        $this->buffer .= "<img src=\"/data/matmatronch/na.gif"."\" alt=\"\" class=\"fiche_image\" />\n";

      $this->buffer .= "</div>";

    $this->buffer .= "<p class=\"codebar\">".$product->code_barre."</p>\n";

    $this->buffer .= "<p class=\"price\">";

    $this->buffer .= number_format($prix/100, 2, ",", " ") ." &euro;";

    $this->buffer .= "</p>";
    $this->buffer .= "</div>\n";
    $this->buffer .= "</a>";
  }
}
?>
