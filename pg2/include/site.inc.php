<?php

require_once($topdir."include/site.inc.php");


class pgsite extends site
{

  function pgsite()
  {
    $this->site();

    $this->tab_array = array (
        array ("pg", "pg2/index.php", "Accueil"),
        /*array ("pgsearch", "pg2/search.php", "Recherche" ),
        array ("pgagenda", "pg2/agenda.php", "Agenda" ),
        array ("pgbplans", "pg2/bplans.php", "Bons plans" ),
        array ("pgbus",    "pg2/bus.php", "Bus" ),
        array ("jobetu",   "jobetu/", "Job-étu" ),*/
        array ("retour","index.php","Site AE UTBM")
        );

    $this->add_css("themes/pg/css/site.css");

  }

  function start_page ( $section, $title,$compact=false )
  {
    global $topdir;

    interfaceweb::start_page($section,$title." (version provisoire)",$compact);

    //$this->add_box("calendrier",new calendar($this->db));

    $this->set_side_boxes("left",array("pg","connexion"),"pg_left");

    $this->add_box("connexion", $this->get_connection_contents());
  }

  function is_admin()
  {
    return $this->user->is_in_group("pg2_admin");
  }


  function get_connection_contents ()
  {
    global $topdir;
    global $wwwtopdir;

    if ( !$this->user->is_valid() )
    {
      $cts = new contents("Connexion");
      $frm = new form("connect",$topdir."connect.php",true,"POST","Connexion");
      $frm->add_select_field("domain","Connexion",array("utbm"=>"UTBM / Assidu", "id"=>"ID", "autre"=>"E-mail", "alias"=>"Alias"),"autre");
      $frm->add_text_field("username","Utilisateur","prenom.nom","",20,true);
      $frm->add_password_field("password","Mot de passe","","",20);
      $frm->add_checkbox ( "personnal_computer", "Me connecter automatiquement la prochaine fois", false );
      $frm->add_submit("connectbtn","Se connecter");
      $cts->add($frm);

      $cts->add_paragraph("<a href=\"".$wwwtopdir."article.php?name=docs:connexion\">Aide</a> - <a href=\"".$wwwtopdir."password.php\">Mot de passe perdu</a>");

      return $cts;
    }

    $cts = new contents("Le petit géni et moi");
    $cts->add_paragraph("Bonjour <b>".$this->user->prenom." ".$this->user->nom."</b>");

    $sublist = new itemlist("Mon Compte","boxlist");
    $sublist->add("<a href=\"".$topdir."user.php?id_utilisateur=".$this->user->id."\">Informations personnelles</a> (Site AE)");
    /*if( $this->user->is_in_group("jobetu_etu") )
    {
      $jobuser = new jobuser_etu($this->db);
      $jobuser->load_by_id($this->user->id);
      $jobuser->load_annonces();
      $sublist->add("<a href=\"".$topdir."jobetu/board_etu.php\">Mon compte JobEtu (".count($jobuser->annonces).")</a>");
    }
    else if( $this->user->is_in_group("jobetu_client") )
      $sublist->add("<a href=\"".$topdir."jobetu/board_client.php\">AE JobEtu</a>");
    else
      $sublist->add("<a href=\"".$topdir."jobetu/index.php\">AE JobEtu</a>");
*/
    $cts->add($sublist,true, true, "accountbox", "boxlist", true, true);

    /* Bouton de Deconnexion */
    $frm = new form("disconnect",$topdir."disconnect.php",false,"POST","Deconnexion");
    $frm->add_submit("disconnect","Se déconnecter");
    $cts->add($frm);

    return $cts;
  }

}



?>
