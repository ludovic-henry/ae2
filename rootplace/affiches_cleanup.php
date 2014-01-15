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

$cts = new contents("<a href=\"index.php\">Administration</a> / Maintenance / Nettoyage affiches");

$dfile = new dfile($site->db,$site->dbrw);

$lst = new itemlist();

$req = new requete($site->db,"SELECT d_file.*, d_file_rev.*
FROM d_file
INNER JOIN d_file_rev ON (d_file.id_file=d_file_rev.id_file AND d_file_rev.id_rev_file=d_file.id_rev_file_last )
INNER JOIN d_folder AS f1 ON ( d_file.id_folder=f1.id_folder)
INNER JOIN d_folder AS f2 ON ( f1.id_folder_parent=f2.id_folder)
LEFT JOIN nvl_nouvelles_files ON ( d_file.id_file=nvl_nouvelles_files.id_file)
LEFT JOIN wiki_ref_file ON ( d_file.id_file=wiki_ref_file.id_file)
WHERE f2.id_folder_parent IS NULL
AND f1.titre_folder='Affiches'
AND nvl_nouvelles_files.id_nouvelle IS NULL
AND wiki_ref_file.id_wiki IS NULL");

while ( $row = $req->get_row() )
{
  $dfile->_load($row);
  $lst->add($dfile->get_html_link()." : Supprimée");
  $dfile->delete_file();
}

$cts->add($lst);


$site->add_contents($cts);
$site->end_page();

?>
