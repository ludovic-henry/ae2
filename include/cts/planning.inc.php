<?php
/* Copyright 2006
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

/**
 * Affiche un planning hebdomadaire
 *
 * @author Julien Etelain
 * @ingroup display_cts
 */
class weekplanning extends stdcontents
{
    var $get_page;

    /**
     * Génére un planning hebdomadaire
     * @param $titre Titre du contenu
     * @param $db Connection à la base de donnée
     * @param $sql Requete de selection SQL (SELECT ... FROM ... WHERE ....) (finir par WHERE 1 s'il n'y aucune condition)
     * @param $idf Champ SQL d'identification
     * @param $startf Champ SQL de debut
     * @param $startf Champ SQL de fin
     * @param $namef Champ SQL du nom
     * @param $page Adresse de la page pour le suivant/précédent
     * @param $infopage Adresse de la page d'information sur un élément
     */
    function weekplanning ( $titre, $db, $sql, $idf, $startf, $endf, $namef, $page, $infopage, $extra="", $unlundi=null, $deuxsemaines=false )
    {
        $this->title=false;


        if ( !is_null($unlundi) )
            {
                $start = $unlundi;
                if ( $deuxsemaines && isset($_REQUEST["semainedeux"]) )
                    $start += 7*24*3600;
            }
        else
            {
                if (isset($_REQUEST["pstartdate"]))
                    $start = strtotime($_REQUEST["pstartdate"]);

                if ( $start < 1)
                    $start = strtotime(date("Y-m-d"));
            }

        $end = $start + (6*24*60*60)+1;

        $req = new requete($db, $sql." AND $startf >= '".date("Y-m-d 00:00:00",$start)."' AND $startf <= '".date("Y-m-d 23:59:59",$end)."' $extra ORDER BY $startf");

        if ( strstr($page,"?"))
            $page = $page."&amp;";
        else
            $page = $page."?";

        if ( strstr($infopage,"?"))
            $infopage = $infopage."&amp;";
        else
            $infopage = $infopage."?";

        if ( $n = strpos($startf,".") )
            $startf = substr($startf,$n+1);

        if ( $n = strpos($endf,".") )
            $endf = substr($endf,$n+1);


        while ( $row = $req->get_row() )
            {
                $st = strtotime($row[$startf]);
                $ed = strtotime($row[$endf]);
                do {

                    $endofday = strtotime(date("Y-m-d 23:59:59",$st));

                    if ( isset($day[date("Y-m-d",$st)][$st]) )
                        $day[date("Y-m-d",$st)][$st][3] .= ", ".$row[$namef] ;
                    else
                        $day[date("Y-m-d",$st)][$st] =
                            array(
                                  $st,
                                  min($endofday,$ed),
                                  $row[$idf],
                                  $row[$namef]
                                  );
                    $st=$endofday+1;
                } while ( $endofday < $ed );
            }


        if ( is_null($unlundi) )
            {
                $this->buffer .= "<table class=\"weekplanning\" width=\"100%\">\n<tr class=\"head\">\n";
                $this->buffer .= "<td class=\"head_larrow\"><a href=\"".$page."pstartdate=".date("Y-m-d",strtotime(date("Y-m-d",$start)." -1 week"))."\">&lArr;</a></td>\n";
                $this->buffer .= "<td class=\"head_title\">$titre (".strftime("%A %d %B %G",$start).")</td>\n";
                $this->buffer .= "<td class=\"head_rarrow\"><a href=\"".$page."pstartdate=".date("Y-m-d",strtotime(date("Y-m-d",$start)." +1 week"))."\">&rArr;</a></td>\n";
                $this->buffer .= "</tr>\n</table>\n";
            }
        else
            {
                $this->buffer .= "<table class=\"weekplanning\" width=\"100%\">\n<tr class=\"head\">\n";

                if ( $deuxsemaines && isset($_REQUEST["semainedeux"]) )
                    $this->buffer .= "<td class=\"head_larrow\"><a href=\"".$page."\">&lArr;</a></td>\n";
                else
                    $this->buffer .= "<td class=\"head_larrow\"></td>\n";

                $this->buffer .= "<td class=\"head_title\">$titre</td>\n";

                if ( $deuxsemaines && !isset($_REQUEST["semainedeux"]) )
                    $this->buffer .= "<td class=\"head_rarrow\"><a href=\"".$page."semainedeux\">&rArr;</a></td>\n";
                else
                    $this->buffer .= "<td class=\"head_rarrow\"></td>\n";
                $this->buffer .= "</tr>\n</table>\n";
            }

        $this->buffer .= "<table class=\"weekplanning\" width=\"100%\">\n";
        /*$this->buffer .= "<tr class=\"planninghead\">";

          $this->buffer .= "</tr>";*/
        $scale = 24*7;
        $height = floor((24*60*60/$scale)+20);
        $this->buffer .= "<tr>\n<td class=\"day\" style=\"width:9%; height:".$height."px;\">\n";
        $this->buffer .= "<div class=\"dayhead daycount\">&nbsp;</div>\n";

        for($i=0;$i<24;$i++)
            {
                $ln = floor(($i+1)*60*60/$scale)-floor($i*60*60/$scale);
                $this->buffer .= "<div class=\"dayitem daycount\" style=\"height:".($ln-3)."px;\">".$i."h00</div>\n";
            }


        $this->buffer .= "</td>\n";
        for($i=$start;$i<$end;$i+=24*60*60)
            {
                $this->buffer .= "<td class=\"day\" style=\"width:13%; height:".$height."px; vertical-align:top;\">\n";
                if ( is_null($unlundi) )
                    $this->buffer .= "<div class=\"dayhead\" style=\"height:20px;\">".strftime("%A %d",$i)."</div>\n";
                else
                    $this->buffer .= "<div class=\"dayhead\" style=\"height:20px;\">".strftime("%A",$i)."</div>\n";

                $last=0;

                if(!empty($day[date("Y-m-d",$i)]))
                    {
                        foreach ( $day[date("Y-m-d",$i)] as $row )
                            {
                                $st = floor(($row[0]-$i)/$scale);
                                $ln = floor(($row[1]-$i)/$scale)-$st;

                                if ( $st != $last )
                                    $this->buffer .= "<div style=\"height:".($st-$last+3)."px; overflow:hidden;\">&nbsp;</div>\n";

                                $this->buffer .= "<div class=\"dayitem\" style=\"height:".($ln-4)."px;\"><span class=\"itemhour\">".date("H:i",$row[0])."</span> <a href=\"".$infopage.$idf."=".$row[2]."\">".$row[3]."</a></div>\n";
                                $last=$st+$ln;
                            }
                    }


                $this->buffer .= "</td>\n";
            }

        $this->buffer .= "</tr>\n</table>\n";

    }


}



?>
