<?php

/** @file Gestion des associations et clubs
 *
 */

/* Copyright 2007
 * - Simon Lopez <simon DOT lopez AT ayolo DOT org>
 *
 * Ce fichier fait partie du site de l'Association des étudiants de
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


class carnetperso extends stdentity
{

	/* table asso */
	var $id_utilisateur;
	var $carnet=array();


	/** Charge le carnet perso de l'utilisateur ayant pour ID $id
	 * @param $id ID de l'utilisater
	 */
	function load_by_user_id ( $id )
	{
	  $this->carnet=array();
	  $this->id_utilisateur=$id;
		$req = new requete($this->db,
		                   "SELECT `id_utilisateur_member`, `nom`".
		                   "FROM `carnetperso` ".
		                   "INNER JOIN `carnetperso_cat` USING(`id_cat`) ".
				               "WHERE `id_utilisateur` = '" . mysql_real_escape_string($id) . "'");
		if ( $req->lines > 0 )
		{
			while(list($id_utl_member,$categorie))
			{
			  if(isset($carnet[$categorie]))
			    $carnet[$categorie][]=$id_utl_member;
			  else
			  {
			    $carnet[$categorie]=array();
			    $carnet[$categorie][]=$id_utl_member;
			  }
			}
		}
	}

	/** Ajoute un contact
	 * @param $id_utilisateur_member  Id du contact
	 * @param $id_cat	                Id de la catégorie
	 * @param $id_utilisateur         Id du propriétaire du carnet
	 */
	function add_contact ( $id_utilisateur_member, $id_cat=1, $id_utilisateur=null )
	{
		if ( is_null($this->dbrw) ) return false; // "Read Only" mode
		if ( is_null($id_utilisateur) )  $id_utilisateur=$this->id_utilisateur;

		$sql = new insert ($this->dbrw,
			                 "carnetperso",
			                 array("id_utilisateur" => $id_utilisateur,
				                     "id_utilisateur_member" => $id_utilisateur_member,
				                     "id_cat" => $id_cat
				                    )
			                );

		if ( $sql )
		{
		  if($id_utilisateur=$this->id_utilisateur)
		    $this->load_by_user_id ( $id_utilisateur )
			return false;
		}
    else
      return true;
	}

	function delete_contact ( $id_utilisateur_member, $id_cat=1, $id_utilisateur=null )
	{
	  if ( is_null($this->dbrw) ) return; // Read Only mode
	  if ( is_null($id_utilisateur) )  $id_utilisateur=$this->id_utilisateur;

	  new delete($this->dbrw,
	             "carnetperso",
	             array("id_utilisateur"=>$id_utilisateur,
	                   "id_cat"=>$id_cat,
	                   "id_utilisateur_member"=>$id_utilisateur_member
	                  )
	            );
	  if($id_utilisateur=$this->id_utilisateur)
		  $this->load_by_user_id ( $id_utilisateur )
	}
}

?>
