<?php
/* Copyright 2007
 * - Julien Etelain <julien CHEZ pmad POINT net>
 *
 * Ce fichier fait partie du site de l'Association des 0tudiants de
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

require_once($topdir."include/entities/geopoint.inc.php");
require_once($topdir."include/horraire.inc.php");

/**
 * Un reseau de bus.
 * Un reseau peut se décomposer en sous-réseaux.
 * @see lignebus
 * @ingroup pg2
 * @author Julien Etelain
 */
class reseaubus extends stdentity
{
  var $nom;
  var $siteweb;
  var $id_reseaubus_parent;

  /**
   * Charge un élément par son id
   * @param $id Id de l'élément
   * @return false si non trouvé, true si chargé
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT *
        FROM `pg_reseaubus`
				WHERE `id_reseaubus` = '".mysql_real_escape_string($id)."'
				LIMIT 1");

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
    $this->id = $row['id_reseaubus'];
    $this->nom = $row['nom_reseaubus'];
    $this->siteweb = $row['siteweb_reseaubus'];
    $this->id_reseaubus_parent = $row['id_reseaubus_parent'];
  }

  function create ( $nom, $siteweb, $id_reseaubus_parent=null )
  {
    $this->nom = $nom;
    $this->siteweb = $siteweb;
    $this->id_reseaubus_parent = $id_reseaubus_parent;

    $req = new insert ( $this->dbrw, "pg_reseaubus",
      array(
      "nom_reseaubus" => $this->nom,
      "siteweb_reseaubus" => $this->siteweb,
      "id_reseaubus_parent" => $this->id_reseaubus_parent
      ) );

    if ( !$req->is_success() )
    {
      $this->id = null;
      return false;
    }

	  $this->id = $req->get_id();
    return true;
  }

  function update ( $nom, $siteweb, $id_reseaubus_parent=null )
  {
    $this->nom = $nom;
    $this->id_lignebus_parent = $id_lignebus_parent;

    new update ( $this->dbrw, "pg_reseaubus",
      array(
      "nom_reseaubus" => $this->nom,
      "siteweb_reseaubus" => $this->siteweb,
      "id_reseaubus_parent" => $this->id_reseaubus_parent
      ),
      array("id_reseaubus"=>$this->id) );
  }

  function delete ()
  {
    new delete($this->dbrw, "pg_reseaubus",array("id_reseaubus"=>$this->id));
    $this->id=null;
  }

  function prefer_list()
  {
    return true;
  }

}

/**
 * Une ligne de bus.
 *
 * Une ligne appartient à un réseau ou sous réseau.
 * Une ligne comporte une liste d'arrets ordonnées.
 * Une ligne peut être une "sous-ligne", dans ce cas, la ligne "principale"
 * contient uniquement les arrets communs aux sous-lignes, chaque sous ligne
 * contiennent tous les arrets.
 * @see arretbus
 * @ingroup pg2
 * @author Julien Etelain
 */
class lignebus extends stdentity
{
  var $nom;
  var $id_lignebus_parent;
  var $id_reseaubus;
  var $couleur;

  /**
   * Charge un élément par son id
   * @param $id Id de l'élément
   * @return false si non trouvé, true si chargé
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT *
        FROM `pg_lignebus`
				WHERE `id_lignebus` = '".mysql_real_escape_string($id)."'
				LIMIT 1");

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
    $this->id = $row['id_lignebus'];
    $this->nom = $row['nom_lignebus'];
    $this->id_lignebus_parent = $row['id_lignebus_parent'];
    $this->id_reseaubus = $row['id_reseaubus'];
    $this->couleur = $row['couleur_lignebus'];
  }

  function create ( $nom, $id_reseaubus, $couleur, $id_lignebus_parent=null )
  {
    $this->nom = $nom;
    $this->id_lignebus_parent = $id_lignebus_parent;
    $this->id_reseaubus = $id_reseaubus;
    $this->couleur = $couleur;

    $req = new insert ( $this->dbrw, "pg_lignebus",
      array(
      "nom_lignebus" => $this->nom,
      "id_lignebus_parent" => $this->id_lignebus_parent,
      "id_reseaubus" => $this->id_reseaubus,
      "couleur_lignebus" => $this->couleur
      ) );

    if ( !$req->is_success() )
    {
      $this->id = null;
      return false;
    }

	  $this->id = $req->get_id();
    return true;
  }

  function update ( $nom, $id_reseaubus, $couleur, $id_lignebus_parent=null )
  {
    $this->nom = $nom;
    $this->id_lignebus_parent = $id_lignebus_parent;
    $this->id_reseaubus = $id_reseaubus;
    $this->couleur = $couleur;

    new update ( $this->dbrw, "pg_lignebus",
      array(
      "nom_lignebus" => $this->nom,
      "id_lignebus_parent" => $this->id_lignebus_parent,
      "id_reseaubus" => $this->id_reseaubus,
      "couleur_lignebus" => $this->couleur
      ),
      array("id_lignebus"=>$this->id) );
  }

  function delete ()
  {
    new delete($this->dbrw, "pg_lignebus",array("id_lignebus"=>$this->id));
    new delete($this->dbrw, "pg_lignebus_arrets",array("id_lignebus"=>$this->id));
    $this->id=null;
  }

  function add_arret ( $id_arretbus, $num_passage )
  {
    new insert ( $this->dbrw, "pg_lignebus_arrets",
      array(
      "id_lignebus"=>$this->id,
      "id_arretbus"=>$id_arretbus,
      "num_passage_arret"=>$num_passage
      ));
  }

  function update_arret ( $id_arretbus, $num_passage )
  {
    new update ( $this->dbrw, "pg_lignebus_arrets",
      array(
      "num_passage_arret"=>$num_passage
      ),
      array(
      "id_lignebus"=>$this->id,
      "id_arretbus"=>$id_arretbus
      ));
  }

  function remove_arret ( $id_arretbus )
  {
    new delete ( $this->dbrw, "pg_lignebus_arrets",
      array(
      "id_lignebus"=>$this->id,
      "id_arretbus"=>$id_arretbus
      ));
  }

  function get_arret ( $id_arretbus )
  {
    $req = new requete($this->db,
      "SELECT num_passage_arret ".
      "FROM pg_lignebus_arrets ".
      "WHERE id_lignebus='".mysql_real_escape_string($this->id)."' ".
      "AND id_arretbus='".mysql_real_escape_string($id_arretbus)."'");

    if ( $req->lines != 1 )
      return null;

    list($num) = $req->get_row();
    return $num;
  }

  function get_path ( )
  {
    $path=array();
    $req = new requete($this->db,
      "SELECT lat_geopoint AS lat, long_geopoint AS long ".
      "FROM pg_lignebus_arrets ".
      "INNER JOIN geopoint ON (geopoint.id_geopoint=pg_lignebus_arrets.id_arretbus) ".
      "WHERE id_lignebus='".mysql_real_escape_string($this->id)."' ".
      "ORDER BY num_passage_arret");
    while ( $row = $req->get_row() )
      $path[]=$row;
    return $path;
  }

  function add_passage ( $jours, $datedebut, $datefin, $heures, $exceptionlevel=0 )
  {
    $id_arretbus_debut = null;
    $id_arretbus_fin = null;
    $sens = 1;

    asort($heures);
    list($id_arretbus_debut) = array_slice($heures,0,1);
    list($id_arretbus_fin) = array_slice($heures,-1,1);

    $num_debut = $this->get_arret($id_arretbus_debut);
    $num_fin = $this->get_arret($id_arretbus_fin);

    if ( is_null($num_fin) || is_null($num_fin) )
      $sens=0;
    else if ( $num_debut < $num_fin )
      $sens=1;
    else
      $sens=-1;

    $req = new insert ( $this->dbrw, "pg_lignebus_passage",
      array(
      "id_lignebus" => $this->id,
      "jours_passage" => $jours,
      "debut_passage" => date("Y-m-d H:i:s",$datedebut),
      "fin_passage" => date("Y-m-d H:i:s",$datefin),
      "id_arretbus_debut" => $id_arretbus_debut,
      "id_arretbus_fin" => $id_arretbus_fin,
      "sens_passage" => $sens,
      "exception_passage" => $exceptionlevel
      ) );

    if ( !$req->is_success() )
      return false;

	  $id_lignebus_passage = $req->get_id();

	  foreach ( $heures as $id_arretbus => $heure )
	  {
      new insert ( $this->dbrw, "pg_lignebus_passage_arretbus",
        array(
        "id_lignebus_passage" => $id_lignebus_passage,
        "id_arretbus" => $id_arretbus,
        "heure_passage" => date("H:i:s",$heure),
        ) );
 	  }

    return true;
  }

  function remove_passage ( $id_lignebus_passage )
  {
    new delete($this->dbrw, "pg_lignebus_passage",array("id_lignebus_passage" => $id_lignebus_passage));
    new delete($this->dbrw, "pg_lignebus_passage_arretbus", array("id_lignebus_passage" => $id_lignebus_passage));
  }

  function prefer_list()
  {
    return true;
  }
}

/**
 * Un arret de bus
 *
 * Il s'agit tout bêtement d'un point geographique.
 * @see geopoint
 * @ingroup pg2
 * @author Julien Etelain
 */
class arretbus extends geopoint
{
  /**
   * Charge un arret de bus.
   * @param $id Id de l'arret de bus
   * @return false si non trouvé, true si chargé
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT *
        FROM `geopoint`
				WHERE `id_geopoint` = '".mysql_real_escape_string($id)."'
				AND type_geopoint='arretbus'
				LIMIT 1");

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
    $this->geopoint_load($row);
  }

  /**
   * Creer un nouvel arret de bus
   * @param $id_ville Id de la ville dans le quel l'arret de bus se trouve (null si aucun)
   * @param $nom Nom de l'arret de bus
   * @param $lat Latitude
   * @param $long Longitude
   * @param $eloi Eloignement
   * @return true si crée, false sinon
   */
  function create ( $id_ville,  $nom, $lat, $long, $eloi )
  {
    return $this->geopoint_create ( $nom, $lat, $long, $eloi, $id_ville );
  }

  /**
   * Met à jour les informations relatives à l'arret de bus
   * @param $id_ville Id de la ville dans le quel l'arret de bus se trouve (null si aucun)
   * @param $nom Nom de l'arret de bus
   * @param $lat Latitude
   * @param $long Longitude
   * @param $eloi Eloignement
   */
  function update ( $id_ville, $nom, $lat, $long, $eloi )
  {
    $this->geopoint_update ( $nom, $lat, $long, $eloi, $id_ville );
  }

  /**
   * Supprime l'arret de bus
   */
  function delete ( )
  {
    new delete($this->dbrw, "pg_lignebus_arrets",array("id_arretbus"=>$this->id));
    $this->geopoint_delete();
  }

  function find_arret( $nom, $ville )
  {
    if ( empty($ville) )
      $req = new requete($this->db,"SELECT * FROM geopoint WHERE type_geopoint='arretbus' AND nom_geopoint='".mysql_real_escape_string($nom)."'");
    else
      $req = new requete($this->db,"SELECT * FROM geopoint INNER JOIN loc_ville ON (geopoint.id_ville=loc_ville.id_ville) WHERE type_geopoint='arretbus' AND nom_geopoint='".mysql_real_escape_string($nom)."' AND nom_ville='".mysql_real_escape_string($ville)."'");

    $rows = array();
    while ( $row = $req->get_row() )
      $rows[] = $row;

    return $rows;
  }

  function prefer_list()
  {
    return true;
  }


}

?>
