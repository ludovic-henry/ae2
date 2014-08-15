<?php

/** @file
 *
 * @brief Classe elections
 *
 */
/* Copyright 2005
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Julien Etelain < julien at pmad dot net >
 *
 * Ce fichier fait partie du site de l'Association des �tudiants de
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


class election
{
  /* un objet d'acc�s RO � la base de donn�es */
  var $db;
  /* un objet d'acces RW � la base de donn�es */
  var $dbrw;
  /* les elections en cours (charg�es par la fonction load_current_elections() */
  var $current_elections;

  var $id;
  var $id_groupe;
  var $debut;
  var $fin;
  var $nom;

  /* constructeur */
  function election ($db, $dbrw)
  {
    $this->db = $db;
    $this->dbrw = $dbrw;
  }


	function load_by_id ( $id )
	{
		$req = new requete($this->db, "SELECT * FROM `vt_election`
				WHERE `id_election` = '" . mysql_real_escape_string($id) . "'
				LIMIT 1");

		if ( $req->lines == 1 )
			$this->_load($req->get_row());
		else
			$this->id = -1;
	}


	function _load ($row )
	{
  		$this->id = $row['id_election'];
  		$this->id_groupe = $row['id_groupe'];
  		$this->fin = strtotime($row['date_fin']);
  		$this->debut = strtotime($row['date_debut']);
  		$this->nom = $row['nom_elec'];

	}


	function new_election ( $id_groupe, $debut, $fin, $nom )
	{
  		$this->id_groupe = $id_groupe;
  		$this->fin = $fin;
  		$this->debut = $debut;
  		$this->nom = $nom;

  		$req = new insert($this->dbrw, "vt_election", array(
  					"id_groupe"=>$this->id_groupe,
  					"date_fin"=>date("Y-m-d H:i:s",$this->fin),
  					"date_debut"=>date("Y-m-d H:i:s",$this->debut),
  					"nom_elec"=>$this->nom

  					));

		if ( $req )
			$this->id = $req->get_id();
		else
		{
			$this->id = -1;
			return false;
		}

		return true;

	}

  /* enregistre le vote d'un utilisateur
   *
   * @param $id_utilisateur : l'id de l'etudiant votant
   * @param vote_poste : tableau associatif du type (poste => candidat)
   *
   * @return true en cas de succ�s, false sinon
   **/
  function enregistre_vote($id_utilisateur, $vote_poste)
  {

    /* controle si l'utilisateur a deja vote */
    if ($this->a_vote ($id_utilisateur, $this->id))
      return false;

	$req = new insert($this->dbrw, "vt_a_vote",
		      array("id_election" => $this->id,
			    "id_utilisateur" => $id_utilisateur,
			    "date_vote" => date("Y-m-d H:i:s")));


    /* on insere les votes */
    foreach ($vote_poste as $poste => $candidat)
      {
	/* vote blanc pour un poste) */
	/* note : impossibilit� de passer par la classe update,
	 * etant donn� qu'on fait un +1 sur une colone          */
	  $sql = new requete ($this->dbrw,
			     "UPDATE `vt_postes` SET
                               `votes_total` = `votes_total` + 1
                              WHERE
                               `id_poste` = ".mysql_real_escape_string($poste)."
                              AND
                                `id_election` = ".$this->id);

	if ($candidat == -1 || $sql->lines == 0 )
	  $sql = new requete ($this->dbrw,
			     "UPDATE `vt_postes` SET
                               `votes_blancs` = `votes_blancs` + 1
                              WHERE
                               `id_poste` = ".mysql_real_escape_string($poste)."
                              AND
                                `id_election` = ".$this->id);
	/* incr�mentation du nombre de poste du candidat concern� */
	else
	  $sql = new requete ($this->dbrw,
			     "UPDATE `vt_candidat` SET
                               `nombre_voix` = `nombre_voix` + 1
                              WHERE
                               `id_poste` = ".mysql_real_escape_string($poste)."
                              AND
                                `id_utilisateur` = ".mysql_real_escape_string($candidat));
      }
    return $req;
  }

  /* controle si l'utilisateur a deja vot� */
  function a_vote($id_etudiant)
  {
    $req = new requete($this->db, "SELECT COUNT(*) AS `vote` FROM
                                   `vt_a_vote`
                                   WHERE
                                     `id_election` = ".$this->id."
                                   AND
                                     `id_utilisateur` = ".intval($id_etudiant));
    $rs = $req->get_row();
    if ($rs['vote'] == 1)
      return true;
    else return false;
  }


  /**
   * @brief fonction donnant des statistiques sur les r�sultats
   *
   * @param id_election l'identifiant de l'�lection consid�r�e
   * @return false si erreur, un tableau associatif
   * (postes => candidats ( ... )) sinon
   *
   */
  function get_stats ()
  {
    $this->id = mysql_real_escape_string($this->id);
    /* on part du principe que les gestionnaires AE ont le droit de consulter */
    if (!$this->etu->est_dans_groupe ("gestion_ae"))
      return false;

    /* Resultats des statistiques */
    $results = array();

    /* requete a revoir */
    $req = new requete ($this->db,
			"SELECT `vt_candidat`.`id_candidat`
                              , `etudiants`.`nom`
                              , `etudiants`.`prenom`
                              , `etudiants`.`surnom`
                              , `vt_candidat`.`nombre_voix`
                              , `vt_postes`.`nom_poste`
                              , `vt_postes`.`description_poste`
                              , `vt_postes`.`votes_blanc`
                         FROM `vt_candidat`
                         RIGHT OUTER JOIN `vt_postes` ON
                               `vt_postes`.`id_poste` = `vt_candidat`.`id_poste`
                         INNER JOIN `etudiants` ON
                               `etudiants`.`id` = `vt_candidat`.`id_candidat`
                         WHERE `id_election` = $this->id
                         GROUP BY `vt_candidat`.`id_candidat`");
    for ($i = 0; $i < $req->lines; $i++)
      $results[] = $req->get_row();
  }
  /**
   * @brief fonction d'ajout d'un candidat
   *
   *
   */
  function add_candidat($id_utilisateur, $id_poste, $id_liste = NULL)
  {
    $ins = new insert ($this->dbrw,
		       "vt_candidat",
		       array("id_utilisateur" => $id_utilisateur,
			     "id_poste" => $id_poste,
			     "id_liste" => $id_liste));
    return $ins;
  }

  function remove_candidat($id_utilisateur, $id_poste)
  {
    $ins = new delete ($this->dbrw,
		       "vt_candidat",
		       array("id_utilisateur" => $id_utilisateur,
			     "id_poste" => $id_poste));
    return $ins;
  }


  /**
   * @brief fonction d'ajout d'une liste
   *
   *
   */
  function add_liste($id_utilisateur, $nom_liste)
  {
    $ins = new insert ($this->dbrw,
		       "vt_liste_candidat",
		       array("id_utilisateur" => $id_utilisateur,
			     "id_election" => $this->id,
			     "nom_liste" => $nom_liste));
    return $ins;
  }

  function remove_liste($id_liste)
  {
    $ins = new delete ($this->dbrw,
		       "vt_liste_candidat",
		       array("id_liste" => $id_liste,"id_election" => $this->id));
    $ins = new delete ($this->dbrw,
		       "vt_candidat",
		       array("id_liste" => $id_liste));
    return $ins;
  }


  /**
   *
   * @brief fonction d'ajout d'un poste
   *
   */
  function add_poste( $nom_poste, $description_poste)
  {
    $ins = new insert ($this->dbrw,
		       "vt_postes",
		       array("id_election" => $this->id,
			     "nom_poste" => $nom_poste,
			     "description_poste" => $description_poste));
    return $ins;
  }

  function remove_poste ( $id_poste )
  {
    $ins = new delete ($this->dbrw,
		       "vt_postes",
		       array("id_election" => $this->id,
			     "id_poste" => $id_poste));

    $ins = new delete ($this->dbrw,
		       "vt_candidat",
		       array("id_poste" => $id_poste));

    return $ins;




  }


  /** @Static */


  /** Fonction permettant de r�cup�rer les elections en cours
   *
   * @return un tableau associatif du type :
   * (id_election => description_election)
   *
   *
   */
  function load_current_elections($etu)
  {
    $elections = array();
    $etu->charge_groupes();

    /* tous les groupes ne sont pas repr�sentatifs d'elections (groupe
     * ancien par exemple) mais ceci est un moyen relativement rapide
     * de selectionner les elections auquel l'�tudiant a le droit de
     * participer */
    $etu_groupes = "";

    foreach ($etu->groupes as $key)
      $etu_groupes = $etu_groupes . $key['id'] .", ";
    /* troncature pour virer la derniere virgule */
    $etu_groupes = substr($etu_groupes, 0, strlen($etu_groupes) -2);

    $sql ="SELECT `vt_election`.`id_election`
                , `vt_election`.`date_debut`
                , `vt_election`.`date_fin`
           FROM `vt_election`
           WHERE
                  `vt_election`.`date_debut` <= NOW()
           AND
                  `vt_election`.`date_fin`   >= NOW()
           AND
                  `vt_election`.`id_groupe` IN (".$etu_groupes.")";
    $select = new requete($this->db, $sql);

    for ($i = 0; $i < $select->lines; $i++)
      {
	$row = $select->get_row();
	$elections[] = array($row['id_election'] => "Elections ".
			     $row['nom_groupe'] ." du ".
			     $row['date_debut'] . " au " .
			     $row['date_fin']);
      }
    $this->current_elections = $elections;
  }
}

?>
