<?php

/* Copyright 2008
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
require_once($topdir . "include/cts/user.inc.php");

// Ne pas oublier le dernier '/'
define('SAVE_DIR', '/var/www/var/tmp/');
define('OUTPUT_DIR', '/var/www/var/tmp/matmat/');

$site = new site ();

if ( !$site->user->is_in_group("root") )
  $site->error_forbidden("none","group",7);

$site->start_page("none","Administration");

if(isset($_POST['action'])
   && $_POST['action']=='bloubiboulga'
   && is_dir("/var/www/ae2/data/img")
   && file_exists (SAVE_DIR.$_POST['zipeuh']))
{
  mkdir(OUTPUT_DIR);
  if(is_dir(OUTPUT_DIR))
  {
    $user = new utilisateur($site->db);
    $zip = new ZipArchive;
    if (!$zip->open (SAVE_DIR.$_POST['zipeuh']))
      die ('Impossible d\'ouvrir le fichier zip à : '.$_FILES['zipeuh']['tmp_name']);
    if (!$zip->extractTo (OUTPUT_DIR))
      die ('Impossible d\'extraire l\'archive à '.$_FILES['zipeuh']['tmp_name']);
    $zip->close ();
    $rotation = $_POST['rotate'];

    $h = opendir(OUTPUT_DIR);
    while ($f=readdir($h))
    {
      if ($f == "." || $f == "..")
        continue;
      if(strtolower(substr($f,-3)) == 'jpg') {
        $pos = strpos(strtolower ($f), '.identity');
        $avatar = $pos ? false : true;
        $num = $avatar ? substr($f, 0, -4) : substr($f, 0, $pos);

        if (isset($_REQUEST["carteae"]))
          $user->load_by_carteae($num, false);
        else
          $user->load_by_id($num);

        if ( $user->is_valid() )
        {
          $id = $user->id;

          if ($avatar)
            exec(escapeshellcmd("/usr/share/php5/exec/convert ".OUTPUT_DIR.$f." -rotate ".$rotation." -thumbnail 225x300 /data/matmatronch/".$id.".jpg"));
          else
            exec(escapeshellcmd("/usr/share/php5/exec/convert ".OUTPUT_DIR.$f." -rotate ".$rotation." -thumbnail 225x300 /data/matmatronch/".$id.".identity.jpg"));
        }
      }
      // Delete temp img
      unlink(OUTPUT_DIR.$f);
    }
    rmdir (OUTPUT_DIR);
  }
}

$cts = new contents("Administration/Import massif de photos matmatronch");
$frm = new form("photos","?",true,"POST","Et paf les photos");
$frm->add_hidden("action","bloubiboulga");
$frm->add_text_field ( "zipeuh", 'Nom du fichier (à balancer dans /var/www/var/tmp/)', '', true);
$frm->add_text_field ( "rotate", 'Rotation à appliquer en degré', '+90', true);
$frm->add_checkbox ( "carteae", "Les boulets qui ont fait les photos ont utilisé les numéros de carte AE" );
$frm->add_submit("paff","Et paf!");
$cts->add($frm,true);

$site->add_contents($cts);

$site->end_page();

?>
