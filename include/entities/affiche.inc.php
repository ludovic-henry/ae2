<?php
/* Copyright 2010
 * - Julien Etelain < julien at pmad dot net >
 * - Pierre Mauduit <pierre POINT mauduit CHEZ utbm POINT fr>
 * - Mathieu Briand < briandmathieu at hyprua dot org >
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
 */

/** @file
 * Gestion des affiches
 *
 */

/**
 * Nouvelle du site
 */
class affiche extends stdentity
{
  /** Id de l'affiche */
  var $id;

  /** Auteur de l'affiche */
  var $id_utilisateur;

  /** Association/club concerné */
  var $id_asso;

  /** Titre */
  var $titre;

  /** Le fichier lié */
  var $id_file;

  /** Date de début */
  var $date_deb;

  /** Date de fin */
  var $date_fin;

  /** Date de modication */
  var $date;

  /** Etat de modération: true modéré, false non modéré */
  var $modere;

  /** Utilisateur ayant modéré l'affiche */
  var $id_utilisateur_moderateur;

  /** Plages horaires de l'affiche */
  var $horaires;

  /** Fréquence d'affichage */
  var $frequence;

  /** Charge une affiche en fonction de son id
   * $this->id est égal à null en cas d'erreur
   * @param $id id de la fonction
   */
  function load_by_id ( $id )
  {
    $req = new requete($this->db, "SELECT * FROM `aff_affiches`
        WHERE `id_affiche` = '" .
           mysql_real_escape_string($id) . "'
        LIMIT 1");

    if ( $req->lines == 1 )
    {
      $this->_load($req->get_row());
      return true;
    }

    $this->id = null;
    return false;
  }

  /*
   * fonction de chargement (privee)
   *
   * @param row tableau associatif
   * contenant les informations sur l'affiche.
   *
   */
  function _load ( $row )
  {
    $this->id      = $row['id_affiche'];
    $this->id_utilisateur  = $row['id_utilisateur'];
    $this->id_asso    = $row['id_asso'];
    $this->titre      = $row['titre_aff'];
    $this->id_file    = $row['id_file'];
    $this->date_deb   = strtotime($row['date_deb']);
    $this->date_fin    = strtotime($row['date_fin']);
    $this->date        = strtotime($row['date_aff']);
    $this->modere      = $row['modere_aff'];
    $this->id_utilisateur_moderateur  = $row['id_utilisateur_moderateur'];
    $this->horaires   = $row['horaires_aff'];
    $this->frequence  = $row['frequence_aff'];
  }

  /** Construit un stdcontents avec l'affiche
   */
  function get_contents ()
  {
    $image = new image($this->titre, "http://ae.utbm.fr/d.php?action=download&download=preview&id_file=".$this->id_file);

    $cts = new contents("Affiche : ".$this->titre);
    $cts->add($image);
    $cts->add_paragraph("Affichée du ".textual_plage_horraire($this->date_deb, $this->date_fin));

    return $cts;
  }

  /** Supprime l'affiche
   */
  function delete ()
  {
    if ( !$this->dbrw ) return;
    if (($this->date_deb <= time()) && $this->modere)
      $this->expire();
    else
      new delete($this->dbrw,"aff_affiches",array("id_affiche"=>$this->id));
    $this->id = null;
  }

  /** Fait expirer une affiche
  */

  function expire()
  {
    $this->date_fin = time;

    $req = new update ($this->dbrw,
           "aff_affiches",
           array ("date_modifie" => date("Y-m-d H:i:s"),
            "date_fin" => date("Y-m-d H:i:s")
            ),
         array(
           "id_affiche"=>$this->id
           ));
  }

  /** Valide l'affiche
   */
  function validate($id_utilisateur_moderateur)
  {
    if ( !$this->dbrw ) return;
    new update($this->dbrw,"aff_affiches",array("modere_aff"=>1,"id_utilisateur_moderateur"=>$id_utilisateur_moderateur),array("id_affiche"=>$this->id));
    $this->modere_aff = 1;
    $this->id_utilisateur_moderateur = $id_utilisateur_moderateur;
  }

  /** Invalide l'affiche
   */
  function unvalidate()
  {
    if ( !$this->dbrw ) return;
    new update($this->dbrw,"aff_affiches",array("modere_aff"=>0),array("id_affiche"=>$this->id));
    $this->modere_aff = 0;
  }


  /** @brief Ajoute une affiche
   *
   * @param id_utilisateur l'identifiant de l'utilisateur
   * @param id_asso (facultatif) l'identifiant de l'association
   * @param titre titre de l'affiche
   * @param id_file id du fichier de l'affiche
   * @param date_deb début de la campagne d'affichage
   * @param date_fin fin de la campagne d'affichage
   *
   * @return true ou false en fonction du resultat
   */
  function add_affiche($id_utilisateur,
        $id_asso = null,
        $titre,
        $id_file,
        $date_deb,
        $date_fin,
        $horaires=0,
        $frequence=1)

  {
    if (!$this->dbrw)
      return false;

    $this->id_utilisateur = $id_utilisateur;
    $this->id_asso = $id_asso;
    $this->titre = $titre;
    $this->id_file = $id_file;
    $this->date_deb = $date_deb;
    $this->date_fin = $date_fin;
    $this->horaires = $horaires;
    $this->frequence = $frequence;

    $req = new insert ($this->dbrw,
           "aff_affiches",
           array ("id_utilisateur" => $id_utilisateur,
            "id_asso" => $id_asso,
            "titre_aff" => $titre,
            "id_file" => $id_file,
            "date_modifie" => date("Y-m-d H:i:s"),
            "date_deb" => date("Y-m-d H:i:s", $date_deb),
            "date_fin" => date("Y-m-d H:i:s", $date_fin),
            "modere_aff" =>  false,
            "id_utilisateur_moderateur"=>null,
            "horaires_aff" =>$horaires,
            "frequence_aff" =>$frequence,
            ));

    if ( $req )
      $this->id = $req->get_id();
    else
      $this->id = null;

    return ($req != false);
  }


  /**
   * Modifie l'affiche
   *
   */
  function save_affiche(
        $id_asso = null,
        $titre,
        $date_deb,
        $date_fin,
        $modere=false,
        $id_utilisateur_moderateur=null,
        $horaires=0,
        $frequence=1)
  {
    if (!$this->dbrw)
      return false;

    $this->id_asso = $id_asso;
    $this->titre = $titre;
    $this->date_deb = $date_deb;
    $this->date_fin = $date_fin;
    $this->modere = $modere;
    $this->id_utilisateur_moderateur = $id_utilisateur_moderateur;
    $this->horaires = $horaires;
    $this->frequence = $frequence;

    $req = new update ($this->dbrw,
           "aff_affiches",
           array ( "id_asso" => $id_asso,
            "titre_aff" => $titre,
            "date_modifie" => date("Y-m-d H:i:s"),
            "date_deb" => date("Y-m-d H:i:s", $date_deb),
            "date_fin" => date("Y-m-d H:i:s", $date_fin),
            "modere_aff" => $modere,
            "id_utilisateur_moderateur"=>$id_utilisateur_moderateur,
            "horaires_aff" =>$horaires,
            "frequence_aff" =>$frequence,
            ),
         array(
           "id_affiche"=>$this->id
           ));
  }

  /* Renvoie un sqltable des affiches que l'utilisateur peut modifier
  */
  function get_html_list($user){
    $where = "";
    if ( !$user->is_in_group("moderateur_site") && !$user->is_in_group("bdf-bureau") )
      $where = "AND (`id_utilisateur` = '".$user->id."'
              OR `id_asso` IN (".$user->get_assos_csv(ROLEASSO_MEMBREBUREAU)."))";

    $req = new requete($this->db, "SELECT aff_affiches.*,
         CONCAT(`utilisateurs`.`prenom_utl`,
            ' ',
            `utilisateurs`.`nom_utl`) AS `nom_utilisateur`
        FROM `aff_affiches`
        INNER JOIN `utilisateurs` USING (id_utilisateur)
        WHERE `date_fin` > NOW()" .
        $where .
        "ORDER BY date_deb, date_fin");
    $tbl = new sqltable(
      "listaff",
      "Campagnes d'affichage en cours ou à venir",
      $req,
      "affiches.php",
      "id_affiche",
      array("titre_aff"=>"Titre", "nom_utilisateur"=>"Auteur", "date_deb"=>"Début", "date_fin"=>"Fin", "horaires_aff"=>"Horaires", "frequence_aff"=>"Fréquence"),
      ($user->is_in_group("moderateur_site")) ? array("view" => "Voir", "increase"=>"Augmenter la fréquence", "decrease"=>"Diminuer la fréquence", "delete" => "Supprimer") : array("view" => "Voir", "delete" => "Supprimer"),
      array(),
      array("horaires_aff" => array(0=>"Toute la journée", 1=>"Entre 8h et 12h", 2=>"Entre 11h30 et 14h", 3=>"Entre 12h et 18h", 4=>"Entre 18h et 6h"))
      );

    return $tbl;
  }

  /* Vérifie si un changement à eu lieu depuis 'last'
  */
  function check_update($last){
    $plages_horaires = array(1=>array(28800, 43200), 2=>array(41400, 50400), 3=>array(43200, 64800), 4=>array(64800, 21600));

    eval("\$time=".strftime("%H*3600+%M*60+%S;", strtotime($last)));
    $last_plages = array(0);
    foreach($plages_horaires as $id => $plage)
      if (($time >= $plage[0]) && ($time < $plage[1]))
        $last_plages[] = $id;

    eval("\$time=".strftime("%H*3600+%M*60+%S;"));
    $cur_plages = array(0);
    foreach($plages_horaires as $id => $plage)
      if (($time >= $plage[0]) && ($time < $plage[1]))
        $cur_plages[] = $id;

    $req = new requete($this->db, "SELECT COUNT(*) FROM `aff_affiches`
        WHERE (date_deb > '".$last."' AND date_deb < NOW())
        OR (date_fin > '".$last."' AND date_fin < NOW())
        OR (date_modifie > '".$last."' AND date_modifie < NOW())
        OR ((date_deb < NOW()) AND (date_fin > NOW())
          AND (((horaires_aff IN (".implode(",",$last_plages).")) AND (horaires_aff NOT IN (".implode(",",$cur_plages).")))
            OR ((horaires_aff NOT IN (".implode(",",$last_plages).")) AND (horaires_aff IN (".implode(",",$cur_plages).")))
          )
        )
        ");

    list($count_modif) = $req->get_row();

    return ($count_modif > 0);
  }

  /* Génère un pdf avec les affiches
   */
  function gen_pdf(){
    $plages_horaires = array(1=>array(28800, 43200), 2=>array(41400, 50400), 3=>array(43200, 64800), 4=>array(64800, 21600));
    eval("\$time=".strftime("%H*3600+%M*60+%S;"));
    $cur_plages = array(0);
    foreach($plages_horaires as $id => $plage)
      if (($time >= $plage[0]) && ($time < $plage[1]))
        $cur_plages[] = $id;

    $req = new requete($this->db, "SELECT id_file, frequence_aff FROM `aff_affiches`
        WHERE date_deb < NOW()
        AND date_fin > NOW()
        AND horaires_aff IN (".implode(",",$cur_plages).")
        AND modere_aff = '1'
        ORDER BY frequence_aff DESC");

    $file = new dfile($this->db, $this->dbrw);

    $fichiers = array();
    if ( $req->lines < 1 )
    {
      $file->load_by_id(5006);
      $fichiers[] = $file->get_real_filename();
    }
    else
    {
      $nb_aff = 0;
      while ($row = $req->get_row())
        $nb_aff += $row['frequence_aff'];

      $req->go_first();
      $i = 0;
      while ($row = $req->get_row())
      {
        for($n=0; $n < $row['frequence_aff']; $n++)
        {
          $j = $i + ($n * $nb_aff/$row['frequence_aff']);
          while(isset($fichiers[$j]))
            $j++;

          $file->load_by_id($row['id_file']);
          $fichiers[$j] = $file->get_real_filename();
        }
        $i++;
      }
    }

    ksort($fichiers);

    header("Content-Type: application/pdf");
    passthru("convert -density 300x300 ".implode(' ', $fichiers)." pdf:-");
  }

  /* Génère un fichier xml des affiches à afficher
   */
  function gen_xml(){
    $req = new requete($this->db, "SELECT id_file, frequence_aff, horaires_aff FROM `aff_affiches`
        WHERE date_deb < NOW()
        AND date_fin > NOW()
        AND modere_aff = '1'
        ORDER BY frequence_aff DESC");

    header("Content-Type: text/xml");
    print "<presentation>\n";

    $file = new dfile($this->db, $this->dbrw);

    $nbaff = 0;
    while ($row = $req->get_row())
    {
      $file->load_by_id($row['id_file']);

      if (! $file->modere)
        continue;

      $fichier = $file->id.'.'.$file->id_rev_file;
      print "  <affiche>\n";
      print "    <horaire>".$row['horaires_aff']."</horaire>\n";
      print "    <fichier>".$fichier."</fichier>\n";
      print "    <frequence>".$row['frequence_aff']."</frequence>\n";
      print "  </affiche>\n";

      $nbaff++;
    }
    if ( $nbaff < 1 )
    {
      $file->load_by_id(7838);
      $fichier = $file->id.'.'.$file->id_rev_file;
      print "  <affiche>\n";
      print "    <horaire>0</horaire>\n";
      print "    <fichier>".$fichier."</fichier>\n";
      print "    <frequence>1</frequence>\n";
      print "  </affiche>\n";
    }

    print "</presentation>\n";
  }

  function decrease_frequence()
  {
    if ($this->frequence > 0)
    {
      $this->frequence--;
      new update($this->dbrw,"aff_affiches",
          array("frequence_aff"=>$this->frequence, "date_modifie" => date("Y-m-d H:i:s"),),
          array("id_affiche"=>$this->id));
    }
  }

  function increase_frequence()
  {
    if ($this->frequence < 3)
    {
      $this->frequence++;
      new update($this->dbrw,"aff_affiches",
          array("frequence_aff"=>$this->frequence, "date_modifie" => date("Y-m-d H:i:s"),),
          array("id_affiche"=>$this->id));
    }
  }
}


?>
