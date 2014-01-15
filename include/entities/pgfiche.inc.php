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

/**
 * @defgroup pg2 Petit Géni 2.0
 *
 */


require_once($topdir."include/entities/geopoint.inc.php");
require_once($topdir."include/cts/special.inc.php");
require_once($topdir."include/horraire.inc.php");

/**
 * Catégorie du petit géni
 * @ingroup pg2
 * @author Julien Etelain
 */
class pgcategory extends stdentity
{
  var $id_pgcategory_parent;
  var $nom;
  var $description;
  var $ordre;

  var $couleur_bordure_web;
  var $couleur_titre_web;
  var $couleur_contraste_web;

  var $couleur_bordure_print;
  var $couleur_titre_print;
  var $couleur_contraste_print;



  /**
   * Charge un élément par son id
   * @param $id Id de l'élément
   * @return false si non trouvé, true si chargé
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT *
        FROM `pg_category`
				WHERE `id_pgcategory` = '".mysql_real_escape_string($id)."'
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
    $this->id = $row['id_pgcategory'];

    $this->id_pgcategory_parent = $row['id_pgcategory_parent'];
    $this->nom = $row['nom_pgcategory'];
    $this->description = $row['description_pgcategory'];
    $this->ordre = $row['ordre_pgcategory'];

    $this->couleur_bordure_web = $row['couleur_bordure_web_pgcategory'];
    $this->couleur_titre_web = $row['couleur_titre_web_pgcategory'];
    $this->couleur_contraste_web = $row['couleur_contraste_web_pgcategory'];

    $this->couleur_bordure_print = $row['couleur_bordure_print_pgcategory'];
    $this->couleur_titre_print = $row['couleur_titre_print_pgcategory'];
    $this->couleur_contraste_print = $row['couleur_contraste_print_pgcategory'];
  }

  function create ( $id_pgcategory_parent, $nom, $description, $ordre, $couleur_bordure_web, $couleur_titre_web,$couleur_contraste_web, $couleur_bordure_print, $couleur_titre_print, $couleur_contraste_print )
  {
    $this->id_pgcategory_parent = $id_pgcategory_parent;
    $this->nom = $nom;
    $this->description = $description;
    $this->ordre = $ordre;

    $this->couleur_bordure_web = $couleur_bordure_web;
    $this->couleur_titre_web = $couleur_titre_web;
    $this->couleur_contraste_web = $couleur_contraste_web;

    $this->couleur_bordure_print = $couleur_bordure_print;
    $this->couleur_titre_print = $couleur_titre_print;
    $this->couleur_contraste_print = $couleur_contraste_print;

    $req = new insert ( $this->dbrw, "pg_category",
      array(
      "id_pgcategory_parent" => $this->id_pgcategory_parent,
      "nom_pgcategory" => $this->nom,
      "description_pgcategory" => $this->description,
      "ordre_pgcategory" => $this->ordre,

      "couleur_bordure_web_pgcategory" => $this->couleur_bordure_web,
      "couleur_titre_web_pgcategory" => $this->couleur_titre_web,
      "couleur_contraste_web_pgcategory" => $this->couleur_contraste_web,

      "couleur_bordure_print_pgcategory" => $this->couleur_bordure_print,
      "couleur_titre_print_pgcategory" => $this->couleur_titre_print,
      "couleur_contraste_print_pgcategory" => $this->couleur_contraste_print
      ) );

    if ( !$req->is_success() )
    {
      $this->id = null;
      return false;
    }

	  $this->id = $req->get_id();
    return true;
  }

  function update ( $id_pgcategory_parent, $nom, $description, $ordre, $couleur_bordure_web, $couleur_titre_web,$couleur_contraste_web, $couleur_bordure_print, $couleur_titre_print, $couleur_contraste_print )
  {
    $this->id_pgcategory_parent = $id_pgcategory_parent;
    $this->nom = $nom;
    $this->description = $description;
    $this->ordre = $ordre;

    $this->couleur_bordure_web = $couleur_bordure_web;
    $this->couleur_titre_web = $couleur_titre_web;
    $this->couleur_contraste_web = $couleur_contraste_web;

    $this->couleur_bordure_print = $couleur_bordure_print;
    $this->couleur_titre_print = $couleur_titre_print;
    $this->couleur_contraste_print = $couleur_contraste_print;

    new update ( $this->dbrw, "pg_category",
      array(
      "id_pgcategory_parent" => $this->id_pgcategory_parent,
      "nom_pgcategory" => $this->nom,
      "description_pgcategory" => $this->description,
      "ordre_pgcategory" => $this->ordre,

      "couleur_bordure_web_pgcategory" => $this->couleur_bordure_web,
      "couleur_titre_web_pgcategory" => $this->couleur_titre_web,
      "couleur_contraste_web_pgcategory" => $this->couleur_contraste_web,

      "couleur_bordure_print_pgcategory" => $this->couleur_bordure_print,
      "couleur_titre_print_pgcategory" => $this->couleur_titre_print,
      "couleur_contraste_print_pgcategory" => $this->couleur_contraste_print
      ),
      array("id_pgcategory"=>$this->id) );
  }

  function delete ()
  {
    $req = new requete($pgcategory->db,
      "SELECT * ".
      "FROM `pg_fiche` ".
      "INNER JOIN `geopoint` ON (pg_fiche.id_pgfiche=geopoint.id_geopoint) ".
      "WHERE id_pgcategory='".$this->id."'");

    $fiche = new pgfiche($this->db,$this->dbrw);
    while ( $row = $req->get_row() )
    {
      $fiche->_load($row);
      $fiche->delete();
    }

    $req = new requete($pgcategory->db,
      "SELECT * ".
      "FROM `pg_category` ".
      "WHERE id_pgcategory_parent='".$this->id."'");

    $category = new pgcategory($this->db,$this->dbrw);
    while ( $row = $req->get_row() )
    {
      $category->_load($row);
      $category->delete();
    }

    new delete ( $this->dbrw, "pg_category", array("id_pgcategory"=>$this->id) );
    new delete ( $this->dbrw, "pg_category_tags", array("id_pgcategory"=>$this->id) );
    new delete ( $this->dbrw, "pg_fiche_extra_pgcategory", array("id_pgcategory"=>$this->id) );
  }

  function get_html_link()
  {
    global $topdir,$wwwtopdir;

    require_once($topdir."include/cts/pg.inc.php");

    return "<a href=\"".$wwwtopdir."pg2/?id_pgcategory=".$this->id."\"><img src=\"".pgicon($this->couleur_bordure_web)."\" class=\"icon\" alt=\"\" /> ". htmlentities($this->get_display_name(),ENT_COMPAT,"UTF-8")."</a>";
  }
  function prefer_list()
  {
    return true;
  }


}


define("HR_OUVERTURE",      0);
define("HR_EXCP_OUVERTURE", 1);
define("HR_EXCP_FERMETURE", 2);

/**
 * Fiche du petit géni
 * @ingroup pg2
 * @author Julien Etelain
 */
class pgfiche extends geopoint
{
  var $id_pgcategory;
  var $id_rue;
  var $id_entreprise;

  var $description;
  var $longuedescription;
  var $tel;
  var $fax;
  var $email;
  var $website;
  var $numrue;
  var $adressepostal;

  var $placesurcarte;
  var $contraste;
  var $appreciation;
  var $commentaire;

  var $date_maj;
  var $date_validite;
  var $id_utilisateur_maj;

  var $archive;
  /**
   * Charge un élément par son id
   * @param $id Id de l'élément
   * @return false si non trouvé, true si chargé
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT *
        FROM `pg_fiche`
        INNER JOIN `geopoint` ON (pg_fiche.id_pgfiche=geopoint.id_geopoint)
				WHERE `id_pgfiche` = '".mysql_real_escape_string($id)."'
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
    $this->id = $row['id_pgfiche'];

    $this->geopoint_load($row);

    $this->id_pgcategory = $row['id_pgcategory'];
    $this->id_rue = $row['id_rue'];
    $this->id_entreprise = $row['id_entreprise'];

    $this->description = $row['description_pgfiche'];
    $this->longuedescription = $row['longuedescription_pgfiche'];
    $this->tel = $row['tel_pgfiche'];
    $this->fax = $row['fax_pgfiche'];
    $this->email = $row['email_pgfiche'];
    $this->website = $row['website_pgfiche'];
    $this->numrue = $row['numrue_pgfiche'];
    $this->adressepostal = $row['adressepostal_pgfiche'];

    $this->placesurcarte = $row['placesurcarte_pgfiche'];
    $this->contraste = $row['contraste_pgfiche'];
    $this->appreciation = $row['appreciation_pgfiche'];
    $this->commentaire = $row['commentaire_pgfiche'];
    $this->infointerne = $row['infointerne_pgfiche'];
    $this->date_maj = is_null($row['date_maj_pgfiche'])?null:strtotime($row['date_maj_pgfiche']);
    $this->date_validite = is_null($row['date_validite_pgfiche'])?null:strtotime($row['date_validite_pgfiche']);
    $this->id_utilisateur_maj = $row['id_utilisateur_maj'];
    $this->archive = $row['archive_pgfiche'];
  }

  function create ( $id_ville, $nom, $lat, $long, $eloi, $id_pgcategory, $id_rue, $id_entreprise, $description, $longuedescription, $tel, $fax, $email, $website, $numrue, $adressepostal, $placesurcarte=null, $contraste=null, $appreciation=null, $commentaire=null, $infointerne=null, $date_maj=null, $date_validite=null, $id_utilisateur_maj=null )
  {
    if ( !$this->geopoint_create ( $nom, $lat, $long, $eloi, $id_ville ) )
      return false;

    $this->id_pgcategory = $id_pgcategory;
    $this->id_rue = $id_rue;
    $this->id_entreprise = $id_entreprise;

    $this->description = $description;
    $this->longuedescription = $longuedescription;
    $this->tel = telephone_userinput($tel);
    $this->fax = telephone_userinput($fax);
    $this->email = $email;
    $this->website = $website;
    $this->numrue = $numrue;
    $this->adressepostal = $adressepostal;

    $this->placesurcarte = $placesurcarte;
    $this->contraste = $contraste;
    $this->appreciation = $appreciation;
    $this->commentaire = $commentaire;
    $this->infointerne = $infointerne;

    $this->date_maj = $date_maj;
    $this->date_validite = $date_validite;
    $this->id_utilisateur_maj = $id_utilisateur_maj;

    $this->archive=false;

    $req = new insert($this->dbrw,"pg_fiche", array(
      'id_pgfiche' => $this->id,

      'id_pgcategory' => $this->id_pgcategory,
      'id_rue' => $this->id_rue,
      'id_entreprise' => $this->id_entreprise,

      'description_pgfiche' => $this->description,
      'longuedescription_pgfiche' => $this->longuedescription,
      'tel_pgfiche' => $this->tel,
      'fax_pgfiche' => $this->fax,
      'email_pgfiche' => $this->email,
      'website_pgfiche' => $this->website,
      'numrue_pgfiche' => $this->numrue,
      'adressepostal_pgfiche' => $this->adressepostal,

      'placesurcarte_pgfiche' => $this->placesurcarte,
      'contraste_pgfiche' => $this->contraste,
      'appreciation_pgfiche' => $this->appreciation,
      'commentaire_pgfiche' => $this->commentaire,
      'infointerne_pgfiche' => $this->infointerne,
      'date_maj_pgfiche' => is_null($this->date_maj)?null:date("Y-m-d H:i:s",$this->date_maj),
      'date_validite_pgfiche' => is_null($this->date_validite)?null:date("Y-m-d H:i:s",$this->date_validite),
      'id_utilisateur_maj' => $this->id_utilisateur_maj,

      'archive_pgfiche' => $this->archive
      ));

    if ( !$req->is_success() )
    {
      $this->geopoint_delete();
      return false;
    }
    return true;
  }

  function update ( $id_ville, $nom, $lat, $long, $eloi, $id_pgcategory, $id_rue, $id_entreprise, $description, $longuedescription, $tel, $fax, $email, $website, $numrue, $adressepostal, $placesurcarte=null, $contraste=null, $appreciation=null, $commentaire=null, $infointerne=null, $date_maj=null, $date_validite=null, $id_utilisateur_maj=null )
  {
    $this->geopoint_update ( $nom, $lat, $long, $eloi, $id_ville );

    $this->id_pgcategory = $id_pgcategory;
    $this->id_rue = $id_rue;
    $this->id_entreprise = $id_entreprise;

    $this->description = $description;
    $this->longuedescription = $longuedescription;
    $this->tel = telephone_userinput($tel);
    $this->fax = telephone_userinput($fax);
    $this->email = $email;
    $this->website = $website;
    $this->numrue = $numrue;
    $this->adressepostal = $adressepostal;

    $this->placesurcarte = $placesurcarte;
    $this->contraste = $contraste;
    $this->appreciation = $appreciation;
    $this->commentaire = $commentaire;
    $this->infointerne = $infointerne;

    $this->date_maj = $date_maj;
    $this->date_validite = $date_validite;
    $this->id_utilisateur_maj = $id_utilisateur_maj;

    new update($this->dbrw,"pg_fiche", array(
      'id_pgcategory' => $this->id_pgcategory,
      'id_rue' => $this->id_rue,
      'id_entreprise' => $this->id_entreprise,
      'description_pgfiche' => $this->description,
      'longuedescription_pgfiche' => $this->longuedescription,
      'tel_pgfiche' => $this->tel,
      'fax_pgfiche' => $this->fax,
      'email_pgfiche' => $this->email,
      'website_pgfiche' => $this->website,
      'numrue_pgfiche' => $this->numrue,
      'adressepostal_pgfiche' => $this->adressepostal,
      'placesurcarte_pgfiche' => $this->placesurcarte,
      'contraste_pgfiche' => $this->contraste,
      'appreciation_pgfiche' => $this->appreciation,
      'commentaire_pgfiche' => $this->commentaire,
      'infointerne_pgfiche' => $this->infointerne,
      'date_maj_pgfiche' => is_null($this->date_maj)?null:date("Y-m-d H:i:s",$this->date_maj),
      'date_validite_pgfiche' => is_null($this->date_validite)?null:date("Y-m-d H:i:s",$this->date_validite),
      'id_utilisateur_maj' => $this->id_utilisateur_maj),
      array('id_pgfiche' => $this->id));
  }


  function add_arretbus ( $id_arretbus )
  {
    new insert($this->dbrw, "pg_fiche_arretbus", array("id_pgfiche"=>$this->id,"id_arretbus"=>$id_arretbus));
  }

  function delete_arretbus ( $id_arretbus )
  {
    new delete($this->dbrw, "pg_fiche_arretbus", array("id_pgfiche"=>$this->id,"id_arretbus"=>$id_arretbus));
  }

  function add_extra_pgcategory ( $id_pgcategory, $titre=null, $soustitre=null )
  {
    new insert($this->dbrw, "pg_fiche_extra_pgcategory", array(
      "id_pgfiche"=>$this->id,
      "id_pgcategory"=>$id_pgcategory,
      "titre_extra_pgcategory"=>$titre,
      "soustire_extra_pgcategory"=>$soustire
      ));
  }

  function delete_extra_pgcategory ( $id_pgcategory )
  {
    new delete($this->dbrw, "pg_fiche_extra_pgcategory", array("id_pgfiche"=>$this->id,"id_pgcategory"=>$id_pgcategory));
  }


  function add_tarif ( $id_typetarif, $min_tarif, $max_tarif, $commentaire, $date_maj=null, $date_validite=null )
  {
    $this->delete_tarif($id_typetarif);

    $req = new requete($this->db,"SELECT pg_fiche_tarif.min_tarif,  pg_fiche_tarif.max_tarif, pg_fiche_tarif.id_typetarif FROM pg_fiche_tarif INNER JOIN pg_typetarif ON (pg_fiche_tarif.id_typetarif=pg_typetarif.id_typetarif_parent) WHERE pg_typetarif.id_typetarif='".".mysql_real_escape_string($id)."."'");

    if ( $req->lines == 1 )
    {
      list($min,$max,$id)  = $req->get_row();

      if ( $min_tarif < $min )
        $min = $min_tarif;

      if ( $max_tarif > $max )
        $max = $max_tarif;

      new update($this->dbrw, "pg_fiche_tarif", array(
        "id_pgfiche"=>$this->id,
        "id_typetarif"=>$id,
        "min_tarif"=>$min,
        "max_tarif"=>$max));
    }


    new insert($this->dbrw, "pg_fiche_tarif", array(
      "id_pgfiche"=>$this->id,
      "id_typetarif"=>$id_typetarif,
      "min_tarif"=>$min_tarif,
      "max_tarif"=>$max_tarif,
      "commentaire_tarif"=>$commentaire,
      "date_maj_tarif"=>is_null($date_maj)?null:date("Y-m-d H:i:s",$date_maj),
      "date_validite_tarif"=>is_null($date_validite)?null:date("Y-m-d H:i:s",$date_validite)));
  }

  function delete_tarif ( $id_typetarif )
  {
    new delete($this->dbrw, "pg_fiche_tarif", array("id_pgfiche"=>$this->id,"id_typetarif"=>$id_typetarif));
  }

  function add_reduction ( $id_typereduction, $valeur, $unite, $commentaire, $date_maj=null, $date_validite=null )
  {
    $this->delete_reduction($id_typereduction);

    new insert($this->dbrw, "pg_fiche_reduction", array(
      "id_pgfiche"=>$this->id,
      "id_typereduction"=>$id_typereduction,
      "valeur_reduction"=>$valeur,
      "unite_reduction"=>$unite,
      "commentaire_reduction"=>$commentaire,
      "date_maj_reduction"=>is_null($date_maj)?null:date("Y-m-d H:i:s",$date_maj),
      "date_validite_reduction"=>is_null($date_validite)?null:date("Y-m-d H:i:s",$date_validite)));
  }

  function delete_reduction ( $id_typereduction )
  {
    new delete($this->dbrw, "pg_fiche_reduction", array("id_pgfiche"=>$this->id,"id_typereduction"=>$id_typereduction));
  }


  function add_service ( $id_service, $commentaire, $date_maj=null, $date_validite=null )
  {
    $this->delete_service($id_service);

    new insert($this->dbrw, "pg_fiche_service", array(
      "id_pgfiche"=>$this->id,
      "id_service"=>$id_service,
      "commentaire_service"=>$commentaire,
      "date_maj_service"=>is_null($date_maj)?null:date("Y-m-d H:i:s",$date_maj),
      "date_validite_service"=>is_null($date_validite)?null:date("Y-m-d H:i:s",$date_validite)));
  }

  function delete_service ( $id_service )
  {
    new delete($this->dbrw, "pg_fiche_service", array("id_pgfiche"=>$this->id,"id_service"=>$id_service));
  }

  function add_horraire ( $datevaldebut, $datevalfin, $type, $jours, $heuredebut, $heurefin )
  {
    $ouvert = 1;
    if ( $type == HR_EXCP_FERMETURE )
      $ouvert = -1;

    new insert($this->dbrw, "pg_fiche_horraire", array(
      "id_pgfiche"=>$this->id,
      "datevaldebut_horraire"=>date("Y-m-d H:i:s",$datevaldebut),
      "datevalfin_horraire"=>is_null($datevalfin)?null:date("Y-m-d H:i:s",$datevalfin),
      "type_horraire"=>$type,
      "jours_horraire"=>$jours,
      "ouvert_horraire"=>$ouvert,
      "heuredebut_horraire"=>date("H:i:s",$heuredebut),
      "heurefin_horraire"=>date("H:i:s",$heurefin)));
  }

  function set_horraires (  $datevaldebut, $datevalfin, $horraires )
  {
    new delete($this->dbrw, "pg_fiche_horraire", array("id_pgfiche"=>$this->id,"type_horraire"=> HR_OUVERTURE));

    foreach ( $horraires as $jours => $heures )
    {
      foreach ( $heures as $heure )
      {
        $this->add_horraire( $datevaldebut, $datevalfin, HR_OUVERTURE, $jours, $heure[0], $heure[1]);
      }
    }
  }


  /**
   * Integre une mise à jour à la fiche.
   * @param $pgfichemaj instance de pgfichemaj
   * @see pgfichemaj
   */
  function integrate_update ( &$pgfichemaj )
  {
    $this->id_pgcategory = $pgfichemaj->id_pgcategory;
    $this->id_rue = $pgfichemaj->id_rue;
    $this->id_entreprise = $pgfichemaj->id_entreprise;

    $this->description = $pgfichemaj->description;
    $this->longuedescription =  $pgfichemaj->longuedescription;
    $this->tel = $pgfichemaj->tel;
    $this->fax = $pgfichemaj->fax;
    $this->email = $pgfichemaj->email;
    $this->website = $pgfichemaj->website;
    $this->numrue = $pgfichemaj->numrue;
    $this->adressepostal = $pgfichemaj->adressepostal;

    $this->date_maj = $pgfichemaj->date_soumission;
    $this->date_validite = $pgfichemaj->date_validite;
    $this->id_utilisateur_maj = $pgfichemaj->id_utilisateur;

    new update($this->dbrw, "pg_fiche", array(
      'id_pgcategory' => $this->id_pgcategory,
      'id_rue' => $this->id_rue,
      'id_entreprise' => $this->id_entreprise,
      'description_pgfiche' => $this->description,
      'longuedescription_pgfiche' => $this->longuedescription,
      'tel_pgfiche' => $this->tel,
      'fax_pgfiche' => $this->fax,
      'email_pgfiche' => $this->email,
      'website_pgfiche' => $this->website,
      'numrue_pgfiche' => $this->numrue,
      'adressepostal_pgfiche' => $this->adressepostal,
      'date_maj_pgfiche' => is_null($this->date_maj)?null:date("Y-m-d H:i:s",$this->date_maj),
      'date_validite_pgfiche' => is_null($this->date_validite)?null:date("Y-m-d H:i:s",$this->date_validite),
      'id_utilisateur_maj' => $this->id_utilisateur_maj),
      array('id_pgfiche' => $this->id));

    $this->geopoint_update ( $pgfichemaj->nom, $pgfichemaj->lat, $pgfichemaj->long, $pgfichemaj->eloi, $pgfichemaj->id_ville );

    $this->set_tags($pgfichemaj->tags);

    new delete($this->dbrw,"pg_fiche_arretbus",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche_extra_pgcategory",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche_tarif",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche_service",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche_reduction",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche_horraire",array("id_pgfiche"=>$this->id));

    foreach ( $pgfichemaj->extra_pgcategory as $row )
      $this->add_extra_pgcategory($row[0], $row[1], $row[2]);

    foreach ( $pgfichemaj->arretsbus as $id )
      $this->add_arretbus($id);

    foreach ( $pgfichemaj->tarifs as $row )
      $this->add_tarif ( $row[0], $row[1], $row[2], $row[3], $this->date_maj,is_null($row[4])?$this->date_validite:$row[4] );

    foreach ( $pgfichemaj->reductions as $row )
      $this->add_reduction ( $row[0], $row[1], $row[2], $row[3], $this->date_maj,is_null($row[4])?$this->date_validite:$row[4] );

    foreach ( $pgfichemaj->services as $row )
      $this->add_service ( $row[0], $row[1], $this->date_maj,is_null($row[4])?$this->date_validite:$row[4] );

    foreach ( $pgfichemaj->horraires as $row )
      $this->add_horraire ( $row[0], $row[1], $row[2], $row[3], $row[4], $row[5] );

  }

  function delete ()
  {
    new delete($this->dbrw,"pg_fiche_arretbus",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche_extra_pgcategory",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche_tarif",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche_service",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche_reduction",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche_horraire",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche_tags",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fiche",array("id_pgfiche"=>$this->id));
    new delete($this->dbrw,"pg_fichemaj",array("id_pgfiche"=>$this->id));
    $this->geopoint_delete();
  }

  function replace_by ( &$fiche )
  {
    if ( $this->id_pgcategory != $fiche->id_pgcategory )
      $fiche->add_extra_pgcategory($this->id_pgcategory, $this->nom, $this->description);
    $this->delete();
  }

}

/**
 * Demande de mise à jour d'une fiche
 * @ingroup pg2
 * @author Julien Etelain
 */
class pgfichemaj extends pgfiche
{
  var $id_pgfiche;
  var $arretsbus; // (array/données sérialisés)
  var $tarifs; // (array/données sérialisés)
  var $reductions; // (array/données sérialisés)
  var $services; // (array/données sérialisés)
  var $extra_pgcategory; // (array/données sérialisés)
  var $horraires; // (array/données sérialisés)
  var $tags; // (données "brut")

  var $date_soumission;
  var $id_utilisateur;
  var $commentaire;

  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT *
        FROM `pg_fichemaj`
				WHERE `id_pgfichemaj` = '".mysql_real_escape_string($id)."'
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
    $this->id = $row["id_pgfichemaj"];
    $this->id_pgfiche = $row["id_pgfiche"];

    $this->nom = $row["nom_pgfichemaj"];
    $this->id_ville = $row["id_ville"];
    $this->lat = $row["lat_pgfichemaj"];
    $this->long = $row["long_pgfichemaj"];
    $this->eloi = $row["eloi_pgfichemaj"];

    $this->id_pgcategory = $row["id_pgcategory"];
    $this->id_rue = $row["id_rue"];
    $this->id_entreprise = $row["id_entreprise"];

    $this->description = $row["description_pgfichemaj"];
    $this->longuedescription = $row["longuedescription_pgfichemaj"];
    $this->tel = $row["tel_pgfichemaj"];
    $this->fax = $row["fax_pgfichemaj"];
    $this->email = $row["email_pgfichemaj"];
    $this->website = $row["website_pgfichemaj"];
    $this->numrue = $row["numrue_pgfichemaj"];
    $this->adressepostal = $row["adressepostal_pgfichemaj"];

    $this->date_validite = is_null($row["date_validite_pgfichemaj"])?null:strtotime($row["date_validite_pgfichemaj"]);

    $this->arretsbus = unserialize($row["arretsbus_pgfichemaj"]); // (array/données sérialisés)
    $this->tarifs = unserialize($row["tarifs_pgfichemaj"]); // (array/données sérialisés)
    $this->reductions = unserialize($row["reductions_pgfichemaj"]); // (array/données sérialisés)
    $this->services = unserialize($row["services_pgfichemaj"]); // (array/données sérialisés)
    $this->extra_pgcategory = unserialize($row["extra_pgcategory_pgfichemaj"]); // (array/données sérialisés)
    $this->horraires = unserialize($row["horraires_pgfichemaj"]); // (array/données sérialisés)
    $this->tags = $row["tags_pgfichemaj"];

    $this->date_soumission = strtotime($row["date_soumission_pgfichemaj"]);
    $this->id_utilisateur = $row["id_utilisateur"];
    $this->commentaire = $row["commentaire_pgfichemaj"];

    $this->placesurcarte = null;
    $this->contraste = null;
    $this->appreciation = null;
    $this->infointerne = null;

  }

  function create ( $id_ville, $nom, $lat, $long, $eloi, $id_pgcategory, $id_rue, $id_entreprise, $description, $longuedescription, $tel, $fax, $email, $website, $numrue, $adressepostal, $placesurcarte=null, $contraste=null, $appreciation=null, $commentaire=null, $infointerne=null, $date_maj=null, $date_validite=null, $id_utilisateur_maj=null )
  {

    $this->id_ville = $id_ville;
    $this->nom = $nom;
    $this->lat = $lat;
    $this->long = $long;
    $this->eloi = $eloi;

    $this->id_pgcategory = $id_pgcategory;
    $this->id_rue = $id_rue;
    $this->id_entreprise = $id_entreprise;

    $this->description = $description;
    $this->longuedescription = $longuedescription;
    $this->tel = telephone_userinput($tel);
    $this->fax = telephone_userinput($fax);
    $this->email = $email;
    $this->website = $website;
    $this->numrue = $numrue;
    $this->adressepostal = $adressepostal;
    $this->date_validite = $date_validite;
    return true;
  }

  function create_fichemaj ( $id_pgfiche, $id_utilisateur, $commentaire, $date_validite )
  {
    $this->id_pgfiche = $id_pgfiche;
    $this->date_soumission = time();
    $this->id_utilisateur = $id_utilisateur;
    $this->commentaire = $commentaire;

    $this->placesurcarte = null;
    $this->contraste = null;
    $this->appreciation = null;
    $this->infointerne = null;

    $this->date_maj = $this->date_soumission;
    $this->date_validite = $date_validite;
    $this->id_utilisateur_maj = $id_utilisateur;

    $req = new requete($this->dbrw, "pg_fichemaj", array(
      "id_pgfiche" => $this->id_pgfiche,
      "nom_pgfichemaj" => $this->nom,
      "id_ville" => $this->id_ville,
      "lat_pgfichemaj" => $this->lat,
      "long_pgfichemaj" => $this->long,
      "eloi_pgfichemaj" => $this->eloi,
      "id_pgcategory" => $this->id_pgcategory,
      "id_rue" => $this->id_rue,
      "id_entreprise" => $this->id_entreprise,
      "description_pgfichemaj" => $this->description,
      "longuedescription_pgfichemaj" => $this->longuedescription,
      "tel_pgfichemaj" => $this->tel,
      "fax_pgfichemaj" => $this->fax,
      "email_pgfichemaj" => $this->email,
      "website_pgfichemaj" => $this->website,
      "numrue_pgfichemaj" => $this->numrue,
      "adressepostal_pgfichemaj" => $this->adressepostal,
      "date_validite_pgfichemaj" => is_null($this->date_validite)?null:date("Y-m-d",$this->date_validite),
      "arretsbus_pgfichemaj" => serialize($this->arretsbus),
      "tarifs_pgfichemaj" => serialize($this->tarifs),
      "reductions_pgfichemaj" => serialize($this->reductions),
      "services_pgfichemaj" => serialize($this->services),
      "extra_pgcategory_pgfichemaj" => serialize($this->extra_pgcategory),
      "horraires_pgfichemaj" => serialize($this->horraires),
      "tags_pgfichemaj" => $this->tags,
      "date_soumission_pgfichemaj" => date("Y-m-d",$this->date_soumission),
      "id_utilisateur" => $this->id_utilisateur,
      "commentaire_pgfichemaj" => $this->commentaire));

    if ( !$req->is_success() )
    {
      $this->id = null;
      return false;
    }

	  $this->id = $req->get_id();
    return true;

  }


  function update ( $id_ville, $nom, $lat, $long, $eloi, $id_pgcategory, $id_rue, $id_entreprise, $description, $longuedescription, $tel, $fax, $email, $website, $numrue, $adressepostal, $placesurcarte=null, $contraste=null, $appreciation=null, $commentaire=null, $infointerne=null, $date_maj=null, $date_validite=null, $id_utilisateur_maj=null )
  {

    $this->id_ville = $id_ville;
    $this->nom = $nom;
    $this->lat = $lat;
    $this->long = $long;
    $this->eloi = $eloi;

    $this->id_pgcategory = $id_pgcategory;
    $this->id_rue = $id_rue;
    $this->id_entreprise = $id_entreprise;

    $this->description = $description;
    $this->longuedescription = $longuedescription;
    $this->tel = telephone_userinput($tel);
    $this->fax = telephone_userinput($fax);
    $this->email = $email;
    $this->website = $website;
    $this->numrue = $numrue;
    $this->adressepostal = $adressepostal;
    $this->date_validite = $date_validite;
  }

  function update_fichemaj ( $commentaire, $date_validite )
  {
    $this->commentaire = $commentaire;
    $this->date_validite = $date_validite;
    $this->date_soumission = time();

    new update($this->dbrw, "pg_fichemaj", array(
      "nom_pgfichemaj" => $this->nom,
      "id_ville" => $this->id_ville,
      "lat_pgfichemaj" => $this->lat,
      "long_pgfichemaj" => $this->long,
      "eloi_pgfichemaj" => $this->eloi,
      "id_pgcategory" => $this->id_pgcategory,
      "id_rue" => $this->id_rue,
      "id_entreprise" => $this->id_entreprise,
      "description_pgfichemaj" => $this->description,
      "longuedescription_pgfichemaj" => $this->longuedescription,
      "tel_pgfichemaj" => $this->tel,
      "fax_pgfichemaj" => $this->fax,
      "email_pgfichemaj" => $this->email,
      "website_pgfichemaj" => $this->website,
      "numrue_pgfichemaj" => $this->numrue,
      "adressepostal_pgfichemaj" => $this->adressepostal,
      "date_validite_pgfichemaj" => is_null($this->date_validite)?null:date("Y-m-d",$this->date_validite),
      "arretsbus_pgfichemaj" => serialize($this->arretsbus),
      "tarifs_pgfichemaj" => serialize($this->tarifs),
      "reductions_pgfichemaj" => serialize($this->reductions),
      "services_pgfichemaj" => serialize($this->services),
      "extra_pgcategory_pgfichemaj" => serialize($this->extra_pgcategory),
      "horraires_pgfichemaj" => serialize($this->horraires),
      "tags_pgfichemaj" => $this->tags,
      "date_soumission_pgfichemaj" => date("Y-m-d",$this->date_soumission),
      "commentaire_pgfichemaj" => $this->commentaire),
      array("id_pgfichemaj"=>$this->id));
  }

  function add_arretbus ( $id_arretbus )
  {
    $this->arretsbus[] = $id_arretbus;
  }

  function add_extra_pgcategory ( $id_pgcategory, $titre=null, $soustitre=null )
  {
    $this->extra_pgcategory[] = array($id_pgcategory, $titre, $soustitre);
  }

  function add_tarif ( $id_typetarif, $min_tarif, $max_tarif, $commentaire, $date_validite=null )
  {
    $this->tarifs[] = array($id_typetarif, $min_tarif, $max_tarif, $commentaire, $date_validite);
  }

  function add_reduction ( $id_typereduction, $valeur, $unite, $commentaire, $date_validite=null )
  {
    $this->reductions[] = array($id_typereduction, $valeur, $unite, $commentaire, $date_validite);
  }

  function add_service ( $id_service, $commentaire, $date_validite=null )
  {
    $this->services[] = array($id_service, $commentaire, $date_validite);
  }

  function add_horraire ( $datevaldebut, $datevalfin, $type, $jours, $heuredebut, $heurefin )
  {
    $this->horraires[] = array($datevaldebut, $datevalfin, $type, $jours, $heuredebut, $heurefin);
  }

  function integrate_update ( &$pgfichemaj )
  {
    return;
  }

  function delete ()
  {
    new delete($this->dbrw,"pg_fichemaj",array("id_pgfichemaj"=>$this->id));
  }

  function replace_by ( &$fiche )
  {
    return;
  }


}




?>
