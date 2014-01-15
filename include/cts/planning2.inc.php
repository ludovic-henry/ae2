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

/**
 * @file
 */
require_once($topdir."include/cts/standart.inc.php");

/**
 * Affiche un planning hebdomadaire
 *
 * @author Simon Le Lann
 * @ingroup display_cts
 */
class planningv extends stdcontents
{

    var $planning;
    var $week_start;
    var $days_per_row;
    var $is_multi_day;


    function make_mono($body,$used_names)
    {
	$buffer = "<table class=\"".(($this->is_multi_day)?"pl2_mono_multi ":"")."pl2_mono\">\n<tr>\n";
        $buffer .= "<th class=\"pl2_gap_name\"></th>";
	foreach($used_names as $name)
	{
		$buffer .= "<th class=\"pl2_gap_name\">$name</th>\n";
	}
	$buffer .= "</tr>";
	$buffer .= $body;
	$buffer .= "</table>";
	return $buffer;
    }
 
    function make_multi($days)
    {
	$buffer = "<table class=\"pl2_multi\">\n<tr>\n";
	$head = 0;
	foreach(array_keys($days) as $day)
	{
		$head++;
		$buffer .= "<th class=\"pl2_day_name\">".strftime("%A %d/%m",strtotime($day)+(($this->planning->weekly)?($this->week_start):0))."</th>";
		if($head >= $this->days_per_row)
			break;
	}
	$buffer .= "</tr>\n<tr>";
	$col = 0;
	foreach($days as $body)
	{
		$col++;
		$buffer .= "<td class=\"pl2_multi\">";
		$buffer .= $body;
		$buffer .= "</td>";
		if(($col % $this->days_per_row) == 0)
		{
			$buffer .= "</tr></table>";
			$buffer .= "<table class=\"pl2_multi\">\n<tr>\n";
			$header = -$col;
			foreach(array_keys($days) as $day)
			{
				$header ++;
				if($header > 0)
					$buffer .= "<th class=\"pl2_day_name\">".strftime("%A %d/%m",strtotime($day)+(($this->planning->weekly)?($this->week_start):0))."</th>";
				if($header >= $this->days_per_row)
					break;
			}
			$buffer .= "</tr>\n<tr>";
		}
	}
		$buffer .= "</tr></table>";
		
	return $buffer;
	
    }

    /**
     * Génére un planning hebdomadaire
     * @param $titre Titre du contenu
     * @param $db Connection à la base de donnée
     */
    function planningv ( $titre, $db, $id_planning, $start, $end, $site, $force_single_column = false, $show_admin = false, $days_per_row = 7)
    {
	global $wwwtopdir;

	setlocale(LC_ALL, "fr_FR.UTF8");
	$this->title=false;
	$this->days_per_row = $days_per_row;

	$this->planning = new planning2($db, $db);
	$this->planning->load_by_id($id_planning);
	
	if(!$site->user->is_in_group_id($this->planning->group) && !$site->user->is_in_group_id($this->planning->admin_group) 
		&& !$this->planning->is_public && !$site->user->is_in_group("gestion_ae"))
	{
		$this->buffer .= "<p>Droits insuffisants pour lire ce planning</p>";
		return;
	}

	$gaps = null;
	if($this->planning->weekly)
		$gaps = $this->planning->get_gaps($start, $end);
	else
		$gaps = $this->planning->get_gaps(null, null);

	$gaps_data = array();
	while( list( $gap_id, $gap_start, $gap_end, $gap_name, $gap_count) = $gaps->get_row())
	{
		$gap_data = array();
		$users = $this->planning->get_users_for_gap($gap_id,$start);
		while( list( $utl, $nom_utl, $user_gap_id ) = $users->get_row() ){
			$gap_data[] = array( 0 => $utl, 1 => $nom_utl, 2=> $user_gap_id);
		}
		$gaps_data[$gap_id] = array( "id" => $gap_id, "start" => $gap_start, "end" => $gap_end, "name" => $gap_name, "count" => $gap_count, "user" => $gap_data);
	}
	if(empty($gaps_data))
	{
		$this->buffer .= "<p>Aucun creneau</p>";
		return;
	}

	$gaps_time = null;
	if($this->planning->weekly)
		$gaps_time = $this->planning->get_gaps_time($start, $end);
	else
		$gaps_time = $this->planning->get_gaps_time(null, null);
	if($gaps_time->lines <= 0)
	{
		$this->buffer .= "<p>Erreur: Heures des creneaux non trouvees</p>";
		return;
	}

	$this->week_start = $this->planning->get_week_start($start);
	list( $start ) = $gaps_time->get_row();
	$end = $start;
	while( list($tmp) = $gaps_time->get_row() )
		$end = $tmp;
	if($this->planning->weekly)
	{
		$start = strtotime($start) + $this->week_start;
		$end = strtotime($end) + $this->week_start;
	}
	else
	{
		$start = strtotime($start);
		$end = strtotime($end);
	}

	$this->is_multi_day = true;
	if( date("Y m d",$start) === date("Y m d",$end) || $force_single_column)
		$this->is_multi_day = false;

	$gaps_names = $this->planning->get_gaps_names();
	$names = array();
	while( list( $name ) = $gaps_names->get_row() )
	{
		$name = trim($name);
		if(empty($name))
			continue;
		$names[] = $name;
		$end_times[$name] = -1;
	}
	$gaps_time->go_first();
	$days = array();
	$current_day = null;
	while(list( $time ) = $gaps_time->get_row())
	{
		if($current_day === null)
			$current_day = strtotime(gmdate("Y-m-d 00:00:00",strtotime($time." UTC"))." UTC");
		while($current_day < strtotime(gmdate("Y-m-d 00:00:00",strtotime($time))))
		{

			if(is_null($days[gmdate("Y-m-d 00:00:00",$current_day)]) 
				|| !in_array(gmdate("Y-m-d 23:59:59",$current_day),$days[gmdate("Y-m-d 00:00:00",$current_day)],true))
				$days[gmdate("Y-m-d 00:00:00",$current_day)][] = gmdate("Y-m-d 23:59:59",$current_day);
			$current_day += 86400;
			if(is_null($days[gmdate("Y-m-d 00:00:00",$current_day)]) 
				|| !in_array(gmdate("Y-m-d 00:00:00",$current_day),$days[gmdate("Y-m-d 00:00:00",$current_day)],true))
				$days[gmdate("Y-m-d 00:00:00",$current_day)][] = gmdate("Y-m-d 00:00:00",$current_day);
		}

		if(is_null($days[gmdate("Y-m-d 00:00:00",$current_day)]) || !in_array($time,$days[gmdate("Y-m-d 00:00:00",$current_day)],true))
		{
			$days[gmdate("Y-m-d 00:00:00",$current_day)][] = $time;
		}
	}

	if($this->is_multi_day)
		$days_buffer = array();
	else
		$days_buffer = "";
	foreach($days as $day_key => $day)
	{
		$last_time = null;
		$used_names = array();
		list( $current_day ) = $day;
		$current_day_start = strtotime(date("Y-m-d 00:00:00",strtotime($current_day." UTC"))." UTC");
		$current_day_end = strtotime(date("Y-m-d 23:59:59",strtotime($current_day." UTC"))." UTC");
		$used_names = array();
		foreach($names as $name)
		{
			$end_times[$name] = -1;
			$gaps->go_first();
			while( list( $gap_id, $gap_start, $gap_end, $gap_name, $gap_count) = $gaps->get_row())
			{
				$gap_start = strtotime($gap_start." UTC");
				$gap_end = strtotime($gap_end." UTC");
				if($gap_name === $name 
					&& ( ($gap_start >= $current_day_start && $gap_start <= $current_day_end)
					||  ($gap_end >= $current_day_start && $gap_end <= $current_day_end) 
					|| ($gap_start <= $current_day_start && $gap_end >= $current_day_end) ))
					if(!in_array($name,$used_names,true))
						$used_names[] = $name;
			}
		}
		$lines_buffer = "";
		foreach($day as $time)
		{
			$line_buffer = "";
			$is_empty = true;
			if($last_time === null)
			{
				$last_time = $time;
				continue;
			}
			$line_buffer .= "<tr>\n";
			$line_buffer .= "<td class=\"pl2_horaires\"><div class=\"pl2_horaires\"><div style=\"top:0;\">".date("H:i", strtotime($last_time))."</div><div style=\"position:absolute; bottom:0;\">".date("H:i", strtotime($time))."</div></div></td>";
			
			foreach($used_names as $name)
			{
				if(strtotime($last_time." UTC") <= $end_times[$name])
				{
					$is_empty = false;
					continue;
				}
				$new_gaps = array();
				$curr_gaps = array();
				$gaps->go_first();
				while( list( $gap_id, $gap_start, $gap_end, $gap_name, $gap_count) = $gaps->get_row())
				{
					if($gap_name === $name
						&& strtotime($gap_start) === strtotime($last_time))
					{
						$new_gaps[] = $gap_id;
					}
					if($gap_name === $name
						&& strtotime($gap_start) < strtotime($last_time)
						&& strtotime($gap_end) > strtotime($last_time))
						$curr_gaps[] = $gap_id;
				}
				if(empty($new_gaps) && empty($curr_gaps))
				{
					$line_buffer .= "<td class=\"pl2_no_gap\"></td>";
				}
				else
				{
					$is_empty = false;
					$end_time = PHP_INT_MAX;
					$gaps->go_first();
					while( list( $gap_id, $gap_start, $gap_end, $gap_name, $gap_count) = $gaps->get_row())
					{
						if($gap_name === $name
							&& strtotime($gap_end) > strtotime($last_time))
						{
							if(strtotime($gap_start) < $end_time && strtotime($gap_start) > strtotime($last_time))
								$end_time = strtotime($gap_start);
							
							if(strtotime($gap_end) < $end_time)
								$end_time = strtotime($gap_end);
						}
					}
					$end_times[$name] = $end_time;
					$span = 0;
					for($i = 0; $i < count($day); $i++)
					{
						$tmp_time = $day[$i];
						if(strtotime($tmp_time) > strtotime($last_time)
							&& strtotime($tmp_time) <= $end_time)
						{
							$span++;
						}
					}
					$totalMax = 0;
					$totalCount = 0;
					$cell_buffer = "";

					foreach($new_gaps as $gap_id)
					{
						$count = 0;
						$my_gap = $gaps_data[$gap_id];
						$gap_count = $my_gap["count"];
						$cell_buffer .= "<div class=\"pl2_names\">";
						foreach(  $my_gap["user"] as $gap_data)
						{
							$count++;
							if($gap_data[0] == $site->user->id || $site->user->is_in_group_id($this->planning->admin_group)
								|| $site->user->is_in_group("gestion_ae"))
								$cell_buffer .= ($count==1?"":", ")."<a href=\"".$wwwtopdir."planning2.php?action=remove_from_gap&user_gap_id=$gap_data[2]&id_planning=".$this->planning->id."\">".$gap_data[1]."</a>";
							else
								$cell_buffer .= ($count==1?"":", ").$gap_data[1];

						}
						if($count < $gap_count)
						{
							$cell_buffer .= ($count?" et ":"")."<a class=\"pl2_link\" href=\"".$wwwtopdir."planning2.php?action=add_to_gap&gap_id=$gap_id&id_planning=".$this->planning->id."\"><span>".($gap_count - $count)."&nbsp;personne".(($gap_count - $count)>=2?"s":"")."</span></a>";
						}
						if($show_admin && (     $site->user->is_in_group_id($this->planning->admin_group)
							|| $site->user->is_in_group("gestion_ae")))
						{
							$cell_buffer .= " <a href=\"".$wwwtopdir."planning2.php?view=del_gap&id_gap=$gap_id&id_planning=".$this->planning->id."\">Supprimer</a>";
						}
						$cell_buffer .= "</div>";
						$totalCount += $count;
						$totalMax += $gap_count;
					}
					foreach($curr_gaps as $gap_id)
					{
						$count = 0;
						$my_gap = $gaps_data[$gap_id];
						$gap_count = $my_gap["count"];
						$cell_buffer .= "<div class=\"pl2_names\">";
						foreach(  $my_gap["user"] as $gap_data)
						{
							$count++;
							if($gap_data[0] == $site->user->id || $site->user->is_in_group_id($this->planning->admin_group)
								|| $site->user->is_in_group("gestion_ae"))
								$cell_buffer .= ($count==1?"Continué: ":", ")."<a href=\"".$wwwtopdir."planning2.php?action=remove_from_gap&user_gap_id=$gap_data[2]&id_planning=".$this->planning->id."\">".$gap_data[1]."</a>";
							else
								$cell_buffer .= ($count==1?"Continué: ":", ").$gap_data[1];

						}
						if($count < $gap_count)
						{
							$cell_buffer .= ($count?" et ":"")."<a class=\"pl2_link\" href=\"".$wwwtopdir."planning2.php?action=add_to_gap&gap_id=$gap_id&id_planning=".$this->planning->id."\">".($gap_count - $count)."&nbsp;personne".(($gap_count - $count)>=2?"s":"")."</a>";
						}
						if($show_admin && (     $site->user->is_in_group_id($this->planning->admin_group)
							|| $site->user->is_in_group("gestion_ae")))
						{
							$cell_buffer .= " <a href=\"".$wwwtopdir."planning2.php?view=del_gap&id_gap=$gap_id&id_planning=".$this->planning->id."\">Supprimer</a>";
						}
						$cell_buffer .= "</div>";
						$totalCount += $count;
						$totalMax += $gap_count;
					}
					if($totalCount < $totalMax)
					{
						$line_buffer .= "<td class=\"pl2_gap_partial\" rowspan=$span><div class=\"pl2_gap_partial\">";
						$line_buffer .= $cell_buffer;
						$line_buffer .= "</div></td>";
					}
					else
					{
						$line_buffer .= "<td class=\"pl2_gap_full\" rowspan=$span><div class=\"pl2_gap_full\">";
						$line_buffer .= $cell_buffer;
						$line_buffer .= "</div></td>";
					}
				}
			}
			
			$line_buffer .= "</tr>\n";
			if(!$is_empty)
				$lines_buffer .= $line_buffer;
			$last_time = $time;
		}
		$tmp_names = $used_names;
		reset($tmp_names);
		if(!$this->is_multi_day)
			$days_buffer .= $this->make_mono($lines_buffer,$tmp_names);
		else
			if(!empty($lines_buffer))
				$days_buffer[$day_key] .= $this->make_mono($lines_buffer,$tmp_names);

	}

	
	if($this->is_multi_day)
	{
		$this->buffer .= $this->make_multi($days_buffer);
	}
	else
		$this->buffer .= $days_buffer;

    }

    function get_buffer()
    {
       return $this->buffer;
    }


}



?>
