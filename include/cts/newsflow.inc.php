<?php
/* Copyright 2006,2007
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
 * @file
 */

require_once($topdir."include/entities/news.inc.php");
require_once($topdir."include/cts/cached.inc.php");

/**
 * Affiche une liste de nouvelles à la manière de la page d'acceuil du site,
 * mais sous forme d'une liste simple.
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class newslist extends itemlist
{
  /**
   * Génère une lsite de nouvelle
   * @param $title Titre
   * @param $db Connecion à la base de donnée
   * @param $cond condition SQl sur les nouvelles à afficher
   * @param $nb nombre de nouvelles à lister
   */
  function  newslist ( $title, $db, $cond=false, $nb=10, $class="boxnewslist" )
  {
    global $topdir;
    $this->title = $title;
    $this->class=$class;

    $sql = new requete($db,"SELECT * FROM nvl_nouvelles " .
        "INNER JOIN nvl_dates ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
        "WHERE nvl_nouvelles.type_nvl='".NEWS_TYPE_APPEL."' AND modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
        "NOW() > nvl_dates.date_debut_eve AND NOW() < nvl_dates.date_fin_eve");

    while ( $row = $sql->get_row() )
    {
      if ( strlen($row["titre_nvl"]) > 35 )
        $row["titre_nvl"] = substr($row["titre_nvl"],0,32)."...";

      $titre = "<a href=\"".$topdir."news.php?id_nouvelle=".$row["id_nouvelle"]."\">".
        htmlentities($row["titre_nvl"],ENT_NOQUOTES,"UTF-8").
        "</a>";

      $this->add($titre);
    }

    $sql = new requete($db,"SELECT nvl_nouvelles.*,asso.nom_unix_asso FROM nvl_nouvelles " .
        "LEFT JOIN asso ON asso.id_asso = nvl_nouvelles.id_asso " .
        "WHERE type_nvl='".NEWS_TYPE_NOTICE."' AND modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
        "DATEDIFF(NOW(),date_nvl) < 14 " .
        "LIMIT 3");

    while ( $row = $sql->get_row() )
    {
      if ( strlen($row["titre_nvl"]) > 35 )
        $row["titre_nvl"] = substr($row["titre_nvl"],0,32)."...";

      $titre = "<a href=\"".$topdir."news.php?id_nouvelle=".$row["id_nouvelle"]."\">".
        htmlentities($row["titre_nvl"],ENT_NOQUOTES,"UTF-8").
        "</a>";

      $this->add($titre);
    }

    $ids = array(0);

    $sql = new requete($db,"SELECT nvl_nouvelles.*,asso.nom_unix_asso,nvl_dates.date_debut_eve,nvl_dates.date_fin_eve " .
        "FROM nvl_dates " .
        "INNER JOIN  nvl_nouvelles ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
        "LEFT JOIN asso ON asso.id_asso = nvl_nouvelles.id_asso " .
        "WHERE (type_nvl='".NEWS_TYPE_EVENT."' "./*OR type_nvl='".NEWS_TYPE_HEBDO."'*/") AND  modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
        "NOW() < nvl_dates.date_fin_eve " .
        "ORDER BY nvl_dates.date_debut_eve " .
        "LIMIT 5");

    while ( $row = $sql->get_row() )
    {
      if ( strlen($row["titre_nvl"]) > 30 )
        $row["titre_nvl"] = substr($row["titre_nvl"],0,27)."...";

      $titre = date("d/m",strtotime($row["date_debut_eve"]))." - <a href=\"".$topdir."news.php?id_nouvelle=".$row["id_nouvelle"]."\">".
        htmlentities($row["titre_nvl"],ENT_NOQUOTES,"UTF-8").
        "</a>";


      $this->add($titre);
      $ids[]=$row["id_nouvelle"];
    }

    $sql = new requete($db,"SELECT nvl_nouvelles.*,asso.nom_unix_asso,nvl_dates.date_debut_eve,nvl_dates.date_fin_eve " .
        "FROM nvl_dates " .
        "INNER JOIN  nvl_nouvelles ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
        "LEFT JOIN asso ON asso.id_asso = nvl_nouvelles.id_asso " .
        "WHERE type_nvl='".NEWS_TYPE_EVENT."' AND  modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
        "nvl_dates.id_nouvelle NOT IN (".implode(",",$ids).") AND " .
        "NOW() < nvl_dates.date_debut_eve " .
        "ORDER BY nvl_dates.date_debut_eve " .
        "LIMIT 10");

    while ( $row = $sql->get_row() )
    {
      if ( strlen($row["titre_nvl"]) > 30 )
        $row["titre_nvl"] = substr($row["titre_nvl"],0,27)."...";

      $titre = date("d/m",strtotime($row["date_debut_eve"]))." - <a href=\"".$topdir."news.php?id_nouvelle=".$row["id_nouvelle"]."\">".
        htmlentities($row["titre_nvl"],ENT_NOQUOTES,"UTF-8").
        "</a>";

      $this->add($titre);
      $ids[]=$row["id_nouvelle"];
    }


  }
}


/**
 * Permet d'afficher une liste de nouvelles simple.
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
abstract class newslister extends stdcontents
{
  var $ids;

  function appel_list ( $sql )
  {
    global $topdir;
    $other_buffer = new stdcontents();
    if ( $sql->lines > 0 )
    {
      $this->puts("<div class=\"newsappel\" id=\"newsappel\">\n", $other_buffer);
      $this->puts("<div id=\"hide_apples\">\n", $other_buffer);
      $this->puts("<a href=\"#\" onclick=\"hide_with_cookies('newsappel', 'AE2_HIDE_APPLES'); return false;\">", $other_buffer);
      $this->puts("<img src=\"".$topdir."images/actions/delete.png\" alt=\"faire disparaitre\" title=\"faire disparaitre\"/>", $other_buffer);
      $this->puts("</a>\n", $other_buffer);
      $this->puts("</div>\n", $other_buffer);
      $this->puts("<ul>\n", $other_buffer);
      while ( $row = $sql->get_row())
      {
        $this->puts("<li><a href=\"news.php?id_nouvelle=".$row['id_nouvelle']."\">".$row['titre_nvl']."</a></li>\n", $other_buffer);
      }
      $this->puts("</ul>\n</div>\n", $other_buffer);
    }

    return $other_buffer;
  }

  function notices_list ( $sql, $title = "Informations" )
  {
    global $wwwtopdir, $topdir;
    $other_buffer = new stdcontents();
    if ( $sql->lines > 0 )
    {
      $this->puts("<div class=\"newsnotices\" id=\"newsnotices\">", $other_buffer);
      $this->puts("<div id=\"hide_notice\">\n", $other_buffer);
      $this->puts("<a href=\"#\" onclick=\"hide_with_cookies('newsnotices', 'AE2_HIDE_NOTICE'); return false;\">", $other_buffer);
      $this->puts("<img src=\"".$topdir."images/actions/delete.png\" alt=\"faire disparaire\" title=\"faire disparaire\"/>", $other_buffer);
      $this->puts("</a>\n", $other_buffer);
      $this->puts("</div>\n", $other_buffer);
      if ( !is_null($title) )
        $this->puts("<h2>".$title."</h2>\n", $other_buffer);

      $this->puts("<ul>\n", $other_buffer);
      $n=0;
      while ( $row = $sql->get_row())
      {
        if ( $row['id_asso'] )
        {
          $img = "/data/img/logos/".$row['nom_unix_asso'].".icon.png";
          if ( !file_exists("/var/www/ae2".$img) )
            $img = $wwwtopdir."images/default/news.icon.png";
        }
        else
          $img = $wwwtopdir."images/default/news.icon.png";

        $when = " ".date("d/m/Y",strtotime($row['date_nvl']));

        $when .= " - <a href=\"forum2/?react=react".
           "&amp;id_nouvelle=".$row['id_nouvelle'].
           "&amp;id_asso=".$row['id_asso'].
           "&amp;titre_sujet=".urlencode($row['titre_nvl']).
           "\">Réactions</a>";

        $this->puts("<li class=\"nvlitm nvl$n\"><img src=\"$img\" alt=\"\" class=\"nvlicon\" /><a href=\"news.php?id_nouvelle=".$row['id_nouvelle']."\" class=\"nvltitre\">".$row['titre_nvl']."</a> <span class=\"when\">$when</span><br/><span class=\"nvlresume\">".doku2xhtml($row['resume_nvl'])."</span><div class=\"clearboth\"></div></li>\n", $other_buffer);
        $n = ($n+1)%2;
      }
      $this->puts("</ul></div>\n", $other_buffer);
    }

    return $other_buffer;
  }

  function days_list ( $sql, $title = "Événements aujourd'hui et dans les prochains jours" )
  {
    global $wwwtopdir, $topdir;
    $other_buffer = new stdcontents();

    $this->puts("<div class=\"newssoon\">", $other_buffer);
    if ( !is_null($title) )
      $this->puts("<h2>".$title."</h2>\n", $other_buffer);
    if ( $sql->lines > 0 )
    {
      $prevday=null;
      $n=0;
      while( $row = $sql->get_row() )
      {
        $this->ids[] = $row["id_nouvelle"];
        $debut = strtotime($row['date_debut_eve']);
        $fin = strtotime($row['date_fin_eve']);
        $day = date("Y-m-d",$debut);

        if ( is_null($prevday) || $prevday != $day )
        {
          if ( !is_null($prevday))
            $this->puts("</ul>\n", $other_buffer);

          $this->puts("<h3>".strftime("%A %d %B",$debut)."</h3>\n", $other_buffer);
          $this->puts("<ul>\n", $other_buffer);
          $prevday=$day;
          //$n=0;
        }

        if ( $row['id_asso'] )
        {
          $img = "<a href=\"asso.php?id_asso=".$row['id_asso']."\"><img src=\"/data/img/logos/".$row['nom_unix_asso'].".icon.png\" alt=\"\" class=\"nvlicon\" /></a>";
          if ( !file_exists("/var/www/ae2/data/img/logos/".$row['nom_unix_asso'].".icon.png") )
            $img = "<a href=\"asso.php?id_asso=".$row['id_asso']."\"><img src=\"".$wwwtopdir."images/default/news.icon.png\" alt=\"\" class=\"nvlicon\" /></a>";
        }
        else
        {
          $img = "<img src=\"".$wwwtopdir."images/default/news.icon.png\" alt=\"\"/>";
        }

        if ( $day != date("Y-m-d",$fin) && (($fin-$debut) > (60*60*24)))
          $hour = "de ".strftime("%H:%M",$debut) . " jusqu'au ".strftime("%A %d %B %H:%M",$fin);
        else
          $hour = "de ".strftime("%H:%M",$debut) . " jusqu'à ".strftime("%H:%M",$fin);

        if (!defined ("MOBILE"))
          $hour .= " - <a href=\"forum2/?react=react".
            "&amp;id_nouvelle=".$row['id_nouvelle'].
            "&amp;id_asso=".$row['id_asso'].
            "&amp;titre_sujet=".urlencode($row['titre_nvl']).
            "\">Réactions</a>";


        $this->puts("<li class=\"nvlitm nvl$n\">$img<a href=\"news.php?id_nouvelle=".$row['id_nouvelle']."\" class=\"nvltitre\">".$row['titre_nvl']."</a> <span class=\"hour\">$hour</span><br/><span class=\"nvlresume\">".doku2xhtml($row['resume_nvl'])."</span><div class=\"clearboth\"></div></li>\n", $other_buffer);


        $n = ($n+1)%2;
      }

      $this->puts("</ul>\n", $other_buffer);
    }
    else
      $this->puts("<p>Rien de prévu pour le moment...</p>\n", $other_buffer);

    $this->puts("</div>\n", $other_buffer);

    return $other_buffer;
  }


  function nottomiss_list ( $sql, $title="Prochainement... à ne pas rater !" )
  {
    global $wwwtopdir, $topdir;
    $other_buffer = new stdcontents();

    if ( $sql->lines > 0 )
    {
      $this->puts("<div class=\"newsnottomiss\">", $other_buffer);

      if ( !is_null($title) )
        $this->puts("<h2>".$title."</h2>\n", $other_buffer);

      $this->puts("<ul>\n", $other_buffer);

      while( $row = $sql->get_row() )
      {
        $debut = strtotime($row['date_debut_eve']);
        $hour = "le ".strftime("%A %d %B %G à %H:%M",$debut);
        $hour .= " - <a href=\"forum2/?react=react".
           "&amp;id_nouvelle=".$row['id_nouvelle'].
           "&amp;id_asso=".$row['id_asso'].
           "&amp;titre_sujet=".urlencode($row['titre_nvl']).
           "\">Réactions</a>";
        $this->puts("<li class=\"nvlttls\"><a href=\"news.php?id_nouvelle=".$row['id_nouvelle']."\">".$row['titre_nvl']."</a> <span class=\"hour\">$hour</span></li>", $other_buffer);
      }
      $this->puts("</ul>\n", $other_buffer);
      $this->puts("</div>\n", $other_buffer);
    }

    return $other_buffer;
  }

  function puts ( $data, &$other_buffer = null )
  {
    $this->buffer .= $data;
    if ($other_buffer != null)
        $other_buffer->buffer .= $data;
  }
}

/**
 * Affichage des nouvelles pour la page d'acceuil du site.
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class newsfront extends newslister
{

  function newsfront ( $db )
  {
    $this->class="nvls";
    $cacheprefix = null;
    $cacheexpiry = 3600;
    $cache = null;

    if(!defined("MOBILE")) {
        $this->title = "Les dernières nouvelles de la vie étudiante de l'UTBM";
        $cacheprefix = "n_";
    } else { /* MOBILE version */
        $this->title = "Les news";
        $cacheprefix = "m_";
    }

    if(!defined("MOBILE")) {
        if(!$_COOKIE['AE2_HIDE_APPLES']) {
            $cache = new cachedcontents ('apples');
            if ($cache->is_cached ())
                $this->puts ($cache->get_cache ()->buffer);
            else {
                $sql = new requete($db,"SELECT * FROM nvl_nouvelles " .
                                   "INNER JOIN nvl_dates ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
                                   "WHERE nvl_nouvelles.type_nvl='".NEWS_TYPE_APPEL."' AND modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
                                   "NOW() > nvl_dates.date_debut_eve AND NOW() < nvl_dates.date_fin_eve");
                $cache->set_contents_until ($this->appel_list($sql), $cacheexpiry);
            }
        }

        if(!$_COOKIE['AE2_HIDE_NOTICE']) {
            $cache = new cachedcontents ('notices');
            if ($cache->is_cached ())
                $this->puts ($cache->get_cache ()->buffer);
            else {
                $sql = new requete($db,"SELECT nvl_nouvelles.*,asso.nom_unix_asso FROM nvl_nouvelles " .
                                   "LEFT JOIN asso ON asso.id_asso = nvl_nouvelles.id_asso " .
                                   "WHERE type_nvl='".NEWS_TYPE_NOTICE."' AND modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
                                   "DATEDIFF(NOW(),date_nvl) < 14 " .
                                   "LIMIT 3");

                $cache->set_contents_until ($this->notices_list($sql), $cacheexpiry);
            }
        }
        $this->ids=array(0);
    } /* ifndef MOBILE */

    $sql = new requete($db,"SELECT nvl_nouvelles.*,".
                       "asso.nom_unix_asso, nvl_dates.date_debut_eve, nvl_dates.date_fin_eve " .
                       "FROM nvl_dates " .
                       "INNER JOIN  nvl_nouvelles ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
                       "LEFT JOIN asso ON asso.id_asso = nvl_nouvelles.id_asso " .
                       "WHERE type_nvl='".NEWS_TYPE_EVENT."' AND  modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
                       "NOW() < nvl_dates.date_fin_eve " .
                       "AND DATEDIFF(nvl_dates.date_debut_eve,NOW()) < 14 " .
                       "ORDER BY nvl_dates.date_debut_eve " .
                       "LIMIT 6");


    $cache = new cachedcontents ($cacheprefix . 'nvls');
    if ($cache->is_cached ())
        $this->puts ($cache->get_cache ()->buffer);
    else {
        if(!defined("MOBILE"))
            $cache->set_contents_until ($this->days_list($sql), $cacheexpiry);
        else
            $cache->set_contents_until ($this->days_list($sql, null), $cacheexpiry);
    }

    if(!defined("MOBILE")) {
        $cache = new cachedcontents ('nottomiss');
        if ($cache->is_cached ())
            $this->puts ($cache->get_cache ()->buffer);
        else {
            $sql = new requete($db,"SELECT nvl_nouvelles.*,".
                               "asso.nom_unix_asso,nvl_dates.date_debut_eve,nvl_dates.date_fin_eve " .
                               "FROM nvl_dates " .
                               "INNER JOIN  nvl_nouvelles ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
                               "LEFT JOIN asso ON asso.id_asso = nvl_nouvelles.id_asso " .
                               "WHERE type_nvl='".NEWS_TYPE_EVENT."' AND  modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
                               "nvl_dates.id_nouvelle NOT IN (".implode(",",$this->ids).") AND " .
                               "NOW() < nvl_dates.date_debut_eve " .
                               "ORDER BY nvl_dates.date_debut_eve " .
                               "LIMIT 10");

            $cache->set_contents_until ($this->nottomiss_list($sql), $cacheexpiry);
        }
    } /* ifndef MOBILE */
  }
}

/**
 * Affiche les nouvelles concernant une journée.
 *
 * Il est possible de restreindre à une association/activité.
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class newsday extends newslister
{

  function newsday ( $db, $day, $id_asso = null )
  {
    $this->class="nvls";

    $this->title="Le ".strftime("%A %d %B",$day);


    $sql = "SELECT nvl_nouvelles.*,".
        "asso.nom_unix_asso, nvl_dates.date_debut_eve, nvl_dates.date_fin_eve " .
        "FROM nvl_dates " .
        "INNER JOIN  nvl_nouvelles ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
        "LEFT JOIN asso ON asso.id_asso = nvl_nouvelles.id_asso " .
        "WHERE modere_nvl='1' " .
        "AND `nvl_dates`.`date_debut_eve` <= '" . date("Y-m-d",$day+24*60*60) ." 05:59:59' " .
        "AND `nvl_dates`.`date_debut_eve` >= '" . date("Y-m-d",$day) ." 06:00:00' ";

    if ( is_null($id_asso) )
      $sql .= "AND id_canal='".NEWS_CANAL_SITE."' ";
    else
      $sql .= "AND nvl_nouvelles.id_asso='".mysql_real_escape_string($id_asso)."' ";

    $sql .= "ORDER BY nvl_dates.date_debut_eve ";

    $req = new requete($db, $sql);

    $this->days_list($req,"Activités et évenements prévus");

    $sql = "SELECT nvl_nouvelles.*,".
        "asso.nom_unix_asso, nvl_dates.date_debut_eve, nvl_dates.date_fin_eve " .
        "FROM nvl_dates " .
        "INNER JOIN  nvl_nouvelles ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
        "LEFT JOIN asso ON asso.id_asso = nvl_nouvelles.id_asso " .
        "WHERE modere_nvl='1' " .
        "AND `nvl_dates`.`date_debut_eve` <= '" . date("Y-m-d",$day+24*60*60) ." 05:59:59' " .
        "AND `nvl_dates`.`date_fin_eve` >= '" . date("Y-m-d",$day) ." 06:00:00' ".
        "AND `nvl_dates`.`date_debut_eve` < '" . date("Y-m-d",$day) ." 06:00:00' ";

    if ( is_null($id_asso) )
      $sql .= "AND id_canal='".NEWS_CANAL_SITE."' ";
    else
      $sql .= "AND nvl_nouvelles.id_asso='".mysql_real_escape_string($id_asso)."' ";

    $sql .= "ORDER BY nvl_dates.date_debut_eve ";

    $req = new requete($db, $sql);

    $this->nottomiss_list($req,"Toujours d'actualité");
  }
}


?>
