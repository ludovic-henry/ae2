<?php

$topdir = "./../";
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/cts/user.inc.php");
require_once($topdir. "include/cts/gallery.inc.php");
require_once($topdir. "include/cts/special.inc.php");
require_once($topdir. "include/entities/uv.inc.php");
require_once($topdir . "include/entities/ville.inc.php");
require_once($topdir . "include/entities/pays.inc.php");

$site = new site ();

$site->allow_only_logged_users("matmatronch");

if ( !$site->user->ae && !$site->user->utbm )
{
  $site->start_page("matmatronch","MatMaTronch");
  $cts = new contents("Accès limité");
  $cts->add_paragraph("L'accès à la recherche avancée du matmatronch est réservée aux membres de l'utbm.");
  $site->add_contents($cts);
  $site->end_page();
  exit();
}

$GLOBALS["utbm_roles"][""]="Tous";
$GLOBALS["utbm_departements"][""]="Tous";
$semestre = (date("m") > 6 ? "A" : "P") . date("y");

$is_admin = ( $site->user->is_in_group("gestion_ae") || $site->user->is_asso_role ( 27, 1 ));

$info = new contents("Mat'Matronch");
$info->add_paragraph("Le Mat'Matronch donne enfin un nom a un visage, le numero de portable du binome fantome, l'adresse de ce confrere que l'on recherche tant depuis cette fameuse soiree ...");

$list = new itemlist("Voir aussi");
$list->add("<a href=\"javascript:window.external.AddSearchProvider('http://ae.utbm.fr/matmatronch/matmatronch.xml');\">Extension Firefox</a>");
$list->add("La version mobile : <a href=\"http://ae.utbm.fr/m/\">http://ae.utbm.fr/m/</a> <a href=\"".$topdir."iinfo.php\">Informations</a>");
$info->add($list,true);

$list = new itemlist("Aide");
$list->add("<a href=\"".$topdir."article.php?name=docs:matmatronch\">Matmatronch</a>");
$list->add("<a href=\"".$topdir."article.php?name=docs:profil\">Profil personnel</a>");
$info->add($list,true);

$site->add_box("mmtinfo",$info);

$site->set_side_boxes("right",array("mmtinfo"),"mmt2_right");

$site->add_css("css/mmt.css");
$site->start_page("matmatronch","MatMaTronch");
$cts = new contents("Recherche Mat'Matronch");
$uv = new uv($site->db);

$jours = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi", "Dimanche");
$heures = array(8=>8,9=>9,10=>10,11=>11,13=>13,14=>14,15=>15,16=>16,17=>17,18=>18,19=>19);

if ( $_REQUEST["action"] == "search" || $_REQUEST["action"] == "simplesearch" )
{
  $elements = array();
  $order = "ORDER BY `nom_utl`,`prenom_utl`";

  $params="";

  if ( $_REQUEST["pattern"] )
  {
    $pattern = stdentity::_fsearch_prepare_sql_pattern($_REQUEST["pattern"]);
    $elements[] =
      "( CONCAT(`prenom_utl`,' ',`nom_utl`) REGEXP '^".$pattern."' " .
      "OR CONCAT(`nom_utl`,' ',`prenom_utl`) REGEXP '^".$pattern."'  " .
      "OR (`surnom_utbm`!='' AND `surnom_utbm` REGEXP '^".$pattern."'))";
    $params.="&pattern=".rawurlencode($_REQUEST["pattern"]);
  }

  if ( $_REQUEST["nom"] )
  {
    $elements[] = "`nom_utl` REGEXP '". stdentity::_fsearch_prepare_sql_pattern($_REQUEST["nom"])."'";
    $params.="&nom=".rawurlencode($_REQUEST["nom"]);
  }

  if ( $_REQUEST["prenom"] )
  {
    $elements[] = "`prenom_utl` REGEXP '". stdentity::_fsearch_prepare_sql_pattern($_REQUEST["prenom"])."'";
    $params.="&prenom=".rawurlencode($_REQUEST["prenom"]);
  }

  if ( $_REQUEST["surnom"] )
  {
    $p = stdentity::_fsearch_prepare_sql_pattern($_REQUEST["surnom"]);
    $elements[] = "(`surnom_utbm` REGEXP '$p')";
    $order = "ORDER BY `surnom_utbm`,`nom_utl`,`prenom_utl`";
    $params.="&surnom=".rawurlencode($_REQUEST["surnom"]);
  }

  if ( $_REQUEST["date_naissance"] > 1 )
  {
    $elements[] = "`date_naissance_utl`='".date("Y-m-d",$_REQUEST["date_naissance"])."'";
    $params.="&date_naissance=".rawurlencode($_REQUEST["date_naissance"]);
  }

  if ( $_REQUEST["sexe"] && $_REQUEST["sexe"] > 0 )
  {
    $elements[] = "`sexe_utl`='".mysql_escape_string($_REQUEST["sexe"])."'";
    $params.="&sexe=".rawurlencode($_REQUEST["sexe"]);
  }

  if ( $_REQUEST["role"] )
  {
    $elements[] = "`role_utbm`='".mysql_escape_string($_REQUEST["role"])."'";
    $params.="&role=".rawurlencode($_REQUEST["role"]);
  }

  if ( $_REQUEST["departement"] )
  {
    $elements[] = "`departement_utbm`='".mysql_escape_string($_REQUEST["departement"])."'";
    $params.="&departement=".rawurlencode($_REQUEST["departement"]);
  }

  if ( $_REQUEST["semestre"] && $_REQUEST["role"] == "etu" )
  {
    $elements[] = "`semestre_utbm`='".intval($_REQUEST["semestre"])."'";
    $params.="&semestre=".rawurlencode($_REQUEST["semestre"]);
  }

  if ( !empty($_REQUEST["promo"]) && $_REQUEST["promo"] > 0 )
  {
    $elements[] = "`promo_utbm`='".mysql_escape_string($_REQUEST["promo"])."'";
    $params.="&promo=".rawurlencode($_REQUEST["promo"]);
  }

  if ( !empty($_REQUEST["numtel"]) )
  {
    $tel = mysql_escape_string(telephone_userinput($_REQUEST["numtel"]));
    $elements[] = "(`tel_maison_utl`='$tel' OR `tel_portable_utl`='$tel')";
    $params.="&numtel=".rawurlencode($_REQUEST["numtel"]);
  }

  if( $_REQUEST['id_ville'] )
  {
    if (isset($_REQUEST['ville_parents']))
      $elements[] = "( `utilisateurs`.`id_ville`='".intval($_REQUEST['id_ville'])."'".
                    " OR `utl_etu`.`id_ville`='".intval($_REQUEST['id_ville'])."' )";
    else
      $elements[] = "`utilisateurs`.`id_ville`='".intval($_REQUEST['id_ville'])."'";
    $params.="&id_ville=".intval($_REQUEST['id_ville']);
    if( isset($_REQUEST['id_pays'] ) )
      $params.="&id_pays=".intval($_REQUEST['id_pays']);
  } elseif(isset($_REQUEST['id_pays'] ) ) {
    $params.="&id_pays=".intval($_REQUEST['id_pays']);
    if (isset($_REQUEST['ville_parents']))
      $elements[] = "( `utilisateurs`.`id_pays`='".intval($_REQUEST['id_pays'])."'".
                  " OR `utl_etu`.`id_pays`='".intval($_REQUEST['id_pays'])."' )";
    else
      $elements[] = "`utilisateurs`.`id_pays`='".intval($_REQUEST['id_pays'])."'";
  }

  if ( count($elements) > 0 )
  {

    if ( !isset($_REQUEST["inclus_ancien"]) &&  $_REQUEST["action"] != "simplesearch" )
      $elements[] = "`ancien_etudiant_utl`='0'";
    else
      $params.= "&inclus_ancien";

    if ( !isset($_REQUEST["inclus_nutbm"]) &&  $_REQUEST["action"] != "simplesearch" )
      $elements[] = "`utbm_utl`='1'";
    else
      $params.= "&inclus_nutbm";

    if ( !$is_admin && !$site->user->is_in_group("visu_cotisants") && $site->user->cotisant)
      $elements[] = "`publique_utl`>='1'";
    elseif ( !$is_admin && !$site->user->is_in_group("visu_cotisants"))
      $elements[] = "`publique_utl`>='2'";

    $req = new requete($site->db,"SELECT COUNT(`utilisateurs`.`id_utilisateur`) " .
        "FROM `utilisateurs` " .
        "LEFT JOIN `utl_etu` ON `utl_etu`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
        "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
        "WHERE "  .implode(" AND ",$elements));

    list($count) = $req->get_row();

    $cts->add_title(2,"Résultat : $count personne(s)");

    if ( $count == 0 )
      $cts->add_paragraph("Aucune personne ne correspond aux critères.");

    /*elseif ( $count > 350 )
      $cts->add_paragraph("Votre recherche est trop imprécise, il y a plus de 350 personnes correspondantes.");
    */
    else
    {
      $npp=24;
      $page = intval($_REQUEST["page"]);

      if ( $page)
        $st=$page*$npp;
      else
        $st=0;

      if ( $st > $count )
        $st = floor($count/$npp)*$npp;

      $req = new requete($site->db,"SELECT `utilisateurs`.*, `utl_etu`.citation,
        `utl_etu`.adresse_parents, `utl_etu`.ville_parents,
        `utl_etu`.cpostal_parents, `utl_etu`.pays_parents, `utl_etu`.tel_parents,
        `utl_etu`.nom_ecole_etudiant, `utl_etu`.id_ville,
        `utl_etu`.id_pays, `utl_etu_utbm`.semestre_utbm,
        `utl_etu_utbm`.branche_utbm, `utl_etu_utbm`.filiere_utbm,
        `utl_etu_utbm`.surnom_utbm, `utl_etu_utbm`.email_utbm,
        `utl_etu_utbm`.promo_utbm, `utl_etu_utbm`.date_diplome_utbm,
        `utl_etu_utbm`.role_utbm, `utl_etu_utbm`.departement_utbm,
        `utilisateurs`.`id_ville` as `id_ville`,
        `utl_etu`.`id_ville` as `ville_parents`,
        `utilisateurs`.`id_pays` as `id_pays`, `utl_etu`.`id_pays` as `pays_parents`
        FROM `utilisateurs`
        LEFT JOIN `utl_etu` ON `utl_etu`.`id_utilisateur`=`utilisateurs`.`id_utilisateur`
        LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur`
        WHERE "  .implode(" AND ",$elements)." $order LIMIT $st,$npp");

      $user = new utilisateur($site->db);

      $gal = new gallery();

      while ( $row = $req->get_row() )
      {
        $user->_load_all($row);
        $same_promo = ($user->promo_utbm == $site->user->promo_utbm);
        $gal->add_item(new userinfov2($user, "small", false, "user.php", $same_promo));
      }

      $cts->add($gal);

      //if ( $count > $npp )
      //{
        $tabs = array();
        $i=0;
        $n=0;
        while ( $i < $count )
        {
          $tabs[]=array($n,"matmatronch/index.php?action=search&page=".$n.$params,$n+1 );
          $i+=$npp;
          $n++;
        }
        $cts->add(new tabshead($tabs, $page, "_bottom"));
      //}

    }
  }
}
elseif ( $_REQUEST["action"] == "searchedt" )
{
  $uv->load_by_id($_REQUEST["id_uv"]);

  $params="&id_uv=".rawurlencode($uv->id)."&type=".rawurlencode($_REQUEST["type"]);

  if ( $_REQUEST["type"] == 2 )
  {
    $t = "TD";
    $params.="&td_jour=".rawurlencode($_REQUEST["td_jour"])."&td_heure=".rawurlencode($_REQUEST["td_heure"]);
    $cond =
      "AND jour_grp='".mysql_escape_string($_REQUEST["td_jour"])."' ".
      "AND TIME_FORMAT(heure_debut_grp,'%k')='".mysql_escape_string($_REQUEST["td_heure"])."' ";
  }
  elseif ( $_REQUEST["type"] == 3 )
  {
    $t = "TP";
    $params.="&tp_jour=".rawurlencode($_REQUEST["tp_jour"])."&tp_heure=".rawurlencode($_REQUEST["tp_heure"]);
    $cond =
      "AND jour_grp='".mysql_escape_string($_REQUEST["tp_jour"])."' ".
      "AND TIME_FORMAT(heure_debut_grp,'%k')='".mysql_escape_string($_REQUEST["tp_heure"])."' ";
  }
  else
  {
    $t = "C";
    $cond="";
  }

  $req = new requete($site->db,"SELECT id_uv_groupe FROM edu_uv_groupe WHERE semestre_grp='".$semestre."' AND id_uv='".mysql_escape_string($uv->id)."' AND type_grp='".$t."' $cond");

  if ( $req->lines < 1 )
  {
    $cts->add_title(2,"Résultat : Aucun groupe n'a été trouvé");
    $cts->add_paragraph("Aucun groupe n'a été trouvé ($semestre).");
  }
  else
  {
    $groupes=array();
    while(list($id)=$req->get_row()) $groupes[]=$id;

    $req = new requete($site->db,"SELECT COUNT(`utilisateurs`.`id_utilisateur`) " .
        "FROM `edu_uv_groupe_etudiant` " .
        "INNER JOIN `utilisateurs` USING(id_utilisateur) " .
        "LEFT JOIN `utl_etu` ON `utl_etu`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
        "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
        "WHERE id_uv_groupe IN (".implode(",",$groupes).")");

    list($count) = $req->get_row();

    $cts->add_title(2,"Résultat : $count personne(s)");

    if ( $count == 0 )
      $cts->add_paragraph("Aucune personne ne correspond aux critères.");

    /*elseif ( $count > 350 )
      $cts->add_paragraph("Votre recherche est trop imprécise, il y a plus de 350 personnes correspondantes.");
    */
    else
    {
      $npp=24;
      $page = intval($_REQUEST["page"]);

      if ( $page)
        $st=$page*$npp;
      else
        $st=0;

      if ( $st > $count )
        $st = floor($count/$npp)*$npp;

      $req = new requete($site->db,"SELECT `utilisateurs`.*, `utl_etu`.*, `utl_etu_utbm`.*, `utilisateurs`.`id_ville` as `id_ville`, `utl_etu`.`id_ville` as `ville_parents`, `utilisateurs`.`id_pays` as `id_pays`, `utl_etu`.`id_pays` as `pays_parents` " .
        "FROM `edu_uv_groupe_etudiant` " .
        "INNER JOIN `utilisateurs` USING(id_utilisateur) " .
        "LEFT JOIN `utl_etu` ON `utl_etu`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
        "LEFT JOIN `utl_etu_utbm` ON `utl_etu_utbm`.`id_utilisateur`=`utilisateurs`.`id_utilisateur` " .
        "WHERE id_uv_groupe IN (".implode(",",$groupes).") ORDER BY `nom_utl`,`prenom_utl` LIMIT $st,$npp");

      $user = new utilisateur($site->db);

      $gal = new gallery();

      while ( $row = $req->get_row() )
      {
        $user->_load_all($row);
        $same_promo = ($user->promo_utbm == $site->user->promo_utbm);
        $gal->add_item(new userinfov2($user, "small", false, "user.php", $same_promo));
      }

      $cts->add($gal);

      //if ( $count > $npp )
      //{
        $tabs = array();
        $i=0;
        $n=0;
        while ( $i < $count )
        {
          $tabs[]=array($n,"matmatronch/index.php?action=searchedt&page=".$n.$params,$n+1 );
          $i+=$npp;
          $n++;
        }
        $cts->add(new tabshead($tabs, $page, "_bottom"));
      //}
    }
  }
}

$frm = new form("mmtprofil","index.php",true,"POST","Recherche par profil");
$frm->add_hidden("action","search");
$frm->add_text_field("nom","Nom");
$frm->add_text_field("prenom","Prenom");
$frm->add_text_field("surnom","Surnom");
$frm->add_radiobox_field("sexe","Sexe",array(1=>"Homme",2=>"Femme",0=>"Indifférent"),0, -1);
$frm->add_select_field("role","Role",$GLOBALS["utbm_roles"],"etu");
$frm->add_select_field("departement","Departement",$GLOBALS["utbm_departements"],"");
$frm->add_text_field("semestre","Semestre","");
$frm->add_select_field("promo", "Promo", $site->user->liste_promos("Toutes"), 0);
$frm->add_date_field("date_naissance","Date de naissance");
$frm->add_checkbox("inclus_ancien","Inclure les anciens",false);
$frm->add_checkbox("inclus_nutbm","Inclure les non-utbm",false);
//TODO:améliorer la présentation

$frm->add_submit("go","Rechercher");
if ( isset($_REQUEST["action"]) && (isset($_REQUEST["numtel"]) || isset($_REQUEST["pattern"])||$_REQUEST["action"]=="searchedt") )
$cts->add($frm, true, true, "bxnor", false, true, false);
else
$cts->add($frm,true);

/* TODO : mettre à jour...
$semestre = ((date("m") > 6 || date("m") < 2) ? "A" : "P");

/* on est encore en semestre d'automne année précédente *
if (date("m") < 2)
{
  $semestre .= sprintf("%02d", (date("y") -1));
}
/* sinon, semestre suivant *
else
{
  $semestre .= date("y");
}


$req = new requete($site->db, "SELECT `semestre_grp`, `edu_uv_groupe_etudiant`.`id_utilisateur`
                            FROM `edu_uv_groupe`
                            INNER JOIN `edu_uv_groupe_etudiant`
                            USING(`id_uv_groupe`)
                            WHERE `id_utilisateur` = '".$site->user->id."'
                            AND `semestre_grp`= '".$semestre."'
                            GROUP BY `semestre_grp`, `id_utilisateur`");
if ( $req->lines < 1 )
{
  $cts->add_title(2,"Recherche par emploi du temps");

  $cts->add_paragraph("Pour pouvoir utiliser la recherche par emploi du temps, vous devez avoir renseigné votre emploi du temps sur le site.","error");
  $cts->add_paragraph("Créez votre emploi du temps, pour en obtenir une version graphique, pour permettre à vos binomes de vous retrouver plus facilement, et pour trouver en tout simplicité des horraires pour vos réunions.");
  $cts->add_paragraph("<a href=\"../uvs/create.php\">Ajouter votre emploi du temps</a>");
}
else
{
  $type = 1;
  if ( $_REQUEST["action"] == "searchedt" && $_REQUEST["type"] > 0 )
    $type = intval($_REQUEST["type"]);

  $frm = new form("mmtedt","index.php",true,"POST","Recherche par emploi du temps");
  $frm->add_hidden("action","searchedt");
  $frm->add_info("Remarque: Cet outil ne fonctionne que pour les personnes ayant renseigné leur emploi du temps sur le site.");
  $frm->add_entity_smartselect ( "id_uv", "UV", $uv );
  $sfrm = new form("type",null,true,null,"Tous / Cours");
  $frm->add($sfrm,false,true, $type==1 , 1,false,true);

  $sfrm = new form("type",null,true,null,"TD");
  $sfrm->add_select_field("td_jour","Jour",$jours,$_REQUEST["td_jour"]);
  $sfrm->add_select_field("td_heure","Heure (début)",$heures,$_REQUEST["td_heure"]);
  $frm->add($sfrm,false,true, $type==2 , 2,false,true);

  $sfrm = new form("type",null,true,null,"TP");
  $sfrm->add_select_field("tp_jour","Jour",$jours,$_REQUEST["tp_jour"]);
  $sfrm->add_select_field("tp_heure","Heure (début)",$heures,$_REQUEST["tp_heure"]);
  $frm->add($sfrm,false,true, $type==3 , 3,false,true);

  $frm->add_submit("go","Rechercher");

  if ( isset($_REQUEST["action"]) && $_REQUEST["action"]!="searchedt" )
  $cts->add($frm, true, true, "bxedt", false, true, false);
  else
  $cts->add($frm,true);
}
*/

$frm = new form("mmtinv","index.php",true,"POST","Recherche inversée");
$frm->add_hidden("action","search");
$frm->add_text_field("numtel","Numéro de téléphone");
$frm->add_submit("go","Rechercher");
if ( isset($_REQUEST["action"]) && !isset($_REQUEST["numtel"]) )
$cts->add($frm, true, true, "bxtel", false, true, false);
else
$cts->add($frm,true);

$frm = new form("mmtpat","index.php",true,"POST","Recherche simple");
$frm->add_hidden("action","search");
$frm->add_text_field("pattern","Nom/Prenom ou Surnom",$_REQUEST["pattern"]);
$frm->add_submit("go","Rechercher");

if ( isset($_REQUEST["action"]) && !isset($_REQUEST["pattern"]) )
$cts->add($frm, true, true, "bxpat", false, true, false);
else
$cts->add($frm,true);


$ville = new ville($site->db);
$pays = new pays($site->db);
$pays->load_by_id(1);
$frm = new form("mmtville","index.php",true,"POST","Recherche par ville");
$frm->add_hidden("action","search");
$frm->add_entity_smartselect ("id_pays","Pays", $pays,true);
$frm->add_entity_smartselect ("id_ville","Ville", $ville,true,false,array('id_pays'=>'id_pays_id'),true);
$frm->add_checkbox ("ville_parents","Chercher aussi parmis les adresses des parents");
$frm->add_submit("go","Rechercher");
if ( isset($_REQUEST["action"]) && !isset($_REQUEST["ville"]) )
  $cts->add($frm, true, true, "bxville", false, true, false);
else
  $cts->add($frm,true);


$site->add_contents($cts);
$site->end_page();

?>
