<?php
/* Copyright 2006
 * - Julien Etelain < julien at pmad dot net >
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
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/pdf/etiquette.inc.php");
require_once($topdir. "include/entities/objet.inc.php");
require_once($topdir. "include/entities/asso.inc.php");
require_once($topdir. "include/entities/salle.inc.php");

$site = new site ();

$asso_prop = new asso($site->db);
$asso_gest = new asso($site->db);
$objtype = new objtype($site->db);
$salle = new salle($site->db);

if ( $_REQUEST["id_asso"])
  $asso_gest->load_by_id($_REQUEST["id_asso"]);

if ( !$site->user->is_in_group("gestion_ae") && !$asso_gest->is_member_role($site->user->id,ROLEASSO_MEMBREBUREAU) )
  $site->error_forbidden("services");


if ( $_REQUEST["id_objtype"] )
  $objtype->load_by_id($_REQUEST["id_objtype"]);

if ( $_REQUEST["id_asso_prop"])
  $asso_prop->load_by_id($_REQUEST["id_asso_prop"]);

if ( $_REQUEST["id_salle"])
  $salle->load_by_id($_REQUEST["id_salle"]);


if ( $_REQUEST["action"] == "generate" )
{
  $sql = "SELECT " .
      "`inv_objet`.`cbar_objet`, " .
      "`inv_objet`.`nom_objet`, " .
      "`inv_objet`.`num_objet`, " .
      "`asso_gest`.`id_asso` AS `id_asso_gest`, " .
      "`asso_gest`.`nom_asso` AS `nom_asso_gest`, " .
      "`asso_gest`.`nom_unix_asso` AS `nom_unix_asso_gest`, " .
      "`asso_prop`.`id_asso` AS `id_asso_prop`, " .
      "`asso_prop`.`nom_asso` AS `nom_asso_prop`, " .
      "`asso_prop`.`nom_unix_asso` AS `nom_unix_asso_prop`, " .
      "`inv_type_objets`.`nom_objtype` " .
      "FROM `inv_objet` " .
      "INNER JOIN `asso` AS `asso_gest` ON `inv_objet`.`id_asso`=`asso_gest`.`id_asso` " .
      "INNER JOIN `asso` AS `asso_prop` ON `inv_objet`.`id_asso_prop`=`asso_prop`.`id_asso` ".
      "INNER JOIN `inv_type_objets` ON `inv_objet`.`id_objtype`=`inv_type_objets`.`id_objtype` ";

  if ( $objtype->id > 0 )
    $conds[] = "`inv_objet`.`id_objtype`='".intval($objtype->id)."'";

  if ( $asso_gest->id > 0 )
    $conds[] = "`inv_objet`.`id_asso`='".intval($asso_gest->id)."'";

  if ( $asso_prop->id > 0 )
    $conds[] = "`inv_objet`.`id_asso_prop`='".intval($asso_prop->id)."'";

  if ( $salle->id > 0 )
    $conds[] = "`inv_objet`.`id_salle`='".intval($salle->id)."'";


  if ( count($conds) )
    $sql .= "WHERE ".implode(" AND ",$conds);

  $sql .= " ORDER BY `inv_objet`.`id_objtype`,`inv_objet`.`num_objet`";

  $req = new requete($site->db,$sql);


  $pdf = new pdfetiquette();

  while ( $row = $req->get_row())
  {
    $barcode = $row['cbar_objet'];
    $name = $row['nom_objet'];

    $src = "/var/www/ae2/data/img/logos/".$row['nom_unix_asso_gest'].".png";
    $logo = "/var/www/ae2/data/img/logos/".$row['nom_unix_asso_gest'].".jpg";
    if ( !file_exists($logo) && file_exists($src) )
      exec(escapeshellcmd("/usr/share/php5/exec/convert $src -background white $logo"));

    if ( !file_exists($logo) )
    {
      $src = "/var/www/ae2/data/img/logos/".$row['nom_unix_asso_prop'].".png";
      $logo = "/var/www/ae2/data/img/logos/".$row['nom_unix_asso_prop'].".jpg";

      if ( !file_exists($logo) && file_exists($src) )
        exec(escapeshellcmd("/usr/share/php5/exec/convert $src -background white $logo"));
    }

    if ( !file_exists($logo) )
      $logo=null;


    if ( !$name )
      $name = $row['nom_objtype'].' '.$row['num_objet'];

    if ( $row['nom_asso_prop'] != $row['nom_asso_gest'] )
      $owner = $row['nom_asso_prop'].'/'.$row['nom_asso_gest'];
    else
      $owner = $row['nom_asso_prop'];

    $pdf->add_etiquette ( $owner, $name, $barcode,$logo );

  }
  $pdf->Output();

  exit();
}


$site->start_page("services","Génération étiquettes de l'inventaire");

$cts = new contents("Génération étiquettes");

$frm = new form("generate","etiquette.php",false,"POST","Critères de selection");
$frm->add_hidden("action","generate");
$frm->add_entity_select("id_objtype", "Type", $site->db, "objtype", $objtype->id,true);
$frm->add_entity_select("id_asso_prop", "Propriètaire", $site->db, "asso", $asso_prop->id,true, array("id_asso_parent"=>NULL));
$frm->add_entity_select("id_asso", "Gestionnaire", $site->db, "asso",$asso_gest->id,true);
$frm->add_entity_select("id_salle", "Salle", $site->db, "salle",$salle->id,true);
$frm->add_submit("valide","Générer");
$cts->add($frm);




$site->add_contents($cts);

$site->end_page();


?>
