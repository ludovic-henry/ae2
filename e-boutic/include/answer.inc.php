<?
/** @file
 *
 * @brief Classe answer, g�rant l'analyse de la r�ponse des serveurs
 * sogenactif concernant une transaction en ligne.
 */
/* Copyright 2005 - 2006
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 */
$location = "/usr/share/php5/sogenactif/";


require_once ("e-boutic.inc.php");

/* besoins systeme comptoir */
require_once ($topdir . "comptoir/include/comptoir.inc.php");
require_once ($topdir . "comptoir/include/comptoirs.inc.php");
require_once ($topdir . "comptoir/include/cptasso.inc.php");
require_once ($topdir . "comptoir/include/defines.inc.php");
require_once ($topdir . "comptoir/include/facture.inc.php");
require_once ($topdir . "comptoir/include/produit.inc.php");
require_once ($topdir . "comptoir/include/typeproduit.inc.php");
require_once ($topdir . "comptoir/include/venteproduit.inc.php");

/**
 * @defgroup comptoirs_sg Sogenactif
 * @ingroup comptoirs
 */

/**
 * Classe de traitement d'une réponse envoyée par les serveurs Sogenactif
 * @ingroup comptoirs_sg
 * @author Pierre Mauduit
 */
class answer
{
  /* Variables de retour, renvoy�es par le programme response
    (impos�es par Sogenactif) */
  var $code;
  var $error;
  var $merchant_id;
  var $merchant_country;
  var $amount;
  var $transaction_id;
  var $payment_means;
  var $transmission_date;
  var $payment_time;
  var $payment_date;
  var $response_code;
  var $payment_certificate;
  var $authorisation_id;
  var $currency_code;
  var $card_number;
  var $cvv_flag;
  var $cvv_response_code;
  var $bank_response_code;
  var $complementary_code;
  var $complementary_info;
  var $return_context;
  var $caddie;
  var $receipt_complement;
  var $merchant_language;
  var $language;
  var $customer_id;
  var $order_id;
  var $customer_email;
  var $customer_ip_address;
  var $capture_day;
  var $capture_mode;
  var $data;
  /** un acc�s � la base de donn�es (ro) */
  var $db;
  /** un acc�s � la base de donn�es (rw) */
  var $dbrw;


  /** constructeur */
  function answer ($db, $dbrw)
  {
    global $location;

    /* on r�cupere les donn�es post�es */
    $datas = $_POST['DATA'];
    /* le chemin absolu vers le pathfile */
    if (STO_PRODUCTION == false)
      $pathfile = $location . "etc/etc_taiste/pathfile";
    else
      $pathfile = $location . "etc/etc_prod/pathfile";
    /* arguments de passage au binaire de d�cryptage */
    $args  = (" pathfile=$pathfile");
    $args .= " message=$datas";

    /* le chemin vers le binaire */
    $path_bin = $location  . "bin/response";

    /* on appelle le binaire */
    //$ret = exec($path_bin . $args);
    $conn = ssh2_connect ('192.168.2.220', 22, array('hostkey' => 'ssh-rsa'));
    if (ssh2_auth_pubkey_file ($conn, 'ae-web', '/var/www/id_rsa_ae-web.pub', '/var/www/id_rsa_ae-web')) {
      $stream = ssh2_exec ($conn, $path_bin . $args);
      stream_set_blocking ($stream, true);
      $ret = stream_get_contents ($stream);
      fclose ($stream);
    } else {
      $ret = "Erreur. Paiement par CB temporairement indisponible.";
    }

    /* on explose le retour */
    $ret = explode("!", $ret);

    /** la plupart de ces affectations sont inutiles
        toutefois j'ai pr�f�r� rester proche de l'API et les garder */
    $this->code                = $ret[1];
    $this->error               = $ret[2];
    $this->merchant_id         = $ret[3];
    $this->merchant_country    = $ret[4];
    $this->amount              = $ret[5];
    $this->transaction_id      = $ret[6];
    $this->payment_means       = $ret[7];
    $this->transmission_date   = $ret[8];
    $this->payment_time        = $ret[9];
    $this->payment_date        = $ret[10];
    $this->response_code       = $ret[11];
    $this->payment_certificate = $ret[12];
    $this->authorisation_id    = $ret[13];
    $this->currency_code       = $ret[14];
    $this->card_number         = $ret[15];
    $this->cvv_flag            = $ret[16];
    $this->cvv_response_code   = $ret[17];
    $this->bank_response_code  = $ret[18];
    $this->complementary_code  = $ret[19];
    $this->complementary_info  = $ret[20];
    $this->return_context      = $ret[21];
    $this->caddie              = $ret[22];
    $this->receipt_complement  = $ret[23];
    $this->merchant_language   = $ret[24];
    $this->language            = $ret[25];
    $this->customer_id         = $ret[26];
    $this->order_id            = $ret[27];
    $this->customer_email      = $ret[28];
    $this->customer_ip_address = $ret[29];
    $this->capture_day         = $ret[30];
    $this->capture_mode        = $ret[31];
    $this->data                = $ret[32];
    /* 2 connexions a la base */
    $this->db = $db;
    $this->dbrw = $dbrw;

  }

  /**
   * @brief fonction de v�rification d'ajout
   * pour �viter de sauver 2 fois de suite la meme facture
   *
   * @return true ou false selon le cas
   */
  function already_saved()
  {
    $transacid = $this->transaction_id;

    /* le DATEDIFF dans la requete permet de laisser une journee de marge
     * (cas hyper particulier du client commandant � 23h59)
     *
     * id_comptoir = 3 pour e-boutic;
     */
    $sql =  "SELECT `transacid`
             FROM   `cpt_debitfacture`
             WHERE DATEDIFF(NOW(), `date_facture`) IN ('0', '1')
             AND `id_comptoir` = 3
             AND `transacid` = $transacid";

    $req = new requete ($this->db, $sql);

    return ($req->lines > 0 ? true : false);

  }
  /**
   * @brief Fonction d'enregistrement de la commande
   *
   * @param client un objet de type utilisateur
   *
   */
  function register_order()
  {
    if ((!isset($this->caddie))
        || (!isset($this->transaction_id))
        || (!isset($this->customer_id)))
      return -1;

    /* controle de retour banque */
    if ($this->bank_response_code != "00")
      return false;

    /** Si commande déja ajoutée */
    $ret =$this->already_saved();
    if ($ret == true)
      return true;


    $cart = explode(",", $this->caddie);
    $cart = array_count_values($cart);

    /** on passe a debitfacture */
    $debfact = new debitfacture ($this->db, $this->dbrw);

    /** on a besoin d'un utilisateur */

    /* on charge malgre l'existence d'une instance au sein de la
     * classe site, car il se peut que ce code ne soit pas execute
     * dans le cadre de la generation d'un site (autorequest par
     * exemple)
     */

    $usr = new utilisateur($this->db, $this->dbrw);
    $usr->load_by_id ($this->customer_id);

    /* on a besoin d'une instance de comptoir */
    $cpt = new comptoir ($this->db, $this->dbrw);
    $cpt->load_by_id (CPT_E_BOUTIC);

    /* on cree un panier correspondant aux attentes de debitSG() */
    $i = 0;
    foreach ($cart as $item_id => $qte)
    {
      $cpt_cart[$i][0] = $qte;
      /* un objet "venteproduit" */
      $vp = new venteproduit ($this->db, $this->dbrw);
      $vp->load_by_id ($item_id, CPT_E_BOUTIC);
      $cpt_cart[$i][1] = $vp;

      $i++;
    }
    return $debfact->debitSG ($usr,
                              $usr,
                              $cpt,
                              $cpt_cart,
                              $this->transaction_id);
  }
}
?>
