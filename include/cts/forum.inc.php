<?php
/* Copyright 2007
 * - Julien Etelain < julien at pmad dot net >
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
 * @defgroup display_cts_forum Contents forum
 * Contents pour le rendu des pages du forum
 * @ingroup display_cts
 */

require_once($topdir."include/lib/bbcode.inc.php");
require_once($topdir."include/cts/cached.inc.php");

function human_date ( $timestamp )
{
  if ( date("d/m/Y",$timestamp) == date("d/m/Y",time()) )
    return "Aujourd'hui ".date("H:i",$timestamp);

  if ( date("d/m/Y",$timestamp) == date("d/m/Y",time()-86400 ) )
    return "Hier ".date("H:i",$timestamp);

  return date("d/m/Y H:i",$timestamp);
}

/**
 * @ingroup useless
 * @author Julien Etelain
 */
function nosecret_findname ( $matches )
{
  global $site;

  if ( preg_match("`^__([a-zA-z0-9]*)__$`",$matches[2]) )
    return $matches[1];

  $key = strtolower($matches[2]);

  if ( isset($GLOBALS["nosecret_cache"][$key]) )
    return $matches[1].$GLOBALS["nosecret_cache"][$key].$matches[4];

  $sqlpattern = mysql_real_escape_string(str_replace(" ", " ?", str_replace("_","([aeiouy]|é)",str_replace("[","",$matches[2]))))."([`\\\\\']?)";

  $sql = "SELECT `alias_utl`, prenom_utl, nom_utl, utilisateurs.id_utilisateur  " .
          "FROM `utilisateurs` " .
          "WHERE `alias_utl`!='' AND `alias_utl` REGEXP '^".$sqlpattern."$' " .

          "UNION SELECT `surnom_utbm`, prenom_utl, nom_utl, utilisateurs.id_utilisateur " .
          "FROM `utl_etu_utbm` " .
          "INNER JOIN `utilisateurs` ON `utl_etu_utbm`.`id_utilisateur` = `utilisateurs`.`id_utilisateur` " .
          "WHERE `surnom_utbm`!='' ".
          "AND (`surnom_utbm`!=`alias_utl` OR `alias_utl` IS NULL) ".
          "AND `surnom_utbm` REGEXP '^".$sqlpattern."$' " .
          "ORDER BY 1";

  $req = new requete($site->db,$sql);

  if ( !$req || $req->lines == 0 )
    $result=$matches[2];
  else
  {
    $values=array();
    while ( $row = $req->get_row() )
      $values[] = $row[0]." : ".$row['prenom_utl']." ".$row['nom_utl'];
    $result=$matches[2]."(".implode(", ",$values).")";
  }

  $GLOBALS["nosecret_cache"][$key]=$result;

  return $matches[1].$result.$matches[4];
}

/**
 * @ingroup useless
 * @author Julien Etelain
 */
function nosecret ( $text )
{
  return preg_replace_callback("`([^a-zA-Z0-9]|^)([bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ0-9\-]*_([bcdfghjklmnpqrstvwxzBCDFGHJKLMNPQRSTVWXZ0-9_\-]| _)*)([^a-zA-Z0-9]|$)`","nosecret_findname",$text);
}


/**
 * Affiche la liste des sous-forums d'un forum
 * @ingroup display_cts_forum
 * @author Julien Etelain
 */
class forumslist extends stdcontents
{


  function forumslist ( &$forum, &$user, $page )
  {

    $rows = $forum->get_sub_forums($user);
    if(sizeof($rows) != 0)
    {

      $sections=true;

      foreach ( $rows as $row )
      {
        if ( !$row['categorie_forum'] )
          $sections = false;
      }
      $this->buffer .= "<div class=\"forumlist\">\n";

      if (!defined ("MOBILE")) {
        $this->buffer .= "<div class=\"forumhead\">\n";
        $this->buffer .= "<p class=\"nbsujets\">Sujets</p>\n";
        $this->buffer .= "<p class=\"dernier\">Dernier message</p>\n";
        $this->buffer .= "</div>\n";
      }

      if ( $sections )
      {
        $sforum = new forum ( $forum->db );

        foreach ( $rows as $row )
        {
          $sforum->_load($row);
          $srows = $sforum->get_sub_forums($user,$row["non_lu"]);
          $this->_render_section ( $sforum, $srows, $page );
        }
      }
      else
        $this->_render_section ( $forum, $rows, $page );

      $this->buffer .= "</div>\n";

    }
  }

  function _render_section ( &$forum, &$rows, $page )
  {
    $this->buffer .= "<div class=\"forumsection\">\n";

    if($forum->categorie)
      $this->buffer .= "<h2>".htmlentities($forum->titre,ENT_NOQUOTES,"UTF-8")."</h2>\n";
    else
      if(sizeof($rows) == 1)
        $this->buffer .= "<h2>Sous-Forum</h2>\n";
      else
        $this->buffer .= "<h2>Sous-Forums</h2>\n";

    foreach ( $rows as $row )
    {
      if ( $row["non_lu"] )
        $this->buffer .= "<div class=\"forumitem nonlu\">\n";
      else
        $this->buffer .= "<div class=\"forumitem\">\n";



      $this->buffer .= "<h3><a href=\"".$page."?id_forum=".$row['id_forum']."\">".
                       htmlentities($row['titre_forum'], ENT_NOQUOTES, "UTF-8")."</a></h3>\n";

if (!defined ("MOBILE")) {
      if ( $row['description_forum'] )
        $this->buffer .= "<p class=\"description\">".htmlentities($row['description_forum'],ENT_NOQUOTES,"UTF-8")."</p>\n";
      else
        $this->buffer .= "<p class=\"description\">&nbsp;</p>\n";

      $this->buffer .= "<p class=\"nbsujets\">".$row['nb_sujets_forum']."<br/>&nbsp;</p>\n";
}

      if ( !is_null($row['id_message']) )
      {
        if ( strlen($row['titre_sujet']) > 20 )
          $row['titre_sujet'] = substr($row['titre_sujet'],0,17)."...";
        $this->buffer .= "<p class=\"dernier\">".htmlentities($row['titre_sujet'],ENT_NOQUOTES,"UTF-8").
          "<br/><a href=\"".$page."?id_message=".$row['id_message']."#msg".$row['id_message']."\">".
          htmlentities($row['nom_utilisateur_dernier_auteur'],ENT_NOQUOTES,"UTF-8")." ".
          human_date(strtotime($row['date_message']))."</a></p>\n";
      }
      $this->buffer .= "</div>\n";
    }

    $this->buffer .= "</div>\n";

  }


}

/**
 * Affiche la liste des sujets d'un forum(support pagination), ou une liste de
 * sujets issue d'une recherche
 * @ingroup display_cts_forum
 * @see forum
 * @see sujet
 */
class sujetslist extends stdcontents
{


  function sujetslist ( &$forum, &$user, $page, $start, $npp, $gotounread=true )
  {
    global $wwwtopdir;

    if ( is_array($forum) )
      $rows = $forum;
    else
      $rows = $forum->get_sujets($user, $start, $npp);

    /*if ( $gotounread && $user->is_valid() )
      $this->buffer .= "<p>Remarque: En cliquant sur le nom du sujet vous irez directement au premier message non lu</p>\n";*/

    $this->buffer .= "<div class=\"forumsujetsliste\">\n";

if (!defined ("MOBILE")) {
    $this->buffer .= "<div class=\"forumhead\">\n";
    $this->buffer .= "<p class=\"auteur\">Auteur</p>\n";
    $this->buffer .= "<p class=\"nbmessages\">Réponses</p>\n";
    $this->buffer .= "<p class=\"dernier\">Dernier message</p>\n";
    $this->buffer .= "</div>\n";
}

    foreach ( $rows as $row )
    {

      if ( $row['nonlu'] )
        $this->buffer .= "<div class=\"forumsujet nonlu\">\n";
      else
        $this->buffer .= "<div class=\"forumsujet\">\n";

      if ( $row['nonlu'] && $gotounread && $user->is_valid() )
        $this->buffer .= "<h2><a href=\"".$page."?id_sujet=".$row['id_sujet']."&amp;spage=firstunread#firstunread\">".
                         htmlentities($row['titre_sujet'], ENT_NOQUOTES, "UTF-8")."</a></h2>\n";
      else

        $this->buffer .= "<h2><a href=\"".$page."?id_sujet=".$row['id_sujet']."\">".
                         htmlentities($row['titre_sujet'], ENT_NOQUOTES, "UTF-8")."</a></h2>\n";

if (!defined ("MOBILE")) {
      if ( !$row['soustitre_sujet'] )
        $this->buffer .= "<p class=\"soustitre\">&nbsp;</p>\n";
      else
        $this->buffer .= "<p class=\"soustitre\">".htmlentities($row['soustitre_sujet'],ENT_NOQUOTES,"UTF-8")."</p>\n";

      if ( $row['etoile'] )
        $this->buffer .= "<p class=\"sujeticon\"><img src=\"".$wwwtopdir."images/icons/16/star.png\" /></p>\n";
      elseif ( $row['type_sujet'] == 2 )
        $this->buffer .= "<p class=\"sujeticon\"><img src=\"".$wwwtopdir."images/icons/16/sujet2.png\" /></p>\n";
      else
        $this->buffer .= "<p class=\"sujeticon\"><img src=\"".$wwwtopdir."images/icons/16/sujet.png\" /></p>\n";


      /* actions */
      if ( !is_array($forum) )
      if (($user->is_valid() && $user->id == $row['id_utilisateur']) ||($forum->is_admin($user)))
      {
        $this->buffer .= "<p class=\"actions\">";

        $this->buffer .= "<a href=\"?id_sujet=".$row['id_sujet']."&amp;page=delete\">Supprimer</a>";
        $this->buffer .= " | <a href=\"?id_sujet=".$row['id_sujet']."&amp;page=edit\">Editer</a>";
        $this->buffer .= "</p>\n";
      }

      if ( !$row['nom_utilisateur_premier_auteur'] )
        $this->buffer .= "<p class=\"auteur\">&nbsp;</p>\n";
      else
        $this->buffer .= "<p class=\"auteur\">".htmlentities($row['nom_utilisateur_premier_auteur'],ENT_NOQUOTES,"UTF-8")."</p>\n";

      $this->buffer .= "<p class=\"nbmessages\">".($row['nb_messages_sujet']-1)."</p>\n";
}

      if ( !is_null($row['id_message']) )
        $this->buffer .= "<p class=\"dernier\"><a href=\"".$page."?id_message=".$row['id_message']."#msg".$row['id_message']."\">".htmlentities($row['nom_utilisateur_dernier_auteur'],ENT_NOQUOTES,"UTF-8")." ".human_date(strtotime($row['date_message']))."</a></p>\n";

      $this->buffer .= "</div>\n";
    }
    $this->buffer .= "</div>\n";
  }




}

/**
 * Affiche le contenu d'un sujet du forum (support pagination)
 * @ingroup display_cts_forum
 */
class sujetforum extends stdcontents
{

  /*function wikimacro($text)
  {
    global $site;
    if ( $text == "user" )
    {
      $buffer = $site->user->alias;
      return $buffer;
    }
    return $text;
  }*/

  function sujetforum (&$forum, &$sujet, &$user, $page, $start, $npp, $order = "ASC" )
  {
    global $topdir, $wwwtopdir, $conf;

    //$conf["macrofunction"] = array($this,'wikimacro');

    if ( $user->is_valid() )
      $last_read = $sujet->get_last_read_message ( $user->id );
    else
      $last_read = null;

    $rows = $sujet->get_messages ( $user, $start, $npp, $order, $forum->is_admin($user));

    $this->buffer .= "<div class=\"fmsgsliste\">\n";

    $firstunread=true;

    $initial = ($start==0 && $order=="ASC");

    $n=0;
    $i=0;
    foreach ( $rows as $row )
    {
      $i++;
      if ( $i == count($rows) )
        $this->buffer .= "<div id=\"lastmessage\"></div>";


      $t = strtotime($row['date_message']);

      if ( $user->is_valid() &&
      ( is_null($last_read) || $last_read < $row['id_message'] ) &&
      ( is_null($user->tout_lu_avant) || $t > $user->tout_lu_avant ) )
      {
        if ($row['msg_supprime'])
          $this->buffer .= "<div class=\"fmsgentry deleted\" id=\"msg".$row['id_message']."\">\n";
          else
            $this->buffer .= "<div class=\"fmsgentry nonlu\" id=\"msg".$row['id_message']."\">\n";

        if ( $firstunread )
        {
          $firstunread=false;
          $this->buffer .= "<div id=\"firstunread\"></div>";
        }

  /* permalink */
  $this->buffer .= "<a href=\"./?id_message=".
    $row['id_message']."#msg".$row['id_message']."\">";

        if ( $row['titre_message'] )
          $this->buffer .= "<h2 class=\"frmt\">Message non lu: ".htmlentities($row['titre_message'], ENT_NOQUOTES, "UTF-8")."</h2>\n";
        else
          $this->buffer .= "<h2 class=\"frmt\">Message non lu</h2>\n";


      }
      else
      {
        if ($row['msg_supprime'])
          $this->buffer .= "<div class=\"fmsgentry deleted\" id=\"msg".$row['id_message']."\">\n";
        elseif ( $n )
          $this->buffer .= "<div class=\"fmsgentry pair\" id=\"msg".$row['id_message']."\">\n";
        else
          $this->buffer .= "<div class=\"fmsgentry\" id=\"msg".$row['id_message']."\">\n";
        $n=($n+1)%2;

  /* permalink */
  $this->buffer .= "<a href=\"./?id_message=".
    $row['id_message']."#msg".$row['id_message']."\">";

        if ($row['msg_supprime'])
        {
          if ( $row['titre_message'] )
            $this->buffer .= "<h2 class=\"frmt\">Message supprimé: ".htmlentities($row['titre_message'], ENT_NOQUOTES, "UTF-8")."</h2>\n";
          else
            $this->buffer .= "<h2 class=\"frmt\">Message supprimé</h2>\n";
        }
        else
        {
          if ( $row['titre_message'] )
            $this->buffer .= "<h2 class=\"frmt\">".htmlentities($row['titre_message'], ENT_NOQUOTES, "UTF-8")."</h2>\n";
          else
            $this->buffer .= "<h2 class=\"frmt\">&nbsp;</h2>\n";
        }
      }

      $this->buffer .= "<p class=\"date\">".human_date($t)."</p>\n";
      $this->buffer .= "</a>";

       /* actions sur un message */
      $this->buffer .= "<p class=\"actions\">";

       /* utilisateur authentifié */
      if ($user->is_valid())
      {
        $this->buffer .= "<a href=\"?page=reply&amp;id_message=".$row['id_message'].
           "&amp;quote=1\">Répondre en citant</a>";
      }

      if (($user->is_valid() && $user->id == $row['id_utilisateur']) ||($forum->is_admin($user)))
      {
        if ( $initial ) // Pour le message initial, renvoie vers le sujet
        {
          $spage = ceil($start/$npp);
          $this->buffer .= " | <a href=\"?page=edit&amp;id_sujet=".$sujet->id."\">Modifier</a> | ".
             "<a href=\"?page=delete&amp;id_sujet=".$sujet->id."&amp;spage=$spage\">Supprimer</a>";
        }
        elseif (!$row['msg_supprime'])
        {
          $spage = ceil($start/$npp);
          $this->buffer .= " | <a href=\"?page=edit&amp;id_message=".$row['id_message']."\">Modifier</a> | ".
             "<a href=\"?page=delete&amp;id_message=".$row['id_message']."&amp;spage=$spage\">Supprimer</a>";
        }
        elseif ($user->is_in_group("moderateur_forum"))
        {
          $spage = ceil($start/$npp);
           $this->buffer .= " | <a href=\"?page=undelete&amp;id_message=".$row['id_message']."&amp;spage=$spage\">Rétablir</a>";
        }
      }

      $this->buffer .= "</p>\n";

      $this->buffer .= "<div class=\"auteur\">\n";

      $this->buffer .= "<p class=\"funame\"><a href=\"#top\"><img src=\"".$topdir."images/forum/top.png\" /></a>&nbsp;&nbsp;<a href=\"".$wwwtopdir."user.php?id_utilisateur=".$row['id_utilisateur']."\">".htmlentities($row['alias_utl'],ENT_NOQUOTES,"UTF-8")."</a></p>\n";

      $img=null;
      if (file_exists($topdir."data/matmatronch/".$row['id_utilisateur'].".jpg"))
        $img = $wwwtopdir."data/matmatronch/".$row['id_utilisateur'].".jpg";

      if ( !is_null($img) )
        $this->buffer .= "<p class=\"fuimg\"><img src=\"".htmlentities($img,ENT_NOQUOTES,"UTF-8")."\" /></p>\n";


      $this->buffer .= "</div>\n";
      $this->buffer .= "<div class=\"fmsg\">\n";

      $msg_uid = "msg".$row['id_message'];

      if ( isset($_COOKIE["nosecret"]) && $_COOKIE["nosecret"] == 1 )
      {
        $msg_uid .= "nsc";
        $cache = new cachedcontents($msg_uid);
        if (! $cache->is_cached())
          $row['contenu_message'] = nosecret($row['contenu_message']);
      }

      if ( $row['syntaxengine_message'] == "bbcode" )
      //  $this->buffer .= bbcode($row['contenu_message']);
      {
        $cts = cachedcontents::autocache($msg_uid,new bbcontents("",$row['contenu_message'],false));
        $this->buffer .= $cts->html_render();
      }
      elseif ( $row['syntaxengine_message'] == "doku" )
      //  $this->buffer .= doku2xhtml($row['contenu_message']);
      {
        $cts = cachedcontents::autocache($msg_uid,new wikicontents("",$row['contenu_message'],false));
        $this->buffer .= $cts->html_render();
      }
      elseif ( $row['syntaxengine_message'] == "plain" )
        $this->buffer .= "<pre>".htmlentities($row['contenu_message'],ENT_NOQUOTES,"UTF-8")."</pre>";

      else // text
        $this->buffer .= nl2br(htmlentities($row['contenu_message'],ENT_NOQUOTES,"UTF-8"));

      if ($row['msg_modere_info'] && ($forum->is_admin($user)))
      {
        $modere_info = $forum->get_modere_info($row['id_message']);
        foreach($modere_info as $info)
          $this->buffer .= "<div class=\"".$info[0]."\">".$info[1]."</div>\n";
      }

      if ( !is_null($row['signature_utl']) )
      {
        $this->buffer .= "<div class=\"signature\">\n";
        //$this->buffer .= doku2xhtml($row['signature_utl']);
        $cts = cachedcontents::autocache("sig".$row['id_utilisateur'],new wikicontents("",$row['signature_utl'],false));
        $this->buffer .= $cts->html_render();
        $this->buffer .= "</div>\n";
      }

      $this->buffer .= "</div>\n";
      $this->buffer .= "<div class=\"clearboth\"></div>\n";
      $this->buffer .= "</div>\n";
      $initial=false;
    }
    $this->buffer .= "</div>\n";
  }

}

/**
 * Affiche un message du forum
 * @ingroup display_cts_forum
 */
class simplemessageforum extends stdcontents
{

  /**
   * @ingroup useless
   * @author Benjamin Collet
   */
  /*function macroforum($text)
  {
    global $site;
    if ( $text == "user" )
    {
      $buffer = $site->user->alias;
      return $buffer;
    }
    return $text;
  }*/

  function simplemessageforum($message)
  {
      global $topdir, $wwwtopdir, $site;

      //$conf["macrofunction"] = array($this,'macroforum');

      $this->title = "Prévisualisation";

      $t = $message->date;

      $sql = new requete($site->db, "SELECT
        COALESCE(
          utl_etu_utbm.surnom_utbm,
          CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl)
        ) AS alias_utl,
        signature_utl
        FROM utilisateurs
        LEFT JOIN utl_etu_utbm ON (utilisateurs.id_utilisateur=utl_etu_utbm.id_utilisateur)
        WHERE utilisateurs.id_utilisateur=$message->id_utilisateur LIMIT 1 ");
      $row = $sql->get_row();

      $this->buffer .= "<div class=\"fmsgentry\" id=\"msg".$message->id."\">\n";


    /* permalink */
     $this->buffer .= "<a href=\"./?id_message=".
    $message->id."#msg".$message->id."\">";

        if ( $message->titre )
          $this->buffer .= "<h2 class=\"frmt\">".htmlentities($message->titre, ENT_NOQUOTES, "UTF-8")."</h2>\n";
        else
          $this->buffer .= "<h2 class=\"frmt\">&nbsp;</h2>\n";


      $this->buffer .= "<p class=\"date\">".human_date($t)."</p>\n";
      $this->buffer .= "</a>";

  /* ici ont été supprimées les actions sur le message */

      $this->buffer .= "<div class=\"auteur\">\n";

      $this->buffer .= "<p class=\"funame\"><a href=\"".$wwwtopdir."user.php?id_utilisateur=".$message->id_utilisateur."\">".htmlentities($row['alias_utl'], ENT_NOQUOTES,"UTF-8")."</a></p>\n";

      $img=null;
      if (file_exists($topdir."data/matmatronch/".$message->id_utilisateur.".jpg"))
        $img = $wwwtopdir."data/matmatronch/".$message->id_utilisateur.".jpg";

      if ( !is_null($img) )
        $this->buffer .= "<p class=\"fuimg\"><img src=\"".htmlentities($img,ENT_NOQUOTES,"UTF-8")."\" /></p>\n";


      $this->buffer .= "</div>\n";
      $this->buffer .= "<div class=\"fmsg\">\n";

      if ( isset($_COOKIE["nosecret"]) && $_COOKIE["nosecret"] == 1 )
        $message->contenu = nosecret($message->contenu);

      if ( $message->syntaxengine == "bbcode" )
        $this->buffer .= bbcode($message->contenu);

      elseif ( $message->syntaxengine == "doku" )
        $this->buffer .= doku2xhtml($message->contenu);

      elseif ( $message->syntaxengine == "plain" )
        $this->buffer .= "<pre>".htmlentities($message->contenu,ENT_NOQUOTES,"UTF-8")."</pre>";

      else // text
        $this->buffer .= nl2br(htmlentities($message->contenu,ENT_NOQUOTES,"UTF-8"));

      if ( !is_null($row['signature_utl']) )
      {
        $this->buffer .= "<div class=\"signature\">\n";
        $this->buffer .= doku2xhtml($row['signature_utl']);
        $this->buffer .= "</div>\n";
      }

      $this->buffer .= "</div>\n";
      $this->buffer .= "<div class=\"clearboth\"></div>\n";
      $this->buffer .= "</div>\n";
  }
}



?>
