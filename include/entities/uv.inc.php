<?php
/*
 * Copyright 2007
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr/
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
 * @file Gestion des uvs et partie pédagogie du site
 */

/** statut normal */
define('UVCOMMENT_NOTMODERATED', 0);

/** reporté comme abusif par un utilisateur */
define('UVCOMMENT_ABUSE', 1);

/** "Mis en quarantaine" par l'équipe de modération */
define('UVCOMMENT_QUARANTINE', 2);

/**
 * accepté définitivement(après mise en quarantaine)
 * stade apres lequel un utilisateur normal ne peut
 * plus le rapporter comme abusif
 *
 * (l'équipe de modération reste toutefois maitre du statut)
 */
define('UVCOMMENT_ACCEPTED', 3);

/**
 * identifiant du répertoire contenant les fichiers
 * relatifs aux UVs
 *
 */
define('UVFOLDER', 784);

/**
 * tableaux globaux sur les commentaires UV
 * Note : ces critères sont inspirés du projet de David
 * Anderson (Dave`),
 * http://code.google.com/p/critic
 */
$uvcomm_utilite = array(
      '-1' => 'Non renseigné',
      '0' => 'Inutile',
      '1' => 'Pas très utile',
      '2' => 'Utile',
      '3' => 'Très utile',
      '4' => 'Indispensable');


$uvcomm_interet = array('-1' => 'Non renseigné',
      '0'  => 'Aucun',
      '1' => 'Faible',
      '2' => 'Bof',
      '3' => 'Intéressant',
      '4' =>'Tres intéressant');

$uvcomm_travail = array ('-1' =>'Non renseigné',
       '0'=>'Symbolique',
       '1'=>'Faible',
       '2'=>'Moyenne',
       '3'=>'Importante',
       '4'=>'Très importante');

$uvcomm_note = array ('-1' => 'Sans avis',
          '0'=>'Nul',
          '1'=>'Pas terrible',
          '2'=>'Neutre',
          '3'=>'Pas mal',
          '4'=>'Génial');

$uvcomm_qualite = array ('-1' => 'Sans avis',
       '0'=>'Inexistante',
       '1'=>'Mauvaise',
       '2'=>'Moyenne',
       '3'=>'Bonne',
       '4'=>'Excellente');


$departements = array('Humanites', 'TC', 'GESC', 'EE', 'GI', 'IMAP', 'IMSI', 'GMC', 'EDIM');


/** tableaux sur la catégorisation des UVs à l'intérieur des départements */
$humas_cat = array(''=> null, 'EC' => 'EC', 'CG' => 'CG', 'EX' => 'EX');
$tc_cat    = array('' => null, 'CS' => 'CS', 'TM' => 'TM', 'EX' => 'EX');
/** note : à l'heure actuelle, il n'existe pas d'UV de TM en EDIM */
$edim_cat  = array(''=>null,
       'CS' => 'CS',
       'TM' => 'TM',
       'RN' => 'RN',
       'EX' => 'EX');

$gesc_cat  = array(''=> null,
       'CS' => 'CS',
       'TM' => 'TM',
       'RN' => 'RN',
       'EX' => 'EX');
$ee_cat  = array(''=> null,
       'CS' => 'CS',
       'TM' => 'TM',
       'RN' => 'RN',
       'EX' => 'EX');
$gi_cat    = array('' => null,
       'CS' => 'CS',
       'TM' => 'TM',
       'RN' => 'RN',
       'EX' => 'EX');

$gmc_cat   = array('' => null,
       'CS' => 'CS',
       'TM' => 'TM',
       'RN' => 'RN',
       'EX' => 'EX');

$imap_cat  = array('' => null,
       'CS' => 'CS',
       'TM' => 'TM',
       'RN' => 'RN',
       'EX' => 'EX');

$imsi_cat  = array('' => null,
       'CS' => 'CS',
       'TM' => 'TM',
       'RN' => 'RN',
       'EX' => 'EX');

$uv_descr_cat = array('NA' => 'Inconnu',
                      'CS' => 'Connaissances scientifiques',
          'TM' => 'Techniques et méthodes',
          'EX' => 'Extérieur',
          'EC' => 'Expression / Communication',
          'CG' => 'Culture Générale',
          'RN' => 'Remise à niveau');

/**
 * Unité de valeur (pour le guide et les emplois du temps)
 * @ingroup stdentity
 * @author Pierre Mauduit
 */
class uv extends stdentity
{
  /** Code UV en accord avec la codification UTBM (ex: LO41) */
  var $code;
  /** Intitulé de l'UV */
  var $intitule;
  /** Objectifs de l'UV */
  var $objectifs;
  /** Programme de l'UV */
  var $programme;
  /** Nombre de crédits ECTS obtenus en cas de succès */
  var $ects;

  /** booléen indiquant s'il y a des cours dans l'UV */
  var $cours;
  /** booléen indiquant s'il y a des TDs dans l'UV */
  var $td;
  /** booléen indiquant s'il y a des TPs dans l'UV */
  var $tp;
  /** booléen indiquant si un projet doit etre réalisé */
  var $projet;


  /** Tableau des départements dans lesquels l'UV est enseigné */
  var $depts;

  /* Tableau des catégories selon les départements */
  var $cat_by_depts;

  /** un identifiant de répertoire @see dfile */
  var $idfolder;
  /** une instance de répertoire @see dfile */
  var $folder;
  /* un tableau de commentaires @see uvcomments */
  var $comments;

  /** un identifiant de lieu @see lieu */
  var $id_lieu;
  /** un identifiant de lieu @see lieu */
  var $lieu;

  /**
   * Charge une UV par son code
   * @param $code Code de l'UV (codification UTBM)
   * @return false si non chargé, true sinon
   */
  function load_by_code($code)
  {
    $req = new requete($this->db, "SELECT * ".
                                   "FROM ".
                                   "`edu_uv` ".
                                   "WHERE ".
                                   "`code_uv` = '".
                                   mysql_real_escape_string($code).
                                   "' ".
                                   "LIMIT 1");

    if ($req->lines == 1)
    {
      $row = $req->get_row();

      $this->id        = $row['id_uv'];
      $this->code      = $row['code_uv'];
      $this->intitule  = $row['intitule_uv'];
      $this->objectifs = $row['objectifs_uv'];
      $this->programme = $row['programme_uv'];
      $this->ects      = $row['ects_uv'];
      $this->cours     = $row['cours_uv'];
      $this->td        = $row['td_uv'];
      $this->tp        = $row['tp_uv'];
      $this->idfolder  = $row['id_folder'];

      $this->id_lieu   = $row['id_lieu'];

      /* chargement du lieu */
      if ($this->id_lieu != null)
	{
	  $this->load_lieu();
	}

      $this->load_depts();

      return true;
    }

    $this->id = null;
    return false;
  }

  /**
   * Charge le lieu associé à l'UV
   */
  function load_lieu()
  {
    global $topdir;
    require_once($topdir . "include/entities/lieu.inc.php");
    $this->lieu = new lieu($this->db);
    $this->lieu->load_by_id($this->id_lieu);
  }
  /**
   * Charge l'UV par son identifiant
   * @param $id l'identifiant de l'UV
   * @return true si chargé, false sinon
   *
   */
  function load_by_id ($id)
  {
    $req = new requete($this->db, "SELECT * ".
                                  "FROM ".
                                  "`edu_uv` ".
                                  "WHERE ".
                                  "`id_uv` = '".
                                  mysql_real_escape_string($id).
                                  "' ".
                                  "LIMIT 1");

    if ($req->lines == 1)
    {
      $row = $req->get_row();

      $this->id       = $row['id_uv'];
      $this->code     = $row['code_uv'];
      $this->intitule = $row['intitule_uv'];
      $this->objectifs = $row['objectifs_uv'];
      $this->programme = $row['programme_uv'];
      $this->ects     = $row['ects_uv'];
      $this->cours    = $row['cours_uv'];
      $this->td       = $row['td_uv'];
      $this->tp       = $row['tp_uv'];
      $this->idfolder = $row['id_folder'];

      $this->id_lieu   = $row['id_lieu'];

      /* chargement du lieu */
      if ($this->id_lieu != null)
	{
	  $this->load_lieu();
	}

      $this->load_depts();

      return true;
    }

    $this->id = null;
    return false;
  }

  /**
   * @todo à implémenter
   */
  function _load($row)
  {

  }

  /**
   * Charge les départements liés à l'UV
   *
   */
  function load_depts ()
  {
    if (!$this->id)
      return;

    $this->depts = array();

    $req = new requete($this->db,
           "SELECT `id_dept`, `uv_cat` FROM `edu_uv_dept` WHERE `id_uv` = ".$this->id);

    while ($row = $req->get_row())
    {
      $this->depts[] = $row['id_dept'];
      $this->cat_by_depts[$row['id_dept']] = $row['uv_cat'];
    }
  }
  /**
   * Recharge les départements
   *
   */
  function reload_depts ()
  {
    $this->load_depts ();
  }

  /**
   * Modification de l'UV.
   *
   * note : uv_cat doit etre un tableau indexé par le nom du département
   * (on part du principe qu'à un département d'enseignement peut correspondre
   *  une catégorie spécifique).
   *
   * @param $code_uv le nouveau code
   * @param $intitule Le nouvel intitulé
   * @param $obj Le nouvel objectif
   * @param $prog le nouveau programme
   * @param $c booléen indiquant s'il y a des cours
   * @param $td booléen indiquant s'il y a des TDs
   * @param $tp booléen indiquant s'il y a des TPs
   * @param $ects Le nombre de crédits ECTS délivrés
   * @param $depts Un tableau contenant la liste des départements concernés
   * @param $uv_cat Tableau des catégories d'UV par département
   * @param $id_lieu Le lieu d'enseignement
   */
  function modify($code_uv, $intitule, $obj, $prog, $c, $td, $tp, $ects, $depts, $uv_cat = null, $id_lieu = null)
  {
    if ($this->id <= 0)
      return false;

    $this->code      = $code_uv;
    $this->intitule  = $intitule;
    $this->programme = $prog;
    $this->objectifs = $obj;
    $this->ects      = $ects;
    $this->cours     = $c;
    $this->td        = $td;
    $this->tp        = $tp;

    $this->id_lieu   = $id_lieu;

    if ($this->id_lieu != null)
      $this->load_lieu();


    $req = new update ($this->dbrw,
           'edu_uv',
           array('code_uv' => $this->code,
           'intitule_uv' => $this->intitule,
           'objectifs_uv' => $this->objectifs,
           'programme_uv' => $this->programme,
           'cours_uv' => $this->cours,
           'td_uv' => $this->td,
           'tp_uv' => $this->tp,
           'ects_uv' => $this->ects,
	   'id_lieu' => $this->id_lieu),
           array('id_uv' => $this->id));


    /* suppression des départements */
    $req = new delete($this->dbrw,
          'edu_uv_dept',
          array('id_uv' => $this->id));

    global $departements;

    for ($i = 0; $i < count($depts); $i++)
    {
      $dept = mysql_real_escape_string($depts[$i]);
      if (in_array($dept, $departements))
        $req = new insert($this->dbrw,
                          'edu_uv_dept',
                          array("id_uv" => $this->id,
                                "id_dept" => $dept,
                                "uv_cat" => $uv_cat[$dept]));
    }

    $this->reload_depts();

    return;
  }

  /**
   * Enregistrement d'une nouvelle UV en base
   * @param $code_uv Le code de l'UV
   * @param $intitule L'intitulé de l'UV
   * @param $c booléen indiquant s'il y a cours
   * @param $td booléen indiquant s'il y TD
   * @param $tp booléen indiquant s'il y TP
   * @param $ects entier indiquant le nombre de crédits ECTS
   * @param $depts Tableau donnant la liste des départements
   * @param $uv_cat Tableau des catégories par département
   * @param $id_lieu Un identifiant de lieu d'enseignement
   *
   * @return true si succès, false sinon
   */
  function create ($code_uv, $intitule, $c, $td, $tp, $ects, $depts, $uv_cat, $id_lieu = null)
  {
    $this->code     = $code_uv;
    $this->intitule = $intitule;
    $this->ects     = $ects;
    $this->cours    = $c;
    $this->td       = $td;
    $this->tp       = $tp;

    $this->id_lieu = $id_lieu;

    if ($this->id_lieu != null)
      $this->load_lieu();



    $req = new insert ($this->dbrw,
           'edu_uv',
           array('code_uv' => $this->code,
           'intitule_uv' => $this->intitule,
           'cours_uv' => $this->cours,
           'td_uv' => $this->td,
           'tp_uv' => $this->tp,
           'ects_uv' => $this->ects,
	   'id_lieu' => $this->id_lieu));

    if ($req)
    {
      $this->id = $req->get_id();
    }
    else
    {
      $this->id = -1;
      return false;
    }

    global $departements;

    /* ajout des départements */
    for ($i = 0; $i < count($depts); $i++)
    {
      if (in_array($dept, $departements))
        $req = new insert($this->dbrw,
              'edu_uv_dept',
              array("id_uv" => $this->id,
              "id_dept" => $dept,
              "uv_cat" => $uv_cat[$dept]));
    }

    return true;
  }

  /**
   * Chargement des commentaires
   * @param $admin indique si l'utilisateur est administrateur
   */
  function load_comments($admin = false)
  {
    if (!$this->id)
      return false;

    $this->comments = array();

    $sql = 'SELECT '.
           '`id_comment` '.
           'FROM '.
           '`edu_uv_comments` '.
           'WHERE '.
           '`id_uv` = '.$this->id;
//    if ($admin == false)
//      $sql .= " AND state_comment IN (0, 1, 3)";

    $sql .= " ORDER BY date_commentaire ASC";

    $rq = new requete($this->db,$sql);


    $i = 0;
    while ($rs = $rq->get_row())
    {
      $this->comments[$i] = new uvcomment($this->db);
      $this->comments[$i]->load_by_id($rs['id_comment']);
      $i++;
    }
    return;
  }

  function prefer_list()
  {
    return true;
  }

  /**
   * Chargement du dossier relatif à l'UV dans la partie fichier
   * @see dfile
   * @return false si erreur, true si succès
   */
  function load_folder()
  {
    if ($this->idfolder == null)
      return false;

    global $topdir;
    require_once($topdir. "include/entities/folder.inc.php");

    $this->folder = new dfolder($this->db, $this->dbrw);

    $this->folder->load_by_id($this->idfolder);

    return true;
  }

  /**
   * fonction vérifiant qu'un répertoire est bien
   * dans l'arborescence relative aux UVs.
   * @param $id_folder l'identifiant du dossier
   * @see dfile
   */
  function check_folder($id_folder)
  {
    $id_folder = intval($id_folder);

    while (true)
    {
      $req = new requete($this->db, "SELECT ".
                                    "`id_folder_parent` ".
                                    "FROM ".
                                    "`d_folder` ".
                                    "WHERE ".
                                    "`id_folder` = $id_folder ".
                                    "LIMIT 1");
      if ($req->lines < 0)
        return false;

      $row = $req->get_row();

      /* arrivé en haut de l'arbo */
      if ($row['id_folder_parent'] == null)
        return false;

      /* c'est bon. */
      if ($row['id_folder_parent'] == $this->idfolder)
        return true;
      /* sinon on boucle */
      $id_folder = intval($row['id_folder_parent']);
    }
  }
  /**
   * Récupère le chemin d'accès partie fichier propre à l'espace
   * pédagogie.
   * @param $path l'identifiant du répertoire
   * @see dfile
   * @return le chemin d'accès sous forme de chaine de caractères
   */
  function get_path($path)
  {
    $path = intval($path);
    $ret = "";
    while (true)
    {
      $req = new requete($this->db, "SELECT ".
                         "`id_folder_parent` ".
                         ", `nom_fichier_folder` ".
                         ", `id_folder` ".
                         "FROM ".
                         "`d_folder` ".
                         "WHERE ".
                         "`id_folder` = $path ".
                         "LIMIT 1");
      if ($req->lines < 0)
        return false;

      $row = $req->get_row();

      if ($row['id_folder_parent'] == null)
        return $ret;

      $ret = ($row['nom_fichier_folder'] . " / " . $ret);

      if ($row['id_folder_parent'] == $this->idfolder)
        return $ret;


      /* sinon on boucle */
      $path = intval($row['id_folder_parent']);
    }
  }

  /**
   * Crée un répertoire dans la partie fichiers
   * @see dfolder
   * @return true si succès, false sinon
   */
  function create_folder()
  {
    global $topdir;
    require_once($topdir. "include/entities/folder.inc.php");

    // non chargé
    if (!$this->is_valid())
      return false;

    // le répertoire existe deja
    if (!is_null($this->idfolder))
      return false;

    $parent = new dfolder($this->db, $this->dbrw);
    $parent->create_or_load("pédagogie");

    $newfold = new dfolder($this->db, $this->dbrw);
    $newfold->id_groupe_admin = 7;
    $newfold->id_groupe = 7;
    $newfold->droits_acces = 0xDDD;
    $newfold->id_utilisateur = null;
    $newfold->add_folder ( $this->code, $parent->id, "Fichiers relatifs à l'UV ".$this->code, null );

    $newfold->set_modere(true);

    new update($this->dbrw,
             'edu_uv',
             array('id_folder' => $newfold->id),
             array('id_uv' => $this->id));

    $this->idfolder = $newfold->id;

    /* chargement du dossier */
    $this->folder = $newfold;

    return true;
  }

}


/**
 * Commentaire sur une unité de valeur
 * @author Pierre Mauduit
 * @see uv
 */
class uvcomment extends stdentity
{

  /** l'identifiant de l'UV */
  var $id_uv;
  /** l'identifiant de l'utilisateur ayant commenté */
  var $id_commentateur;
  /** note d'obtention (Format UTBM : A, B ...) */
  var $note_obtention;
  /** semestre d'obtention [A,P][0-9][0-9] */
  var $semestre_obtention;

  /** note sur l'intéret de l'UV
   * Est-ce que l'UV vaut le coup d'être suivie ?
   * (Réflexions sur la qualité de l'enseignement,
   *  moyens mis à disposition ...)
   */
  var $interet;

  /** note sur l'utilité de l'UV
   * est-ce que l'UV est utile dans le cadre
   * de la formation d'ingénieur ? */
  var $utilite;

  /** note sur la charge de travail */
  var $charge_travail;
  /** note sur la qualité de l'enseignement */
  var $qualite_ens;
  /** note générale que donne l'étudiant sur l'UV */
  var $note;

  /** commentaire champ texte (syntaxe dokuwiki) */
  var $comment;

  /** date du commentaire */
  var $date;

  /** etat du commentaire (vis à vis de la modération) */
  var $etat;

  /**
   *
   * Fonction de chargement par identifiant
   * @param $id l'identifiant
   * @return true si succès, false sinon
   */
  function load_by_id($id)
  {
    $req = new requete($this->db, "SELECT * ".
                                  "FROM ".
                                  "`edu_uv_comments` ".
                                  "WHERE ".
                                  "`id_comment` = '".
                                  mysql_real_escape_string($id).
                                  "' ".
                                  "LIMIT 1");

    if ($req->lines == 1)
    {
      $row = $req->get_row();

      $this->id              = $row['id_comment'];

      $this->id_uv           = $row['id_uv'];
      $this->id_commentateur = $row['id_utilisateur'];

      $req2 = new requete($this->db, "SELECT * FROM `edu_uv_obtention` ".
                                     "WHERE `id_utilisateur` = ".
                                     intval($this->id_commentateur).
                                     " AND `id_uv` = ".
                                     intval($this->id_uv).
                                     " LIMIT 1");
      /* @todo pour les gens qui ont redoublé,
       * on fait quoi ? l'utilisation des semestres
       * n'est pas optimal pour "trier" en SQL
       */
      if ($req2->lines == 1)
      {
        $row2 = $req2->get_row();
        $this->note_obtention      = $row2['note_obtention'];
        $this->semestre_obtention  = $row2['semestre_obtention'];
      }

      $this->interet         = $row['interet_uv'];
      $this->utilite         = $row['utilite_uv'];
      $this->charge_travail  = $row['travail_uv'];
      $this->qualite_ens     = $row['qualite_uv'];
      $this->note            = $row['note_uv'];

      $this->comment         = $row['comment_uv'];

      $this->date            = $row['date_commentaire'];

      $this->etat            = $row['state_comment'];

      return true;
    }
    return false;
  }

  /**
   * @todo à implémenter
   */
  function _load($row)
  {

  }

  /**
   * Fonction permettant de modifier un commentaire
   * @param $commentaire commentaire sous forme de texte (dokuwiki)
   * @param $note_obtention (null si non renseigné)
   * @param $semestre_obtention le semestre d'obtention
   * @param $interet note sur l'intéret
   * @param $utilite note sur l'utilité
   * @param $note note générale
   * @param $travail indication sur la charge de travail
   * @param $qualite note sur la qualité de l'enseignement
   * @return true si succès, false sinon
   */
  function modify($commentaire,
                  $note_obtention = null,
                  $semestre_obtention,
                  $interet = 3,
                  $utilite = 3,
                  $note    = 3,
                  $travail = 3,
                  $qualite = 3)
  {

    /* Champ `edu_uv_comment`.`note_obtention_uv` DEPRECIE ! */
    $sql = new update($this->dbrw,
          'edu_uv_comments',
          array('note_obtention_uv' => $note_obtention,
          'comment_uv' => $commentaire,
          'interet_uv' => $interet,
          'utilite_uv' => $utilite,
          'note_uv'    => $note,
          'travail_uv' => $travail,
          'qualite_uv' => $qualite,
          'date_commentaire' => date("Y-m-d H:i:s")),
          array ("id_comment" => $this->id));


    $sql2 = new update($this->dbrw,
           'edu_uv_obtention',
           array('note_obtention' => $note_obtention,
           'semestre_obtention' => $semestre_obtention),
           array('id_uv' => $this->id_uv,
           'id_utilisateur' => $this->id_commentateur));


    return ($sql->lines == 1);
  }

  /**
   * Fonction créant un nouveau commentaire en base
   * @param $id_uv l'identifiant de l'UV concernée
   * @param $id_commentateur l'identifiant de l'utilisateur
   * @param $commentaire champ texte (syntaxe dokuwiki)
   * @param $note_obtention note d'obtention du commentateur
   * @param $semestre_obtention semestre d'obtention
   * @param $interet note sur l'intéret
   * @param $utilite note sur l'utilité
   * @param $travail note sur la charge de travail
   * @param $qualite note sur la qualité de l'enseignement
   * @return true si succès, false sinon
   */
  function create($id_uv,
                  $id_commentateur,
                  $commentaire,
                  $note_obtention = null,
                  $semestre_obtention,
                  $interet = 3,
                  $utilite = 3,
                  $note    = 3,
                  $travail = 3,
                  $qualite = 3)
  {
    $sql = new insert($this->dbrw,
          'edu_uv_comments',
          array ('id_uv' => $id_uv,
           'id_utilisateur' => $id_commentateur,
           'note_obtention_uv' => $note_obtention,
           'comment_uv' => $commentaire,
           'interet_uv' => $interet,
           'utilite_uv' => $utilite,
           'note_uv'    => $note,
           'travail_uv' => $travail,
           'qualite_uv' => $qualite,
           'date_commentaire' => date("Y-m-d H:i:s"),
           'state_comment' => 0));

    $sql2 = new insert($this->dbrw,
           'edu_uv_obtention',
           array ('id_uv' => $id_uv,
            'id_utilisateur' => $id_commentateur,
            'note_obtention' => $note_obtention,
            'semestre_obtention' => $semestre_obtention));

    if ($sql->lines <= 0)
      return false;
    else
      $this->load_by_id($sql->get_id());
    return true;
  }

  /**
   * Fonction supprimant un commentaire
   * @return true si succès, false sinon
   */
  function delete()
  {
    if (!$this->id)
      return false;

    $req = new delete($this->dbrw,
          'edu_uv_comments',
          array('id_comment' => $this->id));

    return ($req->lines == 1);
  }

  /**
   * Fonction de modération des commentaires. Tous les étudiants
   * peuvent rapporter un commentaire jugé abusif, les modérateurs
   * peuvent supprimer de la visibilité du site un commentaire.  Dans
   * le premier cas, le commentaire sera visible en rouge, et portera
   * la mention "jugé abusif", dans le deuxième cas, il ne sera pas
   * visible du commun des mortels, mais l'équipe de modération pourra
   * revenir sur sa décision.
   *
   * @param $level le niveau de modération
   * @return true si succès, false sinon
   */
  function modere($level = UVCOMMENT_ABUSE)
  {
    if ($this->id <= 0)
      return false;

    $req = new update($this->dbrw,
          'edu_uv_comments',
          array('state_comment' => $level),
          array('id_comment' => $this->id));

    return ($req->lines > 0);
  }

}

/** Fonctions "globales" sur les UVs */

/**
 *
 * Fonction permettant de récupérer les résultats depuis le site de
 * l'UTBM.  Note importante : Cette fonction n'a jamais été utilisée,
 * du fait de la controverse sur la possibilité par l'AE garder ou non
 * en base l'INE, ce qui n'est pas sans poser quelques soucis vis à
 * vis de nos obligations avec la CNIL. Par ailleurs, elle n'a jamais
 * véritablement été testée, et reste très dépendante des évolutions
 * informatiques de notre école (changement de la page, parsing un peu
 * trashos ...).  Pourtant, elle permettrait par exemple un import
 * automatique des résultats des étudiants dans la partie pédagogie.
 *
 * A méditer.
 *
 * @param $nom le nom de l'étudiant
 * @param $ine l'INE de l'étudiant
 * @return le résultat de l'étudiant sous forme d'un tableau
 * associatif.
 *
 */
function get_results($nom, $ine)
{
  $location = "services.utbm.fr/ACTU/resuv/index.php";
  $query = "nom=${nom}&motdepasse=${ine}";

  $path=explode('/', $location);
  $host=$path[0];
  unset($path[0]);
  $path='/'.(implode('/',$path));
  $post="POST $path HTTP/1.1\r\nHost: $host\r\nContent-type: ".
    "application/x-www-form-urlencoded\r\n${others}User-Agent: ".
    "Mozilla 4.0\r\nContent-length: ".
    strlen($query)."\r\nConnection: close\r\n\r\n$query";

  $h=fsockopen($host,80);
  fwrite($h,$post);
  for($a=0,$r='';!$a;)
    {
      $b=fread($h,8192);
      $r.=$b;
      $a=(($b=='')?1:0);
    }
  fclose($h);
  $page =  $r;

  preg_match_all("/<font.*>(.*)<\/td>/", $page, $plouf);


  $plouf = $plouf[0];

  $ret = array();

  /* brutalos parsing from prehistoric UTBM website */
  for ($i = 0; $i < 16; $i++)
    {
      if ($i < 4)
	continue;

      $plouf[$i] = strip_tags($plouf[$i]);

      // uvs

      if (($i <= 10) && (strlen($plouf[$i]) > 0))
	{
	  $nom_uv = substr($plouf[$i],0,4);
	  $res = substr($plouf[$i], 7);
	  $ret[] = $res;
	}
    }

  // resultat de jury machin
  $ret['res_jury'] = $plouf[15];

  return $ret;
  //  return $plouf;
  //return $page;
}

/**
 * Fonction ajoutant un résultat d'UV à un étudiant
 * @param $id_etu l'identifiant de l'étudiant
 * @param $id_uv l'identifiant de l'UV
 * @param $note La note d'obtention
 * @param $semestre semestre d'otention
 * @param $dbrw un ressource de connexion SQL en RW
 * @return true si succès, false sinon
 */

function add_result_uv($id_etu, $id_uv, $note, $semestre, $dbrw)
{
  if (strlen($semestre) != 3)
    return false;

  if (($semestre[0] != 'A') && ($semestre[0] != 'P'))
    return false;

  $req = new insert($dbrw, "edu_uv_obtention",
        array("id_uv" => $id_uv,
        "id_utilisateur" => $id_etu,
        "note_obtention" => $note,
        "semestre_obtention" => strtoupper($semestre)));
  return ($req->lines == 1);
}
/**
 * Fonction de suppression d'un résultat d'UV
 * @param $id_etu l'identifiant de l'étudiant
 * @param $id_uv l'identifiant de l'UV
 * @param $semestre le semestre d'obtention
 * @param $dbrw une ressource de connexion SQL en RW
 * @return true si succès, false sinon
 */
function delete_result_uv($id_etu, $id_uv, $semestre, $dbrw)
{
  $req = new delete($dbrw, "edu_uv_obtention",
        array("id_utilisateur" => $id_etu,
        "id_uv" => $id_uv,
        "semestre_obtention" => $semestre));

  return ($req->lines == 1);


}

/**
 * Fonction de récupération d'un stdcontents concernant les crédits,
 * correspond au "parcours pédagogique" sur uvs/index.php.
 *
 * Note : ce code a été déporté ici afin d'alléger la page
 * uvs/index.php
 *
 * @param $etu une instance d'étudiant @see utilisateur
 * @param $db une instance de connexion à la base en read only
 * @param $camembert indique si on attend un graphique sous forme de
 * camembert ou non.
 *
 * @return le contents (@see stdcontents) attendu, selon la valeur du
 * paramêtre $camembert.
 */
function get_creds_cts(&$etu, $db, $camembert = false)
{
  global $topdir;
  require_once($topdir . "include/cts/sqltable.inc.php");

  $req = new requete($db, "SELECT ".
                          "`edu_uv`.`id_uv`".
                          ", `edu_uv`.`code_uv`".
                          ", `edu_uv`.`intitule_uv`".
                          ", `edu_uv`.`ects_uv`".
                          ", `edu_uv_dept`.`id_dept`".
                          ", `edu_uv_dept`.`uv_cat`".
                          ", `edu_uv_obtention`.`note_obtention`".
                          ", `edu_uv_obtention`.`semestre_obtention`".
                          ", IF(`edu_uv_comments`.`id_comment`,
                                 '<img src=\"$topdir/images/icons/16/star.png\" alt=\"star\" title=\"vous avez mis un commentaire\" />',
                                 '<img src=\"$topdir/images/icons/16/unstar.png\" alt=\"unstar\" title=\"vous n\'avez pas encore mis de commentaire\" />')
                                 as comment".
                          " FROM".
                          " `edu_uv`".
                          " INNER JOIN".
                          " `edu_uv_obtention`".
                          " USING (`id_uv`)".
                          " INNER JOIN".
                          " `edu_uv_dept`".
                          " USING (`id_uv`)".
                          " LEFT JOIN `edu_uv_comments`".
                          " USING ( `id_uv`, `id_utilisateur` )".
                          " WHERE".
                          " `edu_uv_obtention`.`id_utilisateur` = ".
                          $etu->id .
                          " GROUP BY".
                          " `id_uv`, `semestre_obtention`".
                          " ORDER BY".
                          " `semestre_obtention`");


  $cts = new contents("Détails des crédits obtenus");


  if ($req->lines > 0)
  {
    $totcreds = 0;
    $statsobs = array();
    $totuvs = 0;
    /* on découpe par semestre */
    while ($rs = $req->get_row())
    {
      $totsuvs++;

      if ($rs['uv_cat'] != null)
        $stats_by_cat[$rs['uv_cat']][] = $rs;
      else
        $stats_by_cat['NA'][] = $rs;

      $stats_by_sem[$rs['semestre_obtention']][] = $rs;

      $statsobs[$rs['note_obtention']] ++;
      ksort($statsobs, SORT_STRING);

      if (($rs['note_obtention'] != 'F') && ($rs['note_obtention'] != 'Fx'))
        $totcreds += $rs['ects_uv'];
    }

    /* on trie */
    if (count($stats_by_sem) > 0)
    {
      foreach ($stats_by_sem as $key => $uvsemestre)
      {
        // On récupère l'info sur le semestre (printemps ou automne, A ou P)
        $ap = substr($key, 0, 1);
        // On récupère l'année sur 2 chiffres
        $annee = substr($key, 1, 2);

        /* semestre d'automne, on regarde s'il n'y a pas un semestre de printemps avant de dispo */
        if ($ap == 'A')
        {
          // il existe un semestre de printemps pour la meme année
          if (isset($stats_by_sem['P' . $annee]))
          {
            // on le place dans la liste
            $stats_by_sem_sorted['P' . $annee] = $stats_by_sem['P' . $annee];
          }
          // dans tous les cas, on ajoute le semestre d'automne
          $stats_by_sem_sorted["A" . $annee] = $uvsemestre;
        }
        /* Si c'est un semestre de printemps, on l'ajoute à la liste, et au suivant ! */
        else
        $stats_by_sem_sorted['P' . $annee] = $uvsemestre;
      }
    }

    // affichage anti-chronologique mais fleme d'essayer de comprendre la fontion de tri
    if (count($stats_by_sem_sorted) > 0)
      $stats_by_sem_sorted = array_reverse($stats_by_sem_sorted);

    $first = 0;
    if (count($stats_by_sem_sorted) > 0)
    {
      foreach ($stats_by_sem_sorted as $key => $semestre)
      {
        $ap = substr($key, 0, 1);
        $annee = substr($key, 1, 2);
        if ($ap == "A")
          $sm = "d'Automne ";
        else
          $sm = "de Printemps ";

        $sm .= $annee;

        //$cts->add_title(3, "Semestre " . $sm);
        $table = new sqltable('details_uv', "Semestre " . $sm, $semestre, "./index.php?semestre=$key",
                              "id_uv",
                              array("code_uv" => "Code de l'UV",
                                    "intitule_uv" => "Intitulé de l'UV",
                                    "uv_cat"      => "Catégorie de l'UV",
                                    "note_obtention"=> "Note d'obtention",
                                    "ects_uv"     => "Crédits ECTS",
                                    "comment" => ""),
                              array ("delete" => "Enlever"),
                              array(), array(), false);

       $cts->add($table, true, false, "res_".$ap.$annee, false, true, !($first++), true);
      }
    }
    if ($totcreds > 0)
    {
      $cts->add_title(2, "Récapitulatif");
      $cts->add_paragraph("<b>".$totcreds .
            " crédits ECTS</b> obtenus au long de votre scolarité.");
      if ($etu->a_fait_tc())
      {
        $a_fait_tc = true;
        $rem = 240 - $totcreds;

        $cts->add_paragraph("Ayant fait le TC, il vous faut <b>240 crédits</b> (art. V-3 du réglement ".
          "des études) pour achever votre cursus.");
        if ($rem > 0)
          $cts->add_paragraph("Il vous manque <b>". $rem . " crédits</b>");
        else
          $cts->add_paragraph("Vous disposez d'un surplus de <b>". abs($rem) . " crédits</b>");

      }
      else if ($etu->departement != 'tc')
      {
        $cts->add_paragraph("Etant entré en branche, il faut <b>120 crédits</b> (art. V-3 du réglement ".
          "des études) pour achever votre cursus.");

        $rem = 120 - $totcreds;
        if ($rem > 0)
          $cts->add_paragraph("Il vous manque <b>". $rem . " crédits</b>");
        else
          $cts->add_paragraph("Vous avez un surplus de <b>". abs($rem) . " crédits</b>");
      }

      /* étudiant de TC */
      else
      {
        $cts->add_paragraph("Vous êtes en TC. Il vous faut par conséquent <b>102 crédits</b> en 3 ou 4 ".
                            "semestres, ou bien <b>120 crédits</b> en cas de semestre(s) supplémentaire(s).");

        if ($etu->semestre > 4)
        {
          $rem = 120 - $totcreds;
          if ($rem > 0)
            $cts->add_paragraph("Il vous manque <b>" . $rem . " crédits</b> pour pouvoir entrer en branche.");
          else
            $cts->add_paragraph("Vous avez un surplus de <b>" . abs($rem) . " crédits</b>.");
        }
        else
        {
          $rem = 102 - $totcreds;
          if ($rem > 0)
            $cts->add_paragraph("Il vous manque <b>" . $rem . " crédits</b> pour pouvoir entrer en branche.");
          else
            $cts->add_paragraph("Vous êtes en surplus de <b>" . $rem . " crédits</b>.");
        }
      }
      /* statistiques par catégories */
      if (count($stats_by_cat) > 0)
      {
        $cts->add_title(2, "Statistiques par catégories d'UVs");

        global $uv_descr_cat;

        foreach ($stats_by_cat as $key => $array)
        {
          $cts->add_title(3, "Catégorie " . $uv_descr_cat[$key]);
          $cts->add(new sqltable('details_uv', "", $array, "./index.php?semestre=$key",
                                 "id_uv",
                                 array("code_uv" => "Code de l'UV",
                                       "intitule_uv" => "Intitulé de l'UV",
                                       "uv_cat"      => "Catégorie de l'UV",
                                       "note_obtention"=> "Note d'obtention",
                                       "ects_uv"     => "Crédits ECTS"),
                                       array (),
                                       array()));
          $totcreds_by_cat = 0;
          $totcreds_by_cat_tc = 0;

          foreach ($array as $uv)
          {
            if (($uv['note_obtention'] == 'F') || ($uv['note_obtention'] == 'Fx'))
              continue;

            $totcreds_by_cat += $uv['ects_uv'];

            if ($uv['id_dept'] == 'TC')
              $totcreds_by_cat_tc += $uv['ects_uv'];
          }
          $cts->add_paragraph("Soit un total de <b>".$totcreds_by_cat." crédits ECTS</b> dans cette catégorie.");
          if ($a_fait_tc)
          {
            if (($key == 'CS') || ($key == 'TM'))
            {
              $cts->add_paragraph("Vous avez fait le TC. Il vous faut au moins <b>30 crédits</b> dans cette ".
                                  "catégorie, obtenus via des UVs de branche");
              $cts->add_paragraph("<b>" . ($totcreds_by_cat - $totcreds_by_cat_tc) . " crédits</b> obtenus.");
            }
            else if ($key == 'CG')
            {
              $cts->add_paragraph("Vous avez fait le TC. Il vous faut au moins <b>32 crédits</b> dans cette ".
                                  "catégorie.");
              $cts->add_paragraph("<b>" . $totcreds_by_cat . " crédits</b> obtenus.");
            }
            else if ($key == 'EC')
            {
              $cts->add_paragraph("Vous avez fait le TC. Il vous faut au moins <b>20 crédits</b> dans cette ".
                                 "catégorie.");
              $cts->add_paragraph("<b>" . $totcreds_by_cat . " crédits</b> obtenus.");
            }
          } // fin étudiant ayant fait TC
          else if ($etu->departement != 'tc') // etudiant de branche sans TC
          {
            if (($key == 'CS') || ($key == 'TM'))
            {
              $cts->add_paragraph("Il vous faut au moins <b>30 crédits</b> dans cette ".
                                  "catégorie");
              $cts->add_paragraph("<b>" . $totcreds_by_cat . " crédits</b> obtenus.");
            }
            else if ($key == 'CG')
            {
              $cts->add_paragraph("Il vous faut au moins <b>16 crédits</b> dans cette ".
                                  "catégorie.");
              $cts->add_paragraph("<b>" . $totcreds_by_cat . " crédits</b> obtenus.");
            }
            else if ($key == 'EC')
            {
              $cts->add_paragraph("Il vous faut au moins <b>12 crédits</b> dans cette ".
                                  "catégorie.");
              $cts->add_paragraph("<b>" . $totcreds_by_cat . " crédits</b> obtenus.");
            }

          } // fin étudiants branche sans TC
          else // etudiant TC
          {
            if ($key == 'CS')
            {
              $cts->add_paragraph("Il vous faut au moins <b>48 crédits</b> dans cette ".
                                  "catégorie");
              $cts->add_paragraph("<b>" . $totcreds_by_cat . " crédits</b> obtenus.");
            }
            else if ($key == 'TM')
            {
              $cts->add_paragraph("Il vous faut au moins <b>24 crédits</b> dans cette ".
                                  "catégorie");
              $cts->add_paragraph("<b>" . $totcreds_by_cat . " crédits</b> obtenus.");
            }
            else if (($key == 'CG') || ($key == 'EC'))
            {
              $cts->add_paragraph("Il vous faut au moins <b>24 crédits</b> dans les deux ".
                                  "catégories de culture générale (CG et EC).");
              $cts->add_paragraph("<b>" . $totcreds_by_cat . " crédits</b> obtenus dans cette catégorie.");
            }
          }
        }
      }
    } // todcreds > 0

    if ( $camembert == true)
    {
      global $topdir;
      require_once($topdir . "include/graph.inc.php");

      if (count($statsobs) > 0)
      {
        $cam = new camembert(600,400,array(),2,0,0,0,0,0,0,10,150);
        foreach ($statsobs as $key => $nbuvobt)
        {
          $cam->data($nbuvobt, $key);
        }
      }
      else
        $cam = new camembert(10,10,array(),2,0,0,0,0,0,0,0,0);
      return $cam;
    }
  }
  elseif( $camembert == true )
  {
    global $topdir;
    require_once($topdir . "include/graph.inc.php");
    return new camembert(10,10,array(),2,0,0,0,0,0,0,0,0);
  }

  $cts->add_paragraph("<br/>");

  return $cts;
}

/**
 * Fonction retournant un contents (@see stdcontents) décrivant la
 * boite du site propre à la partie pédagogie.
 *
 */
function get_uvsmenu_box()
{
  global $departements;
  global $site;

  $cts = new contents("Pédagogie");
  $dpt = new itemlist("<a href=\"uvs.php\" title=\"Toutes les UV\">Accéder aux UV</a>");

  foreach ($departements as $dpt_key)
    $dpt->add("<a href=\"uvs.php?iddept=".$dpt_key."\">".$dpt_key."</a>");


  $cts->add($dpt, true);

  $outils = new itemlist("Outils", false, array("<a href=\"edt.php\" title=\"Gérer vos emploi du temps\">Emploi du temps</a>",
                                                "<a href=\"profils.php\" title=\"Toutes les UV\">Profils</a>"));
  $cts->add($outils, true);

  if( $site->user->is_in_group("etudiants-utbm-actuels") )
  {
    $sql = new requete($site->db, "SELECT id_uv, id_comment, code_uv, surnom_utbm
                                    FROM edu_uv_comments
                                    NATURAL JOIN edu_uv
                                    NATURAL JOIN utl_etu_utbm
                                    ORDER BY date_commentaire DESC
                                    LIMIT 5
                                    ");

    $avis = new itemlist("Les derniers commentaires");

    while( $row = $sql->get_row() )
      $avis->add("<a href=\"uvs.php?view=commentaires&id_uv=".$row['id_uv']."#cmt_".$row['id_comment']."\">".$row['code_uv']."  par ".$row['surnom_utbm']."</a>");

    $cts->add($avis, true);
  }

  return $cts;
}


?>
