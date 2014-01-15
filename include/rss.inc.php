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

/**
 * @file
 */

/**
 * Class permettant la génération d'un flux RSS
 * Cette classe a vocation a être étendue. Par défaut elle ne propose peu.
 * Supporte georss et rdf
 * @see rssfeednews
 */
class rssfeed
{

  /** Titre fu flux */
  var $title;
  /** Lien du flux */
  var $link;
  /** Description du flux */
  var $description;
  /** Nom du générateur */
  var $generator;
  /** Timestamp unix de date de génération */
  var $pubDate;

  /**
   * Constructeur de la classe
   */
  function rssfeed()
  {
    $this->pubDate = time();
    $this->generator = "http://ae.utbm.fr/";
  }

  /**
   * Ecrit les différents items
   * (ne fait rien par défaut)
   */
  function output_items()
  {

  }

  /**
   * Ecrit le flux RSS dans son intégralité
   */
  function output ()
  {
    header("Content-Type: text/xml; charset=utf-8");
    echo "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n";
    echo "<rss version=\"2.0\" xmlns:rdf=\"http://www.w3.org/1999/02/22-rdf-syntax-ns#\" xmlns:georss=\"http://www.georss.org/georss/\">\n";
    echo "<channel>\n";

    if ( !empty($this->title) )
      echo "<title>".htmlspecialchars($this->title,ENT_NOQUOTES,"UTF-8")."</title>\n";

    if ( !empty($this->link) )
      echo "<link>".htmlspecialchars($this->link,ENT_NOQUOTES,"UTF-8")."</link>\n";

    if ( !empty($this->description) )
      echo "<description>".htmlspecialchars($this->description,ENT_NOQUOTES,"UTF-8")."</description>\n";

    if ( !empty($this->generator) )
      echo "<generator>".htmlspecialchars($this->generator,ENT_NOQUOTES,"UTF-8")."</generator>\n";

    echo "<pubDate>".gmdate("D, j M Y G:i:s T",$this->pubDate)."</pubDate>\n";

    $this->output_items();

    echo "</channel>\n";
    echo "</rss>\n";
    exit();
  }
}




?>
