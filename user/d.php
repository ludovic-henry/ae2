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

$topdir="../";

require_once($topdir. "include/site.inc.php");
require_once($topdir."include/cts/sqltable.inc.php");

$site = new site ();

$site->allow_only_logged_users("fichiers");

$site->start_page("fichiers","Fichiers empruntés");

if(isset($_REQUEST['action']) && $_REQUEST['action']=='res')
{
  require_once($topdir."include/entities/files.inc.php");
  $file = new dfile($site->db, $site->dbrw);
  if(isset($_REQUEST["id_file"]))
  {
    $file->load_by_id($_REQUEST["id_file"]);
    if ( $file->is_valid() )
      $file->unlock($site->user);
  }
  elseif(isset($_REQUEST["id_files"])
         && is_array($_REQUEST["id_files"])
         && !empty($_REQUEST["id_files"])
        )
  {
    foreach($_REQUEST["id_files"] as $id)
    {
      $file->load_by_id($id);
      if ( $file->is_valid() )
        $file->unlock($site->user);
    }
  }
}

$cts = new contents("Fichiers empruntés");
$req = new requete($site->db, "SELECT `id_file` ".
                              ", `titre_file` ".
                              ", CONCAT( ".
                              "DATE_FORMAT(`time_file_lock`,GET_FORMAT(DATE,'EUR')) ".
                              ", ' ' ".
                              ", DATE_FORMAT(`time_file_lock`,GET_FORMAT(TIME,'EUR')) ".
                              ") as datetime ".
                              "FROM `d_file_lock` ".
                              "INNER JOIN `d_file` USING(`id_file`) ".
                              "WHERE `d_file_lock`.`id_utilisateur`='".$site->user->id."'");
if($req->lines>0)
{
  $cts->add(new sqltable( "emprunts_fichiers"
                         ,"Fichiers empruntés"
                         , $req
                         , "d.php"
                         , "id_file"
                         , array( "titre_file"=>"Fichier"
                                 , "datetime"=>"date d'emprunt")
                         , array("res"=>"Restituer")
                         , array("res"=>"Restituer")
                        )
            , true);;
}
else
{
  $cts->add_paragraph("Vous n'avez aucun fichier emprunté");
}

$site->add_contents($cts);
$site->end_page();

?>
