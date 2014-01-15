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
$topdir="../";
require_once("include/comptoirs.inc.php");
require_once($topdir. "include/localisation.inc.php");
$site = new sitecomptoirs();


if ( $_REQUEST["action"]=="set_salle" && ($site->user->is_in_group("gestion_localisation") || $site->user->is_in_group("gestion_ae") ))
{
  set_localisation($_REQUEST["id_salle"]);
  $id_salle=$_REQUEST["id_salle"];
}
else
  $id_salle=get_localisation();

$site->start_page("services","Bienvenue");

$cts = new contents("Comptoirs AE");

if ( is_null($id_salle) )
{
  if ( ($site->user->is_in_group("gestion_localisation") || $site->user->is_in_group("gestion_ae") ) )
  {
    $frm = new form("set_salle","");
    $frm->allow_only_one_usage();
    $frm->add_hidden("action","set_salle");
    $frm->add_entity_select("id_salle", "Salle où se trouve cet ordinateur", $site->db, "salle");
    $frm->add_submit("valid","Enregistrer");
    $cts->add($frm);
  }
  else
    $cts->add_paragraph("Veuillez contacter un administrateur pour activer cet ordinateur.");

}
else
{
  $cts->add_paragraph("Veuillez selectionner le comptoirs dans le quel vous voulez vendre. Votre compte barman devra être activé, si cela n'est pas le cas, veuillez vous adresser à un membre du bureau de l'AE.");

  $sql = new requete($site->db,"SELECT id_comptoir,nom_cpt FROM cpt_comptoir WHERE `type_cpt`='0' AND id_salle='$id_salle' ORDER BY nom_cpt");

  $lst = new itemlist("Comptoirs disponibles");

  while ( list($id,$nom) = $sql->get_row())
    $lst->add("<a href=\"comptoir.php?id_comptoir=$id\">$nom</a>");
  $cts->add($lst,true);
}


$cts->add_paragraph("<a href=\"admin.php\">Administration</a>");


$site->add_contents($cts);
$site->end_page();

?>
