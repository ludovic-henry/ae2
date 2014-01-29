<?php
/** @file
 *
 * @brief Fonctions générales du site.
 *
 */
/* Copyright 2004,2005,2006,2007,2008
 * - Alexandre Belloni <alexandre POINT belloni CHEZ utbm POINT fr>
 * - Thomas Petazzoni <thomas POINT petazzoni CHEZ enix POINT org>
 * - Maxime Petazzoni <maxime POINT petazzoni CHEZ bulix POINT org>
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Julien Etelain <julien CHEZ pmad POINT net>
 * - Benjamin Collet <bcollet CHEZ oxynux POINT org>
 * - Sarah Amsellem <sarah POINT amsellem CHEZ gmail POINT com>
 * - Simon Lopez < simon dot lopez at ayolo dot org >
 *
 * Ce fichier fait partie du site de l'Association des 0tudiants de
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

if ( !isset($GLOBALS['nosession']) )
  session_start();

if ( isset ($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on" )
  $GLOBALS["is_using_ssl"] = true;

require_once($topdir . "include/interface.inc.php");
require_once($topdir . "include/globals.inc.php");
require_once($topdir . "include/cts/calendar.inc.php");
require_once($topdir . "include/entities/sondage.inc.php");
require_once($topdir . "include/entities/campagne.inc.php");
require_once($topdir . "include/entities/cotisation.inc.php");
require_once($topdir . "jobetu/include/jobuser_etu.inc.php");

/** La classe principale du site */
class site extends interfaceweb
{

  /** Constructeur de la classe */
  function site ($siteae=true)
  {
    global $timing;
    $timing["includes"] = microtime(true)+$timing["all"];
    $timing["site::site"] -= microtime(true);

    $this->siteae=$siteae;

    $dbro = new mysqlae ();

    if (!$dbro->dbh)
      $this->fatal("no db");

    $dbrw = new mysqlae ("rw");
    if (!$dbrw->dbh)
      $this->fatal("no dbrw");

    $this->interfaceweb($dbro, $dbrw);

    if($_COOKIE['AE2_SESS_ID'])
      $this->load_session($_COOKIE['AE2_SESS_ID']);

    if ( $this->get_param("closed",false) && !$this->user->is_in_group("root") )
      $this->fatal("closed");

    $timing["site::site"] += microtime(true);

    /*
     * LEs css du site ae restent sur le site ae
     */
/*    if ( $siteae )
    {
      $this->add_css("themes/congres08/css/site.css");
    }*/

  }

  private function unset_session (  )
  {
    if ( isset($_COOKIE['AE2_SESS_ID']) )
    {
      $domain = ($_SERVER['HTTP_HOST'] != 'localhost' && $_SERVER['HTTP_HOST'] != '127.0.0.1') ? $_SERVER['HTTP_HOST'] : false;

      setcookie ("AE2_SESS_ID", "", time() - 3600, "/", $domain);
      unset($_COOKIE['AE2_SESS_ID']);
    }

    if ( !isset($_SESSION["visite"]) )
      unset($_SESSION['visite']);

    if ( isset($_SESSION['session_redirect']) )
      unset($_SESSION['session_redirect']);
  }


  /**
   * Charge une session en fonction de son identidiant.
   * @param $sid Identifiant de la session
   */
  function load_session ( $sid )
  {

    $req = new requete($this->db,
      "SELECT `utilisateurs`.*, `expire_sess`, `derniere_visite`  ".
      "FROM `site_sessions` ".
      "INNER JOIN `utilisateurs` USING(`id_utilisateur`) ".
      "WHERE `id_session` = '".mysql_escape_string($sid)."'");

    if ($req->lines < 1 )
    {
      $this->unset_session();
      return;
    }

    $row = $req->get_row();

    if ( $row["hash_utl"] != "valid")
    {
      new delete($this->dbrw,"site_sessions",array("id_session" => $sid));
      $this->unset_session();
      return;
    }

    $expire = $row['expire_sess'];

    // On n'est pas à une minute près
    $d = date("Y-m-d H:i:")."00";

    if ( !is_null($expire) )
    {
      $expire = strtotime($expire)-time();

      if ( $expire < 0 ) // Session expirée, fait le ménage
      {
        new delete($this->dbrw,"site_sessions",array("id_session" => $sid));
        $this->unset_session();
        return;
      }

      if ( $d != $row['derniere_visite'] )
        new update($this->dbrw, "site_sessions",
            array(
              "derniere_visite" => $d,
              "expire_sess" => date("Y-m-d H:i:s",time()+(16*60))),
              array("id_session" => $sid));
    }
    else if ( $d != $row['derniere_visite'] )
    {
      new update($this->dbrw, "site_sessions",
          array("derniere_visite" => $d),
          array("id_session" => $sid));
    }

    $this->user->_load($row);

    if ( !isset($_SESSION["visite"]) )
    {
      $this->user->visite();
      $_SESSION["visite"]=time();
    }

    if ( !isset($_SESSION["usersession"]) ) // restore le usersession
      $_SESSION["usersession"] = $this->user->get_param("usersession",null);

  }

  /**
   * Connecte l'utilisateur chargé dans le champ user ($this->user) pour
   * 15 minutes, ou permanente, en créant une sessions et en envoyant un cookie.
   * @param $forever Precise si la session doit être permanente
   * @return l'identifiant de la session
   */
  function connect_user ($forever=true)
  {
    if ( $forever )
      $expire = null;
    else
      $expire = date("Y-m-d H:i:s",time()+(30*60)); // Session expire dans 30 minutes

    $sid = md5(rand(0,32000) . $_SERVER['REMOTE_ADDR'] . rand(0,32000));

    $req = new insert($this->dbrw, "site_sessions",
            array(
              "id_session"      => $sid,
              "id_utilisateur"    => $this->user->id,
              "date_debut_sess"  => date("Y-m-d H:i:s"),
              "derniere_visite"  => date("Y-m-d H:i:s"),
              "expire_sess" => $expire
              ));

    if (($pos = strpos($_SERVER['HTTP_HOST'], ':')) > 0) {
      $http_host = substr($_SERVER['HTTP_HOST'], 0, $pos);
    } else {
      $http_host = $_SERVER['HTTP_HOST'];
    }

    if ($http_host == 'localhost' || $http_host == '127.0.0.1') {
      setcookie ("AE2_SESS_ID", $sid, time() + 31536000, "/");
    } else {
      setcookie ("AE2_SESS_ID", $sid, time() + 31536000, "/", $http_host);
    }

    die();

    $this->user->visite();

    return $sid;
  }

  /**
   * Crée un identifiant unique pour connecter ultérieurement un utilisateur.
   * Utile pour envoyer un lien par e-mail avec authentification automatique.
   * Le "token" est en fait un identifiant de session, il expire au bout de 60 jours.
   * @param $id_utilisateur Id de l'utilisateur pour qui le "token" doit être généré
   * @return le "token" (identifiant de session)
   * @see load_token
   */
  function create_token_for_user ( $id_utilisateur )
  {
    $sid = "T".$id_utilisateur."$".md5(rand(0,32000) . "TOKEN" . $id_utilisateur . rand(0,32000));

    $req = new insert($this->dbrw, "site_sessions",
            array(
              "id_session"      => $sid,
              "id_utilisateur"    => $id_utilisateur,
              "date_debut_sess"  => date("Y-m-d H:i:s"),
              "derniere_visite"  => date("Y-m-d H:i:s"),
              "expire_sess" => date("Y-m-d H:i:s",time()+(24*60*60*60))
              ));

    return $sid;
  }

  /**
   * Ouvre une session pour l'utilisateur associé à un token donné.
   * La session est ouverte par le biai de connect_user(). Le token n'est plus
   * valable après l'appel à cette fonction.
   * @param $token le "token"
   * @return null en cas d'echec, ou l'identifiant de la session ouverte
   * @see connect_user
   * @see create_token_for_user
   */
  function load_token ( $token )
  {
    $this->user->id=null;
    $this->load_session($token);
    if ( $this->user->is_valid() )
    {
      new delete($this->dbrw, "site_sessions", array("id_session"=>$token) );
      return $this->connect_user();
    }
    return null;
  }

  /**
   * Crée une session pour  l'utilisateur chargé dans le champ user ($this->user)
   * pour 15 minutes, ou permanente.
   * @param $forever Precise si la session doit être permanente
   * @return l'identifiant de la session
   */
  function create_session ($forever=true)
  {
    if ( $forever )
      $expire = null;
    else
      $expire = date("Y-m-d H:i:s",time()+(15*60)); // Session expire dans 15 minutes

    $sid = md5(rand(0,32000) . $_SERVER['REMOTE_ADDR'] . rand(0,32000));

    $req = new insert($this->dbrw, "site_sessions",
                      array("id_session"      => $sid,
                            "id_utilisateur"  => $this->user->id,
                            "date_debut_sess" => date("Y-m-d H:i:s"),
                            "derniere_visite" => date("Y-m-d H:i:s"),
                            "expire_sess"     => $expire
                           ));

    return $sid;
  }

  function get_connection_contents()
  {
  }

  /**
   * Demarre la page à rendre en spécifiant quelques informations clefs.
   * Aucune donnée ne sera envoyé au client avant l'appel de end_page.
   * Gènère la liste des boites en fonction de la section.
   * @param $section Nom de la section
   * @param $title Titre de la page
   * @param $compact Cache le logo et la boite informations (utile pour augmenter la taille de contenu visisble sans scroll)
   */
  function start_page ( $section, $title,$compact=false )
  {
    global $topdir,$timing;

    require_once($topdir."include/cts/cached.inc.php");

    if ( isset($_REQUEST["fetch"]) )
      return;

    if ($section == "e-boutic")
      $section = "services";

    $timing["site::start_page"] -= microtime(true);
    parent::start_page($section,$title,$compact);

if(!defined("MOBILE")) {
    $this->add_box("calendrier", cachedcontents::autocache ("newscalendar", new calendar($this->db)));

    if ( $section == "accueil" )
    {
      require_once($topdir . "include/cts/box_slide_show.inc.php");
      $slides = new box_slideshow('L\'info en boucle');
      $slides->add_slide($this->get_weekly_photo_contents());
      $slides->add_slide($this->get_planning_contents());
      $slides->add_slide($this->get_planning_permanences_contents());
      if ($this->user->is_valid() && ($this->get_param ("forum_open", false)
            || $this->user->is_in_group ("moderateur_forum")
            || $this->user->is_in_group ("root")))
        $slides->add_slide($this->get_forum_box());
      if(!$slides->is_empty())
         $this->add_box("info_en_boucle",$slides);
      //Nb: alerts est *trés* long à calculer, il ne sera donc que dans accueil
      $this->add_box("alerts",$this->get_alerts());

      $this->add_box("anniv", $this->get_anniv_contents());
      $this->add_box("planning", $this->get_planning_contents());

      if ($this->user->is_valid())
      {
        $this->add_box("sondage",$this->get_sondage());
        $this->set_side_boxes("right",
                              array("alerts",
                                    "calendrier",
                                    "info_en_boucle",
                                    "anniv",
                                    "stream",
                                    "sondage"
                              ),
                              "accueil_c_right");
      }
      else
        $this->set_side_boxes("right",
                              array("calendrier",
                                    "info_en_boucle",
                                    "stream"
                              ),
                              "accueil_nc_right");

    }
    elseif ( $section == "pg" )
    {
      //$this->set_side_boxes("left",array("connexion"),"pg_left");
    }
    elseif ( $section == "matmatronch" )
      require_once($topdir . "include/cts/newsflow.inc.php");
    elseif($section!='e-boutic' && $section!='sas')
    {
      $this->set_side_boxes("left",array());
      $this->set_side_boxes("right",array());
    }
} /* ifnedf MOBILE */

    $timing["site::start_page"] += microtime(true);

  }

  /**
   * Gènère la boite "Attention".
   * @param renvoie un stdcontents, ou null (si vide)
   */
  function get_alerts()
  {
    global $topdir;

    if ( !$this->user->is_valid() ) return null;
    if ( $this->user->type=="srv" ) return null;
    $elements = array();

    if(date("m-d",$this->user->date_naissance) == date("m-d"))
      $elements[] = "<b>Joyeux anniversaire de la part de toute l'ae :)</b><br><small>Si ce n'est pas ton anniversaire nous t'invitons à mettre ton profil à jour : <a href='".$topdir."user.php?page=edit'><b>ici</b></a></small>";

    $carte = new carteae($this->db);
    $carte->load_by_utilisateur($this->user->id);

    $today = date("Y-m-d H:i:s");

    $cpg = new campagne($this->db,$this->dbrw);
    $req = new requete($this->db, "SELECT `id_campagne` FROM `cpg_campagne` WHERE `date_fin_campagne`>='$today' ORDER BY date_debut_campagne DESC");
    while(list($id)=$req->get_row())
      if($cpg->load_by_id($id) && $this->user->is_in_group_id($cpg->group) && !$cpg->a_repondu($this->user->id))
        $elements[] = "<a href=\"".$topdir."campagne.php?id_campagne=".$cpg->id."\"><b>Campagne en cours : ".$cpg->nom."</b>.</a>";

    if ( $carte->is_valid() )
    {
      if ( $carte->etat_vie_carte == CETAT_ATTENTE &&
        !file_exists("/data/matmatronch/" . $this->user->id .".identity.jpg") )
      {
        $elements[] = "<a href=\"".$topdir."user.php?see=photos&page=edit\"><b>Vous devez ajouter une photo</b> pour que votre carte AE soit imprimée.</a>";
      }
      elseif ($carte->etat_vie_carte == CETAT_AU_BUREAU_AE )
      {
        $lieu = "Belfort";
        $this->user->load_all_extra();
        if ( $this->user->departement == "tc" || $this->user->departement == "mc" )
          $lieu = "Sevenans";
        elseif ( $this->user->departement == "edim" )
          $lieu = "Montbéliard";

        $elements[] = "<b>Votre carte AE est prête</b>. Elle vous attend au bureau de l'AE de $lieu.";
      }
    }

    if ($this->user->is_in_group("gestion_syscarteae")) {
      $req = new requete($this->db, "SELECT `nom_cpt`
        FROM (SELECT `nom_cpt`, ROUND(SUM(`montant_rech`)/100,2) as `somme`
                FROM (
                  SELECT DISTINCT `id_comptoir`, `nom_cpt`,  MAX(`date_releve`) `date_releve`, `caisse_videe`
                  FROM (
                     SELECT DISTINCT `id_comptoir`,`nom_cpt`
                     FROM `cpt_comptoir`) comptoir
                  INNER JOIN `cpt_caisse`
                  USING (id_comptoir)
                  WHERE `caisse_videe` = '1'
                  GROUP BY `id_comptoir`) caisse
                INNER JOIN `cpt_rechargements`
                USING (id_comptoir)
                WHERE `date_releve` < `date_rech`
                GROUP BY `id_comptoir`) liste
                WHERE `somme` >= '1500'");


      if ($req->lines > 0) {
        while(list($comptoir,$somme) = $req->get_row()) {
            $elements[] = "<b>La caisse ".$comptoir." déborde !</b>";
        }
      }
    }

    if( $this->user->is_in_group("moderateur_site") )
    {
      $req = new requete($this->db,"SELECT COUNT(*) FROM `nvl_nouvelles`  WHERE `modere_nvl`='0' ");
      list($nbnews) = $req->get_row();

      $req = new requete($this->db,"SELECT COUNT(*) FROM `d_file`  WHERE `modere_file`='0' ");
      list($nbfichiers) = $req->get_row();
      $req = new requete($this->db,"SELECT COUNT(*) FROM `d_folder`  WHERE `modere_folder`='0' ");
      list($nbdossiers) = $req->get_row();
      $nbfichiers+=$nbdossiers;

      $req = new requete($this->db,"SELECT COUNT(*) FROM `planet_flux`  WHERE `modere`='0' ");
      list($nbflux) = $req->get_row();
      $req = new requete($this->db,"SELECT COUNT(*) FROM `planet_tags`  WHERE `modere`='0' ");
      list($nbtags) = $req->get_row();
      $nbflux+=$nbtags;

      $req = new requete($this->db,"SELECT COUNT(*) FROM `aff_affiches`  WHERE `modere_aff`='0' ");
      list($nbaffiches) = $req->get_row();

      $req = new requete($this->db,"SELECT COUNT(*) FROM `pedag_uv_commentaire` WHERE `valid`='0' ");
      list($nbcomsignales) = $req->get_row();

      if ( $nbnews > 0 )
        $elements[] = "<a href=\"".$topdir."ae/moderenews.php\"><b>$nbnews nouvelle(s)</b> à modérer</b></a>";

      if ( $nbfichiers > 0 )
        $elements[] = "<a href=\"".$topdir."ae/moderedrive.php\"><b>$nbfichiers fichier(s) et dossier(s)</b> à modérer</a>";

      if ( $nbflux > 0 )
        $elements[] = "<a href=\"".$topdir."planet/index.php?view=modere\"><b>$nbflux flux</b> à modérer</b></a>";

      if ( $nbaffiches > 0 )
        $elements[] = "<a href=\"".$topdir."ae/modereaffiches.php\"><b>$nbaffiches affiche(s)</b> à modérer</b></a>";

      if ( $nbcomsignales > 0 )
        $elements[] = "<a href=\"".$topdir."ae/moderecomuv.php\"><b>$nbcomsignales commentaire(s)</b> d'UV abusifs signalés</b></a>";
    }

    if ( $this->user->is_in_group("gestion_salles") )
    {
      $req = new requete($this->db,"SELECT COUNT(*) ".
        "FROM sl_reservation " .
        "INNER JOIN sl_salle ON sl_salle.id_salle=sl_reservation.id_salle " .
        "WHERE ((sl_reservation.date_accord_res IS NULL) OR " .
        "(sl_salle.convention_salle=1 AND sl_reservation.convention_salres=0)) " .
        "AND sl_reservation.date_fin_salres >= '$today'");
      list($count) = $req->get_row();

      if ( $count > 0 )
        $elements[] = "<a href=\"".$topdir."ae/modereres.php\"><b>$count reservation(s) de salles</b> à modérer</a>";

      $req = new requete($this->db,"SELECT COUNT(*) ".
        "FROM sl_reservation " .
        "INNER JOIN sl_salle ON sl_salle.id_salle=sl_reservation.id_salle " .
        "WHERE sl_reservation.date_accord_res IS NOT NULL " .
        "AND sl_reservation.date_debut_salres >= '$today' " .
        "AND DATEDIFF(sl_reservation.date_debut_salres,'".$today."') <= 10");
      list($count) = $req->get_row();

      if ( $count > 0 )
        $elements[] = "<a href=\"".$topdir."ae/modereres.php\"><b>$count reservation(s) de salles</b> dans les 10 prochaines jours</a>";

    }
    else if( $this->user->is_in_group("bdf-bureau") )
    {
      $req = new requete($this->db,"SELECT COUNT(*) ".
        "FROM sl_reservation " .
        "INNER JOIN sl_salle ON sl_salle.id_salle=sl_reservation.id_salle " .
        "WHERE sl_reservation.date_debut_salres >= '$today' " .
        "AND (sl_salle.id_salle='5' OR sl_salle.id_salle='28')");
      list($count) = $req->get_row();

      if ( $count > 0 )
      $elements[] = "<a href=\"".$topdir."ae/modereres.php\"><b>$count reservation(s) du foyer et de la Kfet</b></a>";
    }

    if ( $this->user->is_in_group("gestion_emprunts") )
    {
      $req = new requete($this->db,"SELECT COUNT(*) " .
        "FROM inv_emprunt WHERE etat_emprunt=0 AND date_fin_emp >= NOW() ");
      list($count) = $req->get_row();

      if ( $count > 0 )
        $elements[] = "<a href=\"".$topdir."ae/modereemp.php\"><b>$count emprunt(s) de matériel</b> à modérer</a>";

      $req = new requete($this->db,"SELECT COUNT(*) " .
        "FROM inv_emprunt WHERE date_debut_emp >= NOW()");
      list($count) = $req->get_row();

      if ( $count > 0 )
        $elements[] = "<a href=\"".$topdir."ae/modereemp.php?view=togo\"><b>$count emprunt(s) de matériel</b> à venir</a>";
    }

    $req = new requete($this->db, "SELECT COUNT(*) " .
      "FROM `cpt_vendu` " .
      "INNER JOIN `cpt_debitfacture` ON `cpt_debitfacture`.`id_facture` =`cpt_vendu`.`id_facture` " .
      "WHERE `id_utilisateur_client`='".$this->user->id."' AND a_retirer_vente='1'");

    list($nb) = $req->get_row();

    if ( $nb > 0 )
      $elements[] = "<a href=\"".$topdir."comptoir/encours.php\">Vous avez des commandes à venir retirer</a>";

    $sql = new requete($this->db,"SELECT `vt_election`.id_election, `vt_election`.nom_elec " .
        "FROM `vt_election` " .
        "LEFT JOIN vt_a_vote ON (`vt_a_vote`.`id_election`=`vt_election`.`id_election` AND vt_a_vote.id_utilisateur='".$this->user->id."')  " .
        "WHERE `date_debut`<= NOW() " .
        "AND `date_fin` >= NOW() " .
        "AND `id_groupe` IN (".$this->user->get_groups_csv().") " .
        "AND vt_a_vote.id_utilisateur IS NULL");

    if ( $sql->lines != 0 )
    {
      while ( list($id,$nom) = $sql->get_row() )
      {
        $elements[] = "<a href=\"".$topdir."elections.php?id_election=$id\"><b>Votez pour les élections : $nom</b></a>";
      }
    }

    /* À partir de septembre 2010, possibilité d'avoir une fiche accessible
     * par les utbm non cotisants.
     * Alerte pour ceux qui n'ont pas modifié leur fiche matmatronch depuis
     */
    if ( $this->user->date_maj < 1283292000 )// 01/09/2010 00:00
        $elements[] = "<a href=\"".$topdir."user.php?page=edit#__publique_2\"><b>La politique d'accès aux fiches Matmatronch a changé : choisissez qui peut accéder à votre fiche.</b></a>";

    if (  is_null($this->user->date_maj) )
        $elements[] = "<b>Vous n'avez pas r&eacute;cemment mis &agrave; jour votre fiche Matmatronch</b> : <a href=\"".$topdir."majprofil.php\">La mettre &agrave; jour</a>";
    elseif ( (time() - $this->user->date_maj) > (6*30*24*60*60) )
        $elements[] = "<b>Vous n'avez pas mis &agrave; jour votre fiche Matmatronch depuis ".round((time() - $this->user->date_maj)/(24*60*60))." jours !</b> : <a href=\"".$topdir."majprofil.php\">La mettre &agrave; jour</a>";


    if( $this->user->is_in_group("sas_admin") && (!$this->get_param("closed.sas",false) && is_dir("/var/www/ae2/data/sas")) )
    {
      $req = new requete($this->db, "SELECT COUNT(*) FROM `sas_cat_photos` WHERE `modere_catph`='0' ");
      list($ncat) = $req->get_row();
      $req = new requete($this->db, "SELECT COUNT(*) FROM `sas_photos` WHERE `modere_ph`='0'");
      list($nphoto) = $req->get_row();
      if ( $ncat > 0 || $nphoto > 0 )
      {
        $msg = "";
        if ( $ncat > 0 )
          $msg .= $ncat." catégorie(s)";
        if ( $ncat > 0 && $nphoto > 0 )
          $msg .= " et ";
        if ($nphoto > 0 )
          $msg .= $nphoto." photo(s)";
        $elements[] = "<a href=\"".$topdir."sas2/modere.php\">$msg &agrave; moderer dans le SAS</a>";
      }
      $req = new requete($this->db, "SELECT COUNT(*) FROM (SELECT id_photo FROM `sas_personnes_photos` WHERE `modere_phutl`='0' UNION SELECT id_photo FROM sas_photos WHERE propose_incomplet <> incomplet) a");
      list($nnoms) = $req->get_row();
      if($nnoms > 0)
      {
        $elements[] = "<a href=\"".$topdir."sas2/moderenoms.php\">$nnoms propositions &agrave; moderer dans le SAS</a>";
      }
      $req = new requete($this->db, "SELECT COUNT(*) FROM sas_photos WHERE incomplet = 1");
      list($nnoms) = $req->get_row();
      if($nnoms > 0)
      {
        $elements[] = "<a href=\"".$topdir."sas2/complete.php\">$nnoms photos incomplètes dans le SAS</a>";
      }
    }

    if( !$this->get_param("closed.sas",false) && is_dir("/var/www/ae2/data/sas"))
    {
      $req = new requete($this->db, "SELECT COUNT(*) FROM `sas_personnes_photos` WHERE `id_utilisateur`='".$this->user->id."' AND `vu_phutl`='0' AND `modere_phutl`='1'");
      list($nphoto) = $req->get_row();
      if ( $nphoto > 0 )
        $elements[] = "<a href=\"".$topdir."user/photos.php?see=new\"><b>".$nphoto." nouvelle(s) photo(s)</b> dans le SAS</a>";
      $req = new requete($this->db, "SELECT COUNT(*) FROM sas_personnes_photos JOIN sas_photos ON sas_photos.id_photo = sas_personnes_photos.id_photo WHERE sas_photos.incomplet = 1 AND sas_personnes_photos.id_utilisateur = ".$this->user->id);
      list($nincomplet) = $req->get_row();
      if ( $nincomplet > 0 )
        $elements[] = "<a href=\"".$topdir."sas2/?modeincomplet\"><b>".$nincomplet." photo(s) incompl&egrave;te(s) o&ugrave; vous &ecirc;tes pr&eacute;sent</a>";
    }

    $req = new requete($this->db, "SELECT COUNT(*) FROM `d_file_lock` WHERE `id_utilisateur`='".$this->user->id."'");
    list($nblocks)=$req->get_row();
    if($nblocks>0)
       $elements[] = "<a href=\"".$topdir."user/d.php\"><b>".$nblocks." fichiers(s) emprunté(s)</b></a>";

    $cotiz = new cotisation($this->db);
    $cotiz->load_lastest_by_user ( $this->user->id );

    if ( ($cotiz->is_valid()) && ($cotiz->date_fin_cotis < time()) && (time()-$cotiz->date_fin_cotis < (30*24*60*60)) )
    {
      $elements[] = "<a href=\"".$topdir."e-boutic/?cat=23\"><b>Votre cotisation &agrave; l'AE est expir&eacute;e !</b> Renouvelez l&agrave; en ligne avec E-boutic.</a>";
    }

    if ( !$this->user->droit_image && !$this->get_param("closed.sas",false) && is_dir("/var/www/ae2/data/sas") )
    {
      $sql = new requete($this->db,
        "SELECT COUNT(*) " .
        "FROM sas_personnes_photos " .
        "INNER JOIN sas_photos ON (sas_photos.id_photo=sas_personnes_photos.id_photo) " .
        "WHERE sas_personnes_photos.id_utilisateur=".$this->user->id." " .
        "AND sas_personnes_photos.accord_phutl='0' " .
        "AND sas_personnes_photos.modere_phutl='1' " .
        "AND (droits_acces_ph & 0x100)");
      list($count) = $sql->get_row();

      if ( $count > 0 )
        $elements[] ="<a href=\"".$topdir."sas2/droitimage.php?page=process\"><b>$count photo(s)</b> nécessitent votre accord</a>";

    }

    /* alertes covoiturage */
    $nbsteps = $this->user->covoiturage_steps_moderation();

    if ($nbsteps == 1)
    {
      $elements[] = "<a href=\"".$topdir."covoiturage/\">$nbsteps étape de covoiturage à modérer<b></a>";
    }
    else if ($nbsteps > 1)
      $elements[] = "<a href=\"".$topdir."covoiturage/\">$nbsteps étapes de covoiturage à modérer<b></a>";

    $assoces = $this->user->get_assos(ROLEASSO_PRESIDENT);

    if (count($assoces) > 0)
    {
      require_once($topdir. "include/entities/asso.inc.php");

      if ( !$this->get_param("backup_server",true) )
      {
        foreach ($assoces as $key => $assoce)
        {
            $asso = new asso($this->db);
            $asso->load_by_id($key);
            $pm = $asso->get_pending_unmod_mail();
            if ($pm == 1)
            {
                $elements[] = "<a href=\"https://ae.utbm.fr/mailman/admindb/".$asso->nom_unix.".membres\"><b>$pm e-mail en attente de modération sur la liste de diffusion de ". $asso->nom_unix . "</b></a>";
            }
            else if ($pm > 1)
            {
                $elements[] = "<a href=\"https://ae.utbm.fr/mailman/admindb/".$asso->nom_unix.".membres\"><b>$pm e-mails en attente de modération sur la liste de diffusion de ". $asso->nom_unix . "</b></a>";
            }
        }
      }
    }

    if ( count($elements) == 0 ) return null;

    $cts = new contents("Attention");

    if ( count($elements) == 1 )
      $cts->add_paragraph("Nous attirons votre attention sur l'&eacute;l&eacute;ment suivant:");
    else
      $cts->add_paragraph("Nous attirons votre attention sur les &eacute;l&eacute;ments suivants:");

    $cts->add(new itemlist(false,false,$elements));
    return $cts;
  }

  /**
   * Gènère la boite sondage.
   * @param renvoie un stdcontents, ou null (si vide)
   */
  function get_sondage()
  {
    global $topdir;

    $sdn = new sondage($this->db,$this->dbrw);

    $sdn->load_lastest();
    if ( !$sdn->is_valid() )
      return NULL;

    if ( $this->user->type=="srv" ) return null;

    require_once($topdir."include/cts/react.inc.php");

    $react = new reactonforum ( $this->db, $this->user, $sdn->question, array("id_sondage"=>$sdn->id), null, false );

    if ( $sdn->a_repondu($this->user->id) )
    {
      $cts = new contents("Sondage");

      $cts->add_paragraph("<b>".$sdn->question."</b>");

      $cts->puts("<p>");

      $res = $sdn->get_results();

      foreach ( $res as $re )
      {
        $cumul+=$re[1];

        if ($sdn->total > 0)
            $pc = $re[1]*100/$sdn->total;
        else
            $pc = 0;

        $cts->puts($re[0]."<br/>");

        $wpx = floor($pc);
        if ( $wpx != 0 )
          $cts->puts("<div class=\"activebar\" style=\"width: ".$wpx."px\"></div>");
        if ( $wpx != 100 )
          $cts->puts("<div class=\"inactivebar\" style=\"width: ".(100-$wpx)."px\"></div>");

        $cts->puts("<div class=\"percentbar\">".round($pc,1)."%</div>");
        $cts->puts("<div class=\"clearboth\"></div>\n");

      }

      if ( $cumul < $sdn->total )
      {
        $pc = ( $sdn->total-$cumul)*100/$sdn->total;
        $cts->puts("<br/>Blanc ou nul : ".round($pc,1)."%");
      }

      $cts->puts("</p>");

      $cts->add_paragraph("(".$sdn->total." réponses)","nbvotes");

      $cts->add($react);

      $cts->add_paragraph("<a href=\"sondage.php\">Archives</a>","nbvotes");

      return $cts;
    }

    $cts = new contents("Sondage");
    $cts->add_paragraph("<b>".$sdn->question."</b>");


    $frm = new form("sondage","sondage.php");
    $frm->add_hidden("id_sondage",$sdn->id);

    $reps = $sdn->get_reponses();
    foreach( $reps as $num => $rep )
      $resp_[$num] = "$rep<br/>";

    $frm->add_radiobox_field ( "numrep", "", $resp_ );

    $frm->add_submit("answord","Repondre");
    $cts->add($frm);

    $cts->add($react);

    $cts->add_paragraph("<a href=\"sondage.php\">Archives</a>","nbvotes");

    return $cts;
  }

  /** Génre la boite qui affiche les anniversaires */
  function get_anniv_contents ()
  {
    global $topdir;

    require_once($topdir."include/cts/cached.inc.php");

    $cache = new cachedcontents("anniv");

    if ( $cache->is_cached () )
      return $cache->get_cache();

    $cts = new contents("Anniversaire");

    $annee = date("Y");

    $req = new requete ($this->db, "SELECT `utilisateurs`.`id_utilisateur`,`utilisateurs`.`nom_utl`,".
    "`utilisateurs`.`prenom_utl`,`utl_etu_utbm`.`surnom_utbm`,`utilisateurs`.`date_naissance_utl` ".
    "FROM `utilisateurs` ".
    "INNER JOIN `utl_etu_utbm` ON `utilisateurs`.`id_utilisateur` = `utl_etu_utbm`.`id_utilisateur` ".
    "WHERE `utilisateurs`.`date_naissance_utl` LIKE '%-" . date("m-d") . "' ".
    "AND (`utilisateurs`.`ancien_etudiant_utl` = '0' OR `utilisateurs`.`ae_utl` = '1') ".
    "AND (`utl_etu_utbm`.`role_utbm` LIKE 'etu' OR `utl_etu_utbm`.`role_utbm` LIKE 'anc') ".
    "AND `utilisateurs`.`hash_utl` LIKE 'valid' ".
    "ORDER BY `utilisateurs`.`date_naissance_utl` DESC");

    if ($req->lines > 0)
    {
      $cts->add_paragraph($this->get_param('box.Anniversaire'));

      $old_age = 0;
      $count   = 0;

      while ($res = $req->get_row())
      {

        $age=$annee-substr($res['date_naissance_utl'],0,4);
        if (!$count || ($old_age != $age))
        {
          if ( $count )
            $cts->puts("</ul>\n");

          $cts->puts("<h2 class=\"epure\">" . $age . " ans</h2>\n");
          $cts->puts("<ul>\n");
          $old_age = $age;
        }

        if (empty($res['surnom_utbm']))
          $nom = $res['prenom_utl'] . " " . $res['nom_utl'];
        else
          $nom = $res['surnom_utbm'];

        $ref = "anniv". $res['id_utilisateur'];

        $count++;
        $cts->puts ("<li><a id=\"$ref\" onmouseover=\"show_tooltip('$ref','$topdir','utilisateur','".$res['id_utilisateur']."');\" onmouseout=\"hide_tooltip('$ref');\" href=\"". $topdir ."user.php?id_utilisateur=". $res['id_utilisateur'] .
         "\">" . $nom . "</a></li>\n");
      }
      $cts->puts("</ul>\n");
    }
    else
    {
      $cts->add_paragraph("L'AE est triste de vous annoncer qu'il n'y a pas d'anniversaire aujourd'hui.\n");
    }

    return $cache->set_contents_timeout($cts, strtotime('tomorrow'));
  }

  /** Fonction qui génére le contents du dernier planning de l'AE */
  function get_planning_contents ()
  {
    global $topdir;
    if ( !file_exists($topdir."var/img/com/planning.jpg"))
      return null;

    $planning_valid = filemtime($topdir."var/img/com/planning.jpg") + (7 * 24 * 60 * 60);
    if ( time() <= $planning_valid )
    {
      $cts = new contents("Planning");
      $cts->puts("<center><a href=\"".$topdir."article.php?name=planning\"><img src=\"".$topdir."var/img/com/planning-small.jpg?".$planning_valid."\" alt=\"Planning\" /></a></center>");
      return $cts;
    }
  }

  /**
   * Gènère la boite contenant la photo de la semaine.
   * @param renvoie un stdcontents
   */
  function get_weekly_photo_contents ()
  {
    global $topdir;
    if ( !file_exists($topdir."var/img/com/weekly_photo.jpg"))
      return null;
    $weekly_photo_valid = filemtime($topdir."var/img/com/weekly_photo.jpg") + (7 * 24 * 60 * 60);
    if ( time() <= $weekly_photo_valid )
    {
      $cts = new contents("Photo de la semaine");
      $cts->puts("<center><a href=\"".$topdir."article.php?name=weekly_photo\"><img src=\"".$topdir."var/img/com/weekly_photo-small.jpg?".$weekly_photo_valid."\" style=\"margin-bottom:0.5em;\" alt=\"Photo de la semaine\" /></a><br/>");
      $cts->puts($this->get_param('box.Weekly_photo'));
      $cts->puts("</center>");
      return $cts;
    }
    return null;
  }

  /**
   * Vérifie la vie des comptoirs :).
   */

  function get_comptoir()
  {
    global $topdir;
    // 1- On ferme les sessions expirés
    $req = new requete ($this->dbrw,
           "UPDATE `cpt_tracking` SET `closed_time`='".date("Y-m-d H:i:s")."'
            WHERE `activity_time` <= '".date("Y-m-d H:i:s",time()-intval(ini_get("session.gc_maxlifetime")))."'
            AND `closed_time` IS NULL");


    // 2- On récupère les infos sur les bars ouverts
    $req = new requete ($this->dbrw,
           "SELECT MAX(activity_time),id_comptoir
            FROM `cpt_tracking`
            WHERE `activity_time` > '".date("Y-m-d H:i:s",time()-intval(ini_get("session.gc_maxlifetime")))."'
            AND `closed_time` IS NULL
            GROUP BY id_comptoir");

    while ( list($act,$id) = $req->get_row() )
      $activity[$id]=strtotime($act);

    // 3- On récupère les infos sur tous les bars
    $req = new requete ($this->dbrw,
           "SELECT id_comptoir, nom_cpt
            FROM cpt_comptoir
            WHERE type_cpt='0'
            AND id_comptoir != '4'
            AND id_comptoir != '8'
            AND id_comptoir != '13'
            ORDER BY nom_cpt");
    $list='';
    $i=0;
    while ( list($id,$nom) = $req->get_row() )
    {
      $i++;
      $led = "green";
      $descled = "ouvert";

      if ( !isset($activity[$id]) )
      {
        $led = "red";
        $descled = "fermé (ou pas d'activité depuis plus de ".(intval(ini_get("session.gc_maxlifetime"))/60)." minutes)";
      }
      elseif ( time()-$activity[$id] > 600 )
      {
        $led = "yellow";
        $descled = "ouvert (mais pas d'activité depuis plus de 10 minutes)";
      }
if(!defined("MOBILE")) {
      $list.="<a href=\"".$topdir."comptoir/activity.php?id_comptoir=$id\"><img src=\"".$topdir."images/leds/".$led."led2.png\" class=\"icon\" alt=\"".htmlentities($descled,ENT_NOQUOTES,"UTF-8")."\" title=\"".htmlentities($descled,ENT_NOQUOTES,"UTF-8")."\" /> $nom</a>";
      if($i<$req->lines)
        $list.='<br />';
} else {
      $list .= "<span class=\"".$led."\">".strtoupper($nom{0})."</span>";
      if($i < $req->lines)
        $list .= " ";
}
    }

    return '<div id="head_comptoirs">'.$list.'</div>';
  }

  /**
   * Gènère la boite d'information sur le forum
   * @param renvoie un stdcontents
   */
  function get_forum_box ()
  {
    global $wwwtopdir, $topdir;
    require_once($topdir . "include/entities/forum.inc.php");
    $forum = new forum($this->db);
    $forum->load_by_id(1);

    $cts = new contents("Forum");

    $query = "SELECT frm_sujet.titre_sujet, ".
        "frm_sujet.id_sujet, " .
        "frm_message.date_message, " .
        "frm_message.id_message, " .
        "dernier_auteur.alias_utl AS `nom_utilisateur_dernier_auteur`, " .
        "dernier_auteur.id_utilisateur AS `id_utilisateur_dernier`, " .
        "premier_auteur.alias_utl AS `nom_utilisateur_premier_auteur`, " .
        "premier_auteur.id_utilisateur AS `id_utilisateur_premier`, " .
        "1 AS `nonlu`, " .
        "titre_forum AS `soustitre_sujet` " .
        "FROM frm_sujet " .
        "INNER JOIN frm_forum USING(id_forum) ".
        "LEFT JOIN frm_message ON ( frm_message.id_message = frm_sujet.id_message_dernier ) " .
        "LEFT JOIN utilisateurs AS `dernier_auteur` ON ( dernier_auteur.id_utilisateur=frm_message.id_utilisateur ) " .
        "LEFT JOIN utilisateurs AS `premier_auteur` ON ( premier_auteur.id_utilisateur=frm_sujet.id_utilisateur ) ".
        "LEFT JOIN frm_sujet_utilisateur ".
          "ON ( frm_sujet_utilisateur.id_sujet=frm_sujet.id_sujet ".
          "AND frm_sujet_utilisateur.id_utilisateur='".$this->user->id."' ) ".
        "WHERE ";

    if( is_null($this->user->tout_lu_avant))
      $query .= "(frm_sujet_utilisateur.id_message_dernier_lu<frm_sujet.id_message_dernier ".
                "OR frm_sujet_utilisateur.id_message_dernier_lu IS NULL) ";
    else
      $query .= "((frm_sujet_utilisateur.id_message_dernier_lu<frm_sujet.id_message_dernier ".
                "OR frm_sujet_utilisateur.id_message_dernier_lu IS NULL) ".
                "AND frm_message.date_message > '".date("Y-m-d H:i:s",$this->user->tout_lu_avant)."') ";

    if ( !$forum->is_admin( $this->user ) )
    {
      $grps = $this->user->get_groups_csv();
      $query .= "AND ((droits_acces_forum & 0x1) OR " .
        "((droits_acces_forum & 0x10) AND id_groupe IN ($grps)) OR " .
        "(id_groupe_admin IN ($grps)) OR " .
        "((droits_acces_forum & 0x100) AND frm_forum.id_utilisateur='".$this->user->id."')) ";
    }
    $query .= "AND msg_supprime='0' ";

    $query_fav = $query."AND frm_sujet_utilisateur.etoile_sujet='1' ";
    $query_fav .= "ORDER BY frm_message.date_message DESC ";
    $query_fav .= "LIMIT 5 ";

    $query .= "AND ( frm_sujet_utilisateur.etoile_sujet IS NULL OR frm_sujet_utilisateur.etoile_sujet!='1' ) ";
    $query .= "ORDER BY frm_message.date_message DESC ";
    $query .= "LIMIT 5 ";

    $req = new requete($this->db,$query_fav);

    if ( $req->lines > 0 )
    {
      $cts->add_title(2,"<a href=\"".$wwwtopdir."forum2/search.php?page=unread\">Favoris non lus</a>");
      $list = new itemlist();
      for ($i=0; $i < 4 && $row = $req->get_row(); $i++)
      {
        $list->add("<a href=\"".$wwwtopdir."forum2/?id_sujet=".$row['id_sujet']."&amp;spage=firstunread#firstunread\"\">".
        htmlentities($row['titre_sujet'], ENT_NOQUOTES, "UTF-8").
        "</a>");
      }
      $cts->add($list);
      if ( $req->lines > 4 )
        $cts->add_paragraph("<a href=\"".$wwwtopdir."forum2/search.php?page=unread\">suite...</a>");
    }

    $i=$req->lines;

    $req = new requete($this->db,$query);

    if ( $req->lines > 0 )
    {
      $cts->add_title(2,"<a href=\"".$wwwtopdir."forum2/search.php?page=unread\">Derniers messages non lus</a>");
      $list = new itemlist();
      for ($i=0; $i < 4 && $row = $req->get_row(); $i++)
      {
        $list->add("<a href=\"".$wwwtopdir."forum2/?id_sujet=".$row['id_sujet']."&amp;spage=firstunread#firstunread\"\">".
        htmlentities($row['titre_sujet'], ENT_NOQUOTES, "UTF-8").
        "</a>");
      }
      $cts->add($list);
      if ( $req->lines > 4 )
        $cts->add_paragraph("<a href=\"".$wwwtopdir."forum2/search.php?page=unread\">suite...</a>");
    }
    elseif($i==0)//ni favoris ni pas favoris
      return null;

    return $cts;
  }

  /**
   * Génère la boite des permanences à venir
   * @param renvoie un stdcontents
   */
  function get_planning_permanences_contents()
  {
    $cache = new cachedcontents ("planning_perm");
    if ( $cache->is_cached () )
       return $cache->get_cache();

    $cts = new contents("Prochaines permanences");

    $cts->add_paragraph("<a href=\"".$wwwtopdir."planning\">Les plannings de permanences</a>");

    $req = new requete($this->db,"SET lc_time_names='fr_FR'");

    //TODO : Faire en sorte qu'il affiche tout seul les différents plannings et plus avoir à hardcoder l'id_planning
    $sublist = new itemlist("Bureau AE - Belfort");

    $req = new requete($this->db,"SELECT DAYNAME(start_gap) AS day, HOUR(start_gap) AS hour,
                                  IF(DAYOFWEEK(start_gap)<DAYOFWEEK(CURDATE()),true,false) as next
                                  FROM pl_gap
                                  INNER JOIN pl_gap_user USING(id_gap)
                                  WHERE  pl_gap.id_planning='164' AND (((DAYOFWEEK(start_gap)>DAYOFWEEK(CURDATE())
                                    OR (DAYOFWEEK(start_gap)=DAYOFWEEK(CURDATE()) AND HOUR(start_gap)>=HOUR(CURTIME())))
                                    AND ((WEEKOFYEAR(CURDATE())-WEEKOFYEAR(start_gap))%2)=0)
                                    OR (DAYOFWEEK(start_gap)<DAYOFWEEK(CURDATE())
                                    AND (((WEEKOFYEAR(CURDATE())+1)-WEEKOFYEAR(start_gap))%2)=0))
                                  GROUP BY id_gap
                                  ORDER BY IF(DAYOFWEEK(start_gap)<DAYOFWEEK(CURDATE()),1,0), DAYOFWEEK(start_gap), HOUR(start_gap) LIMIT 3");

    if($req->lines < 1)
    {
      $sublist->add("Aucune permanence à venir pour cette semaine");
    }
    else
    {
      while(list($day,$hour,$next) = $req->get_row() )
      {
        if($next)
          $sublist->add(ucfirst($day) . " prochain à " . $hour . "h");
        else
          $sublist->add(ucfirst($day) . " à " . $hour . "h");
      }
    }

    $cts->add($sublist, true, true, "bureau_ae_belfort", "boxlist", true, true);

        $sublist = new itemlist("Bureau AE - Sevenans");

    $req = new requete($this->db,"SELECT DAYNAME(start_gap) AS day, HOUR(start_gap) AS hour,
                                  IF(DAYOFWEEK(start_gap)<DAYOFWEEK(CURDATE()),true,false) as next
                                  FROM pl_gap
                                  INNER JOIN pl_gap_user USING(id_gap)
                                  WHERE  pl_gap.id_planning='166' AND (((DAYOFWEEK(start_gap)>DAYOFWEEK(CURDATE())
                                    OR (DAYOFWEEK(start_gap)=DAYOFWEEK(CURDATE()) AND HOUR(start_gap)>=HOUR(CURTIME())))
                                    AND ((WEEKOFYEAR(CURDATE())-WEEKOFYEAR(start_gap))%2)=0)
                                    OR (DAYOFWEEK(start_gap)<DAYOFWEEK(CURDATE())
                                    AND (((WEEKOFYEAR(CURDATE())+1)-WEEKOFYEAR(start_gap))%2)=0))
                                  GROUP BY id_gap
                                  ORDER BY IF(DAYOFWEEK(start_gap)<DAYOFWEEK(CURDATE()),1,0), DAYOFWEEK(start_gap), HOUR(start_gap) LIMIT 3");

    if($req->lines < 1)
    {
      $sublist->add("Aucune permanence à venir pour cette semaine");
    }
    else
    {
      while(list($day,$hour,$next) = $req->get_row() )
      {
        if($next)
          $sublist->add(ucfirst($day) . " prochain à " . $hour . "h");
        else
          $sublist->add(ucfirst($day) . " à " . $hour . "h");
      }
    }

    $cts->add($sublist, true, true, "bureau_ae_sevenans", "boxlist", true, true);

        $sublist = new itemlist("Bureau AE - Montbéliard");

    $req = new requete($this->db,"SELECT DAYNAME(start_gap) AS day, HOUR(start_gap) AS hour,
                                  IF(DAYOFWEEK(start_gap)<DAYOFWEEK(CURDATE()),true,false) as next
                                  FROM pl_gap
                                  INNER JOIN pl_gap_user USING(id_gap)
                                  WHERE  pl_gap.id_planning='165' AND (((DAYOFWEEK(start_gap)>DAYOFWEEK(CURDATE())
                                    OR (DAYOFWEEK(start_gap)=DAYOFWEEK(CURDATE()) AND HOUR(start_gap)>=HOUR(CURTIME())))
                                    AND ((WEEKOFYEAR(CURDATE())-WEEKOFYEAR(start_gap))%2)=0)
                                    OR (DAYOFWEEK(start_gap)<DAYOFWEEK(CURDATE())
                                    AND (((WEEKOFYEAR(CURDATE())+1)-WEEKOFYEAR(start_gap))%2)=0))
                                  GROUP BY id_gap
                                  ORDER BY IF(DAYOFWEEK(start_gap)<DAYOFWEEK(CURDATE()),1,0), DAYOFWEEK(start_gap), HOUR(start_gap) LIMIT 3");

    if($req->lines < 1)
    {
      $sublist->add("Aucune permanence à venir pour cette semaine");
    }
    else
    {
      while(list($day,$hour,$next) = $req->get_row() )
      {
        if($next)
          $sublist->add(ucfirst($day) . " prochain à " . $hour . "h");
        else
          $sublist->add(ucfirst($day) . " à " . $hour . "h");
      }
    }

    $cts->add($sublist, true, true, "bureau_ae_montbeliard", "boxlist", true, true);

    // Let's cache that for a week
    $cache->set_contents_until ($cts, 604800);

    return $cts;
  }

  /**
   * Gènère la boite d'information Superflux
   * @param renvoie un stdcontents
   */
  function get_stream_box()
  {
    $cts = new contents("Superflux");

    $cts->add_paragraph("La webradio de l'AE");

    if ( $GLOBALS["taiste"] )
      $infofile = $topdir."var/cache/stream";
    else
      $infofile = $topdir."var/cache/stream-prod";

    if ( file_exists($infofile) )
      $GLOBALS["streaminfo"] = unserialize(file_get_contents($infofile));

    if ( $GLOBALS["streaminfo"]["ogg"] || $GLOBALS["streaminfo"]["mp3"] )
    {
      if ( $GLOBALS["streaminfo"]["title"] || $GLOBALS["streaminfo"]["artist"] )
      {
        $cts->add_title(2,"Actuellement");

        $cts->add_paragraph("<span id=\"streaminfo\">".
          htmlentities($GLOBALS["streaminfo"]["title"], ENT_NOQUOTES, "UTF-8").
          " - ".
          htmlentities($GLOBALS["streaminfo"]["artist"], ENT_NOQUOTES, "UTF-8")."</span>");
      }

      if ( $GLOBALS["streaminfo"]["message"] )
      {
        $cts->add_title(2,"Information");
        $cts->add_paragraph($GLOBALS["streaminfo"]["message"]);
      }

      $cts->add_title(2,"Ecouter");
      $list = new itemlist();

      if ( $GLOBALS["streaminfo"]["mp3"] )
      {
        $list->add("<a href=\"".$wwwtopdir."stream.php\" onclick=\"return popUpStream('".$wwwtopdir."');\">Lecteur web</a>");
        $list->add("<a href=\"".$GLOBALS["streaminfo"]["mp3"]."\">Flux MP3</a>");
      }

      if ( $GLOBALS["streaminfo"]["ogg"] )
        $list->add("<a href=\"".$GLOBALS["streaminfo"]["ogg"]."\">Flux Ogg</a>");

      $cts->add($list);
    }
    else
      $cts->add_paragraph("Indisponible");

    return $cts;
  }

  /**
   * S'assure qu'a partir de ce point, seul les utilisateur connecté peuvent
   * accèder à la suite. Dans le cas d'un utilisateur connecté, affiche une
   * erreur avec la section précisé active, propose aussi de se connecter et/ou
   * de créer un compte et arrête l'execution du script.
   * @param $section Section à activer en cas d'utilisateur non connecté.
   */
  function allow_only_logged_users($section="none")
  {
    global $topdir;

    if ( $this->user->is_valid() )
      return;
    require_once($topdir."include/cts/login.inc.php");

    $this->start_page($section,"Identification requise");
    $this->add_contents(new loginerror($section));
    $this->end_page();
    exit();
  }

  /**
   * Erreur "Fatale" (ensemble du site) : Arrête l'execution du script et
   * affiche un message de maintenance.
   * @param $debug Texte inséré en comentaire dans le message de maintenance. Utile pour déterminer la raison du problème.
   */
  function fatal ($debug="fatal")
  {
    global $wwwtopdir;
    echo "<?xml version=\"1.0\"?>\n";
    echo "<!DOCTYPE html PUBLIC \"--//W3C//DTD XHTML 1.1//EN\" ";
    echo "\"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n\n";
    echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"fr\">\n";
    echo " <head>\n";
    echo "  <title>AE UTBM</title>\n";
    echo "  <link rel=\"stylesheet\" href=\"".$wwwtopdir."css/fatal.css\" title=\"fatal\" />\n";
    echo " </head>\n\n";
    echo " <body><!-- DEBUG INFO: $debug -->\n";
    echo "  <p><img src=\"".$wwwtopdir."images/fatalerror.jpg\" alt=\"Site en maintenance\" /></p>\n";
    echo " </body>\n";
    echo "</html>\n";
    exit();
  }

  /**
   * Erreur "fatale" dans une section : Arrête l'execution du script et affiche
   * un message de maintenance dans l'interface du site avec la section précisé
   * active.
   * @param $section Section où se produit l'erreur.
   */
  function fatal_partial($section="none")
  {
    global $wwwtopdir;

    $this->set_side_boxes("right",array());
    $this->set_side_boxes("left",array());

    $this->start_page($section,"En maintenance");
    $cts = new image ( "En maintenance", $wwwtopdir."images/partialclose.jpg" );
    $cts->cssclass = "partialclose";
    $cts->title = null;
    $this->add_contents($cts);
    $this->end_page();
    exit();
  }

  /**
   * Affiche une erreur "Accès refusé", en explique la raison si précisé et
   * arrête l'execution du script.
   * @param $section Section où s'est produite l'erreur
   * @param $reason Raison du refus d'accés ("group","private")
   * @param $id_group Si la raison est "group", groupe dont il aurai fallut faire partie pour accèder au contenu. (Utilisé par expliciter l'erreur)
   */
  function error_forbidden($section="none",$reason=null,$id_group=null)
  {
    $this->start_page($section,"Accés refusé");

    if ( $reason == "group" )
    {
      $who = "aux administrateurs";

      if ( $id_group == 10000 )
        $who = "aux membres de l'AE";
      elseif ( $id_group == 10001 )
        $who = "aux membres de l'UTBM";
      elseif ( $id_group >= 40000 )
        $who = "aux membres de la promo ".($id_group-40000);
      elseif ( $id_group >= 30000 )
        $who = "aux membres de l'activité";
      elseif ( $id_group >= 20000 )
        $who = "au bureau de l'activité";
      elseif ( $id_group >= 10000 )
        $who = "";

      $this->add_contents(new contents("Accés refusé","<p>Accès réservé $who.</p>"));
    }
    elseif ( $reason == "private" && $section =="matmatronch" )
      $this->add_contents(new contents("Accés refusé","<p>Cette fiche est privée, la personne concernée a souhaité que les informations la concernant ne soit pas rendues publiques.</p>"));

    elseif ( $reason == "blacklist_machines" )
      $this->add_contents(new contents("Accès refusé","<p>Vous n'avez pas le droit d'utiliser les machines à laver de l'AE, car vous n'avez pas respecté les conditions d'utilisations.</p>"));

    elseif ( $reason == "invalid" )
      $this->add_contents(new contents("Mode incompatible","<p>Ce mode ne peut pas être utilisé avec l'élément demandé.</p>"));

    elseif ( $reason == "wrongplace" )
      $this->add_contents(new contents("Salle invalide","<p>Il n'est pas possible d'accèder à cette page depuis ce poste. En cas de problème, demandez à un administrateur de corriger la salle associée à ce poste.</p>"));

    else
      $this->add_contents(new contents("Accés refusé","<p>Vos droits sont insuffisants pour accéder à cette page.</p>"));
    $this->end_page();
    exit();
  }

  /**
   * Affiche une erreur "non trouvé", ou si possible redirige l'utilisateur,
   * arrête l'execution du script dans tous les cas. La redirection est soit
   * celle précisé, soit vers la page principale de la section.
   * @param $section Section où s'est produite l'erreur.
   * @param $redirect Redirection à faire.
   */
  function error_not_found($section="none", $redirect=null)
  {
    global $wwwtopdir;

    if ( !is_null($redirect) )
    {
      header("Location: $redirect");
      exit();
    }

    if (!empty($this->tab_array))
    {
      foreach ($this->tab_array as $entry)
      {
        if ( $section == $entry[0] )
          $redirect = $wwwtopdir . $entry[1];
      }
    }

    if ( !is_null($redirect) )
    {
      header("Location: $redirect");
      exit();
    }

    $this->start_page($section,"Non trouvé");
    $this->add_contents(new contents("Non trouvé","<p>L'élément demandé n'a pas été trouvé</p>"));
    $this->end_page();

    exit();
  }

  function redirect($url='/', $argv=null)
  {
    $params=null;
    if($argv){
      (strpos($url, '?') !== false) ? $params='?' : $params='&';
      foreach($argv as $key=>$val)
        $params .= $key."=".$val.'?';
      rtrim($url, '?');
    }

    header("Location: $url");
    exit();
  }

  function return_file (  $uid, $mime_type, $mtime, $size, $file )
  {
    // Ferme la session si elle est encore ouverte
    if ( !isset($GLOBALS['nosession']) )
      session_write_close();

    // Ferme les accès à la base de donnés
    $this->db->close();
    $this->dbrw->close();


    $etag = $uid."M".$mtime;

    header('ETag: "'.$etag.'"', true);
    header("Cache-Control: public, max-age=3600", true);

    if ( !isset($_SERVER["HTTP_CACHE_CONTROL"]) )
    {
      if ( isset($_SERVER["HTTP_IF_NONE_MATCH"]) )
      {
        $asked = str_replace('"', '',stripslashes($_SERVER['HTTP_IF_NONE_MATCH']));
        if ( $asked == $etag )
        {
          //file_put_contents("counter",intval(@file_get_contents("counter"))+1);
          header("HTTP/1.1 304 Not Modified", true, 304);
          exit();
        }
      }
      elseif ( isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) )
      {
        $asked = strtotime($_SERVER["HTTP_IF_MODIFIED_SINCE"]);
        if ( $mtime <= $asked )
        {
          //file_put_contents("counter",intval(@file_get_contents("counter"))+1);
          header("HTTP/1.1 304 Not Modified", true, 304);
          exit();
        }
      }
    }

    $modified = gmdate("D, d M Y H:i:s \G\M\T",$mtime);

    header("Last-Modified: ".$modified, true);
    header("Content-Length: ".$size, true);
    header("Content-Type: ".$mime_type);
    header("Content-Disposition: filename=\"".$uid."\"");

    readfile($file);
    exit();
  }

  function return_simplefile (  $uid, $mime_type, $file )
  {
    $this->return_file (  $uid, $mime_type, filemtime($file), filesize($file), $file );
  }

  /**
   * Permet de loguer les actions critiques ou sensibles sur le site.
   * @deprecated, utiliser directement la fonction globale _log()
   * @param $action_log Action effectuée (exemple : Suppression facture)
   * @param $description_log Détails de l'opération effectuée
   * @param $context_log Contexte dans lequel l'opération a été effectuée (compteae, rezome...)
   * @param $id_utilisateur Si applicable, id de l'utilisateur ayant effectué l'action
   */
  function log($action_log, $description_log, $context_log, $id_utilisateur=null)
  {
    if(!is_null($id_utilisateur) && $id_utilisateur != $this->user->id)
    {
      $user = new utilisateur($this->db);
      if($user->load_by_id($id_utilisateur))
      {
        _log($this->dbrw,$action_log, $description_log, $context_log,$user);
        return;
      }
    }
    _log($this->dbrw,$action_log, $description_log, $context_log,$this->user);
  }

}
?>
