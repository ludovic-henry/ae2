<?php
/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License a
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
 *  Mini site de consultation du petit géni
 *
 */

$topdir = "./";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/mysqlpg.inc.php");
require_once($topdir. "include/catalog.inc.php");
require_once($topdir."include/cts/board.inc.php");

$site = new site ();
$site->start_page ("services", "Petit géni");
$cts = new contents();
$cts->add_title(2, "Petit géni : c'est repartit !");
$cts->add_paragraph("Le petit géni est actuellement en reconstruction, la nouvelle version sera bientôt disponible. En attendant, pour toutes questions ou renseignements, merci de contacter l'équipe à l'adresse suivante: petit [dot] geni [at] utbm [dot] fr");
$site->add_contents($cts);
$site->end_page();
exit();

$dbpg = new mysqlpg ();

$sqlbase = "SELECT pg_liste.*," .
      "pg_cat1.nom AS nom_cat1, pg_cat1.id AS id_cat1," .
      "pg_cat2.nom AS nom_cat2, pg_cat2.id AS id_cat2, " .
      "pg_cat3.nom AS nom_cat3, pg_cat3.id AS id_cat3," .
      "pg_voie.nom AS nom_voie, pg_voie.id AS id_voie, " .
      "pg_voie_type.nom AS nom_voie_type, pg_voie_type.id AS id_voie_type," .
      "pg_secteur2.nom AS nom_secteur, pg_secteur2.id AS id_secteur " .
      "FROM pg_liste " .
      "INNER JOIN pg_cat3 ON pg_cat3.id =pg_liste.cat " .
      "INNER JOIN pg_cat2 ON pg_cat2.id =pg_cat3.id_cat2 " .
      "INNER JOIN pg_cat1 ON pg_cat1.id =pg_cat2.id_cat1 " .
      "LEFT JOIN pg_voie ON pg_voie.id=pg_liste.voie " .
      "LEFT JOIN pg_voie_type ON pg_voie_type.id=pg_voie.id_type " .
      "LEFT JOIN pg_secteur2 ON pg_secteur2.id=pg_liste.secteur " .
      "WHERE ( pg_liste.status = 1 AND pg_liste.print_web = 1 ) ";

function afficher_encart($id){
  if (file_exists("/var/www/ae/accounts/petitgeni/images/encarts/".$id."_mini.jpg")){
    return "<a href=\"/petitgeni/images/encarts/".$id."_rvb.jpg\"><img src=\"/petitgeni/images/encarts/".$id."_mini.jpg\" alt=\"Encart publicitaire\"></a>\n";
  }
}

class pgresults extends stdcontent
{

  function pgresults ( $title, $req )
  {
    $this->title = $title;
    $i = 0;
    $this->buffer .= "<div class=\"fiches\">\n";
    while ( $row = $req->get_row())
    {
      if (strstr($row['nom_secteur'], "Belfort")){
        $page_secteur = "plans_belfort";
        $secteur = substr($row['nom_secteur'], 9, 2);
      }
      else
      {
        $page_secteur = "plans_territoire";
        $secteur = "";
      }

      $gde_cat_min = strtolower($row['nom_cat1']);
      $adresse = ($row['no']?($row['no'].", "):'') . ($row['nom_voie_type']?(($row['nom_voie_type'][strlen($row['nom_voie_type'])-1] == "'")?($row['nom_voie_type']):($row['nom_voie_type']." ")):'') . $row['nom_voie'];

      $i++;
      $this->buffer .= ($row['mav']||$row['encart'])?"<div class=\"".$gde_cat_min."_faible\">":"";
      $this->buffer .= "<div class=\"fiche\">\n";
      $this->buffer .= "  <div class=\"lienfixe\"><a href=\"/petitgeni/?page=recherche&amp;mode=fiche&amp;id=".$row['id']."\" title=\"Lien fixe\">&sect;</a></div>\n";
      $this->buffer .= "  <h4 class=\"".$gde_cat_min."_texte\">".utf8_encode($row['nom'])."</h4>\n";
      $this->buffer .= $row['tel']?"  <div class=\"tel\"><em>tel</em> : ".utf8_encode($row['tel'])."</div>\n":"";
      $this->buffer .= "  <div class=\"adresse\">".utf8_encode($adresse)."</div>\n";
      $this->buffer .= $row['fax']?"  <div class=\"fax\"><em>fax</em> : ".utf8_encode($row['fax'])."</div>\n":"";
      $this->buffer .= "  <div class=\"secteur\"><a href=\"/petitgeni/?page=".$page_secteur."&amp;secteur=".utf8_encode($secteur)."\">".utf8_encode($row['nom_secteur'])."</a></div>\n";
      $this->buffer .= "  <div class=\"cat\"><a href=\"pgae.php?id_cat3=".$row['id_cat3']."\">".utf8_encode($row['nom_cat1'])." / ".utf8_encode($row['nom_cat2'])." / ".utf8_encode($row['nom_cat3'])."</a></div>\n";
      $this->buffer .= $row['http']?"  <div class=\"http\"><a href=\"".$row['http']."\">".utf8_encode($row['http'])."</a></div>\n":"";
      $this->buffer .= $row['email']?"  <div class=\"email\"><a href=\"mailto:".$row['email']."\">".utf8_encode($row['email'])."</a></div>\n":"";
      $this->buffer .= $row['reduc_petitgeni']?"  <div class=\"reduc_petitgeni\">".utf8_encode($row['reduc_petitgeni'])."</div>\n":"";
      if ($row['description'] || $row['horaire'])
        $ok = true ;
      $this->buffer .= $ok?"  <div class=\"montrer_details\" onclick=\"on_off('detail$i');\"><span>[Détails]</span></div>\n":"";
      $this->buffer .= ($row['mav']||$row['encart'])?"  <div class=\"".$gde_cat_min."_fort\">\n":"  <div class=\"".$gde_cat_min."_faible\">\n";
      $this->buffer .= "  <div class=\"details\" id=\"detail$i\" style=\"display: none;\">\n";
      $this->buffer .= $ok?"    <div class=\"description\">".utf8_encode($row['description'])."</div>\n":"";
      $this->buffer .= $ok?"    <div class=\"horaire\">".utf8_encode($row['horaire'])."</div>\n":"";
      $this->buffer .= "  </div>\n";
      $this->buffer .= "  </div>\n";
      $this->buffer .= "    <div class=\"encart\">".afficher_encart($row['id'])."</div>\n";
      $this->buffer .= "</div>\n";
      $this->buffer .= ($row['mav']||$row['encart'])?"</div>\n":"";

    }
    $this->buffer .= "</div>\n";
  }
}

$info = new contents("Le petit géni");
$info->add_paragraph("Le Petit GÉNI, c'est tout le nécessaire pour vivre à Belfort.<br/> Belfortain depuis des générations ? Tout juste arrivé ? De passage pour quelques jours ?<br/>Le Petit GÉNI est là pour vous aider !");
$info->add_paragraph("<br/><a href=\"asso.php?id_asso=26\">Presentation</a>");

$site->add_box("pginfo",$info);

$info = new contents("Malin");
$info->add_paragraph("<a href=\"/petitgeni/?page=malin_offre\"><img src=\"/petitgeni/styles/defaut/malin_offre.png\" alt=\"\" /><br />Offres Petit GÉNI</a>","center");
$info->add_paragraph("<a href=\"/petitgeni/?page=malin_proposition\"><img src=\"/petitgeni/styles/defaut/malin_proposition.png\" alt=\"\" /><br />Ajouter une fiche</a>","center");
$info->add_paragraph("<a href=\"/petitgeni/?page=malin_pdf\"><img src=\"/petitgeni/styles/defaut/malin_pdf.png\" alt=\"\" /><br />Obtenir le pdf</a>","center");
$info->add_paragraph("&nbsp;");
$info->add_paragraph("<a href=\"".$topdir."iinfo.php\">Plus d'informations...</a>","center");
$site->add_box("pgmalin",$info);

$site->set_side_boxes("right",array("pginfo","pgmalin"),"pg_right");


$site->start_page ("pg", "Petit géni");

$site->add_css("css/pg.css");

class pgitemlist extends stdcontent
{

  function pgitemlist ( $title=false,$class=false)
  {
    $this->title = $title;
    $this->class = $class;
  }

  function add ( $item, $class=false )
  {
    if ( is_object($item) )
      $this->buffer .= "<div".($class?" class=\"item ".$class."\"":" class=\"item\"").">".$item->title."\n".$item->html_render()."</div>\n";
    else
      $this->buffer .= "<div".($class?" class=\"item ".$class."\"":" class=\"item\"").">".$item."</div>\n";
  }

  function html_render()
  {
    return "<div".($this->class?" class=\"list ".$this->class."\"":" class=\"list\"").">\n".$this->buffer."\n</div>\n";
  }
}

if ( isset($_REQUEST["id_cat1"]) )
{
  $sql1 = new requete($dbpg,"SELECT nom,id FROM pg_cat1 WHERE id='".mysql_real_escape_string($_REQUEST["id_cat1"])."'");
  list($nom,$id_cat1) = $sql1->get_row();

  $lst = new pgitemlist("<a href=\"pgae.php\">Le guide</a> / <a href=\"pgae.php?id_cat1=$id_cat1\">".utf8_encode($nom)."</a>","pg_cat1");

  $sql2 = new requete($dbpg,"SELECT nom,id FROM pg_cat2 WHERE id_cat1='$id_cat1' ORDER BY nom");

  while ( list($nom,$id_cat2) = $sql2->get_row() )
  {
    $sublst = new pgitemlist("<a href=\"pgae.php?id_cat2=$id_cat2\">".utf8_encode($nom)."</a>");

    $sql3 = new requete($dbpg,"SELECT nom,id FROM pg_cat3 WHERE id_cat2='$id_cat2' ORDER BY nom");
    while ( list($nom,$id_cat3) = $sql3->get_row() )
      $sublst->add("<a href=\"pgae.php?id_cat3=$id_cat3\">".utf8_encode($nom)."</a>");

    $lst->add($sublst,"pg_$id_cat1");
  }

  $site->add_contents ($lst);
}
elseif ( isset($_REQUEST["id_cat2"]) )
{

  $sql = new requete($dbpg,"SELECT pg_cat1.nom, pg_cat1.id," .
      "pg_cat2.nom, pg_cat2.id " .
      "FROM pg_cat2 " .
      "INNER JOIN pg_cat1 ON pg_cat1.id =pg_cat2.id_cat1 " .
      "WHERE pg_cat2.id='".mysql_real_escape_string($_REQUEST["id_cat2"])."'");

  list($nom1,$id_cat1,$nom2,$id_cat2) = $sql->get_row();

  $lst = new pgitemlist("<a href=\"pgae.php\">Le guide</a> / <a href=\"pgae.php?id_cat1=$id_cat1\">".utf8_encode($nom1)."</a> / <a href=\"pgae.php?id_cat2=$id_cat2\">".utf8_encode($nom2)."</a>","pg_cat1");
  $sublst = new pgitemlist("<a href=\"pgae.php?id_cat2=$id_cat2\">".utf8_encode($nom2)."</a>");

  $sql3 = new requete($dbpg,"SELECT nom,id FROM pg_cat3 WHERE id_cat2='$id_cat2' ORDER BY nom");
  while ( list($nom,$id_cat3) = $sql3->get_row() )
    $sublst->add("<a href=\"pgae.php?id_cat3=$id_cat3\">".utf8_encode($nom)."</a>");

  $lst->add($sublst,"pg_$id_cat1");
  $site->add_contents ($lst);

}
elseif ( isset($_REQUEST["id_cat3"]) )
{
  $sql = new requete($dbpg,"SELECT pg_cat1.nom, pg_cat1.id," .
      "pg_cat2.nom, pg_cat2.id, " .
      "pg_cat3.nom, pg_cat3.id " .
      "FROM pg_cat3 " .
      "INNER JOIN pg_cat2 ON pg_cat2.id =pg_cat3.id_cat2 " .
      "INNER JOIN pg_cat1 ON pg_cat1.id =pg_cat2.id_cat1 " .
      "WHERE pg_cat3.id='".mysql_real_escape_string($_REQUEST["id_cat3"])."'");

  list($nom1,$id_cat1,$nom2,$id_cat2,$nom3,$id_cat3) = $sql->get_row();

  $req = new requete($dbpg,$sqlbase."AND cat='".mysql_real_escape_string($_REQUEST["id_cat3"])."' ORDER BY pg_liste.nom");

  $site->add_contents (new pgresults("<a href=\"pgae.php\">Le guide</a> / <a href=\"pgae.php?id_cat1=$id_cat1\">".utf8_encode($nom1)."</a> / <a href=\"pgae.php?id_cat2=$id_cat2\">".utf8_encode($nom2)."</a> / <a href=\"pgae.php?id_cat3=$id_cat3\">".utf8_encode($nom3)."</a>",$req));

}
elseif ( isset($_REQUEST["recherche"]))
{
  $patterns=explode(" ",$_REQUEST["recherche"]);

  $reqf="";

  foreach ( $patterns as $value ) {

    $value = utf8_decode(mysql_real_escape_string($value));

    if ( $reqf ) $reqf .= " AND ";

    $reqf .= "(pg_liste.nom REGEXP '[[:<:]]".$value."[[:>:]]' OR ".
        "pg_liste.description REGEXP '[[:<:]]".$value."[[:>:]]')";
  }

  $cts = new contents("Recherche \"".$_REQUEST["recherche"]."\"");

  $req = new requete($dbpg,"SELECT pg_cat1.nom, pg_cat1.id," .
      "pg_cat2.nom, pg_cat2.id, " .
      "pg_cat3.nom, pg_cat3.id " .
      "FROM pg_cat3 " .
      "INNER JOIN pg_cat2 ON pg_cat2.id =pg_cat3.id_cat2 " .
      "INNER JOIN pg_cat1 ON pg_cat1.id =pg_cat2.id_cat1 " .
      "WHERE pg_cat3.nom LIKE '%".utf8_decode(mysql_real_escape_string($_REQUEST["recherche"]))."%'");

  $lst = new pgitemlist("Catégories");

  if ( $req->lines == 0 )
    $lst->add("Aucun résultats");
  else
  {
    while ( list($nom1,$id_cat1,$nom2,$id_cat2,$nom3,$id_cat3) = $req->get_row() )
    {
      $lst->add("<a href=\"pgae.php?id_cat3=$id_cat3\">".utf8_encode($nom1)." / ".utf8_encode($nom2)." / ".utf8_encode($nom3)."</a>");
    }
  }
  $cts->add($lst,true);

  $req = new requete($dbpg,$sqlbase."AND ($reqf) ORDER BY pg_liste.nom");
  if ( $req->lines == 0 )
    $cts->add(new contents("Fiches","<p>Aucun résultats</p>"),true);
  else
    $cts->add(new pgresults("Fiches",$req),true);

  $site->add_contents ($cts);
}
else
{
  $cts = new contents("Le guide");

  $board = new board(false,"pg_guide");


  $sql1 = new requete($dbpg,"SELECT nom,id FROM pg_cat1 ORDER BY ordre");

  while ( list($nom,$id_cat1) = $sql1->get_row() )
  {
    $sublst = new pgitemlist("<a href=\"pgae.php?id_cat1=$id_cat1\">".utf8_encode($nom)."</a>");

    $sql2 = new requete($dbpg,"SELECT nom,id FROM pg_cat2 WHERE id_cat1='$id_cat1' ORDER BY nom");
    while ( list($nom,$id_cat2) = $sql2->get_row() )
      $sublst->add("<a href=\"pgae.php?id_cat2=$id_cat2\">".utf8_encode($nom)."</a>");

    $board->add($sublst,true,"pg_$id_cat1");
  }

  $cts->add($board);

  $site->add_contents ($cts);


  $frm = new form("rechpg2","pgae.php",false,"POST","Rechercher");
  $frm->add_text_field("recherche","Faites un voeu");
  $frm->add_submit("btnrechpg2","Exaucer!");
  $site->add_contents ($frm);
}
$site->end_page ();
?>
