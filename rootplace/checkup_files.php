<?php

/* Copyright 2007
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

require_once($topdir. "include/site.inc.php");
require_once($topdir."include/entities/files.inc.php");

$site = new site ();

if ( !$site->user->is_in_group("root") )
  $site->error_forbidden("none","group",7);

$site->start_page("none","Administration");

$cts = new contents("<a href=\"index.php\">Administration</a> / Maintenance / Verifications fichiers");

$dfile = new dfile($site->db,$site->dbrw);

$lst = new itemlist();

$lst->add("<b>Gènère la liste des fichiers attendus</b>");
$excepted = array();
$excepted_thumb = array();
$excepted_preview = array();

$req = new requete($site->db,"SELECT * FROM d_file_rev INNER JOIN d_file USING(id_file)");
while ( $row = $req->get_row() )
{
  $dfile->_load($row);
  $excepted[$dfile->get_real_filename()] = 1;
  if ( ereg("image/(.*)",$dfile->mime_type) )
  {
    $excepted_thumb[$dfile->get_thumb_filename()] = 1;
    $excepted_preview[$dfile->get_screensize_filename()] = 1;
  }
}

$lst->add(count($excepted)." fichiers attendus");
$lst->add(count($excepted_thumb)." fichiers thumb attendus");
$lst->add(count($excepted_preview)." fichiers preview attendus");

function checkup_dir(&$lst,&$excepted,$folder)
{
  $lst->add("<b>Liste tous les fichiers de $folder</b>");

  $found=0;
  $excepted_found=0;
  $unexcepted_found=0;
  if ($dh = opendir($folder))
  {
    while (($file = readdir($dh)) !== false)
    {
      $name = $folder.$file;
      if ( is_file($name) )
      {
        $found++;
        if ( !isset($excepted[$name]) )
        {
          $lst->add("Fichier inattendu : $name Supprimé");
          $unexcepted_found++;
          unlink($name);
        }
        else
        {
          unset($excepted[$name]);
          $excepted_found++;
        }
      }
    }
    closedir($dh);
  }

  $lst->add("$found fichiers trouvés, $excepted_found attendus, $unexcepted_found inattendus, ".count($excepted)." non trouvés");

}

checkup_dir($lst,$excepted,$topdir."data/files/");
checkup_dir($lst,$excepted_thumb,$topdir."data/files/thumb/");
checkup_dir($lst,$excepted_preview,$topdir."data/files/preview/");




$cts->add($lst);

$site->add_contents($cts);
$site->end_page();

?>
