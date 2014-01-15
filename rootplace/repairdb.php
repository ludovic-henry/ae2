<?php

/* Copyright 2007
 * - Julien Etelain < julien dot etelain at gmail dot com >
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

$topdir = "../";
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/entities/carteae.inc.php");
require_once($topdir. "include/entities/cotisation.inc.php");
require_once($topdir. "include/entities/files.inc.php");
require_once($topdir. "include/entities/folder.inc.php");
require_once($topdir. "include/entities/asso.inc.php");

$site = new site ();

if ( !$site->user->is_in_group("root") )
  $site->error_forbidden("none","group",7);

$site->start_page("none","Administration");

$cts = new contents("<a href=\"./\">Administration</a> / Maintenance / Auto-Reparation de la base de données");

$lst = new itemlist();

// Supprime les cotisations rattchés à des utilisateurs qui n'existent pas
$lst->add("<b>Check cotisations users</b>");
$sql = new requete($site->db,"SELECT id_cotisation FROM ae_cotisations LEFT JOIN utilisateurs USING(`id_utilisateur`) WHERE utilisateurs.id_utilisateur IS NULL");
while ( list($id_cotisation) = $sql->get_row() )
{
  $rem = new delete($site->dbrw,"ae_cotisations",array("id_cotisation"=>$id_cotisation));
  $lst->add("Missing user: Cotisation $id_cotisation removed.");
}

// Ajoute les carte AE aux cotisations qui n'ont on pas
$lst->add("<b>Check cotisations cards</b>");
$sql = new requete($site->db,"SELECT ae_cotisations.* FROM ae_cotisations LEFT JOIN ae_carte USING(`id_cotisation`) WHERE ae_carte.id_cotisation IS NULL AND date_fin_cotis > NOW()");
$cotiz = new cotisation($site->db,$site->dbrw);
while ( $row = $sql->get_row() )
{
  $cotiz->_load($row);
  $cotiz->generate_card();
  $lst->add("Missing card for valid cotisation ".$cotiz->id." (user ".$cotiz->id_utilisateur.") : A card added.");
}

// Supprime les cartes AE rattachés à une cotisation qui n'existe pas
$lst->add("<b>Check cards cotisations</b>");
$sql = new requete($site->db,"SELECT id_carte_ae FROM ae_carte LEFT JOIN ae_cotisations USING(`id_cotisation`) WHERE ae_cotisations.id_cotisation IS NULL");
while ( list($id_carte_ae) = $sql->get_row() )
{
  $rem = new delete($site->dbrw,"ae_carte",array("id_carte_ae"=>$id_carte_ae));
  $lst->add("Missing cotisation: Card $id_carte_ae removed.");
}

// Supprime les alias utilisés par plusieurs utilisateurs
$lst->add("<b>Check aliases unicity</b>");
$sql = new requete($site->db,"SELECT COUNT(*),alias_utl FROM `utilisateurs` WHERE alias_utl IS NOT NULL GROUP BY alias_utl HAVING COUNT(*) > 1");
$aliases=array();
while ( $row = $sql->get_row() )
{
  $lst->add("Alias ".$row[1]." used by ".$row[0]." users : Set to NULL.");
  new requete($site->dbrw,"UPDATE `utilisateurs` SET alias_utl=NULL WHERE alias_utl='".mysql_real_escape_string($row[1])."'");
}

// Génére un alias aux utilisateurs du forum
$lst->add("<b>Check forum users aliases</b>");
$sql = new requete($site->db,"SELECT utilisateurs.id_utilisateur,email_utbm,nom_utl,prenom_utl,email_utl ".
"FROM `utilisateurs` ".
"JOIN frm_message ON (frm_message.id_utilisateur=utilisateurs.id_utilisateur) ".
"LEFT JOIN utl_etu_utbm ON (utl_etu_utbm.id_utilisateur=utilisateurs.id_utilisateur) ".
"WHERE alias_utl IS NULL ".
"GROUP BY id_utilisateur");

$user=new utilisateur($site->db);

while ( $row = $sql->get_row() )
{
  $alias="";
  $user->id=$row["id_utilisateur"];
  //load_by_alias ( $alias )
  if ( $row["email_utbm"] )
  {
    $alias = substr($row["email_utbm"],0,strpos($row["email_utbm"], "@"));
    if ( !$user->is_alias_avaible($alias) )
      $alias=null;
  }

  if ( (is_null($alias) || empty($alias)) && $row["email_utl"]  )
  {
    $alias = substr($row["email_utl"],0,strpos($row["email_utl"], "@"));
    if ( !$user->is_alias_avaible($alias) )
      $alias=null;
  }

  if ( is_null($alias) || empty($alias) )
  {
    $alias = $row["prenom_utl"].".".$row["nom_utl"];

    $alias = ereg_replace("(e|é|è|ê|ë|É|È|Ê|Ë)","e",$alias);
    $alias = ereg_replace("(a|à|â|ä|À|Â|Ä)","a",$alias);
    $alias = ereg_replace("(i|ï|î|Ï|Î)","i",$alias);
    $alias = ereg_replace("(c|ç|Ç)","c",$alias);
    $alias = ereg_replace("(u|ù|ü|û|Ü|Û|Ù)","u",$alias);
    $alias = ereg_replace("(n|ñ|Ñ)","n",$alias);

    $alias = ereg_replace("[^a-z0-9\\.]","",$alias);

    $base = $alias;

    if ( !$user->is_alias_avaible($alias) )
      $alias=null;

    $i=1;
    while ( is_null($alias) )
    {
      $alias = $base."-".$i;
      if ( !$user->is_alias_avaible($alias) )
      {
        $alias=null;
        $i++;
      }
    }
  }

  $lst->add("Alias for ".$row["prenom_utl"]." ".$row["nom_utl"]." (".$row["id_utilisateur"].") : $alias");

  new update($site->dbrw,
                      "utilisateurs",
                      array('alias_utl' => $alias),
                      array('id_utilisateur' => $row["id_utilisateur"]));
}

$lst->add("<b>Check folder names (nulls)</b>");

$sql = new requete($site->db,"SELECT * FROM `d_folder` WHERE nom_fichier_folder IS NULL");

$folder=new dfolder($site->db,$site->dbrw);

while ( $row = $sql->get_row() )
{
  $folder->_load($row);
  $folder->update_folder ( $folder->titre, $folder->description, $folder->id_asso );
  $lst->add("Folder #".$folder->id." is now named ".$folder->nom_fichier."");

}

$lst->add("<b>Check files names (unicity)</b>");

$file=new dfile($site->db,$site->dbrw);

$sql = new requete($site->db,"SELECT COUNT(*) AS `nb`,`id_folder`,`nom_fichier_file` FROM `d_file` GROUP BY `id_folder`,`nom_fichier_file` HAVING COUNT(*) > 1");

while ( $row = $sql->get_row() )
{
  $sql2 = new requete($site->db,"SELECT * FROM `d_file` WHERE `id_folder` = '" . mysql_real_escape_string($row['id_folder']) . "' AND `nom_fichier_file` = '" . mysql_real_escape_string($row['nom_fichier_file']) . "' LIMIT ".($row['nb']-1));
  while ( $row = $sql2->get_row() )
  {
    $file->_load($row);
  	$file->nom_fichier= $file->get_free_filename($file->id_folder,$file->nom_fichier,$file->id);
    new update ($site->dbrw,
			"d_file",
			array("nom_fichier_file"=>$file->nom_fichier),
			array("id_file"=>$file->id)
			);
		$lst->add("File #".$file->id." is now named ".$file->nom_fichier."");
  }
}

$cts->add($lst);
$site->add_contents($cts);

$site->end_page();
?>
