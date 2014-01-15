<?php
/** @file
 *
 * @brief Classe request, chargée de créer une requete de paiement
 * pour le systeme sogenactif.
 */
/* Copyright 2005
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 */

require_once ("e-boutic.inc.php");


$location = "/usr/share/php5/sogenactif/";

/**
 * Permet d'élaborer une requête vers les serveurs Sogenactif
 * @ingroup comptoirs_sg
 * @author Pierre Mauduit
 */
class request
{
  /**
   * les arguments à passer au binaire 'request'
   */
  var $request_params;
  /**
   * le retour de l'éxecutable
   */
  var $request_ret;
  /**
   * le trans_id
   */
  var $transid;
  /**
   * le code de retour
   */
  var $code;
  /**
   * le message d'erreur
   */
  var $error;
  /**
   * le "message"
   *
   * note pedrov : il semblerait que depuis l'API V6, le
   * champ de génération du formulaire ait changé (3 => 8 maintenant);
   * en réalité tout dépend du nombre d'arguments renvoyés. Toujours
   * est-il qu'il faut dumper au moins une fois le contenu de la
   * variable et de voir ou sont mis les champs, parce que la doc
   * fournie par la société générale n'est pas très à jour à ce niveau
   * la.
   */
  var $message;
  /**
   * le formulaire html
   */
  var $form_html;
  /**
   * un objet de type mysqlae
   */
  var $db;

  /**
   * @brief Le constructeur de la classe
   *
   * @param un objet de connexion a la base de donnees RW
   * @param id_client l'id du client
   * @param total le montant de la commande en Euros
   * @param caddie le contenu du panier (sous la
   * forme (0=> id_articles,...))  on y fait apparaitre les articles
   * autant de fois qu'ils sont commandés
   */
  function request($dbrw, $id_client, $total, $caddie)
  {
    global $location;

    /* on a besoin d'un RW à la base */
    $this->db = new mysqlae("rw");

    /* génération d'un transaction id */
    $this->get_transid();
    /* on génère la ligne d'arguments d'appel du binaire */

    // PRODUCTION : le parametre ci-dessous identifie l'AE en tant que
    //commercant aupres de la sogé.
    if (STO_PRODUCTION == true)
      $parm ="merchant_id=__ID_SOGE__";

    //Serveurs de tests / marchand bidon
    // toutefois, le numero ci-apres a son importance, et il est bien
    // entendu qu'il n'est pas choisi au hasard (cf la doc
    // sogenactif)
    else
      $parm = "merchant_id=014213245611111";

    $parm .= " merchant_country=fr";
    $parm .= (" amount=".$total);
    $parm .= " currency_code=978";
    $parm .= (" customer_id=".$id_client);
    $parm .= (" caddie=". implode(",", $caddie));
    /* version de production */
    if (STO_PRODUCTION == true)
      $parm .= (" pathfile=" . $location  . "etc/etc_prod/pathfile");
    /* version de test */
    else
      $parm .= (" pathfile=" . $location  . "etc/etc_taiste/pathfile");

    $parm .= (" transaction_id=".$this->transid);

    /* on appelle le binaire */
    //$ret = exec($location . "bin/request " . $parm);
    $conn = ssh2_connect ('192.168.2.220', 22, array('hostkey' => 'ssh-rsa'));
    if (ssh2_auth_pubkey_file ($conn, 'ae-web', '/var/www/id_rsa_ae-web.pub', '/var/www/id_rsa_ae-web')) {
      $stream = ssh2_exec ($conn, $location . "bin/request " . $parm);
      stream_set_blocking ($stream, true);
      $ret = stream_get_contents ($stream);
      fclose ($stream);
    } else {
      $ret = "Erreur. Paiement par CB temporairement indisponible.";
    }

    /* on découpe le résultat à la manière sogenactif */
    $ret = explode("!", $ret);

    /* sauvegarde du tableau obtenu */
    $this->request_ret = $ret;

    /* sauvegarde des variables */
    $this->code = $ret[1];
    $this->error = $ret[2];
    $this->message = $ret[3];
    /* A surveiller, il se peut que l'indice du tableau change */
    // la doc de sogenactif n'est pas tres precise a ce sujet
    // si besoin, afficher le contenu de la classe sur la page
    $this->form_html = $ret[count($ret) - 2];
  }

  /**
   * Génération à la volée d'un transaction-id
   * (nombre de 6 chiffres)
   *
   * D'apres la documentation de sogenactif, ce numero DOIT
   * etre unique sur une journee (sinon erreur).
   *
   */
  function get_transid()
  {
    /* on va générer un transid en fonction de l'heure / min / sec
       et du sess_id                                               */
    $sess_id = $_SESSION['id_etudiant'];
    /* on utilise le temps  */
    $strtime = (date("H") * date("i") * date("s"));
    $sess_id .= $strtime;
    /* on utilise la microseconde */
    $microsec = explode(" ",microtime());
    $microsec[0] = substr($microsec[0],2, 6);
    $sess_id .= $microsec[0];

    /* on calcule le hash md5 */
    $sess_id = md5($sess_id);
    $sess_id = substr($sess_id,0,6);
    $sess_id = hexdec($sess_id);
    $sess_id = substr($sess_id,0,6);

    $sess_id = sprintf("%06d", $sess_id);

    /* ce nombre change chaque micosecondes */
    $this->transid = $sess_id;
    return $sess_id;
  }
}
?>
