<?php

$GLOBALS['types_machines'] = array('laver' => "Machine à laver", 'secher' => "Seche linge");

/**
 * Class gérant un jeton
 */
class machine extends stdentity
{

  var $lettre;
  var $type;
  var $id_salle;
  var $hs;

  /** Charge un jeton en fonction de son id
   * $this->id est égal à -1 en cas d'erreur
   * @param $id id du jeton
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `mc_machines`
        WHERE `id` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = -1;
    return false;
  }

  function load_by_id_creneau ( $id, &$debut )
  {
    $req = new requete($this->db, "SELECT mc_machines.*, mc_creneaux.debut_creneau  FROM mc_creneaux
        INNER JOIN `mc_machines` ON (mc_machines.id=mc_creneaux.id_machine)
        WHERE `id_creneau` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $row = $req->get_row();
      $debut = strtotime($row['debut_creneau']);
      $this->_load($row);
      return true;
    }

    $this->id = -1;
    return false;
  }

  function _load ( $row )
  {
    $this->id = $row['id']; // devrai être id_machine
    $this->lettre = $row['lettre']; // devrai être lettre_machine
    $this->type = $row['type'];  // devrai être type_machine
    $this->id_salle = $row['loc']; // devrai être id_salle
    $this->hs = $row['hs']; // devrai être hs_machine
  }

  function create_machine ( $lettre, $type, $id_salle, $hs=false )
  {
    $this->lettre = $lettre;
    $this->type = $type;
    $this->id_salle = $id_salle;
    $this->hs = $hs;

    $req = new insert ( $this->dbrw, "mc_machines",
     array(
       "lettre"=>$this->lettre,
       "type"=>$this->type,
       "loc"=>$this->id_salle,
       "hs"=>$this->hs));

    if ( $req->is_success() )
    {
      $this->id = $req->get_id();
      return true;
    }

    $this->id = null;
    return false;
  }

  function update_machine ( $lettre, $type, $id_salle, $hs=false )
  {
    $this->lettre = $lettre;
    $this->type = $type;
    $this->id_salle = $id_salle;
    $this->hs = $hs;

    new update ( $this->dbrw, "mc_machines",
     array(
       "lettre"=>$this->lettre,
       "type"=>$this->type,
       "loc"=>$this->id_salle,
       "hs"=>$this->hs),
     array("id"=>$this->id));
  }

  function create_creaneau ( $debut, $fin )
  {
    $sql = new requete ( $this->db, "SELECT `id_creneau` FROM `mc_creneaux`
      WHERE `id_machine`='".$this->id."'
      AND `debut_creneau`='".date("Y-m-d H:i:s",$debut)."'
      AND `fin_creneau`='".date("Y-m-d H:i:s",$fin)."'");

    if ( $sql->lines < 1 )
    {
      new insert ( $this->dbrw, "mc_creneaux",
       array(
         "id_machine"=>$this->id,
         "debut_creneau"=>date("Y-m-d H:i:s",$debut),
         "fin_creneau"=>date("Y-m-d H:i:s",$fin)));
    }
  }

  function create_all_creneaux_between ( $start, $end, $step )
  {
    $current = $start;
    while ( $current < $end )
    {
      $this->create_creaneau($current,$current+$step);
      $current += $step;
    }
  }

  function remove_all_creneaux_between ( $start, $end )
  {
    new requete($this->dbrw,"DELETE FROM mc_creneaux
      WHERE id_machine='".mysql_real_escape_string($this->id)."'
      AND debut_creneau >= '".date("Y-m-d H:i:s",$start)."'
      AND debut_creneau < '".date("Y-m-d H:i:s",$end)."'");
  }

  function free_all_creneaux_between ( $start, $end )
  {
    new requete($this->dbrw,"UPDATE mc_creneaux SET id_utilisateur=NULL, id_jeton=NULL
      WHERE id_machine='".mysql_real_escape_string($this->id)."'
      AND debut_creneau >= '".date("Y-m-d H:i:s",$start)."'
      AND debut_creneau < '".date("Y-m-d H:i:s",$end)."'");
  }

  function take_creneau ( $id_creneau, $id_utilisateur, $force=false )
  {
    if ( $force )
    {
      new update ( $this->dbrw, "mc_creneaux",
        array("id_utilisateur"=>$id_utilisateur),
        array("id_machine"=>$this->id,"id_creneau"=>$id_creneau));
      return;
    }

    new update ( $this->dbrw, "mc_creneaux",
      array("id_utilisateur"=>$id_utilisateur),
      array("id_machine"=>$this->id,"id_creneau"=>$id_creneau,"id_utilisateur"=>null));
  }


  function free_creneau ( $id_creneau, $id_utilisateur )
  {
    new update ( $this->dbrw, "mc_creneaux",
      array("id_utilisateur"=>null),
      array("id_machine"=>$this->id,"id_creneau"=>$id_creneau,"id_utilisateur"=>$id_utilisateur, "id_jeton"=>null));
  }

  function affect_jeton_creneau ( $id_creneau, $id_utilisateur, $id_jeton )
  {
    new update ( $this->dbrw, "mc_creneaux",
      array("id_utilisateur"=>$id_utilisateur, "id_jeton"=>$id_jeton),
      array("id_machine"=>$this->id,"id_creneau"=>$id_creneau));
  }

  function set_hs ( $hs = true )
  {
    $this->hs = $hs;
    new update ( $this->dbrw, "mc_machines",
      array("hs"=>$this->hs),
      array("id"=>$this->id));
  }

  function delete ( )
  {
    new delete ( $this->dbrw, "mc_machines", array("id"=>$this->id));
    new delete ( $this->dbrw, "mc_creneaux", array("id_machine"=>$this->id));
    $this->id = null;
  }
}




?>
