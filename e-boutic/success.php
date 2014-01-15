<?
/** @file
 *
 * @brief Succès du paiement. en principe pas grand chose à faire,
 *  puisque l' "auto_response" a déja du faire le travail
 */

/* Copyright 2006
 *
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 */

$topdir = "../";

require_once($topdir . "include/site.inc.php");
require_once($topdir . "comptoir/include/produit.inc.php");
require_once($topdir . "comptoir/include/venteproduit.inc.php");
require_once("include/e-boutic.inc.php");
require_once("include/answer.inc.php");

$site = new eboutic ();

if ($site->user->id < 0)
  error_403 ();

if (isset($_POST['DATA']))
{

  $success = new answer ($site->db, $site->dbrw);

  /* si erreur ne venant pas de nous */
  if ($success->code != 0)
  {
    $site->start_page ("e-boutic", "Blèh");
    $site->add_contents (new error("e-boutic",
             "<p>Une erreur est survenue lors ".
             "du paiement.</p>"));
  }
  else
  {
    /* sauvegarde du panier */
    /* note : cette fonction est en principe appelée AVANT
     * l'utilisateur dans le script auto_success.php. Seulement on
     * ne peut pas assurer lequel sera invoqué avant (question à
     * l'équipe technique de sogenactif).
     *
     * Une vérification est évidemment faite histoire de ne pas
     * inscrire deux fois les informations.
     *
     */

    $ret = $success->register_order ();
    if ($ret == true)
    {
      $site->empty_cart ();
      $site->start_page ("e-boutic","Succès");
      /* si boutique de test */
      if (STO_PRODUCTION == false)
        $site->add_contents (new contents("ATTENTION",
                "<p class=\"error\">Boutique en ".
                "ligne de test.<br/><br/> ".
                "Les resultats de vos achats sont ".
                "fictifs !</p>"));

      $maincts = new contents ("e-boutic");
      $maincts->add_paragraph ("Votre paiement a été ".
                               "accepté avec succès. L'AE vous ".
                               "remercie d'avoir utilisé son ".
                               "service de vente en ligne !");
      $maincts->add_paragraph ('Si votre commande comporte des e-ticket à imprimer,'.
                               'vous pouvez les récupérer sur la page de <a href="/user/compteae.php">votre compte</a>.');
      $site->add_contents ($maincts);

      $cart = array_count_values(explode(",", $success->caddie));
      $prod = new produit ($site->db,$site->dbrw);
      foreach ($cart as $id => $count)
      {
        $prod->load_by_id($id);
        if ($cl=$prod->get_prodclass($site->user))
          if ( $cts=$cl->get_once_sold_cts($site->user))
            $site->add_contents($cts);
      }
    }
    else
    {
      $site->start_page ("e-boutic", "Blèh");
      $site->add_contents (new error("e-boutic",
             "Une erreur est survenue lors ".
             "de l'enregistrement des " .
             "informations sur la commande dans ".
             "la base."));
    }
  }
}

else
{
  $site->start_page ("e-boutic", "Blèh");
  $site->add_contents(new error("e-boutic",
        "Une erreur est survenue, la commande ".
        "n'a pas été enregistrée. Veuillez ".
        "réessayer."));
}


$site->end_page ();
?>
