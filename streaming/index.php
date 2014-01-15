<?php

/* Copyright 2007-2009
 * - Simon Lopez < simon DOT lopez AT ayolo DOT org >
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
 * along with site program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA
 * 02111-1307, USA.
 */

$topdir = "../";
require_once($topdir. "include/site.inc.php");


$rdd=array("title"=>"remise des diplômes",
           "intro"=>"Cette année, afin de faire participer les personnes non-conviées à la cérémonie de la Remise des Diplômes de l'<a href='http://www.utbm.fr'>Université de Technologie de Belfort-Montbéliard</a>, nous avons mis en place un service expérimental de diffusion de la cérémonie en direct.",
           "date"=>"samedi 19 novembre 2011",
           "h-debut"=>"14h",
           "h-fin"=>"18h",
           "lieu"=>"à l'Axone de Montbéliard",
           "remerciements"=>"Grâce au travail d'UTBM-Productions pendant la cérémonie, nous vous offrons en direct les images montées afin de profiter, chez vous, de la cérémonie. Nous remercions aussi le CRI de l'UTBM ainsi que l'ensemble des bénévoles et partenaires.<br /> <img src=\"utprod.png\" alt=\"UTBM Production\" title=\"UTBM Production\" /> <img src=\"logos_rdd.gif\" alt=\"partenaires\ title=\"partenaires\" />",
           "bandeau"=>"bandeau_rdd.png",
           "photo"=>"photo_rdd.jpg",
           "type"=>"La cérémonie"
          );

/*$ff1j=array("title"=>"Festival du film d'un jour",
           "intro"=>"Cette année, afin de faire participer les personnes ne pouvant se rendre à la cérémonie de cloture du festival du film d'un jour, nous avons mis en place un service expérimental de diffusion de la cérémonie en direct.",
           "date"=>"samedi 3 mai 2008",
           "h-debut"=>"20h30",
           "h-fin"=>"23h",
           "lieu"=>"au Mégarama d'Audincourt (entre Belfort et Montbéliard)",
           "remerciements"=>"Grâce au travail d'UTBM-Productions pendant la cérémonie, nous vous offrons en direct les images montées afin de profiter, chez vous, de la cérémonie. Nous remercions aussi le CRI de l'UTBM ainsi que l'ensemble des bénévoles et partenaires.<div align=\"center\"><img src=\"logos_ff1j.png\" alt=\"partenaires\ title=\"partenaires\" /></div>",
           "bandeau"=>"bandeau_ff1j.png",
           "photo"=>"photo_ff1j.png",
           "type"=>"La cérémonie"
          );

/*$congres=array("title"=>"Congres 2008",
              "intro"=>"Cette année, pour la première fois, vivez le congres en direct sur internet grâce &agrave; l'ae et &agrave; ses bénévoles",
              "date"=>"mercredi 1er octobre",
              "h-debut"=>"8h",
              "h-fin"=>"19h",
              "lieu"=>"depuis sevenans",
              "remerciements"=>"Grâce au travail d'UTBM-Productions pendant la cérémonie, nous vous offrons en direct les images montées afin de profiter, chez vous, de la cérémonie. Nous remercions aussi le CRI de l'UTBM ainsi que l'ensemble des bénévoles et partenaires.",
              "bandeau"=>"bandeau_congres.png",
              "photo"=>"photo_congres.png",
              "type"=>"La retransmission"
          );
$congres=array("title"=>"Congrès industriel 2009",
                "intro"=>"
Mercredi 7 octobre er jeudi 8 octobre se déroule le Congrès industriel à Sevenans. Durant ces deux jours, venez rencontrer les <a href='http://ae.utbm.fr/d.php?action=download&id_file=4524'>entreprises</a> et assister aux <a href='http://ae.utbm.fr/d.php?action=download&id_file=4522'>conférences</a>.",
                "nodate"=>true,
                "date"=>"mercredi 7 octobre",
                "h-debut"=>"10h",
                "h-fin"=>"jeudi 8 octobre 17h",
                "lieu"=>"à l'UTBM Sevenans",
                "remerciements"=>"Grâce au travail d'UTBM-Productions pendant la conférence, nous vous offrons en direct les images montées afin de profiter, chez vous, de la conférence. Nous remercions aussi le CRI de l'UTBM ainsi que l'ensemble des bénévoles et partenaires.",
                "bandeau"=>"http://ae.utbm.fr/d.php?id_file=4523&action=download",
               // "photo"=>"http://ae.utbm.fr/sas2/images.php?/77951.diapo.jpg",
                "type"=>"La retransmission"
            );
*/
$event=$rdd;

$site = new site ();

$site->set_side_boxes("left",array());
$site->set_side_boxes("right",array());


$site->start_page("accueil","Remise des diplômes 2012");

$cts = new contents("Présentation");
$cts->add_paragraph("<div align=\"center\"><img src=\"".$event["bandeau"]."\" alt=\"".$event["title"]."\" title=\"".$event["title"]."\" /><br /><img src=\"".$event["photo"]."\" alt=\"".$event["title"]."\" title=\"".$event["title"]."\" /></div>");
$cts->add_paragraph("Grâce au travail de l'équipe informatique de l'Association des Étudiants et d'UTBM-Productions pendant la conférence, nous vous offrons en direct les images montées afin de profiter chez vous de la conférence. Nous remercions l'ensemble des bénévoles et partenaires.");
$site->add_contents($cts);

$cts = new contents("Suivre en direct");
$cts->add_paragraph("Pour profiter au mieux et de manière plus fiable de cette diffusion, nous vous recommandons l'utilisation du logiciel libre <a href=\"http://www.videolan.org\">VideoLan</a>. Ce logiciel est disponible au <a href=\"http://www.videolan.org/vlc/\">téléchargement</a> pour toutes les plateformes (MS Windows, Mac OS X et Linux compris bien sûr).");
$cts->add_paragraph("Pour accéder au streaming, ouvrez le lien suivant avec <b>VideoLan</b> : <b>http://ae-streaming.utbm.fr/rdd</b>");
$site->add_contents($cts);

$cts = new contents("Propulsé par ...");
$cts->add_paragraph("<img src=\"linux.png\" alt=\"GNU/Linux\" title=\"GNU/Linux\" /> <img src=\"vlc.jpg\" alt=\"VideoLan\" title=\"VideoLan\" /> <img src=\"debian.png\" alt=\"Debian\" title=\"Debian\" />");
$site->add_contents($cts);

$site->end_page();

?>
