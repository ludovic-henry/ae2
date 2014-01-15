<?php
/*
 * AECMS : CMS pour les clubs et activités de l'AE UTBM
 *
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

require_once("include/site.inc.php");

$site->start_page ( CMS_PREFIX."contact", "Contact" );

$cts = new contents("Contact");

if ( !is_null($site->asso->id_parent) )
  $cts->add_paragraph(htmlentities($site->asso->nom,ENT_NOQUOTES,"UTF-8")." est une activitée de l'association des étudiants de l'université de technologie de belfort montbéliard");

$cts->add_title(2,"Adresses");

if ( !is_null($site->asso->id_parent) )
  $cts->add_paragraph(
  "université de technologie de belfort-montébliard<br/>".
  "association des étudiants<br/>".
  htmlentities($site->asso->nom,ENT_NOQUOTES,"UTF-8")."<br/>".
  "90010 BELFORT CEDEX");
else
  $cts->add_paragraph(
  htmlentities($site->asso->nom,ENT_NOQUOTES,"UTF-8")."<br/>".
  nl2br(htmlentities($site->asso->adresse_postale,ENT_NOQUOTES,"UTF-8")));

if ( $site->asso->email )
  $cts->add_paragraph("e-mail: ".htmlentities($site->asso->email,ENT_NOQUOTES,"UTF-8"));

/*
 * Possibilité d'ajouter des informations de contact
 */
$page = new page ($site->db);
$page->load_by_pagename(CMS_PREFIX."__contacts");
if ( !$page->is_valid() )
{
  if ( $site->is_user_admin() )
    $cts->add_paragraph("<a href=\"index.php?page=new&amp;name=__contacts\">Ajouter des contacts</a>");
}
else
{
  if ( $page->is_right($site->user,DROIT_ECRITURE) || $site->is_user_admin() )
    $cts->set_toolbox(new toolbox(array("index.php?page=edit&name=__contacts"=>"Editer")));
  $cts->add_title(2,"Informations complémentaires");
  $cts->add($page->get_contents());
}

$cts->add_title(2,"Liens");

$cts->add_paragraph(
"<a href=\"http://www.utbm.fr/\">université de technologie de belfort-montébliard</a><br/>".
"<a href=\"http://ae.utbm.fr/\">association des étudiants</a>");

$site->add_contents($cts);

$site->end_page();

?>
