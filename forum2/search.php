<?php
/*
 * FORUM2
 *
 * Copyright 2007 - 2010
 * - Julien Etelain < julien dot etelain at gmail dot com >
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Benjamin Collet <bcollet at oxynux dot org>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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

require_once($topdir. "include/site.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir . "include/entities/forum.inc.php");
require_once($topdir . "include/entities/sujet.inc.php");
require_once($topdir . "include/entities/message.inc.php");
require_once($topdir . "include/entities/utilisateur.inc.php");

require_once($topdir . "include/entities/news.inc.php");
require_once($topdir . "include/entities/sondage.inc.php");
require_once($topdir . "sas2/include/cat.inc.php");

require_once($topdir . "include/cts/forum.inc.php");

$site = new site ();

if (!$site->get_param ("forum_open", false)) {
  if (!$site->user->is_in_group ("moderateur_forum") &&
      !$site->user->is_in_group ("root")) {
    $site->start_page ("forum", "Forum");
    $cts = new contents ("Forum fermé",
        $site->get_param ("forum_message", "Maintenance."));
    $site->add_contents ($cts);
    $site->end_page();
    exit();
  } else {
    $cts = new contents ();
    $cts->add_paragraph ("<b>Attention, forum fermé aux non-modérateurs : ".
        $site->get_param ("forum_message", "Maintenance.")."</b>");
    $site->add_contents ($cts);
  }
}

$site->add_css("css/forum.css");

$forum = new forum($site->db);
$forum->load_by_id(1);

if ( $_REQUEST["page"] == "unread" )
{
  $site->allow_only_logged_users("forum");

  $site->start_page("forum","Messages non lus");

  $cts = new contents($forum->get_html_link()." / <a href=\"search.php?page=unread\">Messages non lus</a>");

  $cts->add_paragraph(
  "<a href=\"search.php?page=unread\">".
    "<img src=\"".$wwwtopdir."images/icons/16/reload.png\" class=\"icon\" alt=\"\" />Actualiser".
  "</a> ".
  "<a href=\"./?action=setallread\">".
    "<img src=\"".$wwwtopdir."images/icons/16/valid.png\" class=\"icon\" alt=\"\" />Marquer tout comme lu".
  "</a> ".
  "<a href=\"search.php\">".
    "<img src=\"".$wwwtopdir."images/icons/16/search.png\" class=\"icon\" alt=\"\" />Rechercher".
  "</a>"
  ,"frmtools");


  $query = "SELECT frm_sujet.*, ".
      "frm_message.date_message, " .
      "frm_message.id_message, " .
      "COALESCE(
        dernier_auteur_etu_utbm.surnom_utbm,
        CONCAT(dernier_auteur.prenom_utl,' ',dernier_auteur.nom_utl)
      ) AS `nom_utilisateur_dernier_auteur`, " .
      "dernier_auteur.id_utilisateur AS `id_utilisateur_dernier`, " .
      "COALESCE(
          premier_auteur_etu_utbm.surnom_utbm,
          CONCAT(premier_auteur.prenom_utl,' ',premier_auteur.nom_utl)
        ) AS `nom_utilisateur_premier_auteur`, " .
      "premier_auteur.id_utilisateur AS `id_utilisateur_premier`, " .
      "1 AS `nonlu`, " .
      "titre_forum AS `soustitre_sujet`, " .
      "frm_sujet_utilisateur.etoile_sujet AS `etoile`, " .
      "frm_forum.droits_acces_forum, ".
      "frm_forum.id_groupe ".
      "FROM frm_sujet " .
      "INNER JOIN frm_forum USING(id_forum) ".
      "LEFT JOIN frm_message ON ( frm_message.id_message = frm_sujet.id_message_dernier ) " .
      "LEFT JOIN utilisateurs AS `dernier_auteur` ON ( dernier_auteur.id_utilisateur=frm_message.id_utilisateur ) " .
      "LEFT JOIN utilisateurs AS `premier_auteur` ON ( premier_auteur.id_utilisateur=frm_sujet.id_utilisateur ) ".
      "LEFT JOIN utl_etu_utbm AS `dernier_auteur_etu_utbm` ON ( dernier_auteur_etu_utbm.id_utilisateur=frm_message.id_utilisateur ) " .
      "LEFT JOIN utl_etu_utbm AS `premier_auteur_etu_utbm` ON ( premier_auteur_etu_utbm.id_utilisateur=frm_sujet.id_utilisateur )" .
      "LEFT JOIN frm_sujet_utilisateur ".
        "ON ( frm_sujet_utilisateur.id_sujet=frm_sujet.id_sujet ".
        "AND frm_sujet_utilisateur.id_utilisateur='".$site->user->id."' ) ".
      "WHERE ";

  if( is_null($site->user->tout_lu_avant))
    $query .= "(frm_sujet_utilisateur.id_message_dernier_lu<frm_sujet.id_message_dernier ".
              "OR frm_sujet_utilisateur.id_message_dernier_lu IS NULL) ";
  else
    $query .= "((frm_sujet_utilisateur.id_message_dernier_lu<frm_sujet.id_message_dernier ".
              "OR frm_sujet_utilisateur.id_message_dernier_lu IS NULL) ".
              "AND frm_message.date_message > '".date("Y-m-d H:i:s",$site->user->tout_lu_avant)."') ";

  if ( !$forum->is_admin( $site->user ) )
  {
    $grps = $site->user->get_groups_csv();
    $query .= "AND frm_message.msg_supprime='0' ";
    $query .= "AND ((droits_acces_forum & 0x1) OR " .
      "((droits_acces_forum & 0x10) AND id_groupe IN ($grps)) OR " .
      "(id_groupe_admin IN ($grps)) OR " .
      "((droits_acces_forum & 0x100) AND frm_forum.id_utilisateur='".$site->user->id."')) ";
  }


  $query_fav = $query."AND frm_sujet_utilisateur.etoile_sujet='1' ";
  $query_fav .= "ORDER BY frm_message.date_message DESC ";
  $query_fav .= "LIMIT 75 ";

  $query .= "AND ( frm_sujet_utilisateur.etoile_sujet IS NULL OR frm_sujet_utilisateur.etoile_sujet!='1' ) ";
  $query .= "ORDER BY frm_message.date_message DESC ";
  $query .= "LIMIT 75 ";

  /*$query .= "ORDER BY frm_message.date_message DESC ";
  $query .= "LIMIT 100 ";*/

  $req = new requete($site->db,$query_fav);
  if ( $req->lines > 0 )
  {
    $cts->add_title(2,"Sujets favoris avec des messages non lus");
    $rows = array();
    while ( $row = $req->get_row() )
    {
      if (($row['id_groupe'] != 7) || ($row['droits_acces_forum'] & 0x1) || ($site->user->is_in_group("root")))
        $rows[] = $row;
    }

    $cts->add(new sujetslist($rows, $site->user, "./", null, null,true));
    $cts->add_paragraph("&nbsp;");
  }


  $req = new requete($site->db,$query);
  if ( $req->lines > 0 )
  {
    $cts->add_title(2,"Sujets avec des messages non lus");
    $rows = array();
    {
    while ( $row = $req->get_row() )
      if (($row['id_groupe'] != 7) || ($row['droits_acces_forum'] & 0x1) || ($site->user->is_in_group("root")))
        $rows[] = $row;
    }

    $cts->add(new sujetslist($rows, $site->user, "./", null, null,true));
  }

  $site->add_contents($cts);

  $site->end_page();
  exit();
}
elseif ( $_REQUEST["page"] == "starred" )
{
  $site->allow_only_logged_users("forum");

  $site->start_page("forum","Sujets favoris");

  $cts = new contents($forum->get_html_link()." / <a href=\"search.php?page=starred\">Sujets favoris</a>");

  $query = "SELECT frm_sujet.*, ".
      "frm_message.date_message, " .
      "frm_message.id_message, " .
      "COALESCE(
        dernier_auteur_etu_utbm.surnom_utbm,
        CONCAT(dernier_auteur.prenom_utl,' ',dernier_auteur.nom_utl)
      ) AS `nom_utilisateur_dernier_auteur`, " .
      "dernier_auteur.id_utilisateur AS `id_utilisateur_dernier`, " .
      "COALESCE(
          premier_auteur_etu_utbm.surnom_utbm,
          CONCAT(premier_auteur.prenom_utl,' ',premier_auteur.nom_utl)
        ) AS `nom_utilisateur_premier_auteur`, " .
      "premier_auteur.id_utilisateur AS `id_utilisateur_premier`, " .
      "IF(frm_sujet.id_message_dernier > frm_sujet_utilisateur.id_message_dernier_lu,1,0) AS `nonlu`, " .
      "titre_forum AS `soustitre_sujet`, " .
      "0 AS `etoile`, " .
      "frm_forum.droits_acces_forum, ".
      "frm_forum.id_groupe ".
      "FROM frm_sujet " .
      "INNER JOIN frm_forum USING(id_forum) ".
      "LEFT JOIN frm_message ON ( frm_message.id_message = frm_sujet.id_message_dernier ) " .
      "LEFT JOIN utilisateurs AS `dernier_auteur` ON ( dernier_auteur.id_utilisateur=frm_message.id_utilisateur ) " .
      "LEFT JOIN utilisateurs AS `premier_auteur` ON ( premier_auteur.id_utilisateur=frm_sujet.id_utilisateur ) ".
      "LEFT JOIN utl_etu_utbm AS `dernier_auteur_etu_utbm` ON ( dernier_auteur_etu_utbm.id_utilisateur=frm_message.id_utilisateur ) " .
      "LEFT JOIN utl_etu_utbm AS `premier_auteur_etu_utbm` ON ( premier_auteur_etu_utbm.id_utilisateur=frm_sujet.id_utilisateur )" .
      "LEFT JOIN frm_sujet_utilisateur ".
        "ON ( frm_sujet_utilisateur.id_sujet=frm_sujet.id_sujet ".
        "AND frm_sujet_utilisateur.id_utilisateur='".$site->user->id."' ) ".
      "WHERE frm_sujet_utilisateur.etoile_sujet='1' ";

  if ( !$forum->is_admin( $site->user ) )
  {
    $grps = $site->user->get_groups_csv();
    $query .= "AND ((droits_acces_forum & 0x1) OR " .
      "((droits_acces_forum & 0x10) AND id_groupe IN ($grps)) OR " .
      "(id_groupe_admin IN ($grps)) OR " .
      "((droits_acces_forum & 0x100) AND frm_forum.id_utilisateur='".$site->user->id."')) ";
  }

  $query .= "ORDER BY frm_message.date_message DESC ";

  $req = new requete($site->db,$query);

  if ( $req->lines > 0 )
  {
    $rows = array();
    while ( $row = $req->get_row() )
    {
      if (($row['id_groupe'] != 7) || ($row['droits_acces_forum'] & 0x1) || ($site->user->is_in_group("root")))
        $rows[] = $row;
    }

    $cts->add(new sujetslist($rows, $site->user, "./", null, null,true));
    $cts->add_paragraph("&nbsp;");
  }
  else
    $cts->add_paragraph("Vous n'avez aucun sujet favoris.");

  $site->add_contents($cts);

  $site->end_page();
  exit();
}

if ( isset($_REQUEST["pattern"] ) )
{
  $site->start_page("forum","Recherche ".htmlentities($_REQUEST["pattern"],ENT_COMPAT,"UTF-8"));

  $url = "search.php?pattern=".urlencode($_REQUEST["pattern"]);

  $order_mess .= "ORDER BY date_message DESC ";
  $order_suj  .= "ORDER BY frm_last_message.date_message DESC ";
  if ( !empty($_REQUEST["pattern"]) )
  {
    /*
    // Pauvre mysql... trop lourd pour lui...
    if (isset($_REQUEST['regex']))
    {
      $url .= "&regex";
      $sql_conds = "WHERE (frm_message.titre_message REGEXP '".mysql_real_escape_string($_REQUEST["pattern"])."' OR contenu_message REGEXP '".mysql_real_escape_string($_REQUEST["pattern"])."') ";
    }
    else
      */
      $sql_conds = "WHERE MATCH (frm_message.titre_message,frm_message.contenu_message) AGAINST ('".mysql_real_escape_string($_REQUEST["pattern"])."' IN BOOLEAN MODE) ";

      if ($_REQUEST['order'] != "date")
      {
        $order_mess = "ORDER BY MATCH (frm_message.titre_message,frm_message.contenu_message) AGAINST ('".mysql_real_escape_string($_REQUEST["pattern"])."' IN BOOLEAN MODE) DESC, date_message DESC ";
        $order_suj = "ORDER BY MAX(MATCH (frm_message.titre_message,frm_message.contenu_message) AGAINST ('".mysql_real_escape_string($_REQUEST["pattern"])."' IN BOOLEAN MODE)) DESC, date_message DESC ";
      }
  }
  else
    $sql_conds = "WHERE 1 ";

  if ( !$forum->is_admin( $site->user ) )
  {
    $grps = $site->user->get_groups_csv();
    $sql_conds .= "AND ((droits_acces_forum & 0x1) OR " .
      "((droits_acces_forum & 0x10) AND id_groupe IN ($grps)) OR " .
      "(id_groupe_admin IN ($grps)) OR " .
      "((droits_acces_forum & 0x100) AND frm_forum.id_utilisateur='".$site->user->id."')) ";
  }

  if (!isset($_REQUEST['include_deleted']) || (!$site->user->is_in_group('root') && !$site->user->is_in_group('moderateur_forum')))
  {
    $url .= "&msg_supprime";
    $sql_conds .= "AND frm_message.msg_supprime='0' ";
  }
  if ($_REQUEST['begin_date'])
  {
    $url .= "&begin_date=".$_REQUEST['begin_date'];
    $sql_conds .= "AND frm_message.date_message > '".date("Y-m-d H:i",$_REQUEST['begin_date'])."' ";
  }
  if ($_REQUEST['end_date'])
  {
    $url .= "&end_date=".$_REQUEST['end_date'];
    $sql_conds .= "AND frm_message.date_message < '".date("Y-m-d H:i",$_REQUEST['end_date'])."' ";
  }
  if ($_REQUEST['id_utilisateur'])
  {
    $url .= "&id_utilisateur=".$_REQUEST['id_utilisateur'];
    $sql_conds .= "AND frm_message.id_utilisateur =  '".mysql_real_escape_string($_REQUEST['id_utilisateur'])."' ";
  }
  if ($_REQUEST['id_forum'])
  {
    $url .= "&id_forum=".$_REQUEST['id_forum'];
    $sql_conds .= "AND frm_sujet.id_forum = '".mysql_real_escape_string($_REQUEST['id_forum'])."' ";
  }

  $url .= "&display_type=".$_REQUEST['display_type'];
  $url .= "&order=".$_REQUEST['order'];
  $cts_res = new contents("Résultats de la recherche");

  if ($_REQUEST['display_type'] == "sujets")
  {
    $sql = "SELECT frm_sujet.*, ".
        "frm_last_message.date_message, " .
        "frm_last_message.id_message, " .
        "COALESCE(
          dernier_auteur_etu_utbm.surnom_utbm,
          CONCAT(dernier_auteur.prenom_utl,' ',dernier_auteur.nom_utl)
        ) AS `nom_utilisateur_dernier_auteur`, " .
        "dernier_auteur.id_utilisateur AS `id_utilisateur_dernier`, " .
        "COALESCE(
            premier_auteur_etu_utbm.surnom_utbm,
            CONCAT(premier_auteur.prenom_utl,' ',premier_auteur.nom_utl)
          ) AS `nom_utilisateur_premier_auteur`, " .
        "premier_auteur.id_utilisateur AS `id_utilisateur_premier`, " .
        "IF(frm_sujet.id_message_dernier > frm_sujet_utilisateur.id_message_dernier_lu,1,0) AS `nonlu`, " .
        "titre_forum AS `soustitre_sujet`, " .
        "frm_sujet_utilisateur.etoile_sujet AS etoile, " .
        "frm_forum.droits_acces_forum, ".
        "frm_forum.id_groupe ".
        "FROM frm_message " .
        "LEFT JOIN frm_sujet USING (id_sujet) ".
        "INNER JOIN frm_forum USING(id_forum) ".
        "LEFT JOIN frm_message frm_last_message ON ( frm_last_message.id_message = frm_sujet.id_message_dernier ) " .
        "LEFT JOIN utilisateurs AS `dernier_auteur` ON ( dernier_auteur.id_utilisateur=frm_last_message.id_utilisateur ) " .
        "LEFT JOIN utilisateurs AS `premier_auteur` ON ( premier_auteur.id_utilisateur=frm_sujet.id_utilisateur ) ".
        "LEFT JOIN utl_etu_utbm AS `dernier_auteur_etu_utbm` ON ( dernier_auteur_etu_utbm.id_utilisateur=frm_last_message.id_utilisateur ) " .
        "LEFT JOIN utl_etu_utbm AS `premier_auteur_etu_utbm` ON ( premier_auteur_etu_utbm.id_utilisateur=frm_sujet.id_utilisateur )" .
        "LEFT JOIN frm_sujet_utilisateur ".
          "ON ( frm_sujet_utilisateur.id_sujet=frm_sujet.id_sujet ".
          "AND frm_sujet_utilisateur.id_utilisateur='".$site->user->id."' ) ";

    $sql .= $sql_conds;

    $sql .= "GROUP BY frm_sujet.id_sujet ";
    $sql .= $order_suj;

    $req = new requete($site->db,$sql);

    if ( $req->lines > 0 )
    {
      $rows = array();
      while ( $row = $req->get_row() )
      {
        if (($row['id_groupe'] != 7) || ($row['droits_acces_forum'] & 0x1) || ($site->user->is_in_group("root")))
          $rows[] = $row;
      }

      $cts_res->add(new sujetslist($rows, $site->user, "./", null, null,true));
      $cts_res->add_paragraph("&nbsp;");
    }
    else
      $cts_res->add_paragraph("Aucun résultat trouvé.");
  }
  else
  {
    $sql_count =  "SELECT COUNT(*) FROM frm_message ";
    $sql_count .= $sql_conds;
    $req = new requete($site->db,$sql_count);
    if ( $req->lines > 0 )
      list($mess_count) =  $req->get_row();

    $sql = "SELECT frm_sujet.*, frm_message.*, frm_forum.id_groupe, frm_forum.droits_acces_forum, ".
            "COALESCE( utl_etu_utbm.surnom_utbm, CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl)) AS alias_utl, " .
            "utilisateurs.id_utilisateur, utilisateurs.signature_utl " .
            "FROM frm_message " .
            "INNER JOIN frm_sujet USING ( id_sujet ) ".
            "INNER JOIN frm_forum USING (id_forum) ".
            "LEFT JOIN utilisateurs ON ( utilisateurs.id_utilisateur=frm_message.id_utilisateur ) ".
            "LEFT JOIN utl_etu_utbm ON ( utl_etu_utbm.id_utilisateur=frm_message.id_utilisateur ) ";

    $sql .= $sql_conds;

    $sql .= $order_mess;

    if (isset($_REQUEST['first']) && intval($_REQUEST['first']) > 0)
    {
      $sql .= "LIMIT ".intval($_REQUEST['first']).", 50";
      $first = intval($_REQUEST['first']);
    }
    else
    {
      $sql .= "LIMIT 50";
      $first = 0;
    }

    $req = new requete($site->db,$sql);

    $id_sujet=null;

    if ( $req->lines > 0 )
    {
      $n=0;
      $i=0;
      while ( $row = $req->get_row() )
      {
        $i++;
        if (($row['id_groupe'] != 7) || ($row['droits_acces_forum'] & 0x1) || ($site->user->is_in_group("root")))
        {
          if ( $id_sujet!=$row['id_sujet'] )
          {
            $cts_res->add_title(2, "<a href=\"".$wwwtopdir."forum2/?id_sujet=".$row['id_sujet']."\">".
              "<img src=\"".$wwwtopdir."images/icons/16/sujet.png\" class=\"icon\" alt=\"\" /> ".$row['titre_sujet']."</a>");
            $id_sujet = $row['id_sujet'];
          }

          $buffer = "";

          if ( $i == $req->lines )
            $buffer .= "<div id=\"lastmessage\"></div>";

          $t = strtotime($row['date_message']);

          if ($row['msg_supprime'])
            $buffer .= "<div class=\"fmsgentry deleted\" id=\"msg".$row['id_message']."\">\n";
          elseif ( $n )
            $buffer .= "<div class=\"fmsgentry pair\" id=\"msg".$row['id_message']."\">\n";
          else
            $buffer .= "<div class=\"fmsgentry\" id=\"msg".$row['id_message']."\">\n";
          $n=($n+1)%2;

          /* permalink */
          $buffer .= "<a href=\"./?id_message=".
          $row['id_message']."#msg".$row['id_message']."\">";

          if ($row['msg_supprime'])
          {
            if ( $row['titre_message'] )
              $buffer .= "<h2 class=\"frmt\">Message supprimé: ".htmlentities($row['titre_message'], ENT_NOQUOTES, "UTF-8")."</h2>\n";
            else
              $buffer .= "<h2 class=\"frmt\">Message supprimé</h2>\n";
          }
          else
          {
            if ( $row['titre_message'] )
              $buffer .= "<h2 class=\"frmt\">".htmlentities($row['titre_message'], ENT_NOQUOTES, "UTF-8")."</h2>\n";
            else
              $buffer .= "<h2 class=\"frmt\">&nbsp;</h2>\n";
          }

          $buffer .= "<p class=\"date\">".human_date($t)."</p>\n";
          $buffer .= "</a>";


          $buffer .= "<div class=\"auteur\">\n";

          $buffer .= "<p class=\"funame\"><a href=\"#top\"><img src=\"".$topdir."images/forum/top.png\" /></a>&nbsp;&nbsp;<a href=\"".$wwwtopdir."user.php?id_utilisateur=".$row['id_utilisateur']."\">".htmlentities($row['alias_utl'],ENT_NOQUOTES,"UTF-8")."</a></p>\n";

          $img=null;
          if (file_exists($topdir."data/matmatronch/".$row['id_utilisateur'].".jpg"))
            $img = $wwwtopdir."data/matmatronch/".$row['id_utilisateur'].".jpg";

          if ( !is_null($img) )
            $buffer .= "<p class=\"fuimg\"><img src=\"".htmlentities($img,ENT_NOQUOTES,"UTF-8")."\" /></p>\n";

          $buffer .= "</div>\n";
          $buffer .= "<div class=\"fmsg\">\n";

          $msg_uid = "msg".$row['id_message'];

          if ( isset($_COOKIE["nosecret"]) && $_COOKIE["nosecret"] == 1 )
          {
            $msg_uid .= "nsc";
            $cache = new cachedcontents($msg_uid);
            if (! $cache->is_cached())
              $row['contenu_message'] = nosecret($row['contenu_message']);
          }

          if ( $row['syntaxengine_message'] == "bbcode" )
          //  $buffer .= bbcode($row['contenu_message']);
          {
            $cts = cachedcontents::autocache($msg_uid,new bbcontents("",$row['contenu_message'],false));
            $buffer .= $cts->html_render();
          }
          elseif ( $row['syntaxengine_message'] == "doku" )
          //  $buffer .= doku2xhtml($row['contenu_message']);
          {
            $cts = cachedcontents::autocache($msg_uid,new wikicontents("",$row['contenu_message'],false));
            $buffer .= $cts->html_render();
          }
          elseif ( $row['syntaxengine_message'] == "plain" )
            $buffer .= "<pre>".htmlentities($row['contenu_message'],ENT_NOQUOTES,"UTF-8")."</pre>";

          else // text
            $buffer .= nl2br(htmlentities($row['contenu_message'],ENT_NOQUOTES,"UTF-8"));

          if ($row['msg_modere_info'] && ($forum->is_admin($site->user)))
          {
            $modere_info = $forum->get_modere_info($row['id_message']);
            foreach($modere_info as $info)
              $buffer .= "<div class=\"".$info[0]."\">".$info[1]."</div>\n";
          }

          if ( !is_null($row['signature_utl']) )
          {
            $buffer .= "<div class=\"signature\">\n";
            //$buffer .= doku2xhtml($row['signature_utl']);
            $cts = cachedcontents::autocache("sig".$row['id_utilisateur'],new wikicontents("",$row['signature_utl'],false));
            $buffer .= $cts->html_render();
            $buffer .= "</div>\n";
          }

          $buffer .= "</div>\n";
          $buffer .= "<div class=\"clearboth\"></div>\n";
          $buffer .= "</div>\n";
          $cts_res->puts($buffer);
        }
      }

      $page_idx = 0;
      $tabs = array();
      while(50 * $page_idx < $mess_count)
      {
        $tabs[] = array("page_".$page_idx, "forum2/".$url."&first=".(50*$page_idx), $page_idx+1);
        $page_idx++;
      }

      $cts_res->add(new tabshead($tabs, "page_".$first/50, "_bottom"));
    }
    else
      $cts_res->add_paragraph("Aucun résultat trouvé.");
  }
}

$site->start_page("forum","Recherche");

$cts = new contents($forum->get_html_link()." / <a href=\"search.php\">Recherche</a> / <a href=\"".$url."\">".htmlentities($_REQUEST["pattern"],ENT_COMPAT,"UTF-8")."</a>");

if ($site->user->is_in_group('root') || $site->user->is_in_group('moderateur_forum'))
  $sql = "SELECT id_forum, titre_forum FROM frm_forum ORDER BY titre_forum";
else
{
  $grps = $site->user->get_groups_csv();
  $sql = "SELECT id_forum, titre_forum FROM frm_forum ".
    "WHERE ((droits_acces_forum & 0x1) OR " .
    "((droits_acces_forum & 0x10) AND id_groupe IN ($grps)) OR " .
    "(id_groupe_admin IN ($grps)) OR " .
    "((droits_acces_forum & 0x100) AND frm_forum.id_utilisateur='".$site->user->id."')) ".
    "ORDER BY titre_forum";
}

$forum_cats = array(null=>"(Tous)");
$req = new requete($site->db, $sql);
while( list($value,$name) = $req->get_row()){
  $forum_cats[$value] = $name;
}

$frm = new form("frmsearch",$wwwtopdir."forum2/search.php", true);
$frm->add_text_field("pattern","Recherche");
//$frm->add_checkbox("regex", "Utiliser une expression régulière");
$frm->add_entity_smartselect("id_utilisateur", "Auteur", new utilisateur($site->db), true, false);
$frm->add_date_field("begin_date", "Posté après");
$frm->add_date_field("end_date", "Posté avant");
$frm->add_select_field('id_forum', 'Forum : ', $forum_cats);

if ($site->user->is_in_group('root') || $site->user->is_in_group('moderateur_forum'))
  $frm->add_checkbox("include_deleted", "Rechercher dans les messages supprimés", (!isset($_REQUEST["pattern"]) || isset($_REQUEST["include_deleted"])));

$frm->add_radiobox_field("display_type", "Type d'affichage", array("messages"=>"Afficher les messages", "sujets"=>"Afficher les sujets"), "messages");
$frm->add_radiobox_field("order", "Tri", array("pertinence"=>"Tri par pertinence", "date"=>"Tri par date"), "pertinence");
$frm->add_submit("search","Rechercher");
$frm->set_focus("pattern");
$cts->add($frm);

$site->add_contents($cts);
if (isset($cts_res))
  $site->add_contents($cts_res);

$site->end_page();
exit();

?>
