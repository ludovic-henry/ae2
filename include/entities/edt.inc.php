<?php
/**
 * @file edt.inc.php : Gestion du système des emplois du temps.
 */
/* Copyright 2005,2006
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
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

require_once ($topdir . "include/entities/basedb.inc.php");
require_once ($topdir . "include/cts/edt_img.inc.php");


/** tableau basique des jours (selon la convention propre à la
 * génération d'emploi du temps */
$jour = array("Lundi", "Mardi", "Mercredi", "Jeudi", "Vendredi", "Samedi",
	      "Dimanche");



/**
 * emploi du temps : description "objet" et exports divers (image,
 * ...).  Cette classe est un peu spéciale vis à vis de la philosophie
 * des stdentities, car elle n'a pas d'équivalent de stockage en base
 * de données. D'habitude, les stdentities s'identifient à une table
 * mySQL). Ici, les informations sont diffuses dans plusieurs tables.
 *
 * @ingroup stdentity
 * @author Pierre Mauduit
 *
 */
class edt extends stdentity
{
  /** un tableau décrivant l'inscription aux différentes séances */
  var $edt_arr;

  /** stdentity est défini comme classe abstraite
   * Bien qu'un objet edt soit une extension de stdentity
   * cette fonction n'a pas son utilité ici. (@see stdentity)
   */
  function _load($row) {}

  /** fonction de chargement par identifiant */
  function load_by_id($id) { $this->load($id); }

  /**
   * Fonction de chargement par identifiant, et par semestre éventuel
   * @param $id l'identifiant de l'utilisateur (@see utilisateur)
   * @param $semestre le semestre (si non renseigné, on en déduit le
   * semestre courant.
   */
  function load_by_etu_semestre ($id, $semestre = null)
  {

    $this->id_utilisateur = intval($id);

    $this->edt_arr = null;

    /** semestre courant par défaut */
    if ($semestre == null)
      $semestre = (date("m") > 6 ? "A" : "P") . date("y");
    else
      $semestre = mysql_real_escape_string($semestre);

    $req= new requete($this->db,
		      "SELECT
                              `edu_uv_groupe`.`id_uv_groupe`
                            , `edu_uv_groupe`.`type_grp`
                            , `edu_uv_groupe`.`heure_debut_grp`
                            , `edu_uv_groupe`.`heure_fin_grp`
                            , `edu_uv_groupe`.`jour_grp`
                            , `edu_uv_groupe`.`numero_grp`
                            , `edu_uv_groupe`.`frequence_grp`
                            , `edu_uv_groupe`.`salle_grp`
                            , `edu_uv_groupe_etudiant`.`semaine_etu_grp`
                            , `edu_uv`.`code_uv`
                            , `edu_uv`.`id_uv`
                       FROM
                              `edu_uv_groupe_etudiant`
                       INNER JOIN
                              `edu_uv_groupe`
                              USING (`id_uv_groupe`)
                       INNER JOIN
                              `edu_uv`
                              USING (`id_uv`)
                       WHERE
                             `edu_uv_groupe`.`semestre_grp` = '".$semestre."'
                       AND
                             `edu_uv_groupe_etudiant`.`id_utilisateur` = $id");
    // if ($req->lines <= 0)
    //  return;
    if (mysql_num_rows($req->result) <= 0)
      return;

    global $jour;

    while ($row = $req->get_row())
      {
	$id_seance = $row['id_uv_groupe'];

	if ($row['semaine_etu_grp'] == 'AB')
	  $semaine_seance = 'AB';
	else
	  $semaine_seance = $row['semaine_etu_grp'];

	if ($row['frequence_grp'] == 1)
	  $semaine_seance = 'AB';


	$hrdeb = substr($row['heure_debut_grp'], 0, 2) . "h" . substr($row['heure_debut_grp'], 3,2);
	$hrfin = substr($row['heure_fin_grp'], 0, 2) . "h" . substr($row['heure_fin_grp'], 3,2);

	$jsem = $jour[$row['jour_grp']];

	$type = ($row['type_grp'] == 'C' ? 'Cours' : $row['type_grp']);
	$grp  = $row['numero_grp'];
	$nomuv = $row['code_uv'];
	$iduv  = $row['id_uv'];
	$salle = $row['salle_grp'];

	$this->edt_arr[] = array("id_seance"      => $id_seance,
				 "semaine_seance" => $semaine_seance,
				 "hr_deb_seance"  => $hrdeb,
				 "hr_fin_seance"  => $hrfin,
				 "jour_seance"    => $jsem,
				 "type_seance"    => $type,
				 "grp_seance"     => $grp,
				 "id_uv"          => $iduv,
				 "nom_uv"         => $nomuv,
				 "salle_seance"   => $salle);
      }
    return;
  }

  /**
   * Fonction permettant d'assigner un étudiant à une séance
   * @param $id_etu l'identifiant d el'étudiant
   * @param $id_group l'identifiant de séance
   * @param $freq fréquence de l'inscription de l'étudiant au groupe
   * @return true si succès, false sinon
   */
  function assign_etu_to_grp($id_etu, $id_group, $freq = 'AB')
  {
    if (!$this->dbrw)
      return false;


    $freq = mysql_real_escape_string($freq);

    $sql = new insert($this->dbrw,
		      "edu_uv_groupe_etudiant",
		      array("id_uv_groupe"    => intval($id_group),
			    "id_utilisateur"  => intval($id_etu),
			    "semaine_etu_grp" => $freq));

    if ($sql->lines <= 0)
      return false;

    return true;
  }

  /**
   * Fonction permettant de désinscrire un étudiant d'une séance
   * @param $id_etu l'identifiant d el'étudiant
   * @param $id_group l'identifiant de sénace
   * @return true si succès, false sinon
   */
  function unsubscr_etu_from_grp($id_etu, $id_group)
  {
    if (!$this->dbrw)
      return false;
    $sql = new delete ($this->dbrw,
		       "edu_uv_groupe_etudiant",
		       array ("id_uv_groupe" => intval($id_group),
			      "id_utilisateur" => intval($id_etu)));
    if ($sql->lines <= 0)
      return false;

    return true;

  }

  /**
   * Fonction de création d'UV. DEPRECATED ! Préférez l'utilisation
   * d'une entity UV. (@see uv)
   *
   * @return true si succès, false sinon
   *
   */

  function create_uv ($code_uv,
		      $intitule_uv,
		      $cours_uv = 1,
		      $td_uv = 1,
		      $tp_uv = 1,
		      $ects = 0,
		      $depts = array(),
		      $uv_cat = array(),
		      $lieu = null)
  {

    if (!$this->dbrw)
      return false;

    global $topdir;
    require_once($topdir . "include/entities/uv.inc.php");

    $uv = new uv($this->db, $this->dbrw);

    $uv->create($code_uv,
		$intitule_uv,
		$cours_uv,
		$td_uv,
		$tp_uv,
		$ects,
		$depts,
		$uv_cat,
		$lieu);

    $return ($uv->id > 0);

  }
  /**
   * Suppression d'un emploi du temps
   * @param $id_etu l'identifiant de l'étudiant
   * @param $semestre le semestre concerné par la suppresion
   */
  function delete_edt($id_etu, $semestre)
  {

    $req = new requete($this->db,"SELECT
                                                  `id_uv_groupe`
                                  FROM
                                                  `edu_uv_groupe_etudiant`
                                  INNER JOIN
                                                  `edu_uv_groupe`
                                  USING
                                                  (`id_uv_groupe`)
                                  WHERE
                                                  `id_utilisateur` = ".intval($id_etu)."
                                  AND
                                                  `semestre_grp` = '".mysql_real_escape_string($semestre)."'");

    while ($rs = $req->get_row())
      {
	$todel = $rs['id_uv_groupe'];
	$req2 = new delete($this->dbrw,
			   "edu_uv_groupe_etudiant",
			   array('id_utilisateur' => intval($id_etu),
				 'id_uv_groupe' => $todel));

      }
    return;
  }

  /**
   * Création d'un groupe (séance)
   * @param $iduv identifiant de l'UV
   * @param $type_grp type de séance (cours, TD ou TP)
   * @param $numgrp numéro du groupe (indiqué sur l'emploi du temps SME)
   * @param $hdebgrp heure de début (format hh:mm:ss)
   * @param $hfingrp heure de de fin (format hh:mm:ss)
   * @param $jourgrp numéro de jour (cf tableau des jours)
   * @param $frqgrp fréquence (1 ou 2)
   * @param $semestre semestre pendant lequel a lieu la séance
   * @param $sallegrp salle où a lieu la séance
   *
   * @return true si succès, false sinon
   */
  function create_grp ($iduv,
		       $type_grp,
		       $numgrp,
		       $hdebgrp,
		       $hfingrp,
		       $jourgrp,
		       $freqgrp,
		       $semestre,
		       $sallegrp)
  {
    if (!$this->dbrw)
      return false;

    /* on vérifie que la séance n'a pas deja été renseignée */
    $typg = mysql_real_escape_string($type_grp);
    $hdg = mysql_real_escape_string($hdebgrp);
    $hfg = mysql_real_escape_string($hfingrp);
    $sg = mysql_real_escape_string($semestre);
    $salleg = mysql_real_escape_string($sallegrp);
    $salleg = str_replace(array(" ","-","."), "", $salleg);

    /** @todo : possible que le code ci-apres introduise
     * un bug empéchant la création d'une séance horaire
     * (c.f. retours sur forum bug site AE
     */
    $vfy = new requete($this->db,
		       "SELECT
                                id_uv_groupe
                        FROM
                                edu_uv_groupe
                        WHERE
                                type_grp = '". $typg ."'
                        AND
                                numero_grp = ". intval($numgrp) . "
                        AND
                                heure_debut_grp = '".$hdg . "'
                        AND
                                heure_fin_grp = '".$hfg . "'
                        AND
                                jour_grp = ".intval($jourgrp) . "
                        AND
                                frequence_grp = ".intval($freqgrp) . "
                        AND
                                semestre_grp = '".$sg . "'
                        AND
                                salle_grp = '".$salleg . "'
                        AND
                                id_uv = " . intval($iduv));



    if ($vfy->lines >= 1)
      {
	$rs = $vfy->get_row();
	return $rs['id_uv_groupe'];
      }

    /** @todo : verification zoror si ce test est vrai, c'est qu'on a
     * affaire à un boulet du formulaire web 2.0. Normalement une
     * soustraction php "hh:mm:ss" - "hh:mm:ss" donne un résultat
     * numérique, mais surveiller le comportement (mise à jour PHP
     * ...)
     */

    if ($hfingrp - $hdebgrp <= 0)
      {
        return false;
      }

    $sql = new insert($this->dbrw,
		      "edu_uv_groupe",
		      array("id_uv"           => $iduv,
			    "type_grp"        => $type_grp,
			    "numero_grp"      => $numgrp,
			    "heure_debut_grp" => $hdebgrp,
			    "heure_fin_grp"   => $hfingrp,
			    "jour_grp"        => $jourgrp,
			    "frequence_grp"   => $freqgrp,
			    "semestre_grp"    => $semestre,
			    "salle_grp"       => $sallegrp));

    if ($sql->lines <= 0)
      return false;

    return $sql->get_id();



  }
  /**
   * Modification d'un groupe (séance)
   * @param $iduv identifiant de séance
   * @param $type_grp type de séance (cours, TD ou TP)
   * @param $numgrp numéro du groupe (indiqué sur l'emploi du temps SME)
   * @param $hdebgrp heure de début (format hh:mm:ss)
   * @param $hfingrp heure de de fin (format hh:mm:ss)
   * @param $jourgrp numéro de jour (cf tableau des jours)
   * @param $frqgrp fréquence (1 ou 2)
   * @param $sallegrp salle où a lieu la séance
   * @param $lieugrp lieu de déroulement de la séance (@see lieu)
   *
   * @return true si succès, false sinon
   */
  function modify_grp ($idgrp,
		       $type_grp,
		       $numgrp,
		       $hdebgrp,
		       $hfingrp,
		       $jourgrp,
		       $freqgrp,
		       $sallegrp,
                       $lieugrp = null)
  {
    if (!$this->dbrw)
      {
        return false;
      }
    $sql = new update($this->dbrw,
		      "edu_uv_groupe",
		      array("type_grp"        => $type_grp,
			    "numero_grp"      => $numgrp,
			    "heure_debut_grp" => $hdebgrp,
			    "heure_fin_grp"   => $hfingrp,
			    "jour_grp"        => $jourgrp,
			    "frequence_grp"   => $freqgrp,
			    "salle_grp"       => $sallegrp,
                            "id_lieu"         => $lieugrp),
                      array("id_uv_groupe" => $idgrp));

    return ($sql->lines == 1);
  }

  /**
   * Fonction d'assignation d'une UV à un département.
   * Note : DEPRECATED. Cette fonction a sa place dans
   * l'entity uv (@see uv), et n'a rien à faire ici.
   * Sa présence doit être due à des histoires de compatibilité.
   * @todo : proprifier le code.
   *
   * @param $iduv l'identifiant de l'UV
   * @param $nomdept le nom du département.
   *
   * @return true si succès, false sinon.
   */

  function assign_uv_to_dept($iduv, $nomdept)
  {
    $nomdept = mysql_real_escape_string($nomdept);

    $sql = new insert($this->dbrw,
		      "edu_uv_dept",
		      array("id_uv"           => intval($iduv),
			    "id_dept"         => $nomdept));

    if ($sql->lines <= 0)
      return false;

    return true;

  }
  /**
   * Fonction retirant une uv d'un département
   * @todo : meme remarque que précédemment.
   *
   * @param $iduv l'identifiant de l'UV
   * @param $nomdept le nom du département.
   *
   * @return true si succès, false sinon
   */
  function remove_uv_from_dept($iduv, $nomdept)
  {
    $nomdept = mysql_real_escape_string($nomdept);

    $sql = new delete($this->dbrw,
		      "edu_uv_dept",
		      array("id_uv"           => intval($iduv),
			    "id_dept"         => $nomdept));

    if ($sql->lines <= 0)
      return false;

    return true;


  }
}


?>
