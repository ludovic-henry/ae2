<?php
/**
 * Gestion des jetons des machines à laver
 *
 * Objectif global : suivi des jetons, vérification de la cotisation, paiement par carte AE
 *
 *
 *
 *
 *
 */
$GLOBALS['types_jeton'] = array ('laver' => "Machine à laver", 'secher' => "Seche linge");
$GLOBALS['salles_jeton'] = array ('6' => "Maison des Élèves",'8'=>"Riverotte");

/**
 * Class gérant un jeton
 */
class jeton extends stdentity
{

  var $id_salle;
  var $type;
  var $nom;

  /** Charge un jeton en fonction de son id
   * $this->id est égal à -1 en cas d'erreur
   * @param $id id du jeton
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `mc_jeton`
        WHERE `id_jeton` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = -1;
    return false;
  }

  /** Charge un jeton en fonction de son nom
   * $this->id est égal à -1 en cas d'erreur
   * @param $id id du jeton
   */
  function load_by_nom ( $nom, $type )
  {
    $req = new requete($this->db, "SELECT * FROM `mc_jeton`
      WHERE `nom_jeton` = '" . mysql_real_escape_string($nom) . "'
      AND `type_jeton` = '" . mysql_real_escape_string($type) . "'
      LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = -1;
    return false;
  }

  /** Charge un jeton en fonction de son nom
   * $this->id est égal à -1 en cas d'erreur
   * @param $id id du jeton
   */
  function load_by_nom_and_salle ( $nom, $type, $id_salle )
  {
    $req = new requete($this->db, "SELECT * FROM `mc_jeton`
      WHERE `nom_jeton` = '" . mysql_real_escape_string($nom) . "'
      AND `type_jeton` = '" . mysql_real_escape_string($type) . "'
      AND `id_salle` = '" . mysql_real_escape_string($id_salle) . "'
      LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = -1;
    return false;
  }

  function _load ( $row )
  {
    $this->id      = $row['id_jeton'];
    $this->id_salle = $row['id_salle'];
    $this->type    = $row['type_jeton'];
    $this->nom     = $row['nom_jeton'];
  }

  /**
   * Ajoute un jeton
   * @param $id_salle Id du site au quel le jetton est rattaché
   * @param $type Type de jeton
   * @param $nom Nom du jeton (identifiant)
   */
  function add ( $id_salle, $type, $nom )
  {
    $this->id_salle = $id_salle;
    $this->type = $type;
    $this->nom = $nom;

    $sql = new insert ($this->dbrw,
      "mc_jeton",
      array(
        "id_salle" => $this->id_salle,
        "type_jeton" => $this->type,
        "nom_jeton" => $this->nom
        )
      );

    if ( $sql )
      $this->id = $sql->get_id();
    else
      $this->id = null;

  }

  /**
   * Modifie les informations sur le jeton
   * @param $id_salle Id du site au quel le jetton est rattaché
   * @param $type Type de jeton
   * @param $nom Nom du jeton (identifiant)
   */
  function save ( $id_salle, $type, $nom )
  {
    $this->id_salle = $id_salle;
    $this->type = $type;
    $this->nom = $nom;

    $sql = new update ($this->dbrw,
      "mc_jeton",
      array(
        "id_salle" => $this->id_salle,
        "type_jeton" => $this->type,
        "nom_jeton" => $this->nom
        ),
      array(
        "id_jeton" => $this->id
        )
      );
  }

  /**
   * Supprime un jeton
   * @return 1 si le jeton est umprunté, sinon retourne 0
   */

  function delete ( )
  {
    if ( $this->is_borrowed() == 0 )
    {
      $sql = new delete ($this->dbrw,
        "mc_jeton",
        array(
          "id_jeton" => $this->id
          )
      );
      return 0;
    }
    else
      return 1;

  }

  /**
   * Prête le jeton à un utilisateur.
   * @param $id_utilisateur Id de l'utilisateur
   */
  function borrow_to_user ( $id_utilisateur,$id_gap=NULL )
  {
    if ( $this->is_borrowed() != 0 )
      $this->given_back();

    $sql = new insert ($this->dbrw,
      "mc_jeton_utilisateur",
      array(
        "id_utilisateur" => $id_utilisateur,
        "id_jeton" => $this->id,
        "id_gap" => $id_gap,
        "prise_jeton" => date("Y-m-d H:i:s"),
        "retour_jeton" => NULL
        )
      );
  }

  /**
   * Marque que le jeton a été restitué.
   */
  function given_back ( )
  {
    $sql = new update ($this->dbrw,
      "mc_jeton_utilisateur",
      array(
        "retour_jeton" => date("Y-m-d H:i:s")
        ),
      array(
        "id_jeton" => $this->id,
        "retour_jeton" => NULL
        )
      );
  }

  /**
   * Determine si le jeton est emprunté
   * @return l'id de l'utilisateur si le jeton est emprunté, sinon 0
   */
  function is_borrowed ()
  {
    $sql = new requete($this->db,"SELECT id_utilisateur FROM mc_jeton_utilisateur WHERE id_jeton='".$this->id."' AND retour_jeton IS NULL");

    if ( $sql->lines == 0 )
      return 0;

    list($id_utilisateur) = $sql->get_row();

    return $id_utilisateur;
  }


}




?>
