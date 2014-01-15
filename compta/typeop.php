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
require_once("include/compta.inc.php");
require_once($topdir . "include/entities/asso.inc.php");
require_once($topdir . "include/cts/sqltable.inc.php");

$site = new sitecompta();

$asso  = new asso($site->db);
$opstd = new operation_comptable($site->db);
$opclb = new operation_club($site->db,$site->dbrw);

if ( !$site->user->is_valid() )
  $site->error_forbidden("services");

if ( isset($_REQUEST["id_asso"]) )
{
  $asso->load_by_id($_REQUEST["id_asso"]);

  if( !$asso->is_valid() )
  {
    header("Location: index.php");
    exit();
  }

  if ( !$site->user->is_in_group("compta_admin") && !$asso->is_member_role($site->user->id,ROLEASSO_TRESORIER) )
  {
    header("Location: index.php");
    exit();
  }
}
elseif ( !$site->user->is_in_group("compta_admin") )
{
  header("Location: index.php");
  exit();
}

if ( isset($_REQUEST["id_opclb"]) )
{
  $opclb->load_by_id($_REQUEST["id_opclb"]);

  if( !$opclb->is_valid() || $opclb->id_asso != $asso->id )
  {
    header("Location: index.php");
    exit();
  }
}

if ( $_REQUEST["action"] == "newclubop" )
{
  if ( $_REQUEST["libelle"] && isset($types_mouvements_reel[$_REQUEST["type_mouvement"]]) )
  {
    if ( $site->user->is_in_group("compta_admin") )
      $opstd->load_by_id($_REQUEST["id_opstd"]);

    $opclb->new_op_pstd ( $asso->id, $opstd->id, $_REQUEST["libelle"], $_REQUEST["type_mouvement"] );
  }
}
elseif ( $_REQUEST["action"] == "save" )
{
  if ( $_REQUEST["libelle"] )
  {
    if ( $site->user->is_in_group("compta_admin") )
      $opstd->load_by_id($_REQUEST["id_opstd"]);

    $opclb->save ( $asso->id, $opstd->id, $_REQUEST["libelle"], $opclb->type_mouvement );
  }
}
elseif ( $_REQUEST["action"] == "edit" )
{
  $site->start_page ("services", "Operations ".$asso->nom );

  $frm = new form ("newclubop",is_null($asso->id)?"typeop.php":"typeop.php?id_asso=".$asso->id,true,"POST","Edition");
  $frm->add_hidden("action","save");
  $frm->add_hidden("id_opclb",$opclb->id);
  $frm->add_info($types_mouvements_reel[$opclb->type_mouvement]);
  $frm->add_text_field("libelle","Libellé",$opclb->libelle,true);

  if ( $site->user->is_in_group("compta_admin") )
    $frm->add_select_field("id_opstd","Type comptable",$site->get_typeop_std(false,$opclb->type_mouvement),$opclb->id_opstd);

  $frm->add_submit("valid","Enregistrer");
  $site->add_contents($frm);
  $site->add_contents(new contents(false,"<a href=\"typeop.php?id_asso=".$asso->id."\">Annuler</a>"));
  $site->end_page ();
  exit();
}

if ( $asso->is_valid() )
{
  $site->set_current($asso->id,$asso->nom,null,null,null);

  if ( $_REQUEST["action"] == "fusion" )
  {
    $opclb2 = new operation_club($site->db,$site->dbrw);
    $opclb->id=null;
    foreach ( $_REQUEST["id_opclbs"] as $id)
    {
      if ( is_null($opclb->id) )
        $opclb->load_by_id($id);
      else
      {
        $opclb2->load_by_id($id);
        if ( $opclb2->id != $opclb->id && $opclb2->type_mouvement == $opclb->type_mouvement )
          $opclb2->replace_and_remove($opclb);
      }
    }
  }
  elseif ( ereg("^convert=([0-9]*)$",$_REQUEST["action"],$regs) )
  {

    $opclb2 = new operation_club($site->db,$site->dbrw);

    $opclb->load_by_id( $regs[1]);

    foreach ( $_REQUEST["id_opclbs"] as $id)
    {
      $opclb2->load_by_id($id);
      if ( $opclb2->id != $opclb->id && $opclb2->type_mouvement == $opclb->type_mouvement && !is_null($opclb2->id_asso) )
        $opclb2->replace_and_remove($opclb);
    }
  }


  $site->start_page ("services", "Operations ".$asso->nom );

  $cts = new contents("Natures d'opérations ".$asso->nom );

  $req = new requete ($site->db, "SELECT cpta_op_clb.*," .
      "`cpta_op_plcptl`.`code_plan` " .
      "FROM cpta_op_clb " .
      "LEFT JOIN cpta_op_plcptl ON cpta_op_plcptl.id_opstd = cpta_op_clb.id_opstd " .
      "WHERE cpta_op_clb.id_asso IS NULL " .
      "ORDER BY type_mouvement,libelle_opclb");

  $cts->add(new sqltable(
    "listtops",
    "Natures d'opération communes", $req, "typeop.php?id_asso=".$asso->id,
    "id_opclb",
    array(
      "libelle_opclb"=>"Libelle",
      "type_mouvement"=>"Type de mouvement",
      "code_plan"=>"Code plan."
      ),
    array(),
    array(),
    array("type_mouvement"=>$types_mouvements_reel)
    ),true);


  $batch = array("fusion"=>"Fusionner natures (types) d'opérations");

  $req->go_first();

  $batch[]="----";

  $prevtype=-1;

  while ( $row = $req->get_row() )
  {
    if ( $prevtype != $row["type_mouvement"] )
    {
       $batch[] = "----";
       $prevtype = $row["type_mouvement"];
    }
    if ( $row["type_mouvement"] == -1 )
      $batch["convert=".$row['id_opclb']] = "Remplacer par débit: ".$row['libelle_opclb'];
    else
      $batch["convert=".$row['id_opclb']] = "Remplacer par crédit: ".$row['libelle_opclb'];
  }

  $req = new requete ($site->db, "SELECT `cpta_op_clb`.`id_opclb`, ".
      "`cpta_op_clb`.`libelle_opclb`, ".
      "`cpta_op_clb`.`type_mouvement`, " .
      "COUNT(`cpta_operation`.`id_op`) AS `count`, " .
      "`cpta_op_plcptl`.`code_plan` " .
      "FROM cpta_op_clb " .
      "LEFT JOIN cpta_operation USING(id_opclb) " .
      "LEFT JOIN cpta_op_plcptl ON cpta_op_plcptl.id_opstd = cpta_op_clb.id_opstd " .
      "WHERE cpta_op_clb.id_asso='".$asso->id."' " .
      "GROUP BY `cpta_op_clb`.`id_opclb`".
      "ORDER BY type_mouvement,libelle_opclb");

  $cts->add(new sqltable(
    "listtops",
    "Natures d'opération ".$asso->nom, $req, "typeop.php?id_asso=".$asso->id,
    "id_opclb",
    array(
      "libelle_opclb"=>"Libelle",
      "type_mouvement"=>"Type de mouvement",
      "code_plan"=>"Code plan.",
      "count"=>"Nombre d'utilisations"
      ),
    array("edit"=>"Editer"),
    $batch,
    array("type_mouvement"=>$types_mouvements_reel)
    ),true);


  $frm = new form ("newclubop","typeop.php?id_asso=".$asso->id,true,"POST","Ajouter une nature d'opération");
  $frm->add_hidden("action","newclubop");

  $frm->add_text_field("libelle","Libellé","",true);
  $frm->add_select_field("type_mouvement","Type de mouvement",$types_mouvements_reel);
  if ( $site->user->is_in_group("compta_admin") )
    $frm->add_select_field("id_opstd","Type comptable",$site->get_typeop_std(true));
  $frm->add_submit("valid","Ajouter");
  $cts->add($frm,true);


  $cts->set_help_page("compta-types");
  $site->add_contents($cts);
  $site->end_page ();
  exit();
}



$site->start_page ("services", "Operations communes");

$cts = new contents("Operations communes" );

$req = new requete ($site->db, "SELECT cpta_op_clb.*," .
    "`cpta_op_plcptl`.`code_plan` " .
    "FROM cpta_op_clb " .
    "LEFT JOIN cpta_op_plcptl ON cpta_op_plcptl.id_opstd = cpta_op_clb.id_opstd " .
    "WHERE cpta_op_clb.id_asso IS NULL " .
    "ORDER BY type_mouvement,libelle_opclb");

$cts->add(new sqltable(
    "listtops",
    "Natures d'opération communes", $req, "typeop.php",
    "id_opclb",
    array(
      "libelle_opclb"=>"Libelle",
      "type_mouvement"=>"Type de mouvement",
      "code_plan"=>"Code plan."
      ),
    array("edit"=>"Editer"),
    array(),
    array("type_mouvement"=>$types_mouvements_reel)
    ),true);

$frm = new form ("newclubop","typeop.php",true,"POST","Ajouter une nature d'opération");
$frm->add_hidden("action","newclubop");

$frm->add_text_field("libelle","Libellé","",true);
$frm->add_select_field("type_mouvement","Type de mouvement",$types_mouvements_reel);
$frm->add_select_field("id_opstd","Type comptable",$site->get_typeop_std(true));
$frm->add_submit("valid","Ajouter");
$cts->add($frm,true);

$site->add_contents($cts);

$site->end_page ();


?>
