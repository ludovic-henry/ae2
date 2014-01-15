<?php

class planning2 extends stdentity
{
	var $name;
	var $weekly;
	var $group;
	var $admin_group;
	var $start;
	var $end;
	var $is_public;

	function load_by_id( $id )
	{
		if(is_null($id) || empty($id))
			return false;
		$req = new requete( $this->db, "SELECT * from `pl2_planning`
						WHERE `id_planning` = '".
						intval($id).
						"' LIMIT 1");
		if( $req->lines != 1 )
		{
			$this->id = null;
			return false;
		}
		$this->_load($req->get_row());
		return true;
	}

	function _load( $row )
	{
		$this->id		= $row['id_planning'];
		$this->group		= $row['id_group'];
		$this->admin_group	= $row['id_admin_group'];
		$this->name		= $row['name_planning'];
		$this->weekly		= $row['weekly_planning'];
		$this->start		= strtotime($row['start']." UTC");
		$this->end		= strtotime($row['end']." UTC");
		$this->is_public	= $row['is_public'];
	}

	function add ( $name, $group, $admin_group, $weekly, $start, $end, $is_public = true )
	{
		$this->name = mysql_real_escape_string($name);
		$this->group = intval($group);
		$this->admin_group = intval($admin_group);
		$this->weekly = intval($weekly);
		$this->start = intval($start);
		$this->end = intval($end);
		$this->is_public= ((bool)$is_public);

		$sql = new insert ($this->dbrw,
                       "pl2_planning",
                       array(
                             "name_planning" => $this->name,
			     "id_group" => $this->group,
			     "id_admin_group" => $this->admin_group,
			     "start" => gmdate("Y-m-d H:i:s",$this->start),
			     "end" => gmdate("Y-m-d H:i:s",$this->end),
                             "weekly_planning" => $this->weekly,
                             "is_public" => $this->is_public
                            )
                      );
		if ( !$sql->is_success() )
		{
			$this->id = null;
			return false;
		}

		$this->id = $sql->get_id();

		return true;
	}

	function update ( $name, $group, $admin_group, $start, $end , $is_public)
	{
		$this->name = mysql_real_escape_string($name);
		$this->group = intval($group);
		$this->admin_group = intval($admin_group);
		$this->start = intval($start);
		$this->end = intval($end);
		$this->is_public = ((bool)$is_public);

		$sql = new update ($this->dbrw,
                       "pl2_planning",
                       array(
                             "name_planning" => $this->name,
			     "id_group" => $this->group,
			     "id_admin_group" => $this->admin_group,
			     "start" => gmdate("Y-m-d H:i:s",$this->start),
			     "end" => gmdate("Y-m-d H:i:s",$this->end),
                             "weekly_planning" => $this->weekly,
                             "is_public" => $this->is_public
                            ),
			array("id_planning" => $this->id)
                      );
		return $sql->is_success();
	}

	function remove()
	{
		$sql = new requete($this->db, "SELECT `id_gap` FROM `pl2_gap`
						WHERE id_planning = ".$this->id);
		while(list($gap_id) = $sql->get_row())
		{
			$this->delete_gap( $gap_id );
		}
		$sql = new delete($this->dbrw, "pl2_absence",
			array(
                             "id_planning" => $this->id
                            )
                       );
		$sql = new delete($this->dbrw, "pl2_planning",
			array(
                             "id_planning" => $this->id
                            )
                       );
	}

	function add_gap( $start, $end, $gap_name, $max_users )
	{
		$start = intval($start);
		$end = intval($end);
		$gap_name = mysql_real_escape_string($gap_name);
		$max_users = intval($max_users);

		$gap_name = trim($gap_name);
		if( $max_users <= 0 )
			return -1;
		if(empty($gap_name))
			return -1;
		if($start >= $end)
			return -1;
		if(!$this->weekly && $start < $this->start)
			return -1;
		if(!$this->weekly && $end > $this->end)
			return -1;
		if($this->weekly)
		{
			if($end >= $this->weekly*24*3600)
				return -1;
		}
		$sql = new insert ($this->dbrw,
                       "pl2_gap",
                       array(
			     "id_planning" => $this->id,
                             "name_gap" => $gap_name,
			     "start" => gmdate("Y-m-d H:i:s",$start),
                             "end" => gmdate("Y-m-d H:i:s",$end),
			     "max_users" => $max_users
                            )
                      );
		if ( !$sql->is_success() )
		{
			return -1;
		}

		return $sql->get_id();
	}

	function update_gap( $gap_id, $start, $end, $gap_name, $max_users )
	{
		$gap_id = intval($gap_id);
		$start = intval($start);
		$end = intval($end);
		$gap_name = mysql_real_escape_string($gap_name);
		$max_users = intval($max_users);
		if($gap_id <= 0 )
			return -1;
		if( $max_users <= 0 )
			return -1;
		$gap_name = trim($gap_name);
		if(empty($gap_name))
			return -1;
		if($start >= $end)
			return -1;
		if($start < $this->start)
			return -1;
		if($end > $this->end)
			return -1;
		if($this->weekly)
		{
			if($end >= $this->weekly*24*3600)
				return -1;
		}
		$sql = new update ($this->dbrw,
                       "pl2_gap",
                       array(
			     "id_planning" => $this->id,
                             "name_gap" => $gap_name,
			     "max_users" => $max_users,
			     "start" => gmdate("Y-m-d H:i:s",$start),
                             "end" => gmdate("Y-m-d H:i:s",$end)
                            ),
		       array(
			     "id_gap" => $gap_id
		       )
                      );
	}

	function delete_gap( $gap_id )
	{
		$gap_id = intval($gap_id);
		$sql = new delete($this->dbrw, "pl2_user_gap",
			array(
                       		"id_gap" => $gap_id
			)
                );
		if(!$sql->is_success())
			return false;
		$sql = new delete($this->dbrw, "pl2_gap",
			array(
                             "id_gap" => $gap_id
                            )
                       );
		return $sql->is_success();
	}

	function get_max_users_for( $gap_id, $start, $end )
	{
		$gap_id = intval($gap_id);
		$start = intval($start);
		$end = intval($end);
		if(!$this->weekly)
		{
			$sql = new requete($this->db,
				"SELECT count(*) FROM pl2_user_gap
        	                 JOIN pl2_gap ON pl2_gap.id_gap = pl2_user_gap.id_gap
                	         WHERE pl2_gap.id_gap = '$gap_id'");
			if( list($total) = $sql->get_row())
				return $total;
			else
				return -1;
		}
		$max_users = 0;
		$new_start = $start;
		$to_break = false;
		$date_absence = null;
		$date_min = null;
		while(true)
		{
			$to_break = false;
			$sql = new requete($this->db,
				"SELECT min(pl2_absence.start) FROM pl2_absence
				 JOIN pl2_gap 
				 ON pl2_gap.id_planning = pl2_absence.id_planning
				 JOIN pl2_user_gap
				 ON pl2_gap.id_gap = pl2_user_gap.id_gap
				 AND pl2_user_gap.id_utilisateur = pl2_absence.id_utilisateur
				 WHERE pl2_gap.id_gap = '$gap_id'
				 AND pl2_absence.start < '".gmdate("Y-m-d H:i:s",$end)."'
				 AND pl2_absence.end > '".gmdate("Y-m-d H:i:s",$new_start)."'");
			if($sql->lines <= 0)
				$to_break = true;
			else
			{
				$date_absence = null;
				list( $date_absence ) = $sql->get_row();
				if(is_null($date_absence))
					$to_break = true;
			}

			$sql = new requete($this->db,
				"SELECT min(pl2_user_gap.end) FROM pl2_user_gap
				 JOIN pl2_gap
				 ON pl2_gap.id_gap = pl2_user_gap.id_gap
				 WHERE pl2_user_gap.start <= '".gmdate("Y-m-d H:i:s",$new_start)."' 
				 AND pl2_user_gap.end > '".gmdate("Y-m-d H:i:s",$new_start)."'");

			if($sql->lines <= 0)
			{
				if($to_break)
					break;
			}
			else
			{
				$date_min = null;
				list( $date_min ) = $sql->get_row();
				if(is_null($date_min) && $to_break)
					break;
			}
			if(is_null($date_min))
			{
				$date_min = $date_absence;
			}
			elseif(is_null($date_absence))
			{
			}
			else
				$date_min = ($date_min<$date_absence)?$date_min:$date_absence;
			
			$sql = new requete($this->db,
				"SELECT count(*) FROM pl2_user_gap
        	                 JOIN pl2_gap ON pl2_gap.id_gap = pl2_user_gap.id_gap
                	         WHERE pl2_gap.id_gap = '$gap_id'
				 AND pl2_user_gap.start <= '".gmdate("Y-m-d H:i:s",$new_start)."'
				 AND pl2_user_gap.end >= '".gmdate("Y-m-d H:i:s",$date_min)."'");
			if(!$sql->is_success())
			{
				exit();
			}
			list( $my_max ) = $sql->get_row();
			$max_users = max($my_max,$max_users);
			$new_start = $date_min;
		}
		return $max_users;
		
	}
	
	function is_user_addable( $gap_id, $user_id, $start, $end )
	{
		$gap_id = intval($gap_id);
		$user_id = intval($user_id);
		$start = intval($start);
		$end = intval($end);

		$sql = new requete($this->db, 
			"SELECT * from pl2_user_gap
			 WHERE id_gap = '$gap_id'
			 AND id_utilisateur = '$user_id'
			 AND 
			 (
			 	(	 start <= '".gmdate("Y-m-d H:i:s",$end)."'
					 AND start >= '".gmdate("Y-m-d H:i:s",$start)."'
				)
			  	OR
				(        end >= '".gmdate("Y-m-d H:i:s",$start)."'
					 AND end <= '".gmdate("Y-m-d H:i:s",$end)."'
				)
			 )");
		if($sql->lines > 0)
			return false;
		$sql = new requete($this->db, 
			"SELECT * from pl2_user_gap
			 JOIN pl2_gap ON pl2_gap.id_gap = pl2_user_gap.id_gap
			 WHERE id_utilisateur = '$user_id'
			 AND id_planning IN (SELECT id_planning FROM pl2_gap WHERE id_gap = '$gap_id')
			 AND 
			 (
			 	(	 pl2_user_gap.start <= '".gmdate("Y-m-d H:i:s",$end)."'
					 AND pl2_user_gap.start >= '".gmdate("Y-m-d H:i:s",$start)."'
				)
			  	OR
				(        pl2_user_gap.end >= '".gmdate("Y-m-d H:i:s",$start)."'
					 AND pl2_user_gap.end <= '".gmdate("Y-m-d H:i:s",$end)."'
				)
			  	OR
				(        pl2_user_gap.start <= '".gmdate("Y-m-d H:i:s",$start)."'
					 AND pl2_user_gap.end >= '".gmdate("Y-m-d H:i:s",$end)."'
				)
			  	OR
				(        pl2_user_gap.start >= '".gmdate("Y-m-d H:i:s",$start)."'
					 AND pl2_user_gap.end <= '".gmdate("Y-m-d H:i:s",$end)."'
				)
			 )
			 AND
			 (
				(
					pl2_gap.start < (SELECT tmp.end FROM pl2_gap AS tmp WHERE tmp.id_gap = '$gap_id')
					AND pl2_gap.start > (SELECT tmp.start FROM pl2_gap AS tmp WHERE tmp.id_gap = '$gap_id')
				)
				OR
				(
					pl2_gap.end > (SELECT tmp.start FROM pl2_gap AS tmp WHERE tmp.id_gap = '$gap_id')
                                        AND pl2_gap.end < (SELECT tmp.end FROM pl2_gap AS tmp WHERE tmp.id_gap = '$gap_id')
				)
				OR
				(
					pl2_gap.start <= (SELECT tmp.start FROM pl2_gap AS tmp WHERE tmp.id_gap = '$gap_id')
                                        AND pl2_gap.end >= (SELECT tmp.end FROM pl2_gap AS tmp WHERE tmp.id_gap = '$gap_id')
				)
				OR
				(
					pl2_gap.start >= (SELECT tmp.start FROM pl2_gap AS tmp WHERE tmp.id_gap = '$gap_id')
                                        AND pl2_gap.end <= (SELECT tmp.end FROM pl2_gap AS tmp WHERE tmp.id_gap = '$gap_id')
				)
			 )");
		if($sql->lines > 0)
			return false;
		$users = $this->get_max_users_for($gap_id,$start,$end);
		$sql = new requete($this->db,
			"SELECT max_users FROM pl2_gap
			 WHERE id_gap = $gap_id");
		if($sql->lines != 1)
			return false;
		list( $max_users ) = $sql->get_row();
		return ($users < $max_users);
	}

	function add_user_to_gap( $gap_id, $user_id, $start, $end)
	{
		$gap_id = intval($gap_id);
		$user_id = intval($user_id);
		$start = intval($start);
		$end = intval($end);
		if(!$this->is_user_addable($gap_id,$user_id,$start,$end))
			return -1;
		$sql = new insert ($this->dbrw,
                       "pl2_user_gap",
                       array(
			     "id_gap" => $gap_id,
                             "id_utilisateur" => $user_id,
			     "start" => gmdate("Y-m-d H:i:s",$start),
                             "end" => gmdate("Y-m-d H:i:s",$end)
                            )
                      );
		if ( !$sql->is_success() )
		{
			return -1;
		}

		return $sql->get_id();
	}

	function remove_user_from_gap( $user_gap_id )
	{
		$user_gap_id = intval($user_gap_id);
		$sql = new delete($this->dbrw, "pl2_user_gap",
			array(
                             "id_user_gap" => $user_gap_id
                            )
                       );
	}

	function get_gaps_for_user( $user_id)
	{
		$user_id = intval($user_id);
		return new requete($this->db,
			"SELECT id_gap FROM pl2_user_gap
			 WHERE id_utilisateur = $user_id");
	}

	function get_gaps( $start, $end )
	{
		$start = intval($start);
		$end = intval($end);
		if($this->weekly)
			return new requete($this->db,
				"SELECT id_gap, start, end, name_gap, max_users FROM pl2_gap 
				 WHERE id_planning = $this->id 
				 ORDER BY start ASC");
		else
			return new requete($this->db,
                                "SELECT id_gap, start, end, name_gap, max_users FROM pl2_gap 
                                 WHERE id_planning = $this->id ".
				 (($start==0)?"":("AND end > '".gmdate("Y-m-d H:i:s",$start)."' ")).
				 (($end==0)?"":("AND start < '".gmdate("Y-m-d H:i:s",$end)."' ")).
                                 "ORDER BY start ASC");
	}

	function get_gaps_time( $start, $end )
	{
		$start = intval($start);
		$end = intval($end);
		if($this->weekly)
			return new requete($this->db,
				"SELECT start as date FROM pl2_gap 
				 WHERE id_planning = $this->id 
				 UNION DISTINCT SELECT end as date 
				 FROM pl2_gap 
				 WHERE id_planning = $this->id 
				 ORDER BY date ASC");
		else
			return new requete($this->db,
                                "SELECT start as date FROM pl2_gap 
                                 WHERE id_planning = $this->id ".
				 (($start==0)?"":("AND end > '".gmdate("Y-m-d H:i:s",$start)."' ")).
				 (($end==0)?"":("AND start < '".gmdate("Y-m-d H:i:s",$end)."' ")).
                                 "UNION DISTINCT
				 SELECT end as date FROM pl2_gap 
                                 WHERE id_planning = $this->id ".
				 (($start==0)?"":("AND end > '".gmdate("Y-m-d H:i:s",$start)."' ")).
				 (($end==0)?"":("AND start < '".gmdate("Y-m-d H:i:s",$end)."' ")).
                                 "ORDER BY date ASC");
	}

	function get_gaps_names()
	{
		return new requete($this->db,
			"SELECT DISTINCT name_gap FROM pl2_gap WHERE id_planning = $this->id ORDER BY name_gap");
	}

	function get_gap_info( $gap_id )
	{
		$gap_id = intval($gap_id);
		return new requete($this->db,
                        "SELECT id_gap, name_gap, start, end FROM pl2_gap 
			 WHERE id_gap = $gap_id AND id_planning = $this->id");
	}

	function get_user_gap_info( $user_gap_id )
	{
		$user_gap_id = intval($user_gap_id);
		return new requete($this->db,
                        "SELECT id_gap, id_utilisateur, start, end FROM pl2_user_gap
			 WHERE id_user_gap = $user_gap_id");
	}

	function get_gaps_from_names( $name )
	{
		$name = mysql_real_escape_string($name);
		return new requete($this->db,
			"SELECT id_gap FROM pl2_gap WHERE id_planning = $this->id AND gap_name = '$name' ORDER BY start");
	}

	function get_week_start( $date )
	{
		$date = mysql_real_escape_string($date);
		if($this->weekly)
		{
			$diff = $date - $this->start;
                        $date = $date - ($diff % ($this->weekly*3600*24));
		}
		else
		{
			$req = new requete($this->db,
				"SELECT start FROM pl2_gap 
				 WHERE id_planning = $this->id
				 AND start > '".gmdate("Y-m-d H:i:s",$date)."'
				 ORDER BY start ASC LIMIT 1");
			if($req->lines == 1)
			{
				list( $tmp ) = $req->get_row();
				$date = strtotime( $tmp." UTC" );
			}
		}
		return strtotime(gmdate("Y-m-d 00:00:00",$date)." UTC");
	}

	function get_users_for_gap( $gap_id, $date )
	{
		$gap_id = intval($gap_id);
		$date = mysql_real_escape_string($date);
		$sql = new requete($this->db,
			"SELECT start,end FROM pl2_gap
			 WHERE id_gap = $gap_id");
		if(!$sql->is_success())
			exit();
		list( $start, $end ) = $sql->get_row();

		if($this->weekly)
		{
			/*$date = strtotime(date('o-\\WW',$date));*/
			$diff = $date - $this->start;
			$date = $date - ($diff % ($this->weekly*3600*24));
			$start = strtotime($start." UTC")+$date;
			$end = strtotime($end." UTC")+$date;
			$start =gmdate("Y-m-d H:i:s",$start);
			$end =gmdate("Y-m-d H:i:s",$end);	
		}
		return new requete($this->db,
			"SELECT utilisateurs.id_utilisateur as id_utilisateur, 
				IF(utl_etu_utbm.surnom_utbm!='' AND utl_etu_utbm.surnom_utbm IS NOT NULL,
					utl_etu_utbm.surnom_utbm, 
					CONCAT(`utilisateurs`.`prenom_utl`,' ',`utilisateurs`.`nom_utl`)) 
				as `nom_utilisateur`,
				pl2_user_gap.id_user_gap as id_user_gap
			 FROM pl2_user_gap
			 JOIN utilisateurs
			 ON utilisateurs.id_utilisateur = pl2_user_gap.id_utilisateur
			 LEFT OUTER JOIN utl_etu_utbm
			 ON utilisateurs.id_utilisateur = utl_etu_utbm.id_utilisateur
			 WHERE id_gap = $gap_id
			 AND utilisateurs.id_utilisateur NOT IN
			 (	SELECT id_utilisateur FROM pl2_absence
				JOIN pl2_gap
				ON pl2_gap.id_planning = pl2_absence.id_planning
				WHERE (pl2_absence.start < '$start' AND pl2_absence.end > '$start')
				OR  (pl2_absence.start < '$end' AND pl2_absence.start > '$start')
			 )
			 AND pl2_user_gap.start <= '$start'
			 AND pl2_user_gap.end >= '$end'");
		
	}
	
}

?>
