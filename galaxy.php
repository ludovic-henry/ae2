<?php
/* Copyright 2007
 *
 * - Julien Etelain < julien at pmad dot net >
 *
 * "AE Recherche & Developpement" : Galaxy
 *
 * Ce fichier fait partie du site de l'Association des étudiant
 * de l'UTBM, http://ae.utbm.fr.
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

$topdir = "./";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/galaxy.inc.php");
require_once($topdir. "include/cts/sqltable2.inc.php");

$site = new site ();
$site->allow_only_logged_users("matmatronch");

if ( !$site->user->utbm && !$site->user->ae )
  $site->error_forbidden("matmatronch","group",10001);

$galaxy = new galaxy($site->db,$site->dbrw);

// trichons un peu...

$GLOBALS["entitiescatalog"]["utilisateur"][3]="galaxy.php";

$ready = $galaxy->is_ready_public();

if ( !$ready )
{
  if ( $_REQUEST["action"] == "area_image" || $_REQUEST["action"] == "area_html"  )
    exit();
  $site->fatal_partial("matmatronch");
  exit();
}

define('AREA_WIDTH',500);
define('AREA_HEIGHT',500);

if ( $_REQUEST["action"] == "area_image" || $_REQUEST["action"] == "area_html"  )
{
  $lastModified = gmdate('D, d M Y H:i:s', filemtime("data/img/mini_galaxy.png") ) . ' GMT';
  $etag=md5($_SERVER['SCRIPT_FILENAME']."?".$_SERVER['QUERY_STRING'].'#'.$lastModified);

  if ( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) )
  {
    $ifModifiedSince = preg_replace('/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE']);
    if ($lastModified == $ifModifiedSince)
    {
      header("HTTP/1.0 304 Not Modified");
      header('ETag: "'.$etag.'"');
      exit();
    }
  }

  if ( isset($_SERVER['HTTP_IF_NONE_MATCH']) )
  {
    if ( $etag == str_replace('"', '',stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) )
    {
      header("HTTP/1.0 304 Not Modified");
      header('ETag: "'.$etag.'"');
      exit();
    }
  }
  header("Cache-Control: must-revalidate");
  header("Pragma: cache");
  header("Last-Modified: ".$lastModified);
  header("Cache-Control: public");
  header('ETag: "'.$etag.'"');
}

if ( $_REQUEST["action"] == "area_image" )
{
  $highlight = null;

  if ( isset($_REQUEST["highlight"]) )
    $highlight = explode(",",$_REQUEST["highlight"]);

  header("Content-type: image/png");
  $galaxy->render_area ( intval($_REQUEST['x']), intval($_REQUEST['y']), AREA_WIDTH, AREA_HEIGHT, null, $highlight );
  exit();
}

if ( $_REQUEST["action"] == "area_html" )
{
    header("Content-Type: text/html; charset=utf-8");
    $tx = intval($_REQUEST['x']);
    $ty = intval($_REQUEST['y']);

  if ( isset($_REQUEST["highlight"]) )
      echo "<div style=\"position:relative;\"><img src=\"?action=area_image&amp;x=$tx&amp;y=$ty&amp;highlight=".htmlspecialchars($_REQUEST["highlight"])."\" style=\"position:absolute;top:0px;left:0px;\" />";
  else
      echo "<div style=\"position:relative;\"><img src=\"?action=area_image&amp;x=$tx&amp;y=$ty\" style=\"position:absolute;top:0px;left:0px;\" />";


  $x1 = $tx;
  $y1 = $ty;
  $x2 = $tx+(AREA_WIDTH);
  $y2 = $ty+(AREA_HEIGHT);
  $req = new requete($site->db, "SELECT ".
    "rx_star, ry_star, id_star ".
    "FROM  galaxy_star ".
    "WHERE rx_star >= $x1 AND rx_star <= $x2 AND ry_star >= $y1 AND ry_star <= $y2" );
  while($row = $req->get_row() )
  {
    $x = $row["rx_star"]-$tx-3;
    $y = $row["ry_star"]-$ty-3;
    $id = $row["id_star"];
    echo "<a href=\"galaxy.php?id_utilisateur=$id\" id=\"g$id\" onmouseover=\"show_tooltip('g$id','./','utilisateur','$id');\" onmouseout=\"hide_tooltip('g$id');\" style=\"position:absolute;left:".$x."px;top:".$y."px;width:6px;height:6px;overflow:hidden;\" >&nbsp;</a>";
  }
  echo"</div>";
  exit();
}

if ( $_REQUEST["action"] == "info" )
{
  $user_a = new utilisateur($site->db);
  $user_a->load_by_id($_REQUEST["id_utilisateur_a"]);

  $user_b = new utilisateur($site->db);
  $user_b->load_by_id($_REQUEST["id_utilisateur"]);

    if ( !$user_a->is_valid() || !$user_b->is_valid() )
        $site->error_not_found("matmatronch");

  $site->start_page("matmatronch","galaxy");
  $cts = new contents($user_a->prenom." ".$user_a->nom);
  $tabs = $user_a->get_tabs($site->user);
  $cts->add(new tabshead($tabs,"galaxy"));

  $cts->add_title(2,"Cacul du score \"galaxy\" : ".$user_a->get_html_link()." - ".$user_b->get_html_link());

  $total=0;

  $reasons = new itemlist();

  $req = new requete($site->db, "SELECT COUNT( * ) as c ".
    "FROM `sas_personnes_photos` AS `p1` ".
    "JOIN `sas_personnes_photos` AS `p2` ON ( p1.id_photo = p2.id_photo ".
    "AND p1.id_utilisateur != p2.id_utilisateur ) ".
    "WHERE p1.id_utilisateur='".intval($user_a->id)."' AND p2.id_utilisateur='".intval($user_b->id)."' ");

  list($nbphotos) = $req->get_row();

  $total += round($nbphotos/GALAXY_SCORE_1PTPHOTO);

  $reasons->add("$nbphotos photos ensemble : ".round($nbphotos/GALAXY_SCORE_1PTPHOTO)." points");

  $req = new requete($site->db, "SELECT COUNT(*) ".
    "FROM `parrains` ".
    "WHERE (id_utilisateur='".intval($user_a->id)."' AND id_utilisateur_fillot='".intval($user_b->id)."') ".
    "OR (id_utilisateur='".intval($user_b->id)."' AND id_utilisateur_fillot='".intval($user_a->id)."')");

  list($nbpar) = $req->get_row();

  $total += $nbpar*GALAXY_SCORE_PARRAINAGE;

  $reasons->add("$nbpar lien de parrainage : ".($nbpar*GALAXY_SCORE_PARRAINAGE)." points");

  $req = new requete($site->db,"SELECT asso.nom_asso, a.id_asso,
  SUM(DATEDIFF(LEAST(COALESCE(a.date_fin,NOW()),COALESCE(b.date_fin,NOW())),GREATEST(a.date_debut,b.date_debut))) AS together
  FROM asso_membre AS a
  JOIN asso_membre AS b ON
  (
  b.id_utilisateur='".intval($user_b->id)."'
  AND a.id_asso = b.id_asso
  AND GREATEST(a.date_debut,b.date_debut) < LEAST(COALESCE(a.date_fin,NOW()),COALESCE(b.date_fin,NOW()))
  )
  INNER JOIN asso ON (asso.id_asso = a.id_asso)
  WHERE a.id_utilisateur='".intval($user_a->id)."'
  AND a.role > 0
  AND b.role > 0
  GROUP BY a.id_asso");

  while ( $row = $req->get_row() )
  {
    $reasons->add($row["together"]." jours ensemble à ".$row["nom_asso"]." en membres actifs: ".round((1-exp(-$row["together"]/365))*GALAXY_SCORE_2ANNEESACTIFASSO)." points");
    $total += round((1-exp(-$row["together"]/365))*GALAXY_SCORE_2ANNEESACTIFASSO);
  }

  $req = new requete($site->db,"SELECT asso.nom_asso, a.id_asso,
  SUM(DATEDIFF(LEAST(COALESCE(a.date_fin,NOW()),COALESCE(b.date_fin,NOW())),GREATEST(a.date_debut,b.date_debut))) AS together
  FROM asso_membre AS a
  JOIN asso_membre AS b ON
  (
  b.id_utilisateur='".intval($user_b->id)."'
  AND a.id_asso = b.id_asso
  AND a.date_debut > b.date_fin
  AND GREATEST(a.date_debut,b.date_debut) < LEAST(COALESCE(a.date_fin,NOW()),COALESCE(b.date_fin,NOW()))
  )
  INNER JOIN asso ON (asso.id_asso = a.id_asso)
  WHERE a.id_utilisateur='".intval($user_a->id)."'
  AND NOT (a.role > 0 AND b.role > 0)
  GROUP BY a.id_asso");

  while ( $row = $req->get_row() )
  {
    $reasons->add($row["together"]." jours ensemble à ".$row["nom_asso"]." : ".round((1-exp(-$row["together"]/365))*GALAXY_SCORE_2ANNEESASSO)." points");
    $total += round((1-exp(-$row["together"]/365))*GALAXY_SCORE_2ANNEESASSO);
  }


  $reasons->add("<b>Total: ".round($total)." points</b>");

  if ( round($total) < GALAXY_MINSCORE )
    $reasons->add("<i>Score trop faible pour le lien puisse être considéré comme pertinent</i>");

  $cts->add($reasons);

  $cts->add_title(2,"A propos de galaxy");
  $cts->add_paragraph("<a href=\"article.php?name=rd:galaxy\">Explications sur ce qu'est et ce que n'est pas galaxy</a>");
  $site->add_contents($cts);
  $site->end_page();
  exit();
}
elseif ( isset($_REQUEST["id_utilisateur"]) )
{
  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($_REQUEST["id_utilisateur"]);

    if ( !$user->is_valid() )
        $site->error_not_found("rd");

  $site->add_js("js/sqltable2.js");
  $site->start_page("matmatronch","galaxy");
  $cts = new contents($user->prenom." ".$user->nom);
  $tabs = $user->get_tabs($site->user);
  $cts->add(new tabshead($tabs,"galaxy"));

  $req = new requete($site->db,"SELECT rx_star,ry_star FROM galaxy_star WHERE id_star='".mysql_real_escape_string($user->id)."'");

  if ( $user->publique < 1 )
  {
    $cts->add_paragraph("C'est utilisateur n'est pas présent dans galaxy car son profil n'est pas publique.");
    if ($user->id==$site->user->id)
      $cts->add_paragraph("Pour apparaitre dans galaxy, vous devez rendre votre profil publique ".
          "<a href=\"http://ae.utbm.fr/taiste/user.php?id_utilisateur=".$site->user->id."&page=edit\">en éditant votre fiche Matmatronch</a>.".
          "Vous serez alors automatiquement intégré à galaxy à condition d'être lié à d'autres utilisateurs.");
  }
  elseif ( $req->lines == 0 )
  {
    $cts->add_paragraph("C'est utilisateur n'est pas présent dans galaxy.");
    $cts->add_paragraph("Deux raisons peuvent expliquer cela : soit cet utilisateur n'a pas assez de liens avec les autres pour que son ajout ait un sens, soit le profil de cet utilisateur n'est pas publique ou a été rendu publique récemment.");
  }
  else
  {
    list($rx,$ry) = $req->get_row();

    $cts->add_title(2,"Localisation");

    $hl = $user->id;

    $req = new requete($site->db,
    "SELECT id_star_a
    FROM galaxy_link
    WHERE id_star_b='".mysql_real_escape_string($user->id)."'
    UNION
    SELECT id_star_b
    FROM galaxy_link
    WHERE id_star_a='".mysql_real_escape_string($user->id)."'");

    while (list($id) = $req->get_row() )
      $hl .= ",".$id;

    $tx = intval($rx-375);
    $ty = intval($ry-250);

$site->add_css("css/galaxy.css");
$site->add_js("js/galaxy.js");

$cts->puts("<div class=\"viewer\" id=\"viewer\">
<div class=\"square\" id=\"square0\"></div>
<div class=\"square\" id=\"square1\"></div>
<div class=\"square\" id=\"square2\"></div>
<div class=\"square\" id=\"square3\"></div>
<div class=\"square\" id=\"square4\"></div>
<div class=\"square\" id=\"square5\"></div>
<div class=\"square\" id=\"square6\"></div>
<div class=\"square\" id=\"square7\"></div>
<div class=\"square\" id=\"square8\"></div>
<div class=\"square\" id=\"square9\"></div>
<div class=\"square\" id=\"square10\"></div>
<div class=\"square\" id=\"square11\"></div>
<div class=\"square\" id=\"square12\"></div>
<div class=\"square\" id=\"square13\"></div>
<div class=\"square\" id=\"square14\"></div>
<div class=\"square\" id=\"square15\"></div>
<div class=\"map\" id=\"map\"><img src=\"data/img/mini_galaxy.png\" />
<div class=\"position\" id=\"position\"></div></div></div><script>init_galaxy($tx,$ty,\"&highlight=$hl\");</script>");


    $sql = "SELECT `length_link`, `ideal_length_link`, `tense_link`,
    IF(`id_star_a`='".mysql_real_escape_string($user->id)."',
      COALESCE(`utl_etu_utbm_b`.`surnom_utbm`, CONCAT(`utilisateurs_b`.`prenom_utl`,' ',`utilisateurs_b`.`nom_utl`), `utilisateurs_b`.`alias_utl`),
      COALESCE(`utl_etu_utbm_a`.`surnom_utbm`, CONCAT(`utilisateurs_a`.`prenom_utl`,' ',`utilisateurs_a`.`nom_utl`), `utilisateurs_a`.alias_utl)) AS `nom_utilisateur`,
    IF(`id_star_a`='".mysql_real_escape_string($user->id)."',
      `utilisateurs_b`.`id_utilisateur`,
      `utilisateurs_a`.`id_utilisateur`) AS `id_utilisateur`
    FROM `galaxy_link`
    INNER JOIN `utilisateurs` `utilisateurs_a` ON (`id_star_a`=`utilisateurs_a`.`id_utilisateur`)
    INNER JOIN `utilisateurs` `utilisateurs_b` ON (`id_star_b`=`utilisateurs_b`.`id_utilisateur`)
    LEFT OUTER JOIN `utl_etu_utbm` `utl_etu_utbm_a` ON (`utl_etu_utbm_a`.`id_utilisateur` = `utilisateurs_a`.`id_utilisateur`)
    LEFT OUTER JOIN `utl_etu_utbm` `utl_etu_utbm_b` ON (`utl_etu_utbm_b`.`id_utilisateur` = `utilisateurs_b`.`id_utilisateur`)
    WHERE `id_star_a`='".mysql_real_escape_string($user->id)."' OR `id_star_b`='".mysql_real_escape_string($user->id)."'
    ORDER BY 1";

    $tbl = new sqltable2("listlies", "Personnes liées", "galaxy.php?id_utilisateur_a=".$user->id, "galaxy.php?id_utilisateur=".$user->id);
    $tbl->add_action("info", "Infos");
    $tbl->add_column_number("length_link", "Distance réelle");
    $tbl->add_column_number("ideal_length_link", "Distance cible");
    $tbl->add_column_number("tense_link", "Score");
    $tbl->add_column_entity("nom_utilisateur", "Nom");
    $tbl->set_sql($site->db, "id_utilisateur", $sql);
    $cts->add($tbl,true);

    $cts->add_paragraph("Le score par lien est calculé à partir du nombre de photos où vous êtes tous deux présents, les liens de parrainage, et le temps inscrits dans les mêmes clubs et associations. Ensuite le score permet de déterminer la longueur du lien en fonction du score maximal de tous les liens de chaque personne. Cliquer sur l'icone \"infos\" pour connaitre le calcul du score");

    $sql = "SELECT SQRT(POW(a.x_star-b.x_star,2)+POW(a.y_star-b.y_star,2)) AS dist,
    COALESCE(surnom_utbm, CONCAT(prenom_utl,' ',nom_utl), alias_utl) AS nom_utilisateur,
    utilisateurs.id_utilisateur
    FROM galaxy_star AS a, galaxy_star AS b, utilisateurs
    INNER JOIN `utl_etu_utbm` ON (`utl_etu_utbm`.`id_utilisateur` = `utilisateurs`.`id_utilisateur`)
    WHERE a.id_star='".mysql_real_escape_string($user->id)."'
    AND a.id_star!=b.id_star
    AND b.id_star=`utilisateurs`.`id_utilisateur`
    AND POW(a.x_star-b.x_star,2)+POW(a.y_star-b.y_star,2) < 4
    ORDER BY 1";

    $tbl = new sqltable2("listvoisins", "Voisinnage", "galaxy.php?id_utilisateur=".$user->id);
    $tbl->add_column_number("dist", "Distance");
    $tbl->add_column_entity("nom_utilisateur", "Nom");
    $tbl->set_sql($site->db, "id_star", $sql);
    $cts->add($tbl,true);

    $cts->add_paragraph("Il est possible que de nombreuses personnes soient dans votre \"voisinage\" par pur hasard. Cependant en général il s'agit soit de personnes liées soit de personnes avec un profil similaire.");
  }

  $cts->add_title(2,"A propos de galaxy");
  $cts->add_paragraph("<a href=\"article.php?name=rd:galaxy\">Explications sur ce qu'est et ce que n'est pas galaxy</a>");

  $site->add_contents($cts);
  $site->end_page();
  exit();
}


$site->start_page("matmatronch","Galaxy");
$cts = new contents("Galaxy");

$site->add_css("css/galaxy.css");
$site->add_js("js/galaxy.js");

list($top_x,$top_y,$bottom_x,$bottom_y) = $galaxy->limits();

$top_x = floor($top_x);
$top_y = floor($top_y);
$bottom_x = ceil($bottom_x);
$bottom_y = ceil($bottom_y);

$goX = (($bottom_x-$top_x)*50)-375;
$goY = (($bottom_y-$top_y)*50)-250;

$cts->add_title(2,"Voici galaxy");

$cts->puts("<div class=\"viewer\" id=\"viewer\">
<div class=\"square\" id=\"square0\"></div>
<div class=\"square\" id=\"square1\"></div>
<div class=\"square\" id=\"square2\"></div>
<div class=\"square\" id=\"square3\"></div>
<div class=\"square\" id=\"square4\"></div>
<div class=\"square\" id=\"square5\"></div>
<div class=\"square\" id=\"square6\"></div>
<div class=\"square\" id=\"square7\"></div>
<div class=\"square\" id=\"square8\"></div>
<div class=\"square\" id=\"square9\"></div>
<div class=\"square\" id=\"square10\"></div>
<div class=\"square\" id=\"square11\"></div>
<div class=\"square\" id=\"square12\"></div>
<div class=\"square\" id=\"square13\"></div>
<div class=\"square\" id=\"square14\"></div>
<div class=\"square\" id=\"square15\"></div>
<div class=\"map\" id=\"map\"><img src=\"data/img/mini_galaxy.png\" />
<div class=\"position\" id=\"position\"></div></div></div><script>init_galaxy($goX,$goY,\"\");</script>");

//$cts->add_paragraph("<a href=\"var/galaxy.png\">Tout galaxy sur une seule image</a>");

$frm = new form("galaxygo",$topdir."galaxy.php",true,"GET","Aller vers une personne");
$frm->add_entity_smartselect("id_utilisateur","Nom/Surnom",new utilisateur($site->db));
$frm->add_submit("go","Y aller");

$cts->add($frm,true);

$cts->add_title(2,"A propos de galaxy");
$cts->add_paragraph("<a href=\"article.php?name=rd:galaxy\">Explications sur ce qu'est et ce que n'est pas galaxy</a>");


$site->add_contents($cts);
$site->end_page();

?>
