<?php

define("PL_LUNDI",strtotime("2008-03-10 00:00:00"));

class planning extends stdentity
{

  var $id_asso;
  var $name;
  var $user_per_gap;
  var $start_date;
  var $end_date;
  var $weekly;

  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `pl_planning` ".
                                  "WHERE `id_planning` = '" . mysql_real_escape_string($id) . "' ".
                                  "LIMIT 1");

    if ( $req->lines != 1 )
    {
      $this->id = null;
      return false;
    }

    $this->_load($req->get_row());
    return true;
  }

  function _load ( $row )
  {
    $this->id           = $row['id_planning'];
    $this->id_asso      = $row['id_asso'];
    $this->name         = $row['name_planning'];
    $this->user_per_gap = $row['user_per_gap'];
    $this->start_date   = strtotime($row['start_date_planning']);
    $this->end_date     = strtotime($row['end_date_planning']);
    $this->weekly       = $row['weekly_planning'];
  }

  function add ( $id_asso, $name, $user_per_gap, $start_date, $end_date, $weekly )
  {
    $this->id_asso = $id_asso;
    $this->name = $name;
    $this->user_per_gap = $user_per_gap;
    $this->start_date = $start_date;
    $this->end_date = $end_date;
    $this->weekly = $weekly;

    $sql = new insert ($this->dbrw,
                       "pl_planning",
                       array(
                             "id_asso" => $this->id_asso,
                             "name_planning" => $this->name,
                             "user_per_gap" => $this->user_per_gap,
                             "start_date_planning" => date("Y-m-d H:i:s",$this->start_date),
                             "end_date_planning" => date("Y-m-d H:i:s",$this->end_date),
                             "weekly_planning" => $this->weekly
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

  function save ( $id_asso, $name, $user_per_gap,$start_date, $end_date, $weekly )
  {
    $this->id_asso      = $id_asso;
    $this->name         = $name;
    $this->user_per_gap = $user_per_gap;
    $this->start_date   = $start_date;
    $this->end_date     = $end_date;
    $this->weekly       = $weekly;

    $sql = new update ($this->dbrw,
                       "pl_planning",
                       array(
                             "id_asso" => $this->id_asso,
                             "name_planning" => $this->name,
                             "user_per_gap" => $this->user_per_gap,
                             "start_date_planning" => date("Y-m-d H:i:s",$this->start_date),
                             "end_date_planning" => date("Y-m-d H:i:s",$this->end_date),
                             "weekly_planning" => $this->weekly
                            ),
                       array("id_planning"=>$this->id)
                      );
  }

  function remove ( )
  {
    $sql = new delete ($this->dbrw,
                       "pl_planning",
                       array(
                             "id_planning" => $this->id
                            )
                      );

    $sql = new delete ($this->dbrw,
                       "pl_gap",
                       array(
                             "id_planning" => $this->id
                            )
                      );

    $sql = new delete ($this->dbrw,
                       "pl_gap_user",
                       array(
                             "id_planning" => $this->id
                            )
                      );
  }


  function add_gap ( $start, $end )
  {
    if ( $this->weekly )
    {
      $start += PL_LUNDI;
      $end += PL_LUNDI;
    }

    $sql = new insert ($this->dbrw,
                       "pl_gap",
                        array(
                              "id_planning" => $this->id,
                              "start_gap" => date("Y-m-d H:i:s",$start),
                              "end_gap" => date("Y-m-d H:i:s",$end)
                             )
                      );
    return $sql->get_id();
  }

  function remove_gap ( $id_gap )
  {
    $sql = new delete ($this->dbrw,
                       "pl_gap",
                       array(
                             "id_planning" => $this->id,
                             "id_gap" => $id_gap
                            )
                      );

    $sql = new delete ($this->dbrw,
                       "pl_gap_user",
                       array(
                             "id_planning" => $this->id,
                             "id_gap" => $id_gap
                            )
                      );
  }

  function add_user_to_gap ( $id_gap, $id_utilisateur )
  {
    $req = new requete ($this->db,
                        "SELECT COUNT(*) AS `nb` FROM `pl_gap_user` ".
                        "WHERE `id_gap`='".$id_gap."'");
    if($req->lines==1)
    {
      list($nb)=$req->get_row();
      if($nb==$this->user_per_gap)
        return false;
    }
    $sql = new insert ($this->dbrw,
                       "pl_gap_user",
                       array(
                             "id_planning" => $this->id,
                             "id_gap" => $id_gap,
                             "id_utilisateur" => $id_utilisateur
                            )
                          );
    if( !$sql->is_success() )
      return false;

    return true;
  }

  function remove_user_from_gap ( $id_gap, $id_utilisateur )
  {
    $sql = new delete ($this->dbrw,
                       "pl_gap_user",
                       array(
                             "id_planning" => $this->id,
                             "id_gap" => $id_gap,
                             "id_utilisateur" => $id_utilisateur
                            )
                      );
  }
}


?>
