<?php
/**
 * @brief Etat du panier pour le magasin en ligne de l'AE (e-boutic).
 *
 */

/* Copyright 2006
 *
 * Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * Simon Lopez <simon POINT lopez CHEZ ayolo POINT org>
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
require_once($topdir . "comptoir/include/venteproduit.inc.php");
require_once("include/e-boutic.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/cts/gallery.inc.php");
require_once($topdir . "include/cts/vignette.inc.php");


/* modifications du panier */
if (isset($_POST['cart_modify']))
{
  foreach ($_POST as $item_id => $qte)
  {
    if (!is_int($item_id))
      continue;
    if ($qte == 0)
      unset ($_SESSION['eboutic_cart'][$item_id]);
    if ($qte > 0)
      $_SESSION['eboutic_cart'][$item_id] = $qte;
  }
}

$site = new eboutic ();

$site->allow_only_logged_users("e-boutic");

$site->start_page ("e-boutic", "Etat du panier");

$accueil = new contents ("E-boutic : Etat du panier",
                         "<p>Sur cette page, vous allez pouvoir ".
                         "recenser les articles que vous vous ".
                         "appretez a commander<br/><br/>".
                         "Si tout vous parait normal, vous pouvez ".
                         "passer a l'achat.<br/><br/></p>");


/* panier vide */
if ($site->cart == false)
  $accueil->add_paragraph("Votre panier est actuellement vide");
/* panier non vide */
else
{
  /* faute de mieux, nous utiliserons ici un stdcontents */
  $cart_t = new stdcontents ("Etat du panier");
  $cart_t->buffer .= "<h2>Contenu</h2>\n";
  $cart_t->buffer .= "<form method=\"post\">\n";
  $cart_t->buffer .= "<table class=\"cart\">\n";
  $cart_t->buffer .= "<tr style=\"font-weight: bold;\">".
                     "<td>Article</td>".
                     "<td style=\"text-align: center;\">Quantite</td>".
                     "<td style=\"text-align: right;\">Prix unitaire</td>".
                     "</tr>\n";

  foreach ($site->cart as $item)
  {
    /*
    * On vérifie que le nombre d'occurences d'un même article est bien
    * inférieur à la limite pour utilisateur
    * (celle-ci a pu changer si l'utilisateur a commandé à un comptoir
    * depuis qu'il a ajouté le produit au panier)
    */
    $max = $item->can_be_sold($site->user);
    if ($max >= 0)
      $_SESSION['eboutic_cart'][$item->id] = min($max, $_SESSION['eboutic_cart'][$item->id]);

    for ($i=0 ; $i < $_SESSION['eboutic_cart'][$item->id] + 1 ; $i++)
      $tmp[$i] = $i;

    $cart_t->buffer .= ("<tr>\n".
                    "<td>" . $item->nom . "</td>".
                    "<td style=\"text-align: center;\">");

    if (isset($_POST['cart_submit']))
      $cart_t->buffer .= $_SESSION['eboutic_cart'][$item->id];
    else
      $cart_t->buffer .=
                GenerateSelectList($tmp, $_SESSION['eboutic_cart'][$item->id] , $item->id);

    $cart_t->buffer .= (" </td>\n".
                        " <td style=\"text-align: right;\">".
                        sprintf("%.2f", $item->obtenir_prix(false,$site->user) / 100) .
                        "</td></tr>\n");
  }
  $cart_t->buffer .= ("<tr style=\"font-weight: bold;\">".
                      "<td colspan=\"2\" style=\"text-align: right;\">Total :</td>".
                      "<td style=\"text-align: right;\">" .
                      sprintf("%.2f", $site->total / 100) .
                      " Euros</td></tr>");
  $cart_t->buffer .= ("</table>");

  if (!isset($_POST['cart_submit']))
  {
    $cart_t->buffer .= ("<h2>Actions</h2>\n");
    $cart_t->buffer .= ("<table><tr><td><input type=\"submit\"".
                        " name=\"cart_modify\" " .
                        "value=\"Accepter les modifications\" />\n");
    $cart_t->buffer .= ("</form></td>\n");

    $cart_t->buffer .= ("<td><form action=\"cart.php\" method=\"post\">\n");
    $cart_t->buffer .= ("<input type=\"submit\" name=\"cart_submit\"
                    value=\"Passer la commande\" />\n");
    $cart_t->buffer .= ("</form></td></tr></table>");
  }
  else
    $cart_t->buffer .= ("</form>");
  $accueil->add ($cart_t);


  /* formulaire "proceder au paiement" poste */
  if (isset($_REQUEST['cart_submit']))
  {


    require_once ("./include/request.inc.php");

    /* boutique de test ? */
    if (STO_PRODUCTION == false)
      $site->add_contents (new contents("ATTENTION",
                                        "<p class=\"error\">Boutique en ".
                                        "ligne de test.<br/><br/> ".
                                        "tous vos achats seront fictifs !".
                                        "</p>"));

    /* on a besoin d'un tableau avec les id des articles
      * pour l'invocation d'une class request
      *
      * Si plusieurs articles, ces articles doivent apparaitre
      * autant de fois que de quantite (d'ou la boucle for)
      */
    $cb = true;
    $no_cb_products = "";
    foreach ($site->cart as $item)
    {
      for ($i = 0; $i < $_SESSION['eboutic_cart'][$item->id]; $i++) {
        $cart_contents[] = $item->id;
        if (!$item->cb) {
          $cb = false;
          $no_cb_products .= $item->nom . ' ';
        }
      }
    }

    /* a ce stade le panier ne peut pas etre vide */

    if ( $site->user->type == "srv" ) // Ne propose pas CB/carte AE aux services, mais que sur facture
    {
      $accueil->add_title(1,"Paiement sur facture");

      $accueil->add_paragraph ("Cliquez sur le lien pour valider la commande : <a href=\"./eb_ae.php\">Paiement sur facture</a>");

    }
    else
    {
      /* $cb vaut true si tous les articles du panier peuvent être payés par CB */
      if ($cb) {
        /* pas de nouvelle request si total du panier insuffisant */
        if ($site->total >= EB_TOT_MINI_CB)
        {
          $req = new request ($site->dbrw,
                              $site->user->id,
                              $site->total,
                              $cart_contents);

          $accueil->add_title(1,"Paiement par carte bleue");
         /* le formulaire HTML genere par le binaire sogenactif
           * nous est envoye de facon brute. Il faut donc le
           * rajouter a notre objet $accueil "a l'arrache"       */
         $accueil->add_paragraph ($req->form_html);
        }
        else
        {
          $accueil->add_paragraph ("<h1>Total insuffisant</h1>" .
                                   "<p>La depense engendree par vos ".
                                   "achats actuels est insuffisante ".
                                   "pour envisager un paiement par ".
                                   "carte bancaire. Veuillez opter pour ".
                                   "un paiement par carte AE.</p>");
        }
      } else {
        $accueil->add_paragraph ("<h1>Paiment par CB : impossible</h1>" .
                                 "<p>Au moins un article de votre panier " .
                                 "ne peut être payé avec une carte bancaire. " .
                                 "Veuillez opter pour un paiment par carte " .
                                 "AE. (Produits non compatibles : ".
                                 $no_cb_products.")</p>");
      }

      /* recharger son compte AE avec sa carte AE est debile ... */
      if ($site->is_reloading_AE ())
        $accueil->add_paragraph ("<h1>Paiement par carte AE : impossible</h1>\n".
                                 "<p>Votre panier ".
                                 "contient des bons de rechargement Compte AE.".
                                 "Le paiement par carte AE est par consequent ".
                                 "desactive.</p>");
      else
      {
        if ( $site->user->type == "srv" )
          $accueil->add_paragraph ("<h1>Paiement sur facture</h1>\n" .
                                   "<p>Cliquez sur le lien pour valider la commande</p>\n<p class=\"center\">\n".
                                   " <a href=\"./eb_ae.php\">Paiement sur facture</a></p>\n");

        /* controle si suffisemment sur carte AE pour envisager un paiement */
        elseif (!$site->user->credit_suffisant($site->total) )
          $accueil->add_paragraph ("<h1>Paiement par carte AE : Solde de ".
                                   sprintf("%.2f", $site->user->montant_compte / 100) .
                                   " Euros insuffisant </h1>".
                                   "<p>La depense engendree est trop ".
                                   "importante pour envisager un paiement ".
                                   "par carte AE.<br/>".
                                   "Veuillez recharger ".
                                   "votre compte AE avant de poursuivre.</p>");

        else
          $accueil->add_paragraph ("<h1>Paiement par carte AE</h1>\n" .
                                   "<p>Cliquez sur le logo de l'AE pour payer avec votre carte AE</p>\n<p class=\"center\">\n".
                                   " <a href=\"./eb_ae.php\">\n".
                                   "  <img src=\"".$topdir."images/eb_ae.jpg\" alt=\"paiement carte AE\" />\n".
                                   "  </a></p>\n");
      } // fin paiement Carte AE
    }// fin par service
  } // fin si panier poste et demande paiement effective
} // fin panier non vide

$site->add_contents ($accueil);
$site->end_page ();

?>
