<?php
/**
 * @file
 */




/**
 * Classe gérant un type de produit
 * @see produit
 * @ingroup comptoirs
 */
class typeproduit extends stdentity
{

  /** Nom du type de produit */
  var $nom;
  /** Action par défaut lors de l'ajout */
  var $action;
  /** Association par défaut lors de l'ajout */
  var $id_assocpt;
  /** Id du fichier utilisé pour la vignette du type de produit */
  var $id_file;
  /** Description du type de produit */
  var $description;
  /** Css de la catégorie */
  var $css=null;


  function load_by_id ( $id )
  {

    $req = new requete($this->db,"SELECT * FROM `cpt_type_produit`
               WHERE `id_typeprod`=".intval($id)."");

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
    $this->id = $row["id_typeprod"];
    $this->nom = $row["nom_typeprod"];
    $this->action = $row["action_typeprod"];
    $this->id_assocpt = $row["id_assocpt"];
    $this->id_file = $row["id_file"];
    $this->description = $row["description_typeprod"];
    $this->css = $row['css'];
  }

  function ajout ( $nom, $action, $id_assocpt, $id_file, $description, $css="" )
  {
    $this->nom = $nom;
    $this->action = $action;
    $this->id_assocpt = $id_assocpt;

    $this->id_file = $id_file;
    $this->description = $description;
    $this->css = $css;

    $req = new insert ($this->dbrw,
           "cpt_type_produit",
           array("nom_typeprod" => $this->nom,
             "action_typeprod" => $this->action,
           "id_assocpt" => $this->id_assocpt,
           "id_file" => $this->id_file,
           "description_typeprod" => $this->description,
           "css" => $css
           ));

    if ( !$req )
        return false;

    $this->id = $req->get_id();

    return true;
  }

  function modifier ( $nom, $action, $id_assocpt, $id_file, $description, $css="" )
  {
    $this->nom = $nom;
    $this->action = $action;
    $this->id_assocpt = $id_assocpt;
    $this->id_file = $id_file;
    $this->description = $description;
    $this->css = $css;

    $sql = new update($this->dbrw,
          "cpt_type_produit",
          array("nom_typeprod" => $this->nom,
             "action_typeprod" => $this->action,
           "id_assocpt" => $this->id_assocpt,
           "id_file" => $this->id_file,
           "description_typeprod" => $this->description,
           "css" => $css
           ),
          array("id_typeprod" => $this->id));

    if ( !$sql )
      return false;

    return true;
  }

}

?>
