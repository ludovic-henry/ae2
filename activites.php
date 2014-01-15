<?php

/* Copyright 2008
 * - Benjamin Collet < bcollet AT oxynux DOT org >
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

$topdir = "./";

require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");

$site = new site ();

$site->start_page("presentation","Activités");

$cts = new contents("Responsables des clubs");
if ($site->user->is_in_group("gestion_ae"))
{
  $tabs = array();
  $tabs[] = array("list","activites.php?view=list","Liste");
  $tabs[] = array("trombino","activites.php?view=trombino","Trombino");
  $cts->add(new tabshead($tabs, (isset($_REQUEST['view'])) ? $_REQUEST['view'] : 'trombino'));
}

if (($_REQUEST['view'] == "list") && ($site->user->is_in_group("gestion_ae")))
{
  $req_assos = new requete($site->db, "SELECT asso_parent.id_asso AS id_asso_parent,
        asso_parent.nom_asso AS nom_asso_parent,
        asso.id_asso AS id_asso,
        asso.nom_asso AS nom_asso,
        utilisateurs_resp.id_utilisateur as id_utilisateur_resp,
        CONCAT(utilisateurs_resp.nom_utl,' ',utilisateurs_resp.prenom_utl) AS nom_utilisateur_resp,
        utilisateurs_tres.id_utilisateur AS id_utilisateur_tres,
        CONCAT(utilisateurs_tres.nom_utl,' ',utilisateurs_tres.prenom_utl) AS nom_utilisateur_tres
      FROM asso
      LEFT JOIN asso_membre AS tbl_resp ON (tbl_resp.id_asso=asso.id_asso AND tbl_resp.role='10' AND tbl_resp.date_fin IS NULL)
      LEFT JOIN asso_membre AS tbl_tres ON (tbl_tres.id_asso=asso.id_asso AND tbl_tres.role='7' AND tbl_tres.date_fin IS NULL)
      LEFT JOIN utilisateurs AS utilisateurs_resp ON tbl_resp.id_utilisateur=utilisateurs_resp.id_utilisateur
      LEFT JOIN utilisateurs AS utilisateurs_tres ON tbl_tres.id_utilisateur=utilisateurs_tres.id_utilisateur
      INNER JOIN asso AS asso_parent ON asso.id_asso_parent=asso_parent.id_asso
      WHERE asso.id_asso_parent IN (SELECT id_asso FROM asso WHERE id_asso_parent='1')
      AND `asso`.`hidden` = '0'
      GROUP BY asso.id_asso
      ORDER BY asso_parent.nom_asso, asso.nom_asso");

  $table = new sqltable("", "Liste des responsables et des trésoriers des activités", $req_assos, "", "",
                        array("nom_asso_parent" => "Pôle",
                              "nom_asso" => "Activité",
                              "nom_utilisateur_resp" => "Responsable",
                              "nom_utilisateur_tres" => "Trésorier"
                              ),
                        array(), array(), array() );

  $cts->add($table);
}
else
{
  require_once($topdir."include/cts/gallery.inc.php");

  $site->add_css("css/sas.css");

  $reqpoles = new requete($site->db,
    "SELECT `id_asso`, `nom_asso` FROM `asso` WHERE `id_asso_parent` = '1' AND hidden = '0'");

  while ( $rowpoles = $reqpoles->get_row() )
  {
    $cts->add_title(2, $rowpoles['nom_asso']);

    $req = new requete($site->db,
      "SELECT `utilisateurs`.`id_utilisateur`, " .
      "CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) as `nom_utilisateur`, " .
      "`asso`.`nom_asso`, ".
      "`asso`.`id_asso` ".
      "FROM `asso_membre` " .
      "INNER JOIN `utilisateurs` USING (`id_utilisateur`) " .
      "INNER JOIN `asso` USING (`id_asso`) " .
      "WHERE `asso_membre`.`date_fin` IS NULL " .
      "AND `asso_membre`.`role`='10' " .
      "AND `asso`.`id_asso_parent` = '".$rowpoles['id_asso']."'" .
      "AND `asso`.`hidden` = '0' " .
      ($site->user->is_in_group ("gestion_ae") ? "" : "AND `utilisateurs`.`publique_utl` >= '1'") .
      "GROUP BY `asso`.`id_asso` " .
      "ORDER BY `asso`.`nom_asso`");

    $gal = new gallery();
    while ( $row = $req->get_row() )
    {

      $img = $topdir."images/icons/128/user.png";
      if ( file_exists($topdir."data/matmatronch/".$row['id_utilisateur'].".identity.jpg") )
        $img = $topdir."data/matmatronch/".$row['id_utilisateur'].".identity.jpg";
      elseif ( file_exists($topdir."data/matmatronch/".$row['id_utilisateur'].".jpg") )
        $img = $topdir."data/matmatronch/".$row['id_utilisateur'].".jpg";

      $gal->add_item(
      "<a href=\"../user.php?id_utilisateur=".$row['id_utilisateur']."\"><img src=\"$img\" alt=\"Photo\" height=\"105\"></a>",
      "<a href=\"../user.php?id_utilisateur=".$row['id_utilisateur']."\">".htmlentities($row['nom_utilisateur'],ENT_NOQUOTES,"UTF-8")."</a><br /> <a href=\"../asso.php?id_asso=".$row['id_asso']."\">(".htmlentities($row['nom_asso'],ENT_NOQUOTES,"UTF-8").")</a>");
    }
    $cts->add($gal);
  }
}

$site->add_contents($cts);

$site->end_page();

?>
