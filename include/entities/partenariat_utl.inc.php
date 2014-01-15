<?php


class Partenariat extends stdentity
{
  /* ID du partenariat */
  var $id_partenariat;
  /* ID de l'utilisateur */
  var $id_utilisateur;
  /* Date d'ajout */
  var $date;

  function load_by_id($id)
  {
    $req = new requete($this->db, "SELECT * ".
            "FROM `partenariats_utl` ".
            "WHERE id_partenariat_utl = '".$id."' ");
    if($req->lines == 1)
      $this->_load($req->get_row());
  }

  function load_by_partenariat_utilisateur($id_partenariat, $id_utilisateur)
  {
    $req = new requete($this->db, "SELECT * ".
            "FROM `partenariats_utl` ".
            "WHERE id_utilisateur = '".$id_utilisateur."' ".
            "AND id_partenariat = '".$id_partenariat."'");
    if($req->lines == 1)
      $this->_load($req->get_row());
  }

  function _load($row)
  {
    $this->id = $row['id_partenariat_utl'];
    $this->id_partenariat = $row['id_partenariat'];
    $this->id_utilisateur = $row['id_utilisateur'];
    $this->date = $row['date_partenariat'];
  }

  function add($id_partenariat, $id_utilisateur)
  {
    $this->id_partenariat = $id_partenariat;
    $this->id_utilisateur = $id_utilisateur;
    $this->date = date('Y-m-d');


    $req = new insert($this->dbrw, "partenariats_utl",
              array('id_partenariat'=>$id_partenariat,
                'id_utilisateur'=>$id_utilisateur,
                'date_partenariat'=>$this->date,
              ));
    $this->id = $req->get_id();
  }

  function remove()
  {
    $req = new delete($this->dbrw, "partenariats_utl",
      array('id_partenariat_utl'=>$this->id)
      );
    $this->id = null;
    $this->id_partenariat = null;
    $this->id_utilisateur = null;
    $this->date = null;
  }
}


?>
