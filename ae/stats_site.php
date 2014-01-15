<?php
/* Copyright 2007
 * - Simon Lopez < simon DOT lopez AT ayolo DOT org >
 *
 * Ce fichier fait partie du site de l'Association des étudiants de
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
require_once($topdir. "include/site.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir . "include/graph.inc.php");
$site = new site ();

if (!$site->user->is_in_group ("gestion_ae"))
  $site->error_forbidden("accueil");

function dec2hex($val)
{
  $hex="";
  for($i=0; $i<3; $i++)
  {
    $temp = dechex($val[$i]);
    if(strlen($temp) < 2)
      $hex .= "0". $temp;
    else
      $hex .= $temp;
  }
  return $hex;
}

if ( $_REQUEST["action"] == "os" )
{
  $req = new requete($site->db,"SELECT * FROM `stats_os`  ORDER BY `visites` DESC");
  $cam=new camembert(600,500,array(),2,20,0,0,0,0,0,10,150);
  while($row=$req->get_row())
    $cam->data($row['visites'], $row['os']);
  $cam->png_render();
  exit();
}
if ( $_REQUEST["action"] == "browser" )
{
  $req = new requete($site->db,"SELECT * FROM `stats_browser`  ORDER BY `visites` DESC");
  $cam=new camembert(600,500,array(),2,20,0,0,0,0,0,10,150);
  while($row=$req->get_row())
    $cam->data($row['visites'], $row['browser']);
  $cam->png_render();
  exit();
}
if ( $_REQUEST["action"] == "pages" )
{
  $req = new requete($site->db,"
        SELECT SUBSTRING_INDEX( page, '/', 2 ) dir, SUM( visites ) sum_visites
        FROM `stats_page`
        GROUP BY dir
        ORDER BY sum_visites DESC
        LIMIT 30");
  $cam=new camembert(600,500,array(),2,20,0,0,0,0,0,10,150);
  while($row=$req->get_row())
    $cam->data($row['sum_visites'], $row['dir']);
  $cam->png_render();
  exit();
}

if (isset($_REQUEST['start']))
{
  $start = mysql_real_escape_string($_REQUEST['start']);
  $req = new requete($site->db,"SELECT * FROM `stats_page`  ORDER BY `visites` DESC LIMIT ".$start.",20");

  if ($req->lines < 20)
  {
    $txt = "retour au d&eacute;but  ...";
    $start=-21;
  }
  else
    $txt = "Voir les 20 suivants ...";
  if ($req->lines <= 0)
  {
    $req = new requete($site->db,"SELECT * FROM `stats_page`  ORDER BY `visites` DESC LIMIT 20");
    $start=-21;
  }
  echo "<h1>Pages visit&eacute;es visit&eacute;s</h1>\n";
  echo "<center>\n";
  $sqlt = new sqltable("top_full",
                       "Pages visit&eacute;es visit&eacute;s", $req, "stats.php",
                       "page",
                       array("page"=>"page",
                             "visites"=>"Visites"),
                       array(),
                       array(),
                       array()
                      );

  echo $sqlt->html_render();
  $start = $start+21;
  echo "\n<a href=\"javascript:next(this, $start)\">".$txt."</a>";
  echo "</center>";

  exit();
}


$site->start_page ("accueil", "statistiques du site");

$cts = new contents("Classement");

$cts->add_paragraph("<script language=\"javascript\">
function next(obj, start)
{
  openInContents('cts2', './stats_site.php', 'start='+start);
}
</script>\n");

if ( $_REQUEST["action"] == "reset" )
{
  $req = new requete($site->dbrw, "DELETE FROM `stats_page` WHERE `page`!=''");
  $req = new requete($site->dbrw, "DELETE FROM `stats_os` WHERE `os`!=''");
  $req = new requete($site->dbrw, "DELETE FROM `stats_browser` WHERE `browser`!=''");
  $cts->add_title(2, "Reset");
  $cts->add_paragraph("Le reset des stats a &eacute;t&eacute; effectu&eacute; avec succ&egrave;s");
}

$cts->add_title(2, "Administration");
$cts->add_paragraph("Remettre &agrave; z&eacute;ro les stats du site ae.".
                    "<br /><img src=\"".$topdir."images/actions/delete.png\"><b>ATTENTION CECI EST IRREVERSIBLE</b> : <a href=\"stats_site.php?action=reset\">Reset !</a>");
$site->add_contents($cts);

$cts = new contents("Pages visit&eacute;es visit&eacute;s");
$req = new requete($site->db,"SELECT * FROM `stats_page`  ORDER BY `visites` DESC LIMIT 20");
if($req->lines<20)
  $less=true;
else
  $less=false;
$sqlt = new sqltable("top_full",
                     "", $req, "stats.php",
                     "page",
                     array("page"=>"page",
                           "visites"=>"Visites"),
                     array(),
                     array(),
                     array()
                    );
$cts->add_paragraph("<center>".$sqlt->html_render()."</center>");
if(!$less)
  $cts->add_paragraph("<center><a href=\"javascript:next(this, 21)\">Voir les 20 suivants ...</a></center>");
$cts->add_paragraph("<center><img src=\"stats_site.php?action=pages\" alt=\"pages visitées\" /></center>\n");
$site->add_contents($cts);
/*
$req = new requete($site->db,"SELECT * FROM `stats_browser`  ORDER BY `visites` DESC");
$cts->add(new sqltable("top_full",
                       "Navigateurs utilis&eacute;s", $req, "stats.php",
                       "browser",
                       array("=num" => "N°",
                             "browser"=>"Navigateur",
                             "visites"=>"Total"),
                       array(),
                       array(),
                       array()
         ),true);
 */
$cts = new contents("Navigateurs utilis&eacute;s");
$cts->add_paragraph("<center><img src=\"stats_site.php?action=browser\" alt=\"navigateurs utilis&eacute;s\" /></center>\n");
$site->add_contents($cts);
/*
$req = new requete($site->db,"SELECT * FROM `stats_os`  ORDER BY `visites` DESC");
$cts->add(new sqltable("top_full",
                       "Syst&egrave;mes d'exploitation utilis&eacute;s", $req, "stats.php",
                       "id_utilisateur",
                       array("=num" => "N°",
                             "os"=>"Syst&egrave;me d'exploitation",
                             "visites"=>"Total"),
                       array(),
                       array(),
                       array()
         ),true);
 */
$cts = new contents("Syst&egrave;mes d'exploitation utilis&eacute;s");
$cts->add_paragraph("<center><img src=\"stats_site.php?action=os\" alt=\"syst&egrave;mes d'exploitation utilis&eacute;s\" /></center>\n");
$site->add_contents($cts);

$site->end_page ();

?>
