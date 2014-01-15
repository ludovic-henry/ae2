<?
/* Copyright 2007
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
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
 * @file Envoi de fax via les identifiants freebox de l'AE
 *
 */

/**
 * Envoie et archivage de fax
 * @author Pierre Mauduit
 */
class fax extends stdentity
{
  /* ceci sont des identifiants temporaires fournis par Free */
  var $idfree;
  var $idtfree;

  /* addresse du captcha */
  var $imgcaptcha = "";
  var $captchavalue = "";
  var $captchaaudio = "";

  /* PDF */
  /* le nom d'origine du fichier */
  var $filename;
  /* l'addresse du fichier pdf */
  var $pdffile;

  /* masquer l'expéditeur ? */
  var $mask = "N";

  /* le destinataire */
  var $numdest;

  /* les identifiants freebox de l'AE */
  var $login = "__LOGIN_FREEBOX__";
  var $pass = "__MDP_FREEBOX__";

  /* identifiant de l'utilisateur faisant la demande */
  var $id_utilisateur;
  /* eventuellement l'association / le club concerné */
  var $id_asso;

  /* date de demande d'envoi (création de l'instance)
   * Se renseigner sur la durée de vie des identifiants temporaires filés par free */
  var $date_fax;


  /* Lecture d'une instance de fax */
  function load_by_id($id_fax)
  {
    $query = "SELECT * FROM `fax_fbx` WHERE id_fax = '".intval($id_fax)."' LIMIT 1";

    $req = new requete($this->db, $query);

    if ($req->lines <= 0)
      return false;

    global $topdir;

    $rs = $req->get_row();

    $this->id             = $rs['id_fax'];
    $this->idfree         = $rs['idfree_fax'];
    $this->idtfree        = $rs['idtfree_fax'];
    $this->imgcaptcha     = "http://adsl.free.fr/admin/tel/captcha.pl?id_client=".$this->idfree."&idt=".$this->idtfree;
    $this->captchaaudio   = "http://adsl.free.fr/admin/tel/captcha_audio.pl?id_client=".$this->idfree."&idt=".$this->idtfree;
    $this->filename       = $rs['filename_fax'];
    $this->pdffile        = $topdir . "var/fax/" . $this->id . ".pdf";
    $this->numdest        = $rs['numdest_fax'];
    $this->id_utilisateur = $rs['id_utilisateur'];
    $this->id_asso        = $rs['id_asso'];
    $this->date_fax       = $rs['date_fax'];

    return true;

  }

  /**
   * @todo à implémenter
   */
  function _load($row)
  {

  }

  /* fonction permettant d'envoyer sur la sortie le PDF.
   */
  function output_pdf()
  {
    if (!$this->pdffile)
      return;

    header("Content-Type: application/pdf");
    @readfile($this->pdffile);
  }

  /* Fonction de création d'instance de fax. Ne faxe pas
   * à proprement parler, mais prépare un faxage.
   *
   * @param id_utilisateur : identifiant de l'utilisateur voulant faxer
   * @param numdest : numéro du destinataire
   * @param file : fichier (provenant d'un upload, variable $_FILES)
   * @param id_asso : le club / l'association concernée
   *
   */
  function create_instance($id_utilisateur,
         $numdest,
         $file,
         $id_asso = null)
  {
    if (!$id_utilisateur)
    {
      return false;
    }
    if (!$numdest)
    {
      return false;
    }

    $this->id_asso = $id_asso;

    if ($id_asso == null)
      $id_asso = "NULL";

    $this->id_utilisateur = $id_utilisateur;
    $this->numdest        = $numdest;

    /* connect to the free.fr website */
    $query = "login=".$this->login."&pass=".$this->pass;

    $string = $this->_sendrequest("subscribe.free.fr",
                                  "/login/login.pl",
                                  "application/x-www-form-urlencoded",
                                  $query);

    preg_match_all("/id=([0-9]*)&idt=([a-z0-9]*)/", $string, $found);

    /* now we got ours id / idt from free.fr
     * In theory, no need to sanitize, but in doubt ... */
    $this->idfree  = mysql_real_escape_string($found[1][0]);
    $this->idtfree = mysql_real_escape_string($found[2][0]);

    /* we probably have to "hit" the page, in order
     * to validate kind of "session opening"
     */
    preg_match("/Location: ([^\r\n]*)\r\n/", $string, $tab);
    $opensess = $tab[1];
    $adminitf = @file_get_contents($opensess);

    /* and what about send_fax.pl ? */
    $sendfaxaddr = "http://adsl.free.fr/admin/tel/sendfax.pl?id=".$this->idfree."&idt=".$this->idtfree;
    $newpage = file_get_contents($sendfaxaddr);
    preg_match("/src=\"(captcha.pl?[^\"]*)\"/", $newpage, $found);
    /* so there is our captcha */
//    $this->imgcaptcha = "http://adsl.free.fr/admin/tel/" . $found[1];
    $this->imgcaptcha = "http://adsl.free.fr/admin/tel/captcha.pl?id_client=".$this->idfree."&idt=".$this->idtfree;
    $this->captchaaudio = "http://adsl.free.fr/admin/tel/captcha_audio.pl?id_client=".$this->idfree."&idt=".$this->idtfree;

    if ( !is_uploaded_file($file['tmp_name']))
    {
      return false;
    }

    $this->filename = $file['name'];


    /* and now, plug it into the MySQL base :-) */
    $req = new insert($this->dbrw,
                      "fax_fbx",
                      array ('idfree_fax'     => $this->idfree,
                             'idtfree_fax'    => $this->idtfree,
                             'numdest_fax'    => $this->numdest,
                             'filename_fax'   => $this->filename,
                             'id_utilisateur' => $this->id_utilisateur,
                             'id_asso'        => $this->id_asso,
                             'date_fax'       => date('Y-m-d H:i:s')));
    if ($req)
    {
      $this->id = $req->get_id();
      global $topdir;

      @move_uploaded_file($file['tmp_name'], $topdir ."var/fax/". $this->id . ".pdf");
      $this->pdffile = $topdir . "var/fax/". $this->id . ".pdf";

      /* convert pdf in order to comply with free.fr architecture */
      @exec(escapeshellcmd("mogrify -page A4 " . $this->pdffile));

      return true;
    }
    return false;
  }

  /* positionne la valeur du captcha
   * (Evidemment obligatoire avant de tenter un sendfax ...)
   */

  function set_captcha($cp)
  {
    $this->captchavalue = $cp;
  }

  /* envoie une requete à un serveur Web
   *
   * @param host : le serveur Web à contacter
   * @param page : la page Web qui attend le contenu
   * @param cttype : la ligne spécifiant le content-type
   * @param query : le corps de la requête
   *
   * @return ce que le serveur Web répond (Requête + page HTML
   * éventuelle), false si le serveur n'a pu etre contacté.
   */

  function _sendrequest($host,
      $page,
      $cttype,
      $query,
      $stop = false)
  {

    if ((!$host) || (!$page) || (!$cttype) || (!$query))
    {
      return false;
    }

    $tosend = "POST ".$page." HTTP/1.1\r\n".
              "Host: $host\r\n".
              "Content-type: ".$cttype. "\r\n".
              "User-Agent: Mozilla 4.0\r\n".
              "Content-length: ".strlen($query).
              "\r\nConnection: close\r\n\r\n$query";

    $h = fsockopen($host,80);

    if ($stop == true)
    {
      header("Content-type: text/plain");
      echo $tosend;
      die();
    }
    if (!$h)
      return false;

    fwrite($h, $tosend);

    for($a = 0,$r = '';!$a;)
    {
      $b = fread($h,8192);
      $r .= $b;
      $a = (($b == '')?1:0);
    }

    fclose($h);
    return $r;
  }


  function send_fax($secret = false)
  {

    if ((!$this->numdest) || (!$this->captchavalue) || (!$this->idfree)
        || (!$this->idtfree) || (!file_exists($this->pdffile)))
    {
      return false;
    }

    if ($secret == true)
      $this->mask = 'Y';
    else
      $this->mask = 'N';

    /* préparation de la requete HTTP */
    srand((double) microtime () * 1000000);
    $boundary = substr(md5(rand(0,32000)),0,6);
    $cttype = "multipart/form-data, boundary=".$boundary;

    /* masque */
    $query = "--".$boundary."\r\ncontent-disposition: form-data; name=\"masque\"\r\n";
    $query .= "\r\n";
    $query .= $this->mask;
    $query .= "\r\n";

    /* destination */
    $query .= "--".$boundary."\r\ncontent-disposition: form-data; name=\"dest\"\r\n";
    $query .= "\r\n";
    $query .= $this->numdest;
    $query .= "\r\n";

    /* captcha ? */
    $query .= "--".$boundary."\r\ncontent-disposition: form-data; name=\"cap\"\r\n";
    $query .= "\r\n";
    $query .= $this->captchavalue;
    $query .= "\r\n";

    /* id ? */
    $query .= "--".$boundary."\r\ncontent-disposition: form-data; name=\"id\"\r\n";
    $query .= "\r\n";
    $query .= $this->idfree;
    $query .= "\r\n";

    /* idt */
    $query .= "--".$boundary."\r\ncontent-disposition: form-data; name=\"idt\"\r\n";
    $query .= "\r\n";
    $query .= $this->idtfree;
    $query .= "\r\n";

    /* uploaded file */
    $query .= "--".$boundary."\r\ncontent-disposition: form-data; name=\"uploaded_file\"; filename=\"aefax.pdf\"\r\n";
    $query .= "Content-Type: application/pdf\r\n";
    $query .= "\r\n";
    $query .= file_get_contents($this->pdffile);
    $query .= "\r\n--".$boundary."--\r\n";


    /* on envoie la requete */
    $txtres = $this->_sendrequest("adsl.free.fr",
                                  "/admin/tel/send_fax_valid.pl",
                                  $cttype,
                                  $query);


    /* resultat */
    if (strpos($txtres, "Votre fax est en cours d'envoi") === false)
      return false;
    else
      return true;
  }

}

?>
