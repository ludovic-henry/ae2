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
 $topdir = "./";
setlocale(LC_ALL,"fr_FR.UTF8");

include($topdir. "include/site.inc.php");

require_once($topdir. "include/cts/planning2.inc.php");
require_once($topdir. "include/entities/planning2.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");


$site = new site ();
$site->add_css($topdir . "css/planning2.css");
$site->start_page("plannings","Plannings");

if ( !$site->user->is_valid() || $_REQUEST["view"] ===  true)
  $site->error_forbidden("plannings");


$planning = new planning2($site->db, $site->dbrw);

if (isset($_REQUEST["id_planning"]))
  $planning->load_by_id($_REQUEST["id_planning"]);

$cts = null;
if($planning->id && !($_REQUEST["view"] === "lst"))
	$cts = new contents($planning->name);
else
	$cts = new contents("Liste des Plannings");

$tabs = array(array("lst","planning2.php","Liste"));
if(isset($_REQUEST["id_planning"]))
	$tabs[] = array("view","planning2.php?view=view&id_planning=".$planning->id,"Voir");
if(isset($_REQUEST["id_planning"]) && $planning->id 
	&& $site->user->is_in_group_id($planning->admin_group))
{
	$tabs[] = array("edit","planning2.php?view=edit&id_planning=".$planning->id,"Editer le planning");
	$tabs[] = array("del","planning2.php?view=del&id_planning=".$planning->id,"Supprimer le planning");
	$tabs[] = array("new_gap","planning2.php?view=new_gap&id_planning=".$planning->id,"Ajouter un creneau");
}
$tabs[] = array("new","planning2.php?view=new","Ajouter un planning");

if(isset($_REQUEST["view"]))
	$cts->add(new tabshead($tabs,$_REQUEST["view"]));
else
	$cts->add(new tabshead($tabs,"lst"));

if($_REQUEST["action"] === "del" && isset($_REQUEST["id_planning"]))
{
	if(!($site->user->is_in_group_id($planning->admin_group))
		&& !($site->user->is_in_group("gestion_ae")))
		$cts->add_paragraph("Vous n'avez pas le droit de faire cela");
	else
	{
		$planning->remove();
		$cts->add_paragraph("Suppression terminee");
		$site->add_contents($cts);
		$site->end_page();
		exit();
	}
}

if($_REQUEST["action"] === "new_gap" && isset($_REQUEST["start"]) 
	&& isset($_REQUEST["end"]) && isset($_REQUEST["max_users"])
	&& isset($_REQUEST["name"]) && isset($_REQUEST["id_planning"]))
{
	if(!$site->user->is_in_group_id($planning->admin_group))
	{
		$cts->add_paragraph("Vous n'avez l'autorisation de faire cela");
	}
	else
	{
		$start = $_REQUEST["start"]+date("Z");
		$end = $_REQUEST["end"]+date("Z");
		if($planning->weekly)
		{
			$start -= $planning->get_week_start($start);
			$end -= $planning->get_week_start($end);
		}

		$name = $_REQUEST["name"];
		$max_users = $_REQUEST["max_users"];

		if($planning->add_gap($start, $end, $name, $max_users) != -1)
			$cts->add_paragraph("Ajout du creneau reussi");
		else
			$cts->add_paragraph("Echec de l'ajout du creneau");
	}
}

if($_REQUEST["action"] === "del_gap" && isset($_REQUEST["id_planning"]) && isset($_REQUEST["id_gap"]))
{
	if(!($site->user->is_in_group_id($planning->admin_group))
		&& !($site->user->is_in_group("gestion_ae")))
		$cts->add_paragraph("Vous n'avez pas le droit de faire cela");
	else
	{
		if($planning->delete_gap( $_REQUEST["id_gap"]))
			$cts->add_paragraph("Suppression terminee");
		else
			$cts->add_paragraph("Echec de la suppression");
	}
}

if($_REQUEST["action"] === "edit" && isset($_REQUEST["start"]) 
	&& isset($_REQUEST["end"]) && isset($_REQUEST["name"]) 
	&& isset($_REQUEST["id_planning"]))
{
	$id_group_admin = $planning->admin_group;
	if(!$site->user->is_in_group_id($id_group_admin) && !$site->user->is_in_group("gestion_ae"))
		$cts->add_paragraph("Vous n'avez l'autorisation de faire cela");	else
	{

		$id_group = $planning->group;
		$start = $_REQUEST["start"]+date("Z");
		$end = $_REQUEST["end"]+date("Z");
		$is_public = isset($_REQUEST["is_public"])&&$_REQUEST["is_public"];
		$name = $_REQUEST["name"];

		if($planning->update($name,$id_group, $id_group_admin, $start, $end, $is_public))
			$cts->add_paragraph("Modification du planning reussi");
		else
			$cts->add_paragraph("Echec de la modification du planning");
	}
}

if($_REQUEST["action"] === "new" && isset($_REQUEST["id_group_admin"]) 
	&& isset($_REQUEST["id_group"]) && isset($_REQUEST["start"]) 
	&& isset($_REQUEST["end"]) && isset($_REQUEST["name"]) 
	&& isset($_REQUEST["weekly"]))
{
	$id_group_admin = $_REQUEST["id_group_admin"];
	if(!$site->user->is_in_group_id($id_group_admin))
	{
		$cts->add_paragraph("Vous n'avez l'autorisation de faire cela");	}
	else
	{
		$id_group = $_REQUEST["id_group"];
		$start = $_REQUEST["start"]+date("Z");
		$end = $_REQUEST["end"] + date("Z");
		$is_public = isset($_REQUEST["is_public"])&& $_REQUEST["is_public"];
		$name = $_REQUEST["name"];
		$weekly = $_REQUEST["weekly"];

		if($planning->add($name,$id_group, $id_group_admin, $weekly, $start, $end, $is_public))
			$cts->add_paragraph("Ajout du planning reussi");
		else
			$cts->add_paragraph("Echec de l'ajout du planning");
	}
}


if($_REQUEST["action"] === "add_to_gap" && isset($_REQUEST["gap_id"]))
{
	$gap_id = $_REQUEST["gap_id"];
	if( !$site->user->is_in_group_id($planning->admin_group) && !$site->user->is_in_group_id($planning->group) )
	{
		$cts->add_paragraph("Vous n'avez pas le droit de faire cela.");
		$site->add_contents($cts);
		$site->end_page();
		exit();
	}
	$gap = $planning->get_gap_info( $gap_id );
	if( list ( $id_gap, $name_gap, $start, $end ) = $gap->get_row())
	{
		$frm = new form("add_to_gap","./planning2.php?id_planning=".$planning->id,true,"POST","Permanence sur le creneau $name_gap de $planning->name");
		$frm->add_hidden("action","do_add_to_gap");
		$frm->add_hidden("gap_id",$gap_id);
		if($planning->weekly)
		{
			$frm->add_info("Creneau du ".strftime("%A %H:%M",strtotime($start))." au ".strftime("%A %H:%M",strtotime($end))).
			$frm->add_date_field("start", "Date de debut ",-1,true);
			$frm->add_date_field("end", "Date de fin ",-1,true);
		}
		else
		{
			$frm->add_info("Creneau de $start a $end");
		}
		$frm->add_submit("do_add_to_gap","Valider");
		$cts->add($frm);
	}
}

if($_REQUEST["action"] === "remove_from_gap" && isset($_REQUEST["user_gap_id"]))
{
	$user_gap_id = $_REQUEST["user_gap_id"];
	$user_gap = $planning->get_user_gap_info($user_gap_id);
	if( list( $gap_id, $id_utl, $user_gap_start, $user_gap_end ) = $user_gap->get_row())
	{
		if( $id_utl != $site->user->id && !$site->user->is_in_group_id($planning->admin_group) && !$site->user->is_in_group("gestion_ae") )
		{
			$cts->add_paragraph("Vous n'avez pas le droit de faire cela.");
			$site->add_contents($cts);
			$site->end_page();
			exit();
		}
		$gap = $planning->get_gap_info( $gap_id );
		if( list ( $id_gap, $name_gap, $start, $end ) = $gap->get_row())
		{
			$frm = new form("remove_from_gap","./planning2.php?id_planning=".$planning->id,true,"POST","Permanence sur le creneau $name_gap de $planning->name");
			$frm->add_hidden("action","do_remove_from_gap");
			$frm->add_hidden("user_gap_id",$user_gap_id);
			if($id_utl != $site->user->id)
			{
				$user = new utilisateur($site->db);
				$user->load_by_id($id_utl);
				$frm->add_info("Desinscrire ".$user->get_surnom_or_alias()." du ".strftime("%A %H:%M",strtotime($start)).
					" au ".strftime("%A %H:%M",strtotime($end))."?");
			}
			else
			{
				$frm->add_info("Vous desinscrire du ".strftime("%A %H:%M",$week_start+strtotime($start)).
					" au ".strftime("%A %H:%M",$week_start+strtotime($end))."?");
			}
			$frm->add_submit("do_remove_from_gap","Valider");
			$cts->add($frm);
		}
	}
}

if($_REQUEST["action"] === "do_remove_from_gap" && isset($_REQUEST["user_gap_id"]))
{
	$user_gap_id = $_REQUEST["user_gap_id"];
	$user_gap = $planning->get_user_gap_info($user_gap_id);
	if( list( $gap_id, $id_utl, $user_gap_start, $user_gap_end ) = $user_gap->get_row())
	{
		if( $id_utl != $site->user->id && !$site->user->is_in_group_id($planning->admin_group) && !$site->user->is_in_group("gestion_ae"))
		{
			$cts->add_paragraph("Vous n'avez pas le droit de faire cela.");
			$site->add_contents($cts);
			$site->end_page();
			exit();
		}
		$gap = $planning->get_gap_info( $gap_id );
		if( list ( $id_gap, $name_gap, $start, $end ) = $gap->get_row())
		{
			$planning->remove_user_from_gap( $user_gap_id );
			$cts->add_paragraph("Desinscription reussie!");
		}
	}
}

if($_REQUEST["action"] === "do_add_to_gap" && isset($_REQUEST["gap_id"]))
{
	if(!$planning->weekly || (isset($_REQUEST["start"]) && isset($_REQUEST["end"])))
	{
		$gap_id = $_REQUEST["gap_id"];
		$user_id = $site->user->id;
		$start = null;
		$end = null;
		if(!$planning->weekly)
		{
			$sql = $planning->get_gap_info($gap_id);
			if(!(list($tmp, $tmp2, $start, $end) = $sql->get_row() ))
			{
				$site->add_contents($cts);
			        $site->end_page();
			        exit();
			}
			$start = strtotime($start." UTC");
			$end = strtotime($end." UTC");
		}
		else
		{
			$start = $_REQUEST["start"]+date("Z");
			$end = $_REQUEST["end"]+date("Z");
		}
		if($planning->is_user_addable($gap_id, $user_id, $start, $end ))
		{
			$planning->add_user_to_gap($gap_id, $user_id, $start, $end );
			$cts->add_paragraph("Ajout effectue.");
		}
		else
		{
			$cts->add_paragraph("Impossible de vous ajouter.");
		}
	}
}


if($_REQUEST["view"] === "new_gap" && isset($_REQUEST["id_planning"]))
{
	$frm = new form("new_gap","planning2.php",true,"POST","Nouveau creneau sur le planning \"$planning->name\"?");
	$frm->add_info("Nouveau creneau sur le planning \"$planning->name\"?");
	$frm->add_hidden("action","new_gap");
	$frm->add_hidden("id_planning",$planning->id);
	$frm->add_text_field("name","Nom","",true);
	$frm->add_text_field("max_users","Nombre de personne","1",true);
	$frm->add_datetime_field("start", "Debut ",$planning->start-date("Z"),true);
	$frm->add_datetime_field("end", "Fin ",$planning->end-date("Z"),true);
	$frm->add_submit("new_gap","Valider");
	$cts->add($frm);
}


if($_REQUEST["view"] === "del_gap" && isset($_REQUEST["id_planning"]) && isset($_REQUEST["id_gap"]))
{
	$frm = new form("del_gap","planning2.php",true,"POST","Suppression du creneau ?");
	$frm->add_info("Suppression du creneau ?");
	$frm->add_hidden("action","del_gap");
	$frm->add_hidden("id_planning",$planning->id);
	$frm->add_hidden("id_gap",$_REQUEST["id_gap"]);
	$frm->add_submit("del_gap","Supprimer le creneau");
	$cts->add($frm);
}

if($_REQUEST["view"] === "del" && isset($_REQUEST["id_planning"]))
{
	$frm = new form("del","planning2.php",true,"POST","Suppression du planning \"$planning->name\"?");
	$frm->add_info("Suppression du planning \"$planning->name\"?");
	$frm->add_hidden("action","del");
	$frm->add_hidden("id_planning",$planning->id);
	$frm->add_submit("del","Supprimer");
	$cts->add($frm);
}

if($_REQUEST["view"] === "edit" && isset($_REQUEST["id_planning"]))
{
	$frm = new form("edit","planning2.php",true,"POST","Edition du planning");
	$frm->add_hidden("action","edit");
	$frm->add_hidden("id_planning",$planning->id);
	$frm->add_text_field("name","Nom",$planning->name,true);
	$frm->add_date_field("start", "Date de debut ",$planning->start-date("Z"),true);
	$frm->add_date_field("end", "Date de fin ",$planning->end-date("Z"),true);
	$frm->add_checkbox("is_public","Publique",$planning->is_public,false);
	$frm->add_submit("edit","Valider");
	$cts->add($frm);
}

if($_REQUEST["view"] === "new")
{
	$frm = new form("new","planning2.php",true,"POST","Nouveau planning");
	$frm->add_hidden("action","new");
	$frm->add_text_field("name","Nom","",true);
	$frm->add_entity_select( "id_group_admin", "Propri&eacute;taire", $site->db, "group",0);
	$frm->add_entity_select( "id_group", "Groupe", $site->db, "group",0);

	$frm->add_radiobox_field("weekly","Periodicite",
		array(	0=>"Ponctuel",
			7=>"Hebdomadaire",
			14=>"Bihebdomadaire"),
		0,-1,false,array(),false);
	$frm->add_date_field("start", "Date de debut ",-1,true);
	$frm->add_date_field("end", "Date de fin ",-1,true);
	$frm->add_checkbox("is_public","Publique",false,false);
	$frm->add_submit("new","Valider");
	$cts->add($frm);

	$site->add_contents($cts);
        $site->end_page();
        exit();
}

if(!isset($_REQUEST["id_planning"]) || $_REQUEST["view"] === "lst")
{
	$grps = $site->user->get_groups_csv();
	$sql = new requete($site->db,
		"SELECT id_planning, name_planning, DATE(start) as start, DATE(end) as end FROM pl2_planning
		 WHERE end > NOW() 
		 AND
		 (
			is_public = 1
			OR
			id_group IN ($grps)
			OR
			id_admin_group IN ($grps)
		 )
		 ORDER BY name_planning ASC");
	$table = new sqltable("listeplannings", 
			"Plannings actuels", 
			$sql, 
			"planning2.php?view=view",
			"id_planning",
			array(
				"name_planning" => "Nom",
				"start"		=> "Date de debut",
				"end"		=> "Date de fin"
			),
			array("details" => "Details"),
			array(),
			array());
	$cts->add($table);
	$site->add_contents($cts);
	$site->end_page();
	exit();
}



$planningv = new planningv("",$site->db,$planning->id, time(), time()+7*24*3600, $site, false, true);

$cts->add($planningv);

$site->add_contents($cts);
$site->end_page();
?>
