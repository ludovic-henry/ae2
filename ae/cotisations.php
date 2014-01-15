<?php
/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
 * - Simon Lopez < simon DOT lopez AT ayolo DOT org >
 * - Benjamin Collet < bcollet AT oxynux DOT org >
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
$topdir = "../";

/**
 * @deprecated
 * @todo à refaire complétement
 */

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/entities/cotisation.inc.php");
require_once($topdir. "include/cts/special.inc.php");
require_once($topdir . "include/entities/ville.inc.php");
require_once($topdir . "include/entities/pays.inc.php");
require_once($topdir. "include/entities/partenariat_utl.inc.php");
require_once($topdir. "include/cts/fsearchcache.inc.php");

$site = new site ();

if ( !$site->user->is_in_group("gestion_ae") )
  $site->error_forbidden("services");

if (date("m-d") < "02-15")
{
  $date1 = date("Y") . "-02-15";
  $date2 = date("Y") . "-08-15";
  $date3 = date("Y") + 1 . "-08-15";
  $date4 = date("Y") + 2 . "-08-15";
}
else
{
  if (date("m-d") < "08-15")
  {
    $date1 = date("Y") . "-08-15";
    $date2 = date("Y") + 1 . "-02-15";
    $date3 = date("Y") + 2 . "-02-15";
    $date4 = date("Y") + 3 . "-02-15";
  }
  else
  {
    $date1 = date("Y") + 1 . "-02-15";
    $date2 = date("Y") + 1 . "-08-15";
    $date3 = date("Y") + 2 . "-08-15";
    $date4 = date("Y") + 3 . "-08-15";
  }
}

$partenariats = array(1=> "Ce cotisant vient d'ouvrir un compte à la Société Générale",
                      2=> "Ce cotisant est à la SMEREB",
                      );

$site->start_page ("services", "Gestion des cotisations");

function add_search_form()
{
  global $topdir, $ch;
  $cts = new contents("Gestion des cotisations");
  $frm = new form("searchstudent","cotisations.php",true,"POST","Recherche d'un utilisateur existant");
  $frm->add_hidden("action","searchstudent");
  $subfrm = new form("quicksearch","cotisations.php",false,"POST","Recherche rapide ...");
  $subfrm->add_user_fieldv2("id_utilisateur","Prenom Nom/Surnom");
  $subfrm->add_submit("valid","Cotisation");
  $frm->add($subfrm,false,false,false,false,false,true,true);
  $subfrm = new form("searchemail","cotisations.php",false,"POST","Recherche par email ...");
  $subfrm->add_text_field("email","Adresse e-mail");
  $subfrm->add_submit("valid","Cotisation");
  $frm->add($subfrm,false,false,false,false,false,true,false);
  /*$subfrm = new form("searchcarte","cotisations.php",false,"POST","Par carte AE ...");
  $subfrm->add_text_field("numcarte","Carte AE");
  $subfrm->add_submit("valid","Cotisation");
  $frm->add($subfrm,false,false,false,false,false,true,false);*/
  $cts->add($frm,true);
  return $cts;
}

function add_new_form($id = null)
{
  global $topdir, $ch;

  global $date1, $date2, $date3, $date4, $partenariats;

  $cts = new contents("Gestion des cotisations");

  $frm = new form("newstudent","cotisations.php",true,"POST","Inscription d'un nouvel étudiant UTBM ou administratif UTBM");
  $frm->add_hidden("action","newstudent");
  if ( $ErreurNewStudent )
    $frm->error($ErreurNewStudent);

  $sub_frm_ident = new form("ident",null,null,null,"Identité");

  $sub_frm_ident->add_text_field("nom","Nom","",true);

  $sub_frm_ident->add_text_field("prenom","Prénom","",true);

  $sub_frm_ident->add_text_field("emailutbm","e-mail (UTBM si possible)","",true,false,false,true);
  $sub_frm_ident->add_checkbox("emailutbmvalid", "Sauter la verification d'email et valider immediatement le compte", isset($_SESSION['emailutbmvalid']) && $_SESSION['emailutbmvalid']);

  $frm->add($sub_frm_ident);
  $frm->add_info("&nbsp;");

  $sub_frm_cotiz = new form("cotisation",null,null,null,"Cotisation");
  $sub_frm_cotiz->add_select_field("cotiz","Cotisation",
      array(  0 => "1 Semestre, 15 Euros, $date1",
              1 => "2 Semestres, 28 Euros, $date2",
              2 => "Cursus Tronc Commun, 45 €, jusqu'au $date3",
              3 => "Cursus Branche, 45 €, jusqu'au $date4",
              4 => "Membre honoraire ou occasionnel, 0 €, jusqu'au $date2",
              5 => "Cotisation par Assidu, 4€, jusqu'au $date2",
              6 => "Cotisation par l'Amicale, 4€, jusqu'au $date2",
              7 => "Cotisation réseau UT, 0€, jusqu'au $date1, preuve de cotisation nécessaire",
              8 => "Cotisation CROUS, 4€, jusqu'au $date2",
              9 => "Cotisation Sbarro, 15€, jusqu'au $date2",
      ),1);
  $sub_frm_cotiz->add_select_field("paiement","Mode de paiement",array(1 => "Chèque", 3 => "Liquide", 4 => "Administration"));
  $sub_frm_cotiz->add_info("&nbsp;");

  $sub_frm_cotiz_ecole = new form("ecoleform",null,null,null,"Étudiant");
  //$sub_frm_cotiz_ecole->add_hidden("etudiant",true);
  //$sub_frm_cotiz_ecole->add_checkbox("etudiant","Etudiant",true);
  $sub_frm_cotiz_ecole->add_text_field("ecole","Ecole","UTBM",true);

  $sub_frm_cotiz->add($sub_frm_cotiz_ecole,false,true,true,"ecole",false,true,true);

  $sub_frm_cotiz_other = new form("ecoleform",null,null,null,"Prof/Administratif");
  //$sub_frm_cotiz_other->add_hidden("etudiant",false);
  $sub_frm_cotiz->add($sub_frm_cotiz_other,false,true,false,"other",false,true,false);

  $sub_frm_cotiz->add_info("&nbsp;");
  $sub_frm_cotiz->add_checkbox("droit_image","Droit à l'image",false);
  $sub_frm_cotiz->add_checkbox("cadeau","Cadeau",false);
  $sub_frm_cotiz->add_info("&nbsp;");

  $sub_frm_cotiz->add_info("&nbsp;");
  foreach ($partenariats as $id_partenariat => $texte_partenariat)
    $sub_frm_cotiz->add_checkbox("partenariats[".$id_partenariat."]",$texte_partenariat,false);

  $frm->add($sub_frm_cotiz);

  $frm->add_submit("submit","Enregistrer");
  $cts->add($frm,true);

  return $cts;
}

function add_user_info_form ($user = null)
{
  global $site;
  $ville = new ville($site->db);
  $pays = new pays($site->db);
  $ville->load_by_id($user->id_ville);
  $pays->load_by_id($user->id_pays);
  $ville_parents = new ville($site->db);
  $pays_parents = new pays($site->db);
  $pays_parents->load_by_id($user->id_pays_parents);
  $ville_parents->load_by_id($user->id_ville_parents);

  $sub_frm = new form("infosmmt",null,null,null,"Informations complémentaires");
  $sub_frm->add_info("&nbsp;");
  $sub_frm->add_select_field("sexe","Sexe",array(1=>"Homme",2=>"Femme"),$user->sexe);
  if ($user->date_naissance)
    $sub_frm->add_date_field("date_naissance","Date de naissance",$user->date_naissance,false,true);
  else
    $sub_frm->add_date_field("date_naissance","Date de naissance",strtotime("1986-01-01"),false,true);

  if ($user->utbm)
  {
    $sub_frm->add_select_field("role","Role",$GLOBALS["utbm_roles"],$user->role);
    $sub_frm->add_select_field("departement","Departement",$GLOBALS["utbm_departements"],$user->departement);
    $sub_frm->add_text_field("semestre","Semestre",$user->semestre);
  }
  $sub_frm->add_text_field("addresse","Adresse",$user->addresse);
  $sub_frm->add_entity_smartselect("id_ville","Ville (France)", $ville,true);
  $sub_frm->add_entity_smartselect("id_pays","ou pays", $pays,true);
  $sub_frm->add_text_field("tel_maison","Telephone (fixe)",$user->tel_maison);
  $sub_frm->add_info("et/ou");
  $sub_frm->add_text_field("tel_portable","Telephone (portable)",$user->tel_portable);
  $sub_frm->add_text_field("citation","Citation",$user->citation);

  $sub_frm->add_entity_smartselect ("id_ville_parents","Ville parents (France)", $ville_parents,true);
  $sub_frm->add_entity_smartselect ("id_pays_parents","ou pays parents", $pays_parents,true);
  $sub_frm->add_select_field("taille_tshirt","Taille de t-shirt (non publié***)",
                             array(0=>"-",
                                   "XS"=>"XS",
                                   "S"=>"S",
                                   "M"=>"M",
                                   "L"=>"L",
                                   "XL"=>"XL",
                                   "XXL"=>"XXL",
                                   "XXXL"=>"XXXL"),
                             $user->taille_tshirt);
  $sub_frm->add_info("&nbsp;");

  return $sub_frm;
}

/** Actions */

if ( $_REQUEST["action"] == "cadeau" )
{
  $cotisation = new cotisation($site->db,$site->dbrw);
  $cotisation->load_by_id($_REQUEST["id_cotisation"]);

  if ( $cotisation->id > 0 )
    $cotisation->mark_cadeau();
}

elseif ( $_REQUEST["action"] == "savecotiz" )
{
  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST['id_utilisateur']);

  if ( $user->id < 0 )
  {
    $site->error_not_found("services");
    exit();
  }
  else
  {
    if ( $user->cotisant )
    {
      global $site;
      $req = new requete($site->db,
                         "SELECT date_fin_cotis ".
                         "FROM `ae_cotisations` " .
                         "WHERE `id_utilisateur`='".$user->id."' " .
                         "ORDER BY `date_fin_cotis` DESC LIMIT 1");
      if ( $req->lines == 1 )
      {
        /* on fait de l'incrémental */
        list($curend) = $req->get_row();
        $prevdate=strtotime($curend);

        /* calculs identiques au mode normal mais basé sur la dernière cotiz */
        if (date("m-d",$prevdate) < "02-15")
        {
            $date1 = date("Y",$prevdate) . "-02-15";
            $date2 = date("Y",$prevdate) . "-08-15";
        }
        else
        {
          if (date("m-d",$prevdate) < "08-15")
          {
            $date1 = date("Y",$prevdate) . "-08-15";
            $date2 = date("Y",$prevdate) + 1 . "-02-15";
          }
          else
          {
            $date1 = date("Y",$prevdate) + 1 . "-02-15";
            $date2 = date("Y",$prevdate) + 1 . "-08-15";
          }
        }
      }
    }
    $cotisation = new cotisation($site->db,$site->dbrw);
    if ( $_REQUEST["cotiz"] == 0 ) {
      $date_fin = strtotime($date1);
      $prix_paye = 1500;
    } elseif ( $_REQUEST["cotiz"] == 1 ) {
      $date_fin = strtotime($date2);
      $prix_paye = 2800;
    } elseif ( $_REQUEST["cotiz"] == 2 ) {
      $date_fin = strtotime($date3);
      $prix_paye = 4500;
    } elseif ( $_REQUEST["cotiz"] == 3 ) {
      $date_fin = strtotime($date4);
      $prix_paye = 4500;
    } elseif ( $_REQUEST["cotiz"] == 4 ) {
      $date_fin = strtotime($date2);
      $prix_paye = 0;
    } elseif ( $_REQUEST["cotiz"] == 5 ) {
      $date_fin = strtotime($date2);
      $prix_paye = 400;
    } elseif ( $_REQUEST["cotiz"] == 6 ) {
      $date_fin = strtotime($date2);
      $prix_paye = 400;
    } elseif ( $_REQUEST["cotiz"] == 7 ) {
      $date_fin = strtotime($date1);
      $prix_paye = 0;
    } elseif ( $_REQUEST["cotiz"] == 8 ) {
      $date_fin = strtotime($date2);
      $prix_paye = 400;
    } elseif ( $_REQUEST["cotiz"] == 9 ) {
      $date_fin = strtotime($date2);
      $prix_paye = 1500;
    } else {
      $list->add("Le type de cotisation n'est pas valide");
      $site->add_contents($info);
    }

    $cotisation->load_lastest_by_user ( $user->id );
    $cotisation->add ( $user->id, $date_fin, $_REQUEST["paiement"], $prix_paye, $_REQUEST["cotiz"] );

    $a_pris_cadeau = $_REQUEST["cadeau"] == true;

    if ($a_pris_cadeau && $cotisation->id > 0)
      $cotisation->mark_cadeau();

    $user->load_all_extra();
    $user->droit_image = $_REQUEST["droit"]==true;
    $user->sexe = $_REQUEST['sexe'];
    $user->surnom = $_REQUEST['surnom'];
    $user->date_naissance = $_REQUEST['date_naissance'];
    $user->addresse = $_REQUEST['addresse'];
    $ville = new ville($site->db);
    $pays = new pays($site->db);
    $ville_parents = new ville($site->db);
    $pays_parents = new pays($site->db);
    if ( $_REQUEST['id_ville'] )
    {
      $ville->load_by_id($_REQUEST['id_ville']);
      $user->id_ville = $ville->id;
      $user->id_pays = $ville->id_pays;
    }
    else
    {
      $user->id_ville = null;
      $user->id_pays = $_REQUEST['id_pays'];
    }
    $user->tel_maison = telephone_userinput($_REQUEST['tel_maison']);
    $user->tel_portable = telephone_userinput($_REQUEST['tel_portable']);
    $user->date_maj = time();

    if ( $user->etudiant )
    {
      $user->citation = $_REQUEST['citation'];
      $user->adresse_parents = $_REQUEST['adresse_parents'];
      if ( $_REQUEST['id_ville_parents'] )
      {
        $ville_parents->load_by_id($_REQUEST['id_ville_parents']);
        $user->id_ville_parents = $ville_parents->id;
        $user->id_pays_parents = $ville_parents->id_pays;
      }
      else
      {
        $user->id_ville_parents = null;
        $user->id_pays_parents = $_REQUEST['id_pays_parents'];
      }
      $user->tel_parents = NULL;
      $user->nom_ecole_etudiant = "UTBM";
    }
    if ( $user->utbm )
    {
      $user->surnom = $_REQUEST['surnom'];
      $user->departement = $_REQUEST['departement'];
      $user->semestre = $_REQUEST['semestre'];
      $user->role = $_REQUEST['role'];
    }
    $user->taille_tshirt = $_REQUEST['taille_tshirt'];
    $user->saveinfos();

    $partenariat = new Partenariat($site->db, $site->dbrw);
    if (isset($_REQUEST['partenariats']))
      foreach ($_REQUEST['partenariats'] as $id_partenariat => $val)
        $partenariat->add($id_partenariat, $user->id);

    $info = new contents("Nouvelle cotisation","<img src=\"".$topdir."images/actions/done.png\">&nbsp;&nbsp;La cotisation a bien &eacute;t&eacute; enregistr&eacute;e.<br /><a href=\"" . $topdir . "ae/cotisations.php\">Retour</a>");


    $list = new itemlist();
    $list->add("<a href=\"" . $topdir . "ae/cotisations.php\">Retour aux cotisations</a>");
    $list->add("<a href=\"" . $topdir . "user.php?id_utilisateur=" . $user->id . "\">Retour &agrave; l'utilisateur</a>");
    $info->add($list);

    $info->set_toolbox(new toolbox(array($topdir . "ae/cotisations.php" => "Retour")));
    $site->add_contents($info);
  }
}

elseif ( $_REQUEST["action"] == "searchstudent" )
{
  $conds="";

  $user = array();
  if ( $_REQUEST['search_id'] && XMLRPC_USE )
    $user = $ch->getById($_REQUEST['search_id']);

  if ( $_REQUEST["nom"] )
  {
          $by = "nom";
          $on = $_REQUEST['nom'];
          if ($on)
            $conds .= " AND utilisateurs.nom_utl LIKE '".mysql_real_escape_string($on)."%'";
  }
  if ( $_REQUEST["prenom"] )
  {
          $by = "prénom";
          $on = $_REQUEST['prenom'];
          if ($on)
            $conds .= " AND utilisateurs.prenom_utl LIKE '".mysql_real_escape_string($on)."%'";
  }
  if ( $_REQUEST["email"] )
  {
          $by = "E Mail";
          $on = $_REQUEST['email'];
          if ($on)
            $conds .= " AND (`utilisateurs`.`email_utl` = '" . mysql_real_escape_string($on) . "' OR " .
              "`utl_etu_utbm`.`email_utbm` = '" . mysql_real_escape_string($on) . "') ";
  }
  if ( $_REQUEST["numcarte"] )
  {
          $by = "Code Barre Carte AE";
          list($num,$extra)=explode(" ",$_REQUEST["numcarte"]);
          $on = intval($num);
          $conds .= " AND ae_carte.id_carte_ae = '". mysql_real_escape_string($on)."'";
  }
  if ( isset($_REQUEST['id_utilisateur']) && ($_REQUEST['id_utilisateur'] > 0))
  {
    $by = "Identifiant AE";
    $on = intval($_REQUEST['id_utilisateur']);
    $conds .= " AND utilisateurs.id_utilisateur = '" . mysql_real_escape_string($on) . "'";
  }

  $req = new requete($site->db,"SELECT utilisateurs.nom_utl AS nom_utilisateur, " .
                     "utilisateurs.prenom_utl AS prenom_utilisateur, ".
                     "utilisateurs.id_utilisateur AS id_utl, utilisateurs.ae_utl, ae_cotisations.date_fin_cotis, " .
                     "utl_etu_utbm.branche_utbm, utl_etu_utbm.semestre_utbm" .
                     ", ae_cotisations.a_pris_cadeau ".
                     "FROM utilisateurs " .
                     "LEFT JOIN ae_cotisations ON (utilisateurs.id_utilisateur=ae_cotisations.id_utilisateur AND ae_cotisations.date_fin_cotis > NOW()) " .
                     "LEFT JOIN ae_carte ON `ae_cotisations`.`id_cotisation`=`ae_carte`.`id_cotisation` " .
                     "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur` = `utilisateurs`.`id_utilisateur` " .
                     "WHERE 1 $conds " .
                     "ORDER BY utilisateurs.nom_utl, utilisateurs.prenom_utl LIMIT 1");

  $nb = $req->lines;
  if ($nb == 1)
  {
    $res = $req->get_row();

    $user = new utilisateur($site->db,$site->dbrw);

    $user->load_by_id($res['id_utl']);
    if ( $user->id < 0 )
    {
      $site->error_not_found("services");
      exit();
    }

    if ( $user->id > 0 )
    {
      $user->load_all_extra();

      $cts = new contents("Cotisant ".$user->prenom." ".$user->nom);

      $cts->set_toolbox(new toolbox(array($_SERVER['SCRIPT_NAME']=>"Rechercher un autre cotisant")));

      $ville = new ville($site->db);
      $pays = new pays($site->db);
      $ville->load_by_id($user->id_ville);
      $pays->load_by_id($user->id_pays);

      $cts->add(new image($user->prenom . " " . $user->nom,$topdir."/data/matmatronch/".$user->id.".identity.jpg","fiche_image"));
      $cts->add_paragraph(
                          "<b>". $user->prenom . " " . $user->nom . "</b><br/>" .
                          $user->surnom."<br/>\n".
                          date("d/m/Y",$user->date_naissance) . "<br />" .
                          $user->addresse . "<br />" .
                          $ville->get_html_link()." (".sprintf("%05d", $ville->cpostal).")<br/>".$pays->get_html_link()."<br/>" .
                          $user->tel_maison . "<br/>" .
                          $user->tel_portable . "<br/>"
                         );

      $cts->add_paragraph("Fiche utilisateur : ".$user->get_html_link());

      $cts->add_paragraph("&nbsp;");

      $frm = new form("newcotiz","cotisations.php?id_utilisateur=".$user->id,true,"POST","Nouvelle cotisation");
      $frm->add_hidden("action","newcotiz");
      $frm->add_select_field("cotiz","Cotisation",
          array(  0 => "1 Semestre, 15 Euros, $date1",
                  1 => "2 Semestres, 28 Euros, $date2",
                  2 => "Cursus Tronc Commun, 45 €, jusqu'au $date3",
                  3 => "Cursus Branche, 45 €, jusqu'au $date4",
                  4 => "Membre honoraire ou occasionnel, 0 €, jusqu'au $date2",
                  5 => "Cotisation par Assidu, 4€, jusqu'au $date2",
                  6 => "Cotisation par l'Amicale, 4€, jusqu'au $date2",
                  7 => "Cotisation inter UT, 0€, jusqu'au $date1, preuve de cotisation nécessaire",
                  8 => "Cotisation CROUS, 4€, jusqu'au $date2",
                  9 => "Cotisation Sbarro, 15€, jusqu'au $date2",
          ),1);
      $frm->add_select_field("paiement","Mode de paiement",array(1 => "Chèque", 3 => "Liquide", 4 => "Administration"));
      $frm->add_checkbox("droit_image","Droit &agrave; l'image",$user->droit_image);
      $frm->add_checkbox("a_pris_cadeau","Cadeau distribué",false);
      foreach ($partenariats as $id_partenariat => $texte_partenariat)
        $frm->add_checkbox("partenariats[".$id_partenariat."]",$texte_partenariat,false);
      $frm->add_submit("submit","Enregistrer");
      $cts->add($frm,true);


      $req = new requete($site->db,
                         "SELECT * ".
                         "FROM `ae_cotisations` " .
                         "WHERE `id_utilisateur`='".$user->id."' AND `date_fin_cotis` < NOW()" .
                         "ORDER BY `date_cotis` DESC");

      $tbl = new sqltable(
                          "listcotiz_effectue",
                          "Cotisations effectuées", $req, "cotisations.php?id_utilisateur=".$user->id,
                          "id_cotisation",
                          array("date_cotis"=>"Le",
                                "date_fin_cotis"=>"Jusqu'au",
                                "a_pris_cadeau"=>"Cadeau"),
                          array(), array(), array("a_pris_cadeau"=>array(0=>"Non pris",1=>"Pris"))
                         );

      $req = new requete($site->db,
                         "SELECT * ".
                         "FROM `ae_cotisations` " .
                         "WHERE `id_utilisateur`='".$user->id."' AND `date_fin_cotis` > NOW() " .
                         "ORDER BY `date_cotis` DESC");

      if ($req->lines)
      {
        $_req= new requete($site->db,
                           "SELECT * ".
                           "FROM `ae_cotisations` " .
                           "WHERE `id_utilisateur`='".$user->id."' AND `date_fin_cotis` > NOW() " .
                           "ORDER BY `date_cotis` DESC LIMIT 1");
        $max = 0;
        while( $row = $_req->get_row() )
          if( strtotime($row["date_fin_cotis"]) > time() && strtotime($row["date_fin_cotis"]) > $max )
            $max = strtotime($row["date_fin_cotis"]);
        if($max>0)
          $cts->add_paragraph("<br /><b><font color=\"red\">D&eacute;j&agrave; cotisant jusqu'au : ".date("d/m/Y",$max)." !!!</font></b>");

        $tbl2 = new sqltable(
                            "listcotiz_encours",
                            "Cotisation en cours", $req, "cotisations.php?id_utilisateur=".$user->id,
                            "id_cotisation",
                            array("date_cotis"=>"Le",
                                  "date_fin_cotis"=>"Jusqu'au",
                                  "a_pris_cadeau"=>"Cadeau"),
                            array("Action"=>"Marquer le cadeau pris"), array(), array("a_pris_cadeau"=>array(0=>"Non pris",1=>"Pris"))
                           );
        $cts->add($tbl2,true);
      }

      $cts->add($tbl,true);

      $site->add_contents($cts);
    }

  }
  else if ($nb == 0)
  {
    $cts_2 = add_new_form($_REQUEST['search_id']);
    $cts_2->set_toolbox(new toolbox(array($_SERVER['SCRIPT_NAME']=>"Rechercher un cotisant")));
    $site->add_contents($cts_2);
  }
  else if ($nb > 1 && !XMLRPC_USE)
  {

    $res = $req->get_row();
    $tbl = new sqltable(
                        "listcotiz",
                         $nb." Résultats de la recherche de cotisants par ".$by." sur ".$on, $req, "cotisations.php",
                         "id_utilisateur",
                          array("nom_utilisateur"=>"Nom",
                                "prenom_utilisateur"=>"Prénom",
                                "branche_utbm"=>"Branche",
                                "semestre_utbm"=>"Semestre",
                                "ae_utl"=>"Cotisant",
                                "date_fin_cotis"=>"Jusqu'au",
                                "a_pris_cadeau"=>"Cadeau"),
                          array("pagecotis"=>"Nouvelle cotisation","cadeau"=>"Marquer le cadeau comme pris"), array(), array("ae_utl"=>array(0=>"Non",1=>"Oui"),"a_pris_cadeau"=>array(0=>"NON Pris",1=>"Pris"))
                         );
    $site->add_contents($tbl);
  }

}

elseif ($_REQUEST['action'] == "modifyUser" && $_POST['search_id'])
  $cts = add_new_form($_POST['search_id']);

elseif ( $_REQUEST["action"] == "newcotiz" )
{
  $user = new utilisateur($site->db,$site->dbrw);

  $user->load_by_id($_REQUEST['id_utilisateur']);
  if ( $user->id < 0 )
  {
    $site->error_not_found("services");
    exit();
  }

  if ( $user->id > 0 )
  {
    $user->load_all_extra();
    $cts = new contents("Mise à jour des infos indispensable pour l'impression de la carte AE");
    $frm = new form("infos","cotisations.php?id_utilisateur=".$user->id,true,"POST",null);
    $frm->add_hidden("action","savecotiz");
    if ( $user->utbm )
    {
      $frm->add_text_field("nom","Nom",$user->nom,true,false,false,false);
      $frm->add_text_field("prenom","Prénom",$user->prenom,true,false,false,false);
      $frm->add_text_field("surnom","Surnom",$user->surnom);

      $sub_frm = add_user_info_form($user);

      $frm->add($sub_frm,false,false,false,false,false,true,true);
    }
    else
      $frm->add_info("Cotisant non UTBM");

    $frm->add_hidden("cotiz",$_POST['cotiz']);
    $frm->add_hidden("paiement",$_POST['paiement']);
    $frm->add_hidden("droit_image",$_POST['droit_image']);
    $frm->add_hidden("cadeau",$_REQUEST["cadeau"]);
    if (isset($_REQUEST['partenariats']))
      foreach ($_REQUEST['partenariats'] as $id_partenariat => $val)
        $frm->add_hidden("partenariats[".$id_partenariat."]","1");

    $frm->add_submit("submit","Enregistrer");
    $cts->add($frm);
    $site->add_contents($cts);
  }
}

elseif ($_REQUEST["action"] == "newstudent")
{
  /* on va lui creer un compte utilisateur */
  $user = new utilisateur($site->db, $site->dbrw);

  /* D'abord on desactive le cache de fastsearch histoire que
     l'etudiant soit immediatement accessible par fsearch */
  $cache = new fsearchcache ();
  $cache->disable_cache_temporarily (5);

  /* Si on a le nom d'une ecole parce que c'est un etudiant */
  $email_utbm_needed = false;
  if ($_REQUEST['ecoleform'] == "ecole")
  {
    $etudiant = true;
    $nom_ecole = $_REQUEST['ecole'];
    if ($_REQUEST['ecole'] == "UTBM")
      $email_utbm_needed = true;
  }
  /* cas d'un prof */
  elseif ($_REQUEST['ecoleform'] == "other")
  {
    $etudiant = false;
    $nom_ecole = "UTBM";
    $email_utbm_needed = true;
  }
  else
  {
    $nom_ecole = null;
    $etudiant = false;
  }

  /* Verif de disponibilite */
  if (!$user->is_email_avaible($_REQUEST['emailutbm']))
  {
    $cts = new contents("WARNING");
    $cts->set_toolbox(new toolbox(array("javascript:history.go(-1);"=>"Retour")));
    if(CheckEmail($_REQUEST['emailutbm'],3))
      $cts->add_paragraph("<img src=\"".$topdir."images/actions/info.png\">&nbsp;&nbsp;L'email existe d&eacute;j&agrave; v&eacute;rifier que l'utilisateur ne figure pas dans la liste de la base de donn&eacute;es commune !");
    else
      $cts->add_paragraph("<img src=\"".$topdir."images/actions/info.png\">&nbsp;&nbsp;L'email N'EST PAS VALIDE !!! avec toute sa sympathie, l'équipe informatique qui en a marre de corriger les erreurs de saisies.");
    $site->add_contents($cts,true);
    $site->end_page();
    exit();
  }

  $pass = null;
  $_SESSION['emailutbmvalid'] = $_REQUEST['emailutbmvalid'] == true;
  $user->new_utbm_user($_REQUEST['nom'],
                       $_REQUEST['prenom'],
                       $_REQUEST['emailutbm'], $_REQUEST['emailutbm'],
                       $pass,null,null,null,
                       $etudiant,
                       $_REQUEST['droit_image']==true,
                       $nom_ecole,
                       null, /* date naissance (default) */
                       1, /* sexe (default) */
                       $_REQUEST['emailutbmvalid'] == true);

  if ($user->id < 0)
  {
    $cts = new contents("WARNING");
    $cts->set_toolbox(new toolbox(array("javascript:history.go(-1);"=>"Retour")));
    $cts->add_paragraph("<img src=\"".$topdir."images/actions/delete.png\">&nbsp;&nbsp;Probleme lors de l'ajout de l'utilisateur".$user->id);
    $site->add_contents($cts,true);
    $site->end_page();
    exit();
  }
  else
  {
    $user->load_all_extra();
    $pcts = new contents("Informations de creation");
    $pcts->add_paragraph ('Nouvel utilisateur créé, mot de passe temporaire : '.$pass);
    $cts = new contents("Mise à jour des infos indispensable pour l'impression de la carte AE");
    $frm = new form("infos","cotisations.php?id_utilisateur=".$user->id,true,"POST",null);
    $frm->add_hidden("action","savecotiz");
    $frm->add_text_field("nom","Nom",$user->nom,true,false,false,false);
    $frm->add_text_field("prenom","Prénom",$user->prenom,true,false,false,false);
    $frm->add_text_field("surnom","Surnom (facultatif) ",$user->surnom);
    $sub_frm = add_user_info_form($user);
    $frm->add($sub_frm,false,false,false,false,false,true,true);
    $frm->add_hidden("cotiz",$_POST['cotiz']);
    $frm->add_hidden("paiement",$_POST['paiement']);
    $frm->add_hidden("droit",$_REQUEST["droit_image"]);
    $frm->add_hidden("cadeau",$_REQUEST["cadeau"]);
    if (isset($_REQUEST['partenariats']))
      foreach ($_REQUEST['partenariats'] as $id_partenariat => $val)
        $frm->add_hidden("partenariats[".$id_partenariat."]","1");

    $frm->add_submit("submit","Enregistrer");
    $cts->add($frm);
    if ($_REQUEST['emailutbmvalid'] == true)
        $site->add_contents($pcts);
    $site->add_contents($cts);
  }
}

else
{
  $cts = add_search_form();
  $cts->add(add_new_form());
  $site->add_contents($cts);
}

$site->end_page ();

?>
