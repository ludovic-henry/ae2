<?php

/* Copyright 2005-2010
 * - Julien Etelain <julien CHEZ pmad POINT net>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Cyrille Platteau <6pour5 CHEZ gmail POINT com>
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

/**
 * @file
 * Interface de vente par carte AE sur des comptoirs de type "classique" (bar)
 * ou de type "bureau". Il s'agit de la partie commune des interfaces pour les
 * deux types de comptoir concernés.
 *
 * Permet aussi de preter les livres se trouvant dans la même salle que le
 * comptoir.
 *
 * $site doit être de type sitecomptoirs
 *
 * @see sitecomptoirs
 * @see comptoir
 * @see comptoir/bureau.php
 * @see comptoir/comptoir.php
 */

if ( $_REQUEST["action"] == "logclient" && count($site->comptoir->operateurs))
{
  setcookie('action', '');
  $client = new utilisateur($site->db,$site->dbrw);

  if ( $_REQUEST["code_bar_carte"] )
    $client->load_by_carteae($_REQUEST["code_bar_carte"],true);
  elseif ( $_REQUEST["id_utilisateur_achat"] )
    $client->load_by_id($_REQUEST["id_utilisateur_achat"]);

  setcookie('id_utilisateur_achat', '');

  if ( $client->vol )
  {
    $Erreur = "REFUSE. Carte à saisir.";
    $MajorError="Carte perdue/volée.";
  }
  elseif ( !$client->is_valid() )
    $Erreur = "Client inconnu";
  elseif ( !$client->cotisant )
    $Erreur = "Cotisation AE non renouvelée";
  elseif ( $client->is_in_group("cpt_bloque") )
    $Erreur = "Compte bloqué : prendre contact avec un responsable. Ceci est probablement du à une dette au BDF.";
  else
    $site->comptoir->ouvre_pannier($client,$_REQUEST["prix_barman"] == true);

}
/*
  En pleine vente...
*/
else if ( ($_REQUEST["action"] == "vente" || $_REQUEST["action"] == "ventefin" || $_REQUEST["action"] == "venteann" || $_REQUEST["action"] == "venteanc")
  && count($site->comptoir->operateurs) )
{
  $ok = true;
  if ( !strcasecmp($_REQUEST["code_barre"],"FIN") || isset($_REQUEST['ventefin']) )
  {
    if ( $site->comptoir->mode == "book" )
    {
      if ( count($site->comptoir->panier) )
      {
        $emp = new emprunt ( $site->db, $site->dbrw );
        $endtime = time()+(8*24*60*60);

        $emp->add_emprunt ( $site->comptoir->client->id, null, null, time(), $endtime );
        foreach ( $site->comptoir->panier as $objet )
          $emp->add_object($objet->id);

        $op = first($site->comptoir->operateurs);
        $emp->retrait (  $op->id, 0, 0, "" );

        $rapport_contents = new contents("Pret juqu'au ".date("d/M/Y H:i",$endtime)." (maximum)");
        $rapport_contents->add_paragraph("Pret de matériel n°".$emp->id."");
      }
      $site->comptoir->vider_pour_vente();
    }
    else
    {
      // on gere les produits ajoutes via javascript
      $strBarCodes = $_REQUEST["nouveaux_produits"];

      $arrayBarCode = explode(";", $strBarCodes);

      for ( $i = 0 ; $i<count($arrayBarCode) ; $i++ )
      {
        $produit = new produit($site->db);

        if ($arrayBarCode[$i][0]=="-")
        {
          $produit->charge_par_code_barre(substr($arrayBarCode[$i],1));

          if ( $produit->id > 0 )
          {
            $site->comptoir->enleve_panier($produit);
          }
        }
        else
        {
          $produit = new produit($site->db);

          $produit->charge_par_code_barre($arrayBarCode[$i]);

          if ( $produit->id > 0 )
          {
            $ok = $ok & $site->comptoir->ajout_pannier($produit, $err);
            if (!empty($err) && empty($Erreur))
              $Erreur = $err;
          }
        }
      }
      if ($ok)
      {
        list($client,$vendus) = $site->comptoir->vendre_panier();

        if ( $client )
        {
          $client->refresh_solde();
          $rapport = true;
        }
      }
    }
  }
  elseif ( !strcasecmp($_REQUEST["code_barre"],"ANN") || isset($_REQUEST['venteann']) )
    $site->comptoir->annule_dernier_produit();

  elseif ( !strcasecmp($_REQUEST["code_barre"],"ANC") || isset($_REQUEST['venteanc']) || isset($_REQUEST['rechargeenfait']) )
    $site->comptoir->annule_pannier();

  elseif ( $site->comptoir->mode == "book" )
  {
    $emp = new emprunt ( $site->db, $site->dbrw );
    $bk = new livre($site->db);
    $bk->load_by_cbar( $_REQUEST["code_barre"]);

    if ( $bk->id > 0 )
    {
      if ( $bk->id_salle != $site->comptoir->id_salle )
        $Erreur = "Livre/BD venant d'un autre lieu !!";
      else
      {
        $emp->load_by_objet($bk->id);
        if ( $emp->id > 0 )
        {
          $emp->back_objet($bk->id);
          $message = "Livre/BD marquée comme restituée";
        }
        else
        {
          $ok = $ok & $site->comptoir->ajout_pannier($bk, $err);
          if (!empty($err) && empty($Erreur))
            $Erreur = $err;
        }
      }
    }
  }
  else
  {
    $num = 1;
    $cbar = $_REQUEST["code_barre"];
    $produit = new produit($site->db);

    if ( ereg("^([0-9]*)x(.*)$",$cbar,$regs) )
    {
      $num = intval($regs[1]);
      $cbar = $regs[2];
    }

    $produit->charge_par_code_barre($cbar);

    if ( $produit->id > 0 )
    {
      for ( $i = 0 ; $i<$num ; $i++ )
      {
        $ok = $ok & $site->comptoir->ajout_pannier($produit, $err);
        if (!empty($err) && empty($Erreur))
          $Erreur = $err;
      }
    }
    else
    {
      $emp = new emprunt ( $site->db, $site->dbrw );
      $bk = new livre($site->db);
      $bk->load_by_cbar($cbar);

      if ( $bk->id > 0 )
      {
        if ( $bk->id_salle != $site->comptoir->id_salle )
          $Erreur = "Livre/BD venant d'un autre lieu !!";
        else
        {
          $site->comptoir->switch_to_special_mode("book");
          $emp->load_by_objet($bk->id);
          if ( $emp->id > 0 )
          {
            $emp->back_objet($bk->id);
            $message = "Livre/BD marqué comme restituée";
          }
          else
          {
            $ok = $ok & $site->comptoir->ajout_pannier($bk, $err);
            if (!empty($err) && empty($Erreur))
              $Erreur = $err;
          }
        }
      }
    }

    // on gere les produits ajoutes via javascript
    $strBarCodes = $_REQUEST["nouveaux_produits"];

    $arrayBarCode = explode(";", $strBarCodes);

    for ( $i = 0 ; $i<count($arrayBarCode) ; $i++ )
    {
      $produit = new produit($site->db);

      if ($arrayBarCode[$i][0]=="-")
      {
        $produit->charge_par_code_barre(substr($arrayBarCode[$i],1));

        if ( $produit->id > 0 )
        {
          $site->comptoir->enleve_panier($produit);
        }
      }
      else
      {
        $produit = new produit($site->db);

        $produit->charge_par_code_barre($arrayBarCode[$i]);

        if ( $produit->id > 0 )
        {
          $ok = $ok & $site->comptoir->ajout_pannier($produit, $err);
          if (!empty($err) && empty($Erreur))
            $Erreur = $err;
        }
      }
    }
  }
}
/*
  On nous demande de recharger un compte
*/
else if ( $_REQUEST["action"] == "recharge" && count($site->comptoir->operateurs) && $site->comptoir->rechargement)
{

  $client = new utilisateur($site->db,$site->dbrw);
  $asso = new assocpt($site->db);

  $asso->load_by_id(1); /*AE*/

  $client->load_by_id($_REQUEST["id_utilisateur"]);
  $montant = intval($_REQUEST["montant_centimes"]);
  $id_banque = intval($_REQUEST["id_banque"]);
  $id_typepaie = intval($_REQUEST["id_typepaie"]);

  if ( !$client->is_valid() )
    $RechargementErreur = "Etudiant inconnu";
  elseif ( !$GLOBALS["svalid_call"] )
    $RechargementErreur = "Ignoré";
  elseif ( !$client->cotisant )
    $RechargementErreur = "Cotisation AE non renouvelée";
  elseif ( $client->is_in_group("cpt_bloque") )
    $RechargementErreur = "Compte bloqué : prendre contact avec un responsable. Ceci est probablement du à une dette au BDF.";
  elseif ( $asso->id < 1 )
    $RechargementErreur = "Erreur interne";
  elseif ( $id_typepaie == PAIE_CHEQUE && $id_banque == 0 )
    $RechargementErreur = "Veuillez préciser la banque";

  /* Bienvenue dans un monde de restrictions... :S On va esperer que ça va limiter les erreurs */
  elseif ( $id_typepaie == PAIE_CHEQUE && $montant > 10000  )
    $RechargementErreur = "Montant du chèque trop important : 100 Euros maximum (et 5 Euros minimum) par chèque.";
  elseif ( $id_typepaie == PAIE_CHEQUE && $montant < 500  )
    $RechargementErreur = "Montant du chèque trop faible : 5 Euros minimum (et 100 Euros maximum) par chèque.";

  elseif ( $id_typepaie == PAIE_ESPECS && $montant > 5000  )
    $RechargementErreur = "Montant en espèces trop important : 50 Euros maximum (et 2 Euros minimum) par espèces.";
  elseif ( $id_typepaie == PAIE_ESPECS && $montant < 200  )
    $RechargementErreur = "Montant en espèces trop faible : 2 Euros minimum (et 50 Euros maximum) par espèces.";

  elseif ( $id_typepaie == PAIE_ESPECS && ($montant%10) != 0  )
    $RechargementErreur = "Montant en espèces invalide : pièces de 1 cts, 2 cts et 5 cts non acceptés.";

  elseif ( !isset($_POST['cancelrech']) )
  {
    if ( $id_typepaie == PAIE_ESPECS )
      $id_banque = 0;

    $site->comptoir->recharger_compte($client,$id_typepaie,$id_banque,$montant,$asso);
    $rapportrecharge = true;
    $client->refresh_solde();

    /* si le barman souhaite recharger le compte et commander, on effectue une redirection
       pour ouvrir une interface de vente sur le client */
    if ( isset($_POST['rechcommand']) )
    {
      $strRedirect = 'Location: ';

      if (strcmp($_SERVER['HTTPS'], 'on')==0)
      {
        $strRedirect .= 'https://';
      }
      else
      {
        $strRedirect .= 'http://';
      }

      $strRedirect .= $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

      setcookie('action', 'logclient', time()+60);
      setcookie('id_utilisateur_achat', $client->id, time()+60);
//      $strRedirect .= '&action=logclient&id_utilisateur_achat='.$client->id;

      header($strRedirect);

    }
  }

}
else if ( $_REQUEST["page"] == "confirmrech" && count($site->comptoir->operateurs) && $site->comptoir->rechargement)
{
  $client = new utilisateur($site->db);

  if ( $_REQUEST["code_bar_carte"] )
    $client->load_by_carteae($_REQUEST["code_bar_carte"],true);
  elseif ( $_REQUEST["id_utilisateur_rech"] )
    $client->load_by_id($_REQUEST["id_utilisateur_rech"]);

  $montant = $_REQUEST["montant"];
  $id_banque = intval($_REQUEST["id_banque"]);
  $id_typepaie = intval($_REQUEST["id_typepaie"]);

  if ( $client->vol )
  {
    $RechargementErreur = "REFUSE. Carte à saisir.";
    $MajorError="Carte perdue/volée.";
  }
  elseif ( !$client->is_valid() )
    $RechargementErreur = "Etudiant inconnu";
  elseif ( !$client->cotisant )
    $RechargementErreur = "Cotisation AE non renouvelée";
  else if ( $client->is_in_group("cpt_bloque") )
    $RechargementErreur = "Compte bloqué : prendre contact avec un responsable. Ceci est probablement du à une dette au BDF.";
  else if ( $id_typepaie == PAIE_CHEQUE && $id_banque == 0 )
    $RechargementErreur = "Veuillez préciser la banque";

  /* Bienvenue dans un monde de restrictions... :S On va esperer que ça va limiter les erreurs */
  elseif ( $id_typepaie == PAIE_CHEQUE && $montant > 10000  )
    $RechargementErreur = "Montant du chèque trop important : 100 Euros maximum (et 5 Euros minimum) par chèque.";
  elseif ( $id_typepaie == PAIE_CHEQUE && $montant < 500  )
    $RechargementErreur = "Montant du chèque trop faible : 5 Euros minimum (et 100 Euros maximum) par chèque.";

  elseif ( $id_typepaie == PAIE_ESPECS && $montant > 5000  )
    $RechargementErreur = "Montant en espèces trop important : 50 Euros maximum (et 2 Euros minimum) par espèces.";
  elseif ( $id_typepaie == PAIE_ESPECS && $montant < 200  )
    $RechargementErreur = "Montant en espèces trop faible : 2 Euros minimum (et 50 Euros maximum) par espèces.";

  elseif ( $id_typepaie == PAIE_ESPECS && ($montant%10) != 0  )
    $RechargementErreur = "Montant en espèces invalide : pièces de 1 cts, 2 cts et 5 cts non acceptés.";


  if ( $RechargementErreur )
    unset($_REQUEST["page"]);

}

// Page
$site->start_page("services","Comptoir: ".$site->comptoir->nom);
$site->add_css("css/comptoirs.css");


$cts = new contents($site->comptoir->nom);

if ( count($site->comptoir->operateurs) == 0 )
{
  $cts->add_paragraph("En attente de la connexion d'un barman");
}
else if ( $_REQUEST["page"] == "confirmrech" )
{
  $cts->add(new userinfo($client,true,true,false,false,true,true));

  $lst = new itemlist(false,"inforech");
  $lst->add("Mode de paiement : <b>".$TypesPaiements[$id_typepaie]."</b>") ;
  if ( $id_typepaie == PAIE_CHEQUE )
  {
    $lst->add("Banque : <b>".$Banques[$id_banque]."</b>");
  }
  $lst->add("Montant : <b>".($montant/100)." Euros</b>");
  $cts->add($lst);

  $frm = new form ("recharge","?id_comptoir=".$site->comptoir->id);
    $frm->allow_only_one_usage();
    $frm->add_hidden("action","recharge");
    $frm->add_hidden("id_utilisateur",$client->id);
    $frm->add_hidden("montant_centimes",$montant);
    $frm->add_hidden("id_banque",$id_banque);
    $frm->add_hidden("id_typepaie",$id_typepaie);
    $frm->puts('<input type="submit" class="isubmit" value="Valider" name="validredch" id="validrech" />'."\n");
    $frm->puts('<input type="submit" class="isubmit" value="Annuler" name="cancelrech" id="cancelrech" />'."\n");
    $frm->puts('<input type="submit" class="isubmit" value="Valider et commander" name="rechcommand" id="rechcommand" />'."\n");
  $cts->add($frm);

  $cts->puts("<div class=\"clearboth\"></div>\n");
}
else if ( $site->comptoir->client->id > 0 )
{
  $cts->add(new userinfo($site->comptoir->client,true,true,false,false,true,true));

  $cts->puts('<div class="submit_buttons">'."\n");
    $frm = new form ("venteanc","?id_comptoir=".$site->comptoir->id);

    if ( $Erreur )
      $frm->error($Erreur);

    $frm->add_hidden("action","venteanc");
    $frm->puts('<input type="submit" class="isubmit" value="Annuler commande" name="venteanc" id="venteanc" />'."\n");
    $cts->add($frm);

    // bouton "annuler le dernier produit", marche uniquement pour les produits ajoutés au clavier
    /*
    $frm = new form ("venteann","?id_comptoir=".$site->comptoir->id);
    $frm->add_hidden("action","venteann");
    $frm->puts('<input type="submit" class="isubmit" value="Annuler dernier" name="venteann" id="venteann" />'."\n");
    $cts->add($frm);
    */

    $frm = new form ("ventefin","?id_comptoir=".$site->comptoir->id);
    $frm->add_hidden("action","ventefin");
    $frm->add_hidden("nouveaux_produits", "");
    $frm->set_event("onsubmit", "return checkBarCodeInput()");
    $frm->puts('<input type="submit" class="isubmit" value="Terminer commande" name="ventefin" id="ventefin" />'."\n");
    $cts->add($frm);

    $frm = new form ("rechargeenfait", "?id_comptoir=".$site->comptoir->id.'#confirmrech');
    $frm->add_hidden("action","venteanc");
    $frm->add_hidden("utilisateur_recharge", $site->comptoir->client->id);
    $frm->puts('<input type="submit" class="isubmit" value="Recharge compte" name="rechargeenfait" id="rechargeenfait" />'."\n");
    $cts->add($frm);
  $cts->puts('</div>'."\n");

  $cts->puts('<div id="soldeCourant" class="hide">'.number_format($site->comptoir->client->montant_compte/100, 2).'</div>');

  $dob=$site->comptoir->client->date_naissance;
  $today = mktime();
  $secondes = ($today > $dob)? $today - $dob : $dob - $today;
  $annees = date('Y', $secondes) - 1970;
  if(is_null($dob))
    $cts->add_paragraph('Attention, l\'age de ce cotisant est inconnu','linfo');
  elseif($annees < 18)
    $cts->add_paragraph('Attention, ce cotisant n\'a pas 18 ans et ne peut donc pas acheter d\'alcool','linfo');
  if ( $message )
    $cts->add_paragraph($message,"linfo");

  $frm = new form ("vente","?id_comptoir=".$site->comptoir->id);
  $frm->add_hidden("action","vente");

  if ( $site->comptoir->prix_barman )
    $frm->add_info("<b>Prix barman</b>");

  $frm->add_text_field("code_barre","Code barre","",false,false,false,true,null,"code_barre");
  $frm->add_hidden("nouveaux_produits", "");
  $frm->add_submit("valid","valider");
  $frm->set_focus("code_barre");
  $cts->add($frm);

  $tbl = new table("Panier", false, "panier");

  $total=0;

  if ( count($site->comptoir->panier))
  {

    if ( $site->comptoir->mode == "book" )
    {
      $serie = new serie($site->db);


      foreach ($site->comptoir->panier as $bk)
      {

        if ( $bk->id_serie )
        {
          $serie->load_by_id($bk->id_serie);
          $tbl->add_row(array($serie->nom, $bk->num_livre, $bk->nom));
        }
        else
        {
          $tbl->add_row(array("", "", $bk->nom));
        }
      }
    }
    else
    {
      foreach ($site->comptoir->panier as $vp)
      {
        $panier[$vp->produit->id][0]++;
        $panier[$vp->produit->id][1] = $vp;
      }
      $total=0;
      foreach ( $panier as $info )
      {
        list($nb,$vp) = $info;
        $nbP = $nb;
        if ($nb > 0 && $vp->produit->plateau) {
          $nb -= floor ($nb/6);
        }

	$prix = $vp->produit->obtenir_prix(false,$site->comptoir->client);
	$prixBarman = $vp->produit->obtenir_prix($site->comptoir->prix_barman,$site->comptoir->client);
	$prixCalc = $nb * $prix;
	$prixBarmanCalc = $nbP*$prixBarman;
	$prixFinal = ($prixCalc<$prixBarmanCalc)?$prixCalc:$prixBarmanCalc;

        $tbl->add_row(array("<a href=\"#\" onclick=\"return decrease('".$vp->produit->code_barre."', ".$prix.", ".$prixBarman.", ".(($vp->produit->plateau) ? '1' : '0').', '.(($site->comptoir->prix_barman)?'1':'0').");\">-</a>",
            array($nbP, false, "nbProd".$vp->produit->code_barre),
            "<a href=\"#\" onclick=\"return increase('".$vp->produit->code_barre."', ".$prix.", ".$prixBarman.", ".(($vp->produit->plateau) ? '1' : '0').", ".(($site->comptoir->prix_barman)?'1':'0').");\">+</a>",
            $vp->produit->nom,
            array ($nbP >= 6 ? "P" : "", false, "platProd".$vp->produit->code_barre),
            array(($prixFinal/100)." &euro;", false, "priceProd".$vp->produit->code_barre)),
          false, "prod".$vp->produit->code_barre);

        $total += $prixFinal;
      }
    }

  }

  if ( $site->comptoir->mode != "book" )
  {
    $tbl->add_row(array("", "", "", "", "Total: ", array(($total/100)." &euro;", false, "priceTotal")), "total", "total");
  }

  $cts->add($tbl);


  $products = $site->comptoir->getAvailableProducts($client);

  if ( count($products)>0 )
  {
    $cts->puts("<div class=\"clearboth\"></div>\n");

    $site->add_js("comptoir/comptoir.js");

    $currentProductIndex = 0;
    $currentId_type = 0;
    $tabClass="typeProdTab current";

    $cts->puts("<ul id=\"productsTabs\">");

    while ( $currentProductIndex<count($products) )
    {
      if ( $currentId_type != $products[$currentProductIndex]->id_type )
      {
        $currentId_type = $products[$currentProductIndex]->id_type;
        $typeProd = new typeproduit($site->db,$site->dbrw);
        $typeProd->load_by_id($currentId_type);

        $cts->puts("<li>");
        $cts->puts("<a href=\"#\" id=\"typeProd".$typeProd->id."\" class=\"".$tabClass."\" onclick=\"return changeActiveTab(this.id);\" title=\"".$typeProd->nom."\">".$typeProd->nom."</a>");
        $cts->puts("</li>");

        if ( $currentProductIndex==0 )
        {
          $tabClass="typeProdTab";
        }
      }

      $currentProductIndex++;
    }

    $cts->puts("</ul>");

    $cts->puts("<div class=\"clearboth\"></div>\n");

    $currentId_type = $products[0]->id_type;
    $cts->puts("<div id=\"typeProd".$currentId_type."Contents\"class=\"products\">\n");

    foreach ($products as $product)
    {
      if ( $currentId_type != $product->id_type )
      {
        $currentId_type = $product->id_type;
        $cts->puts("</div>\n");
        $cts->puts("<div id=\"typeProd".$currentId_type."Contents\"class=\"products hide\">\n");
      }
      $cts->add(new productinfo($product, $site->comptoir->prix_barman, false));
      $i++;
    }
    $cts->puts("</div>\n");
  }

  $cts->puts("<div class=\"clearboth\"></div>\n");
}
else
{
  if ( $MajorError )
    $cts->add(new contents("Erreur","<p class=\"majorerror\">".$MajorError."</p>"),true);

  if ( $rapport_contents )
    $cts->add($rapport_contents,true);
  elseif ( $rapport )
  {
    $recu = new itemlist("Recu","recu");
     $recu->add("Client : ".$client->nom." ".$client->prenom.", Nouveau solde ".number_format($client->montant_compte/100, 2)." Euros");

    $cts->add($recu,true);

    $commande = new itemlist ("Dernière commande :", "derniere_commande");

    $ancien_panier = array();
    foreach ($vendus as $vp)
    {
      $ancien_panier[$vp->produit->id][0]++;
      $ancien_panier[$vp->produit->id][1] = $vp;
    }

    foreach ( $ancien_panier as $info )
    {
      list($nb,$vp) = $info;
      $commande->add($nb.' x '.$vp->produit->nom);
    }

    $cts->add($commande, true);
  }
  elseif ( $rapportrecharge )
  {
    $recu = new itemlist("Recu","recu");
    $recu->add("Client : ".$client->nom." ".$client->prenom.", Nouveau solde ".number_format($client->montant_compte/100, 2)." Euros");

    $cts->add($recu,true);
  }

  $frm = new form ("logclient","?id_comptoir=".$site->comptoir->id,true,"POST","Vente");
  $frm->add_hidden("action","logclient");
  if ( $Erreur )
    $frm->error($Erreur);
  $frm->add_text_field("code_bar_carte","Carte AE");
  $frm->add_user_fieldv2("id_utilisateur_achat","ou par Recherche");
  $frm->add_checkbox("prix_barman","Prix barman (si possible)",true);
  $frm->add_submit("valid","valider");
  $frm->set_focus("code_bar_carte");
  $cts->add($frm,true);

  if ( $site->comptoir->rechargement )
  {
    $frm = new form ("confirmrech","?id_comptoir=".$site->comptoir->id,true,"POST","Rechargement");
    $frm->add_hidden("page","confirmrech");
    if ( $RechargementErreur )
      $frm->error($RechargementErreur);
    $frm->add_price_field("montant","Montant");

    foreach ($TypesPaiements as $key => $item )
    {
      $sfrm = new form("id_typepaie",null,null,null,"Paiement par $item");
      if ( $key == PAIE_CHEQUE )
        $sfrm->add_select_field("id_banque","Banque",$Banques);
      if ( $key == PAIE_ESPECS )
        $check = TRUE;
      else
        $check = FALSE ;
      $frm->add($sfrm,false,true, $check ,$key ,false,true);
    }
    /*$frm->add_radiobox_field("id_typepaie","Mode de paiement",$TypesPaiements,PAIE_ESPECS,-1);
    $frm->add_select_field("id_banque","Banque",$Banques);*/
    $frm->add_text_field("code_bar_carte","Carte AE");
    if (isset ($_REQUEST['utilisateur_recharge'])) {
        $utilisateur_recharge = new utilisateur($site->db);
        $utilisateur_recharge->load_by_id (intval ($_REQUEST['utilisateur_recharge']));
        $frm->add_entity_smartselect("id_utilisateur_rech","ou par Recherche", $utilisateur_recharge);
    } else {
        $frm->add_user_fieldv2("id_utilisateur_rech","ou par Recherche");
    }

    $frm->add_submit("valid","valider");
    $cts->add($frm,true);
  }
}

$site->add_contents($cts);

$site->end_page();


?>
