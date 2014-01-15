<?php

/** @file

 */


/**
 * Classe gérant un compte association
 * @ingroup comptoirs
 */
class assocpt extends stdentity
{
  var $montant_ventes;
  var $montant_rechargements;

  function load_by_id ( $id )
  {

    $req = new requete($this->db,"SELECT * FROM cpt_association WHERE id_assocpt='".intval($id)."'");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function _load ( $row )
  {
    $this->id = $row['id_assocpt'];
    $this->montant_ventes = $row['montant_ventes_asso'];
    $this->montant_rechargements = $row['montant_rechargements_asso'];
  }

  function add ( $id )
  {
    $req = new insert($this->dbrw,"cpt_association",array("id_assocpt"=>$id));
  }


  function can_enumerate()
  {
    return true;
  }

  function enumerate ( $null=false, $conds = null )
  {
    $class = get_class($this);

    if ( $null )
      $values=array(null=>"(aucun)");
    else
      $values=array();

    $sql = "SELECT `id_assocpt`,`nom_asso` FROM asso INNER JOIN cpt_association ON asso.id_asso=cpt_association.id_assocpt";

    if ( !is_null($conds) && count($conds) > 0 )
    {
      $firststatement=true;

      foreach ($conds as $key => $value)
      {
        if( $firststatement )
        {
          $sql .= " WHERE ";
          $firststatement = false;
        }
        else
          $sql .= " AND ";

        if ( is_null($value) )
          $sql .= "(`" . $key . "` is NULL)";
        else
          $sql .= "(`" . $key . "`='" . mysql_escape_string($value) . "')";
      }
    }

    $sql .= " ORDER BY 2";

    $req = new requete($this->db,$sql);

    while ( $row = $req->get_row() )
      $values[$row[0]] = $row[1];

    return $values;
  }

}

?>
