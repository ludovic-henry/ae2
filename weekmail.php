<?php
/* Copyright 2009
 * - Simon Lopez < simon dot lopez at ayolo dot org >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
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
require_once($topdir. "include/entities/weekmail.inc.php");
$site = new site ();
$weekmail = new weekmail($site->db);

if(
   (
    isset($_REQUEST['id_weekmail'])
    && $weekmail->load_by_id($_REQUEST['id_weekmail'])
    && $weekmail->is_sent()
   )
   || $weekmail->load_latest_sent()
  )
{
  $site->start_page ("accueil","Weekmail - ".$weekmail->titre);

  // Charger les archives
  $wkm_list = new itemlist("Archives", false, array());
  $last_id = -1;

  // les derniers...
  $req = new requete($site->db,
                     'SELECT `id_weekmail`, `date_weekmail` '.
                     'FROM `weekmail` '.
                     'WHERE `statut_weekmail`=\'1\' '.
                     'ORDER BY `id_weekmail` DESC '.
                     'LIMIT 7');
  while(list($id_wkm, $date_wkm)=$req->get_row())
  {
    if ($id_wkm <=  $weekmail->id)
      break;
    $wkm_list->add("<a href=\"?id_weekmail=$id_wkm\">$date_wkm</a>");
    $last_id = $id_wkm;
  }


  // les suivants
  $req = new requete($site->db,
                     'SELECT `id_weekmail`, `date_weekmail` '.
                     'FROM `weekmail` '.
                     'WHERE `statut_weekmail`=\'1\' '.
                     'AND `id_weekmail` > '.$weekmail->id.' '.
                     'ORDER BY `id_weekmail`'.
                     'LIMIT 8');

  if($req->lines>=1)
  {
    $rev_array = array();
    while($row=$req->get_row())
      $rev_array[] = $row;
    $rev_array = array_reverse($rev_array);

    // le premier élément est juste là pour savoir si on met les "..."
    if ($last_id > $rev_array[0]["id_weekmail"])
      $wkm_list->add("...");
    unset($rev_array[0]);

    foreach($rev_array as $row)
    {
      list($id_wkm, $date_wkm) = $row;
      if ($id_wkm < $last_id)
        $wkm_list->add("<a href=\"?id_weekmail=$id_wkm\">$date_wkm</a>");
    }
  }


  // les précédents
  $req = new requete($site->db,
                     'SELECT `id_weekmail`, `date_weekmail` '.
                     'FROM `weekmail` '.
                     'WHERE `statut_weekmail`=\'1\' '.
                     'AND `id_weekmail` <= '.$weekmail->id.' '.
                     'ORDER BY `id_weekmail` DESC '.
                     'LIMIT 8');

  // actuel
  list($id_wkm, $date_wkm)=$req->get_row();
  $wkm_list->add("<b>$date_wkm</b>");
  $last_id = $id_wkm;

  while(list($id_wkm, $date_wkm)=$req->get_row())
  {
    $wkm_list->add("<a href=\"?id_weekmail=$id_wkm\">$date_wkm</a>");
    $last_id = $id_wkm;
  }

  $req = new requete($site->db,
                     'SELECT `id_weekmail`, `date_weekmail` '.
                     'FROM `weekmail` '.
                     'WHERE `statut_weekmail`=\'1\' '.
                     'ORDER BY `id_weekmail`'.
                     'LIMIT 8');

  $rev_array = array();
  while($row=$req->get_row())
    $rev_array[] = $row;
  $rev_array = array_reverse($rev_array);

  // le premier élément est juste là pour savoir si on met les "..."
  if ($last_id > $rev_array[0]["id_weekmail"])
    $wkm_list->add("...");
  unset($rev_array[0]);

  foreach($rev_array as $row)
  {
    list($id_wkm, $date_wkm) = $row;
    if ($id_wkm < $last_id)
      $wkm_list->add("<a href=\"?id_weekmail=$id_wkm\">$date_wkm</a>");
  }


  $site->add_box("archives_weekmail",$wkm_list );
  $site->set_side_boxes("right",array("archives_weekmail"),"weekmail_right");

  // Erk... clean html content
  $html = preg_replace("/<html>[ \t\n]*<body[^>]*>[ \t\n]*<table[^>]*>/i", "<table bgcolor=\"#333333\">", $weekmail->rendu_html);
  $html = preg_replace("/<\/body>[ \t\n]*<\/html>/i", "", $html);
  $html = preg_replace("/width *= *\"[^\"]*\"/i", "", $html);

  $cts = new contents();
  $cts->puts($html);
  $site->add_contents($cts);
  $site->end_page();

  exit();
}

$site->start_page ("accueil", "Weekmail");
$site->add_contents(new contents('Pas de weekmail.','Aucun weekmail n\'a été trouvé.'));
$site->end_page();

?>
