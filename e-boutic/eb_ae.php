<?php
/*
 * (e-boutic).
 *
 */

/* Copyright 2006
 * Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des ï¿½tudiants de
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

$site->allow_only_logged_users("services");

$site->start_page ("e-boutic", "Etat du panier");

$modal = "par carte AE";
if ( $site->user->type == "srv" )
  $modal = "sur facturation";

$accueil = new contents ("E-boutic : Paiement $modal");


/* panier vide */
if ($site->cart == false)
  $accueil->add_paragraph("Votre panier est actuellement vide");

/* panier non vide */
else
{
  /* pas de formulaire de validation d'achat poste */
  if (!isset($_POST['confirm_payment']))
  {

    if ( $site->user->type == "srv" )
      $accueil->add_paragraph("Vous etes sur le point de ".
       "confirmer l'achat des articles suivants<br/><br/>");
     else
      $accueil->add_paragraph("Vous etes sur le point de ".
       "confirmer l'achat des articles suivants ".
       "a l'aide de votre compte AE (hors systeme ".
       "bancaire)<br/><br/>");

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
      for ($i=0 ; $i < $_SESSION['eboutic_cart'][$item->id] + 1 ; $i++)
        $tmp[$i] = $i;

              $cart_t->buffer .= ("<tr>\n".
                            "<td>" . $item->nom . "</td>".
                            "<td style=\"text-align: center;\">");
        $cart_t->buffer .= $_SESSION['eboutic_cart'][$item->id];
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

    $accueil->add ($cart_t);

    /* formulaire de confirmation */
    $frm = new form ("confirm_payment_AE",
                     "eb_ae.php",
                     false,
                     "POST",
                     "Confirmation");
    $frm->add_hidden("confirm_payment");
    $frm->add_submit("payment_ae_proceed",
                     "OUI");
    $frm->add_submit("payment_ae_cancel",
                     "NON");

    $accueil->add ($frm,true);

  } // fin absence de formulaire dans $_POST
  /* formulaire de confirmation poste */
  else
  {
    //si OUI
    if (isset($_POST["payment_ae_proceed"]))
    {
      $debfact = new debitfacture ($site->db, $site->dbrw);
      $cpt = new comptoir ($site->db, $site->dbrw);
      $cpt->load_by_id (CPT_E_BOUTIC);
      $cpt_cart=array();

      foreach ($site->cart as $item)
      {
        $vp = new venteproduit ($site->db, $site->dbrw);
        $vp->load_by_id ($item->id, CPT_E_BOUTIC);
        $cpt_cart[] = array($_SESSION['eboutic_cart'][$item->id], $vp);
      }

      $debfact->debitAE ($site->user,
                         $site->user,
                         $cpt,
                         $cpt_cart,
                         false);
      $accueil->add_paragraph ("<h1>Vente effectuee</h1>".
                               "<p>Vos achats $modal ont ".
                               "&eacute;t&eacute; effectu&eacute;s.<br/><br/>".
                               "Merci d'avoir utilis&eacute; e-boutic !".
                               "<br/><br/>".
                               "<a href=\"./\">Retour accueil ".
                               "e-boutic</a></p>");

      foreach ($site->cart as $prod)
      {
        if ($cl=$prod->get_prodclass($site->user))
          if ( $cts=$cl->get_once_sold_cts($site->user))
            $site->add_contents($cts);
      }

      $site->empty_cart ();
    }
    //si annulation (NON)
    if (isset($_POST["payment_ae_cancel"]))
    {
      $accueil->add_paragraph ("<h1>Vente annulee</h1>".
                               "<p>Vos achats $modal ont ".
                               "ete annules.<br/><br/>".
                               "<a href=\"./\">Retour accueil ".
                               "e-boutic</a></p>");
    }
  } // fin confirmation postee
}// fin panier non vide


$site->add_contents ($accueil);
$site->end_page ();

?>
