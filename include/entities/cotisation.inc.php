<?php
/*
 * Created on 24 janv. 2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

require_once("carteae.inc.php");

/** Modes paiement cotisation :
 - 1 : cheque
 - 2 : carte bleue
 - 3 : liquide
 - 4 : administration
 - 5 : eboutic */

/** Type de cotisation :
 - 0 : 1 Semestre
 - 1 : 2 Semestres
 - 2 : Cursus Tronc Commun
 - 3 : Cursus Branch
 - 4 : Membre honoraire ou occasionnel
 - 5 : Cotisation par Assidu
 - 6 : Cotisation par l'Amicale
 - 7 : Cotisation réseau UT
 - 8 : Cotisation CROUS
 - 9 : Cotisation Sbarro
*/

class cotisation extends stdentity
{
  var $id_utilisateur;
  var $date_cotis;
  var $date_fin_cotis;
  var $a_pris_cadeau;
  var $a_pris_carte;
  var $mode_paiement_cotis;
  var $prix_paye_cotis;


  /** Charge une carte en fonction de son id
   * $this->id est égal à -1 en cas d'erreur
   * @param $id id de la fonction
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `ae_cotisations`
        WHERE `id_cotisation` = '" . mysql_real_escape_string($id) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  function load_lastest_by_user ( $id_utilisateur )
  {
    $req = new requete($this->db, "SELECT * FROM `ae_cotisations`
        WHERE `id_utilisateur` = '" . mysql_real_escape_string($id_utilisateur) . "'
        ORDER BY `date_fin_cotis` DESC LIMIT 1");

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
    $this->id          = $row['id_cotisation'];
    $this->id_utilisateur      = $row['id_utilisateur'];
    $this->date_cotis      = strtotime($row['date_cotis']);
    $this->date_fin_cotis      = strtotime($row['date_fin_cotis']);
    $this->a_pris_cadeau      = $row['a_pris_cadeau'];
    $this->a_pris_carte      = $row['a_pris_carte'];
    $this->mode_paiement_cotis  = $row['mode_paiement_cotis'];
    $this->prix_paye_cotis    = $row['prix_paye_cotis'];
    $this->type_cotis = $row['type_paye_cotis'];

  }


  function add ( $id_utilisateur, $date_fin, $mode_paiement, $prix_paye, $type_cotis )
  {

    $this->id_utilisateur = $id_utilisateur;
    $this->date_cotis = time();
    $this->date_fin_cotis = $date_fin;
    $this->a_pris_cadeau = (! in_array($type_cotis, array(0, 1, 2, 3)));
    $this->a_pris_carte = false;
    $this->mode_paiement_cotis = $mode_paiement;
    $this->prix_paye_cotis = $prix_paye;
    $this->type_cotis = $type_cotis;

    $sql = new insert ($this->dbrw,
      "ae_cotisations",
      array(
        "id_utilisateur" => $this->id_utilisateur,
        "date_cotis" => date("Y-m-d H:i:s",$this->date_cotis),
        "date_fin_cotis" => date("Y-m-d",$this->date_fin_cotis),
        "a_pris_cadeau" => $this->a_pris_cadeau,
        "a_pris_carte" => $this->a_pris_carte,
        "mode_paiement_cotis" => $this->mode_paiement_cotis,
        "prix_paye_cotis" => $this->prix_paye_cotis,
        "type_cotis" => $this->type_cotis,
        )
      );

    if ( $sql )
      $this->id = $sql->get_id();
    else
    {
      $this->id = null;
      return false;
    }

    $carte = new carteae($this->db,$this->dbrw);

    $carte->load_by_utilisateur($this->id_utilisateur);
    if ( $carte->id > 0 ) // On ré-utilise l'ancienne carte, s'il y a en une utilisable
      $carte->prolongate($this->id,$this->date_fin_cotis);
    else
      $carte->add($this->id,$this->date_fin_cotis);

    if ($type_cotis == 5)
      $type_cotis_txt = "assidu_utl";
    elseif($type_cotis == 6)
      $type_cotis_txt = "amicale_utl";
    elseif($type_cotis == 8)
      $type_cotis_txt = "crous_utl";
    else
      $type_cotis_txt = "ae_utl";

    $req = new update($this->dbrw,"utilisateurs",array($type_cotis_txt=>true),array("id_utilisateur"=>$this->id_utilisateur));

    return true;
  }

  function mark_cadeau($cadeau=true)
  {
    $this->a_pris_cadeau = $cadeau;

    $sql = new update ($this->dbrw,
      "ae_cotisations",
      array(
        "a_pris_cadeau" => $this->a_pris_cadeau
        ),
      array(
        "id_cotisation"=>$this->id
        )
      );
  }

  function mark_carte($carte=true)
  {
    $this->a_pris_carte = $carte;

    $sql = new update ($this->dbrw,
      "ae_cotisations",
      array(
        "a_pris_carte" => $this->a_pris_carte
        ),
      array(
        "id_cotisation"=>$this->id
        )
      );
  }

  function generate_card()
  {
    $carte = new carteae($this->db,$this->dbrw);

    $carte->load_by_utilisateur($this->id_utilisateur);
    if ( $carte->id > 0 ) // On ré-utilise l'ancienne carte, s'il y a en une utilisable
      $carte->prolongate($this->id,$this->date_fin_cotis);
    else
      $carte->add($this->id,$this->date_fin_cotis);
  }

}


?>
