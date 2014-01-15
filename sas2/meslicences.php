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
require_once("include/sas.inc.php");
require_once($topdir. "include/entities/page.inc.php");
$site = new sas();
$site->add_css("css/sas.css");

$site->allow_only_logged_users("sas");

if ( $_REQUEST["action"] == "defaultlicence" )
  $site->user->set_licence_default_sas ( $_REQUEST["id_licence"]
                                        , isset($_REQUEST["applyall"]));

$site->start_page("sas","Gestion des licences");
if ( $_REQUEST["page"]=="process" )
{
  if($_REQUEST['action']=='setlicence')
  {
    $photo = new photo($site->db,$site->dbrw);
    $photo->load_by_id($_REQUEST['id_photo']);
    if($photo->id>0 && $photo->id_utilisateur_photographe==$site->user->id)
      $photo->set_licence($_REQUEST['id_licence']);
  }
  $photo = new photo($site->db,$site->dbrw);
  $cts = new contents("Licence");
  $sql = new requete($site->db,
    "SELECT * " .
    "FROM sas_photos " .
    "WHERE id_utilisateur_photographe=".$site->user->id.
    " AND id_licence IS NULL".
    " ORDER BY id_photo ".
    " LIMIT 1");
  if ( $sql->lines == 1)
  {
    $photo->_load($sql->get_row());
    $cat = new catphoto($site->db);
    $catpr = new catphoto($site->db);
    $cat->load_by_id($photo->id_catph);
    $path = $cat->get_html_link()." / ".$photo->get_html_link();
    $catpr->load_by_id($cat->id_catph_parent);
    while ( $catpr->id > 0 )
    {
      $path = $catpr->get_html_link()." / ".$path;
      $catpr->load_by_id($catpr->id_catph_parent);
    }
    $cts->add_title(2,$path);

    $site->user->load_all_extra();
    $imgcts = new contents();
    $imgcts->add(new image($photo->id,"images.php?/".$photo->id.".diapo.jpg"));
    $cts->add($imgcts,false,true,"sasimg");

    $subcts = new contents();

    if ( ($photo->droits_acces & 1) == 0 )
    {
      require_once($topdir."include/entities/group.inc.php");
      $groups = enumerates_groups($site->db);
      $subcts->add_paragraph("L'accés à cette photo est limité à ".$groups[$photo->id_groupe]);
    }
    $frm = new form("licences","meslicences.php?page=process",false,"POST","Votre souhait");
    $frm->add_hidden("action","setlicence");
    $frm->add_hidden("id_photo",$photo->id);
    $frm->add_entity_select('id_licence','Choix de la licence',$site->db,'licence',false,false,array(),'\'id_licence\' ASC');
    $frm->add_submit("set","Valider");
    $cts->add($frm);
  }
  else
  {
    $cts->add_paragraph("Merci, toutes les photos ont été passés en revue.");
    $cts->add_paragraph("<a href=\"./\">Retour au SAS</a>");
  }

  $site->add_contents($cts);
  $site->end_page ();
  exit();
}



$cts = new contents("Gestion des licences");
$cts->add_paragraph("Plus d'informations sur les licences <a href='licences.php'>ici</a>");
$frm = new form("auto","meslicences.php",false,"POST","Licence par défaut pour mes photos");
$frm->add_hidden("action","defaultlicence");
$frm->add_entity_select('id_licence',
                        'Choix de la licence',
                        $site->db,
                        'licence',
                        $site->user->id_licence_default_sas,
                        false,
                        array(),
                        '\'id_licence\' ASC');
$frm->add_checkbox('applyall','Appliquer à toutes mes photos sans licences');
$frm->add_submit("setdroit","Enregistrer");
$cts->add($frm,true);


$cts->add_title(2,"Mes photos sans licences");
$sql = new requete($site->db,
  "SELECT COUNT(*) " .
  "FROM sas_photos " .
  "WHERE id_utilisateur_photographe=".$site->user->id.
  " AND id_licence IS NULL");
list($count) = $sql->get_row();
$cts->add_paragraph("<a href=\"meslicences.php?page=process\">$count photo(s) en sans licence définie</a>");

$site->add_contents($cts);
$site->end_page ();

?>
