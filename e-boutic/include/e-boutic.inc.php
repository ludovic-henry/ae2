<?
/**
 * @brief La classe e-boutic permettant le design du site de la
 * boutique en ligne.
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


/* definit l'état de e-boutic :
 *
 * false si en test (rien ne passe alors par le circuit bancaire)
 * true  si en production (tout est sérieux);
 */
define ("STO_PRODUCTION", true );
/*
 * identifiant du comptoir e-boutic dans les tables MySQL
 */
define ("CPT_E_BOUTIC", 3);
/*
 * Depense minimale avant autorisation depense par carte bancaire par
 * sogenactif **en centimes d'euro**.
 */
define ("EB_TOT_MINI_CB", 1000);

// Adaptation du catalog pour e-boutic
$GLOBALS["entitiescatalog"]["typeproduit"]   = array ( "id_typeprod", "nom_typeprod", "typeprod.png", "e-boutic/");
$GLOBALS["entitiescatalog"]["produit"]    = array ( "id_produit", "nom_prod", "produit.png", "e-boutic/" );

/**
 * Version spécialisée de site pour e-boutic
 */
class eboutic extends site
{
  /* une variable panier (tableau contenant les articles) */
  var $cart;
  /* une variable total */
  var $total;

  var $comptoir;

  function eboutic ()
  {
    $this->site();
    $this->set_side_boxes("right",array("e-boutic"));
    $this->set_side_boxes("left",array());
    $this->add_css ("css/eboutic.css");

    $this->comptoir = new comptoir($this->db,$this->dbrw);
    $this->comptoir->load_by_id(CPT_E_BOUTIC);

    if ( $this->get_param("closed.eboutic",false) && !$this->user->is_in_group("root")  )
      $this->fatal_partial("services");
  }

  function start_page ($section, $title)
  {
    global $topdir;

    if ( $this->cart == null && isset($_SESSION['eboutic_cart']))
      $this->load_cart();

    /*** boite e-boutic */
    $eb_box = new contents("Panier e-boutic");

    $lst = new itemlist(null,"actions");

    if ($this->cart != null)
    {
      $eb_box->add_paragraph("Votre panier:","intro");

       $prods = new itemlist(null,"items");

      foreach ($this->cart as $item)
      {
        $prods->add($item->nom." x ".$_SESSION['eboutic_cart'][$item->id]);
      }

      $eb_box->add ($prods);


      $eb_box->add_paragraph("Total: ".sprintf("%.2f Euros",$this->total / 100),"total");

      $lst->add ("<a href=\"./cart.php\">Modifier le panier</a>");
      $lst->add ("<a href=\"./cart.php\">Passer la commande</a>");
    }
    else
      $lst->add("Votre panier est actuellement vide.");

    $eb_box->add ($lst);

    $categories = $this->get_cat ();


    $this->add_box ("e-boutic", $eb_box);

    /* demarrage normal de la page */
    parent::start_page("e-boutic",$title);
  }

  /* chargement du panier de l'utilisateur */
  function load_cart ()
  {
    $this->total = 0;

    if ( !isset($_SESSION['eboutic_cart']) || empty($_SESSION['eboutic_cart']) )
      return;

    foreach ( $_SESSION['eboutic_cart'] as $id => $count)
    {
      $prod = new produit ($this->db);
      $prod->load_by_id ($id);
      if ( $prod->is_valid() )
      {
        $this->cart[] = $prod;
        $this->total += ($prod->obtenir_prix(false,$this->user) * $count);
      }
    }

    if ( isset($_SESSION['eboutic_locked']) && $_SESSION['eboutic_locked'] != $site->user->id )
    {
      // Les verrous ont été posés pour "$_SESSION['eboutic_locked']", faut les convertirs pour "$site->user->id"
      // Cela arrive quand un utilisateur se loggue, ou lors d'un changement d'utilisateur
      // cela permet d'authoriser le remplissage du panier si non connecté

      $vp = new venteproduit ($this->db, $this->dbrw);
      $prev_user = new utilisateur($this->db);
      $prev_user->load_by_id($_SESSION['eboutic_locked']);

      foreach ( $this->cart as $prod )
      {
        $vp->charge($prod,$this->comptoir);
        $vp->debloquer ($prev_user,$_SESSION['eboutic_cart'][$item->id]);
        $vp->bloquer ($this->user,$_SESSION['eboutic_cart'][$item->id]);
      }

      $_SESSION['eboutic_locked'] = $site->user->id;
    }

  }

  /* vidange du panier de l'utilisateur */
  function empty_cart ()
  {
    if ($this->cart == null)
      $this->load_cart();

    /* si toujours vide, on sort */
    if ($this->cart == null)
      return;

    /* else */
    foreach ($this->cart as $item)
    {
      $vp = new venteproduit ($this->db, $this->dbrw);
      $vp->load_by_id ($item->id, CPT_E_BOUTIC);
      $vp->debloquer ($this->user,$_SESSION['eboutic_cart'][$item->id]);
    }
    unset($_SESSION['eboutic_cart']);
    unset($_SESSION['eboutic_locked']);
    $this->cart=null;
  }

  /* ajout d'un article dans le panier de l'utilisateur */
  function add_item ($item)
  {
    $vp = new venteproduit ($this->db, $this->dbrw);
    $ret = $vp->load_by_id ($item, CPT_E_BOUTIC);

    if ($ret == false)
      return false;

    $max = $vp->produit->can_be_sold($this->user);

    if ( $cl = $vp->produit->get_prodclass($this->user) )
    {
      if ($this->cart == null)
        $this->load_cart();

      if(!empty($this->cart))
      {
        foreach ($this->cart as $prod)
        {
          $cl2 = $prod->get_prodclass($this->user);
          if ( $cl2 && !$cl->is_compatible($cl2) )
            return false;
        }
      }
    }

    if (($max >= 0) && ($_SESSION['eboutic_cart'][$item] >= $max))
      return false;

    $_SESSION['eboutic_locked'] = $site->user->id;

    $ret = $vp->bloquer($this->user);

    if ($ret == true)
      $_SESSION['eboutic_cart'][$item] += 1;

    return $ret;
  }


  /*
   * Recuperation des articles d'une categorie donnee en argument
   *
   * @param (optionnel) cat la categorie concernee
   * @return false si echec, un tableau si succes
   *
   */
  function get_items_by_cat($cat = null)
  {
    if ($cat)
      $cat = intval($cat);

    /* note sur la requete : 3 designe le comptoir e-boutic */
    /*
    $sql = "SELECT   `cpt_comptoir`.*
                   , `cpt_produits`.*
                   , `cpt_type_produit`.*

    */
    $sql = "SELECT   `cpt_mise_en_vente`.*
                   , `cpt_produits`.*
                   , `cpt_type_produit`.`nom_typeprod`

            FROM     `cpt_mise_en_vente`
            INNER JOIN `cpt_produits` USING (`id_produit`)
            INNER JOIN `cpt_type_produit` USING (`id_typeprod`)

            WHERE `cpt_mise_en_vente`.`id_comptoir` = ".CPT_E_BOUTIC."
            AND `cpt_produits`.`prod_archive` = 0
            AND (`cpt_produits`.date_fin_produit > NOW() OR `cpt_produits`.date_fin_produit IS NULL)
            AND id_produit_parent IS NULL";
            //AND `cpt_produits`.`id_produit_parent` IS NOT NULL";

    if ($cat)
      $sql.=" AND `cpt_produits`.`id_typeprod` = $cat";
    $sql .= " ORDER BY `cpt_produits`.`id_typeprod` ASC";

    $req = new requete ($this->db, $sql);
    if (!$req->lines)
      return false;


    for ($i = 0; $i < $req->lines; $i++)
    {
      /* si une categorie precise est fournie */
      if ($cat != null)
        $tab[] = $req->get_row ();
      /* sinon on ordonne le tableau resultant
       * par categorie                         */
      else
      {
        $rs = $req->get_row ();
        $tab[$rs['nom_typeprod']][] = $rs;
      }
    }
    return $tab;
  }

  /* fonction de recuperation des categories */
  function get_cat ()
  {
    $except="";
    if(!$this->user->ae)
      $except =" AND id_typeprod!=11 ";
    $sql = "SELECT   `cpt_type_produit`.`id_typeprod`
                   , `cpt_type_produit`.`nom_typeprod`
                   , `cpt_type_produit`.`id_file`
                   , `cpt_type_produit`.`description_typeprod`

            FROM     `cpt_mise_en_vente`
            INNER JOIN `cpt_produits` USING (`id_produit`)
            INNER JOIN `cpt_type_produit` USING (`id_typeprod`)

            WHERE `cpt_mise_en_vente`.`id_comptoir` = ".CPT_E_BOUTIC."
            AND (`cpt_produits`.date_fin_produit > NOW() OR `cpt_produits`.date_fin_produit IS NULL)
            $except
            GROUP BY `id_typeprod`
            ORDER BY `id_typeprod`";

    $req = new requete ($this->db, $sql);
    if (!$req->lines)
      return false;


    for ($i = 0; $i < $req->lines; $i++)
      $cat[] = $req->get_row();

    return $cat;
  }
  /* fonction de determination de l'achat (si rechargement compte AE)
   * utilisation : si on recharge son compte AE lors d'un achat,
   * le paiement par carte AE devient debile ...
   */
  function is_reloading_AE ()
  {
    foreach ($this->cart as $item)
    {
      /* 11 = categorie rechargement compte AE */
      if ($item->id_type == 11)
        return true;
    }
    return false;
  }

  /*
   * fonction retournant le panier courant contenu dans une box
   */
  function get_panierBox(){
    $panier = new contents("Mon Panier");

    if ($this->cart == false)
      $panier->add_paragraph("Votre panier est vide");
    else {
      $list = new itemlist("Votre panier contient");

      foreach($this->cart as $item) {
        $list->add($item->nom."<br><span class=\"prix_box\">".
          $_SESSION['eboutic_cart'][$item->id]." x ".sprintf("%.2f", $item->obtenir_prix(false,$this->user)/100).
          "</span><br>");
      }
      $panier->add($list,true);
      $panier->add_paragraph("<b>Total : </b>".sprintf("%.2f",$this->total/100).
        " Euros");
      $panier->add_paragraph("<a href=\"cart.php\">Passer la commande</a>");
    }

    return $panier;
  }
}

?>
