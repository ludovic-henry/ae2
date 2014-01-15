<?php
/**
 * @brief L'accueil du magasin en ligne de l'AE (e-boutic).
 *
 */

/* Copyright 2006,2007
 *
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Simon Lopez <simon POINT lopez CHEZ ayolo POINT org>
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

$topdir="../";

require_once($topdir . "include/site.inc.php");
require_once($topdir . "comptoir/include/produit.inc.php");
require_once($topdir . "comptoir/include/typeproduit.inc.php");

require_once($topdir . "comptoir/include/venteproduit.inc.php");
require_once("include/e-boutic.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/cts/gallery.inc.php");
require_once($topdir . "include/cts/e-boutic.inc.php");


$site = new eboutic();

$produit = new produit($site->db);
$typeproduit = new typeproduit($site->db);

if ( isset($_REQUEST["id_produit"]) )
{
  $produit->load_by_id($_REQUEST["id_produit"]);

  if (
       $produit->is_valid()
       && ($produit->id_type!=11 || ($site->user->ae && $produit->id_type!=11))
     )
  {
    $venteprod = new venteproduit ($site->db);
    if ( !$venteprod->charge($produit,$site->comptoir) )
      $produit->id = null;
    else
      $typeproduit->load_by_id($produit->id_type);
  }
}
elseif ( isset($_REQUEST["item"]) ) // legacy support
{
  $produit->load_by_id($_REQUEST["item"]);

  if ( !$produit->is_valid() )
  {
    $venteprod = new venteproduit ($site->db);
    if ( !$venteprod->charge($produit,$site->comptoir) )
      $produit->id = null;
    else
      $typeproduit->load_by_id($produit->id_type);
  }
}

elseif ( isset($_REQUEST["id_typeprod"]) )
{
  if(   (intval($_REQUEST["id_typeprod"])==11 && $site->user->ae)
     || (intval($_REQUEST["id_typeprod"])!=11) )
  $typeproduit->load_by_id($_REQUEST["id_typeprod"]);
}
elseif ( isset($_REQUEST["cat"]) ) // legacy support
  $typeproduit->load_by_id($_REQUEST["cat"]);


/* vidage du panier */
if ($_REQUEST['act'] == "empty_cart")
{
  $site->empty_cart ();
}

/*mise a jour du panier */
if ($_REQUEST['act'] == "add")
{
  $ret = $site->add_item ($produit->id);

  /* produit non trouve ou stock insuffisant */
  if ($ret == false)
    $add_rs = new error("Ajout",
      "<b>Impossible d'ajouter le produit dans le panier</b>. Soit le produit ne peut être acheté, soit il est incompatible avec un produit se trouvant déjà dans le panier.");
  /*ajout possible */
  else
  {
    $add_rs = new contents ("Ajout");
    $add_rs->add_paragraph ( "Ajout de l'article effectue avec succes.");
    $add_rs->add_paragraph ("<a href=\"./cart.php\">Passer la commande</a>");
    $produit->id=null;
  }
}

if ( $produit->is_valid() && !is_null($produit->id_produit_parent) )
{
  while ( $produit->is_valid() && !is_null($produit->id_produit_parent) )
    $produit->load_by_id($produit->id_produit_parent);

  if ( $produit->is_valid() )
  {
    $venteprod = new venteproduit ($site->db);
    if ( !$venteprod->charge($produit,$site->comptoir) )
      $produit->id = null;
    else
      $typeproduit->load_by_id($produit->id_type);
  }
}


$site->start_page ("e-boutic", "Accueil e-boutic");

/* ajout panier ? */
if (isset($add_rs)) {
  $site->add_contents ($add_rs);
}

if(
   $typeproduit->is_valid()
   && !empty($typeproduit->css)
   && file_exists($wwwtopdir.'css/eboutic/'.$typeproduit->css)
  )
  $site->add_css('css/eboutic/'.$typeproduit->css);

if ( $produit->is_valid() )
{
  $venteprod = new venteproduit ($site->db);
  $typeproduit->load_by_id($produit->id_type);
  if ( $venteprod->charge($produit,$site->comptoir) )
    $site->add_contents (new ficheproduit( $typeproduit, $produit, $venteprod, $site->user ));
}
elseif ( !$typeproduit->is_valid() )
{
  $accueil = new contents("E-boutic",
        "Bienvenue sur E-boutic, la boutique en ligne ".
        "de l'AE. Sur cette page, vous allez pouvoir ".
        "selectionner des categories dans lesquelles ".
        "sont ranges les differents articles proposes ".
        "a la vente.<br/>".
        "Une fois votre panier rempli, vous pourrez ".
        "passer a l'achat, en basculant sur les serveurs".
        " securises de notre partenaire.<br/><br/>".
        "Ce service vous est offert grâce au soutien de la <a href=\"http://jeunes.societegenerale.fr/\">Société Générale</a>.<br/>");

  $site->add_contents ($accueil);



  $items = new requete($site->db,"SELECT `cpt_mise_en_vente`.*, `cpt_produits`.* , `cpt_type_produit`.`nom_typeprod` ".
            "FROM `cpt_mise_en_vente` ".
            "INNER JOIN `cpt_produits` USING (`id_produit`) ".
            "INNER JOIN `cpt_type_produit` USING (`id_typeprod`) ".
            "WHERE `cpt_mise_en_vente`.`id_comptoir` = ".CPT_E_BOUTIC." ".
            "AND `cpt_produits`.`prod_archive` = 0 ".
            "AND (`cpt_produits`.date_fin_produit > NOW() OR `cpt_produits`.date_fin_produit IS NULL) ".
            "AND id_produit_parent IS NULL ".
            "ORDER BY date_mise_en_vente DESC ".
            "LIMIT 4");

  $items_lst = new gallery ("Derniers produits mis en vente");

  while ( $row = $items->get_row() )
    $items_lst->add_item (new vigproduit($row,$site->user));


  /* ajout liste des articles au site */
  $site->add_contents ($items_lst);





  /* recuperation des categories */
  $cat = $site->get_cat ();

  /* on traite les categories en vue d'un affichage dans un
  * contenu itemlist
  */
  $items_lst = new gallery ("Rayons disponibles");
  foreach ($cat as $c)
  {
    $items_lst->add_item (new vigtypeproduit($c));
  }

  $site->add_contents ($items_lst);
}
/* sinon : $_REQUEST['cat'] renseigne */
else
{
  $items = $site->get_items_by_cat ($typeproduit->id);

  if ((count($items) <= 0) || ($items == false))
    $site->add_contents(new contents("<a href=\"index.php\">E-Boutic</a> / ".$typeproduit->get_html_link(),"<p>Aucun produit en vente</p>"));
  else
  {
    /* creation du cts contenant les infos sur les articles */
    $items_lst = new gallery ("<a href=\"index.php\">E-Boutic</a> / ".$typeproduit->get_html_link());

    /* traitement des donnees avant affichage
     * dans un contents gallery               */
    foreach ($items as $row)
      $items_lst->add_item (new vigproduit($row,$site->user));
    /* ajout liste des articles au site */
    $site->add_contents ($items_lst);
  }
  /* fin categorie non vide */
}
  /* ajout du panier au site */
  $site->add_box("panier",$site->get_panierBox());
  $site->set_side_boxes("right",array("panier"),"panier_right");

/* fin page */
$site->end_page ();
?>
