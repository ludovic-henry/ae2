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
 * @file Modération des dossiers virtuels
 * @see include/entities/files.inc.php
 * @see include/entities/folder.inc.php
 */

$topdir="../";
require_once($topdir."include/site.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");
require_once($topdir."include/entities/asso.inc.php");
require_once($topdir."include/entities/files.inc.php");
require_once($topdir."include/entities/folder.inc.php");

$site = new site ();

if ( !$site->user->is_in_group("moderateur_site") )
  $site->error_forbidden("fichiers");


if ( $_REQUEST["action"] == "foldermodere")
{
  $fl = new dfolder($site->db,$site->dbrw);
  foreach ($_REQUEST["id_folders"] as $id)
  {
    $fl->load_by_id($id);
    if ( $fl->id > 0 )
      $fl->set_modere();
  }
}
elseif ( $_REQUEST["action"] == "folderdelete")
{
  $fl = new dfolder($site->db,$site->dbrw);
  foreach ($_REQUEST["id_folders"] as $id)
  {
    $fl->load_by_id($id);
    if ( $fl->id > 0 )
      $fl->delete_folder();
  }
}
elseif ( $_REQUEST["action"] == "filemodere")
{
  $fl = new dfile($site->db,$site->dbrw);
  foreach ($_REQUEST["id_files"] as $id)
  {
    $fl->load_by_id($id);
    if ( $fl->id > 0 )
      $fl->set_modere();
  }
}
elseif ( $_REQUEST["action"] == "filedelete")
{
  $fl = new dfile($site->db,$site->dbrw);
  foreach ($_REQUEST["id_files"] as $id)
  {
    $fl->load_by_id($id);
    if ( $fl->id > 0 )
      $fl->delete_file_rev();
  }
}

$site->start_page("fichiers","Modération des fichiers");
$cts = new contents("Modération");

$req = new requete($site->db,"SELECT d_folder.* " .
        ", `utilisateurs`.`id_utilisateur` ".
        ", CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) AS `nom_utilisateur` ".
        "FROM d_folder " .
        "LEFT JOIN `utilisateurs` USING(`id_utilisateur`) ".
        "WHERE " .
        "modere_folder='0'");

$tbl = new sqltable("modfolders",
        "Dossiers à modérer",
        $req,
        "moderedrive.php",
        "id_folder",
        array("titre_folder"=>"Titre",
        "description_folder"=>"Description",
        "nom_utilisateur"=>"Auteur"),
        array(),
        array("foldermodere" => "Accepter",
        "folderdelete" => "Supprimer"),
        array());

$cts->add($tbl,true);

$req = new requete($site->db,"SELECT d_file.* " .
        ", `utilisateurs`.`id_utilisateur` ".
        ", CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`) AS `nom_utilisateur` ".
        "FROM d_file " .
        "LEFT JOIN `d_file_rev` ON ( `d_file`.`id_file` = `d_file_rev`.`id_file` ".
          "AND `d_file`.`id_rev_file_last` = `d_file_rev`.`id_rev_file` ) ".
        "LEFT JOIN `utilisateurs` ON ( `utilisateurs`.`id_utilisateur` = `id_utilisateur_rev_file` ) ".
        "WHERE modere_file='0' ");

$tbl = new sqltable("modfolders",
        "Fichiers à modérer",
        $req,
        "moderedrive.php",
        "id_file",
        array("titre_file"=>"Titre",
          "mime_type_file"=>"Type",
        "description_file"=>"Description",
        "nom_utilisateur"=>"Auteur"),
        array(),
        array("filemodere" => "Accepter",
        "filedelete" => "Supprimer / Revenir à la version précédente"),
        array());

$cts->add($tbl,true);

$site->add_contents($cts);
$site->end_page();
?>
