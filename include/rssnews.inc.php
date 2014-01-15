<?php
/*
 * Copyright 2007
 * - Julien Etelain < julien dot etelain at gmail dot com >
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

require_once($topdir."include/rss.inc.php");
require_once($topdir."include/entities/news.inc.php");

/**
 * @file
 */

/**
 * Generateur de flux RSS reltif à des nouvelles du site
 * @see rssfeednewshome
 * @see rssfeednewsclub
 */
class rssfeednews extends rssfeed
{
  /** Lien vers la base de données */
  var $db;

  /** URL publique du site (support AECMS) */
  var $pubUrl;

  /**
   * Constructeur de la classe
   * @param $db Lien à la base de données
   */
  function rssfeednews ( &$db )
  {
    $this->db = $db;
    $this->pubUrl = "http://ae.utbm.fr/";
    $this->rssfeed();
  }

  /**
   * Ecrit un ensemble de nouvelles
   * @param $req Requete SQL (Objet requete)
   * @param $ids Liste où seront stocké les id des nouvelles ecritent
   * (utilisé parfois pour éviter les redondances entre plusieurs "sections")
   */
  function output_news ( $req, &$ids )
  {
    if ( $req->lines == 0 ) return;

    while ( $row = $req->get_row() )
    {
        $wikicts = new wikicontents(false, $row["resume_nvl"], true);

        echo "<item>\n";
        echo "<title>".htmlspecialchars($row["titre_nvl"],ENT_NOQUOTES,"UTF-8")."</title>\n";
        echo "<link>".$this->pubUrl."news.php?id_nouvelle=".$row["id_nouvelle"]."</link>\n";
        echo "<description><![CDATA[ ".$wikicts->buffer." ]]></description>\n";
        echo "<pubDate>".gmdate("D, j M Y G:i:s T",strtotime($row["date_nvl"]))."</pubDate>\n";
        echo "<guid>http://ae.utbm.fr/news.php?id_nouvelle=".$row["id_nouvelle"]."</guid>\n";

        if ( !is_null($row["lat_geopoint"]) && !is_null($row["long_geopoint"]) )
          echo "<georss:point>".sprintf("%.12F",$row['lat_geopoint']*360/2/M_PI)." ".
      sprintf("%.12F",$row['long_geopoint']*360/2/M_PI)."</georss:point>\n";

        echo "</item>\n";

          $ids[] = $row["id_nouvelle"];
    }
  }
}

/**
 * Générateur du flux RSS correspondant aux nouvelles du site avec la présentation
 * conforme à la première page du site.
 */
class rssfeednewshome extends rssfeednews
{
  /**
   * Constructeur
   * @param $db lien à la base de données
   */
  function rssfeednewshome ( &$db )
  {
    $this->rssfeednews($db);
    $this->title = "AE UTBM";
    $this->description = "Les dernières nouvelles de la vie étudiante de l'UTBM";
    $this->link = "http://ae.utbm.fr/";
  }

  /*
   * Re-implémentation
   */
  function output_items ()
  {
    $ids = array(0);

    $sql = new requete($this->db,"SELECT * FROM nvl_nouvelles " .
            "INNER JOIN nvl_dates ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
            "LEFT JOIN geopoint ON ( nvl_nouvelles.id_lieu = geopoint.id_geopoint) ".
            "WHERE nvl_nouvelles.type_nvl='".NEWS_TYPE_APPEL."' AND modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
            "NOW() > nvl_dates.date_debut_eve AND NOW() < nvl_dates.date_fin_eve");

    $this->output_news($sql,$ids);

    $sql = new requete($this->db,"SELECT nvl_nouvelles.*,asso.nom_unix_asso,geopoint.* FROM nvl_nouvelles " .
            "LEFT JOIN asso ON asso.id_asso = nvl_nouvelles.id_asso " .
            "LEFT JOIN geopoint ON ( nvl_nouvelles.id_lieu = geopoint.id_geopoint) ".
            "WHERE type_nvl='".NEWS_TYPE_NOTICE."' AND modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
            "DATEDIFF(NOW(),date_nvl) < 14 " .
            "LIMIT 3");

    $this->output_news($sql,$ids);

    $ids = array(0);

    $sql = new requete($this->db,"SELECT nvl_nouvelles.*,asso.nom_unix_asso,nvl_dates.date_debut_eve,nvl_dates.date_fin_eve,geopoint.* " .
            "FROM nvl_dates " .
            "INNER JOIN  nvl_nouvelles ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
            "LEFT JOIN asso ON asso.id_asso = nvl_nouvelles.id_asso " .
            "LEFT JOIN geopoint ON ( nvl_nouvelles.id_lieu = geopoint.id_geopoint) ".
            "WHERE (type_nvl='".NEWS_TYPE_EVENT."' "./*OR type_nvl='".NEWS_TYPE_HEBDO."'*/") AND  modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
            "NOW() < nvl_dates.date_fin_eve " .
            "ORDER BY nvl_dates.date_debut_eve " .
            "LIMIT 5");

    $this->output_news($sql,$ids);

    $sql = new requete($this->db,"SELECT nvl_nouvelles.*,asso.nom_unix_asso,nvl_dates.date_debut_eve,nvl_dates.date_fin_eve,geopoint.* " .
            "FROM nvl_dates " .
            "INNER JOIN  nvl_nouvelles ON (nvl_dates.id_nouvelle=nvl_nouvelles.id_nouvelle) " .
            "LEFT JOIN asso ON asso.id_asso = nvl_nouvelles.id_asso " .
            "LEFT JOIN geopoint ON ( nvl_nouvelles.id_lieu = geopoint.id_geopoint) ".
            "WHERE type_nvl='".NEWS_TYPE_EVENT."' AND  modere_nvl='1' AND id_canal='".NEWS_CANAL_SITE."' AND " .
            "nvl_dates.id_nouvelle NOT IN (".implode(",",$ids).") AND " .
            "NOW() < nvl_dates.date_debut_eve " .
            "ORDER BY nvl_dates.date_debut_eve " .
            "LIMIT 10");

    $this->output_news($sql,$ids);
  }
}

/**
 * Generateur de flux RSS pour une activité donnée
 * Conçu pour AECMS
 */
class rssfeednewsclub extends rssfeednews
{
  var $asso;

  /**
   * Constructeur
   * @param $db Lien à la base de données
   * @param $asso Instance de asso avec l'activité concernée chargée
   * @param $pubUrl URL racine du site à "promouvoir" dans le flux
   */
  function rssfeednewsclub ( &$db, &$asso, $pubUrl )
  {
    $this->rssfeednews($db);
    $this->title = $asso->nom;
    $this->description = "Les dernières nouvelles de ".$asso->nom;
    $this->link = $pubUrl;
    $this->pubUrl = $pubUrl;
    $this->asso = $asso;
  }

  /*
   * Re-implémentation
   */
  function output_items ()
  {
    $ids = array(0);

    $req = new requete($this->db,"SELECT * FROM nvl_nouvelles ".
      "LEFT JOIN geopoint ON ( nvl_nouvelles.id_lieu = geopoint.id_geopoint) ".
      "WHERE id_asso='".mysql_real_escape_string($this->asso->id)."' ".
      "AND `modere_nvl`='1' ".
      "ORDER BY date_nvl DESC ".
      "LIMIT 30");

    $this->output_news($req,$ids);
  }
}

?>
