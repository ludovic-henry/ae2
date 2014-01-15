<?php
/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
 *
 * Ce fichier fait partie du site de l'Association des Etudiants de
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

$topdir="../";
require_once("include/site.inc.php");
require_once($topdir."include/cts/pg.inc.php");

$site = new pgsite();

if ( $_REQUEST["page"] == "reductions" )
{

  $site->start_page("pgbplans","Réductions / Bon Plans - Petit Géni 2.0");

  $cts = new contents("<a href=\"bplans.php\">Bon plans</a> / <a href=\"bplans.php?page=reductions\">Réductions</a>");
  $legals=new pglegals();

  $req = new requete($site->db, "SELECT pg_typereduction.*, COUNT(pg_fiche_reduction.id_pgfiche) AS `nombre`
      FROM `pg_typereduction`
      LEFT JOIN `pg_fiche_reduction` ON(pg_fiche_reduction.id_typereduction=pg_typereduction.id_typereduction)
      GROUP BY id_typereduction
			ORDER BY nom_typereduction");

  while ( $row = $req->get_row() )
  {
    $cts->add_title(2,"<a name=\"reduc".$row["id_typereduction"]."\"></a>".htmlentities($row["nom_typereduction"],ENT_COMPAT,"UTF-8").$legals->add_condition("Pour toutes les conditions et réglements voir en magasin."));
    $cts->add(new wikicontents(null,$row["description_typereduction"]));

    if ( !empty($row["website_typereduction"]) )
      $cts->add_paragraph("Plus d'informations sur : <a href=\"".htmlentities($row["website_typereduction"],ENT_COMPAT,"UTF-8")."\">".htmlentities($row["website_typereduction"],ENT_COMPAT,"UTF-8")."</a>");

    if ( $row["nombre"] > 0 )
      $cts->add_paragraph("<a href=\"search.php?id_typereduction=".$row["id_typereduction"]."\">Voir la liste des commercants proposant ce type de réduction (".$row["nombre"].")</a>");

  }

  $cts->add($legals);
  $site->add_contents($cts);
  $site->end_page();
  exit();
}
elseif ( $_REQUEST["page"] == "services" )
{

  $site->start_page("pgbplans","Services / Bon Plans - Petit Géni 2.0");

  $cts = new contents("<a href=\"bplans.php\">Bon plans</a> / <a href=\"bplans.php?page=services\">Services</a>");
  $legals=new pglegals();

  $req = new requete($site->db, "SELECT pg_service.*, COUNT(pg_fiche_service.id_pgfiche) AS `nombre`
      FROM `pg_service`
      LEFT JOIN `pg_fiche_service` ON(pg_fiche_service.id_service=pg_service.id_service)
      GROUP BY id_service
			ORDER BY nom_service");

  while ( $row = $req->get_row() )
  {
    $cts->add_title(2,"<a name=\"reduc".$row["id_service"]."\"></a>".htmlentities($row["nom_service"],ENT_COMPAT,"UTF-8").$legals->add_condition("Pour toutes les conditions et réglements voir en magasin."));
    $cts->add(new wikicontents(null,$row["description_service"]));

    if ( !empty($row["website_service"]) )
      $cts->add_paragraph("Plus d'informations sur : <a href=\"".htmlentities($row["website_service"],ENT_COMPAT,"UTF-8")."\">".htmlentities($row["website_service"],ENT_COMPAT,"UTF-8")."</a>");

    if ( $row["nombre"] > 0 )
      $cts->add_paragraph("<a href=\"search.php?id_service=".$row["id_service"]."\">Voir la liste des commercants proposant ce service (".$row["nombre"].")</a>");

  }

  $cts->add($legals);
  $site->add_contents($cts);
  $site->end_page();
  exit();
}

$site->start_page("pgbplans","Bon Plans - Petit Géni 2.0");
$cts = new contents("<a href=\"bplans.php\">Bon plans</a>");

$cts->add_paragraph("<a href=\"bplans.php?page=reductions\">Réductions</a>");
$cts->add_paragraph("<a href=\"bplans.php?page=services\">Services</a>");


$site->add_contents($cts);
$site->end_page();

?>
