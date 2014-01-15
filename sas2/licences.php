<?php

/* Copyright 2009
 * - Simon Lopez < simon dot lopez at ayolo dot org >
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
 * @ingroup sas
 * @author Simon Lopez
 */
$topdir="../";
require_once("include/sas.inc.php");
require_once("include/licence.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");
$site = new sas();
$site->add_css("css/sas.css");

$site->start_page("sas","Information sur les licences");

if(isset($_REQUEST['id_licence']))
{
  $licence = new licence($site->db,$site->dbrw);
  if($licence->load_by_id($_REQUEST['id_licence']))
  {
    $cts = new contents($licence->titre);

    $cts->add_paragraph($licence->desc);
    if(!is_null($licence->url))
      $cts->add_paragraph("Plus d'information <a href='".$licence->url."'>ici</a>");
    if(!is_null($licence->icone))
      $cts->add_paragraph("<img src='".$licence->icone."' alt='icone' />");
    $site->add_contents($cts);
  }
}

$cts = new contents("Licences du sas");
$req = new requete($site->db,'select * from licences');
$cts->add(new sqltable(
  "licences",
  "Liste des licences",
  $req,
  "licences.php",
  "id_licence",
  array("titre"=>"Titre"),
  array("detail"=>"Détail"),
  array(),
  array()
  ),true);

$site->add_contents($cts);
$site->end_page();

?>
