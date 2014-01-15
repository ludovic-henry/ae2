<?php

/* Copyright 2007
 * - Manuel Vonthron < manuel DOT vonthron AT acadis DOT org >
 * - Sarah Amsellem < sarah DOT amsellem AT gmail DOT com >
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

$topdir="../";
require_once($topdir. "include/site.inc.php");
require_once($topdir. "include/cts/sqltable.inc.php");
require_once($topdir. "include/cts/user.inc.php");


$site = new site;

$site->start_page ("accueil", "FIMU 2011 - Inscriptions des bénévoles");

$cts = new contents("Festival International de Musique Universitaire");

if ( $site->user->is_valid() )
{
  $sql = new requete($site->db, "SELECT id_utilisateur
                FROM fimu_inscr
                WHERE id_utilisateur = ".$site->user->id);
}

if(isset($_REQUEST['magicform']) && $_REQUEST['magicform']['name'] == "fimu_inscr" && !$sql->lines)
{
  $sql = new insert($site->dbrw, "fimu_inscr",
    array(
      "id_inscr" => '',
      "id_utilisateur" => $site->user->id,
      "jour1" => $_REQUEST['jour1'],
      "jour2" => $_REQUEST['jour2'],
      "jour3" => $_REQUEST['jour3'],
      "jour4" => $_REQUEST['jour4'],
      "jour5" => $_REQUEST['jour5'],
      "jour6" => $_REQUEST['jour6'],
      "choix1_choix" => $_REQUEST['choix1_choix'],
      "choix1_com" => $_REQUEST['choix1_com'],
      "choix2_choix" => $_REQUEST['choix2_choix'],
      "choix2_com" => $_REQUEST['choix2_com'],
      "lang1_lang" => $_REQUEST['lang1_lang'],
      "lang1_lvl" => $_REQUEST['lang1_lvl'],
      "lang1_com" => $_REQUEST['lang1_com'],
      "lang2_lang" => $_REQUEST['lang2_lang'],
      "lang2_lvl" => $_REQUEST['lang2_lvl'],
      "lang2_com" => $_REQUEST['lang2_com'],
      "lang3_lang" => $_REQUEST['lang3_lang'],
      "lang3_lvl" => $_REQUEST['lang3_lvl'],
      "lang3_com" => $_REQUEST['lang3_com'],
      "permis" => $_REQUEST['permis'],
      "voiture" => $_REQUEST['voiture'],
      "afps" => $_REQUEST['afps'],
      "afps_com" => $_REQUEST['afps_com'],
      "poste_preced" => $_REQUEST['poste_preced'],
      "remarques" => $_REQUEST['remarques']
    )
    );
  if($sql->result)
    $cts->add_paragraph("Votre inscription s'est correctement déroulée, ".$site->user->prenom." ". $site->user->nom." <br />
          Nous vous remercions de votre implication. <br />
          A présent si vous ne savez pas quoi faire nous vous conseillons cet excellent <a href='http://fr.wikipedia.org/wiki/M%C3%A9sopotamie'>article sur la Mésopotamie</a>");
  else
    $cts->add_paragraph("Une erreur est survenue <br />
        erreur n°$sql->errno <br />
        détail : $sql->errmsg <br /><br />
        Merci de contacter les authorités compétentes ");

}
else if (isset($_REQUEST['listing']) && ($site->user->is_in_group("gestion_ae") || $site->user->is_in_group("gestion_fimu")))
{

//  $tbl = new itemlist("Liste des personnes s'étant inscrites pour le FIMU via le site de l'AE", false);
  $site->set_side_boxes("left",array());

  $sql = new requete($site->db, "SELECT fimu_inscr.id_utilisateur,
            utilisateurs.nom_utl,
            utilisateurs.prenom_utl,
            utilisateurs.id_utilisateur,
            utilisateurs.email_utl AS email_utilisateur,
            utilisateurs.tel_portable_utl AS portable_utilisateur,
            fimu_inscr.jour1,
            fimu_inscr.jour2,
            fimu_inscr.jour3,
            fimu_inscr.jour4,
            fimu_inscr.jour5,
            fimu_inscr.jour6,
            fimu_inscr.choix1_choix,
            fimu_inscr.choix1_com,
            fimu_inscr.choix2_choix,
            fimu_inscr.choix2_com,
            fimu_inscr.lang1_lang,
            fimu_inscr.lang2_lang,
            fimu_inscr.lang3_lang,
            fimu_inscr.poste_preced,
            fimu_inscr.remarques,
          CONCAT(utilisateurs.prenom_utl,' ',utilisateurs.nom_utl) AS `nom_utilisateur`,
          CONCAT(utilisateurs.addresse_utl,' ',villes.cpostal_ville,' ',villes.nom_ville) AS adresse_utilisateur
          FROM fimu_inscr
          LEFT JOIN utilisateurs
          ON fimu_inscr.id_utilisateur = utilisateurs.id_utilisateur
          LEFT JOIN villes USING (id_ville)");

  $tbl = new sqltable("fimu_benevoles",
        "Liste des personnes s'étant inscrites pour le FIMU via le site de l'AE",
        $sql,
        "index.php",
        "utilisateurs.id_utilisateur",
        array("=num" => "N°",
          "nom_utilisateur" => "Utilisateur",
          "portable_utilisateur" => "Tel",
          "email_utilisateur" => "Mail",
          "adresse_utilisateur" => "Adresse",
          "jour1" => "Jeudi",
          "jour2" => "Vendredi",
          "jour3" => "Samedi",
          "jour4" => "Dimanche",
          "jour5" => "Lundi",
          "jour6" => "Mardi",
          "choix1_choix" => "Choix 1",
          "choix1_com" => "Commentaire",
          "choix2_choix" => "Choix 2",
          "choix2_com" => "Commentaire",
          "lang1_lang" => "Langue 1",
          "lang2_lang" => "Langue 2",
          "lang3_lang" => "Langue 3",
          "poste_preced" => "Precedent",
          "remarques" => "Remarques"
          ),
        array(),
        array(),
        array()
        );
  $cts->add($tbl,true);

}
else
{

/*******************************************************************
 * Start fimu_inscr form
 */

  //$site->error_forbidden("accueil","reserved"); //Commenter pour activer les inscriptions

  if ($site->user->is_in_group("gestion_fimu"))
    $cts->add_paragraph("<a href=\"?listing\">Liste des inscrits</a>");

  $intro = "
  <b>26ème FIMU : les 26, 27 et 28 Mai 2012</b>
<br />
<br />
  L'AE vous permet de vous inscrire en ligne pour être bénévole au FIMU 2012. Le formulaire suivant est la copie conforme de la feuille que vous pourrez trouver dans les points de distribution.
<br />
<br />
  Les informations personnelles (telles que votre nom, prénom, adresse...) seront remplies à partir de vos informations Matmatronch', vous n'avez plus qu'à indiquer vos disponibilités et vos souhaits d'affectation.
<br />
<br />
  Pour plus d'informations sur les différents postes disponible pendant le FIMU, <a href=\"http://ae.utbm.fr/article.php?name=fimu_info\">rendez vous ici</a>.
<br />
<br />
  L'AE, Com'Et, les Belfortains, la Région et certainement une bonne moitié de la planète vous remercient de votre implication dans cet évenement, qui n'existerait pas sans le bénévolat étudiant.
<br />
<br />
    <i>Votre inscription implique une diffusion de vos informations personnelles à l'organisation du FIMU.</i>
<br />
<hr />
  ";

  $cts->add_paragraph($intro);

  if( $site->user->is_valid() )
  {
    $site->user->load_all_extra();
    $usrinfo = new userinfo($site->user, true, false, false, false, true, true);
    $cts->add($usrinfo, false, true, "Informations personnelles");
    $trait = "<hr />";
    $cts->add_paragraph($trait);

  /* Prévention des doublons */
  $sql = new requete($site->db, "SELECT id_utilisateur
          FROM fimu_inscr
          WHERE id_utilisateur = ".$site->user->id);
  if($sql->lines)
  {
    $cts->add_paragraph("Nous vous remercions de votre impressionante volonté d'implication dans le FIMU, cependant vous vous êtes déjà inscrit.");
    $cts->add_paragraph("Si vous souhaitez effectuer une modification dans votre inscription, contactez les administrateurs du site");
  }
  else
  {

  /* Start form */

  $frm = new form("fimu_inscr", "index.php", true, "POST", "Inscription");
  $frm->allow_only_one_usage();

  $subfrm = new form("fimu_inscr", "index.php", true, "POST", "Disponibilités");
    //$subfrm->add_info("Il est fortement souhaitable que vous soyez disponible 3 jours consécutifs minimum");
    $subfrm->add_checkbox("jour1", "Jeudi 24 Mai");
    $subfrm->add_checkbox("jour2", "Vendredi 25 Mai");
    $subfrm->add_checkbox("jour3", "Samedi 26 Mai");
    $subfrm->add_checkbox("jour4", "Dimanche 27 Mai");
    $subfrm->add_checkbox("jour5", "Lundi 28 Mai");
    $subfrm->add_checkbox("jour6", "Mardi 29 Mai");
  $frm->add($subfrm);

  $subfrm = new form("fimu_inscr", "index.php", true, "POST", "<a href='http://ae.utbm.fr/article.php?name=fimu_info'>Souhaits de poste <img src='$topdir/images/tipp.png' /></a>");

    $prefs = array("pilote" => "Pilote de groupe", "regisseur" => "Régisseur de scène", "accueil" => "Accueil du public", "autres" => "Autres");

    $subfrm2 = new form("fimu_inscr", "index.php");
      $subfrm2->add_select_field("choix1_choix", "Choix 1", $prefs);
      $subfrm2->add_text_field("choix1_com", "Commentaire", "", false, 63);
    $subfrm->add($subfrm2, false, false, false, false, true);

    $subfrm2 = new form("fimu_inscr", "index.php");
      $subfrm2->add_select_field("choix2_choix", "Choix 2", $prefs);
      $subfrm2->add_text_field("choix2_com", "Commentaire", "", false, 63);
    $subfrm->add($subfrm2, false, false, false, false, true);

  $frm->add($subfrm);

  $subfrm = new form("fimu_inscr", "index.php", true, "POST", "Langues parlées");

    $subfrm2 = new form("fimu_inscr", "index.php");
      $subfrm2->add_text_field("lang1_lang", "Langue 1");
      $subfrm2->add_text_field("lang1_lvl", "Niveau", "", false, 10);
      $subfrm2->add_text_field("lang1_com", "Commentaire", "", false, 40);
    $subfrm->add($subfrm2, false, false, false, false, true);

    $subfrm2 = new form("fimu_inscr", "index.php");
      $subfrm2->add_text_field("lang2_lang", "Langue 2");
      $subfrm2->add_text_field("lang2_lvl", "Niveau", "", false, 10);
      $subfrm2->add_text_field("lang2_com", "Commentaire", "", false, 40);
    $subfrm->add($subfrm2, false, false, false, false, true);

    $subfrm2 = new form("fimu_inscr", "index.php");
      $subfrm2->add_text_field("lang3_lang", "Langue 3");
      $subfrm2->add_text_field("lang3_lvl", "Niveau", "", false, 10);
      $subfrm2->add_text_field("lang3_com", "Commentaire", "", false, 40);
    $subfrm->add($subfrm2, false, false, false, false, true);

  $frm->add($subfrm);

  $ouinon = array('O' => "Oui", 'N' => "Non");
  $subfrm = new form("fimu_inscr", "index.php", true, "POST", "Autres renseignements");
    $subfrm->add_radiobox_field("permis", "Possession du permis de conduire", $ouinon, "N");
    $subfrm->add_radiobox_field("voiture", "Possession d'une voiture personnelle", $ouinon, "N");

    $subfrm2 = new form("fimu_inscr", "index.php");
      $subfrm2->add_radiobox_field("afps", "Titulaire d'un diplôme de premiers secours (PSC1,...)", $ouinon, "N");
      $subfrm2->add_text_field("type_afps", "Lequel", "", false, 35);
    $subfrm->add($subfrm2, false, false, false, false, true);

    $subfrm->add_text_field("poste_preced", "Poste(s) aux précédents FIMU", "", false, 43);
    $subfrm->add_text_area("remarques", "Remarques/suggestions");



  $frm->add($subfrm);

  $frm->add_submit("valid","Valider");



$cts->add($frm,true);

} //fin condition prevention doublons
  }
else
{
  $mess = "<span style='color:red;'>Pour accéder au formulaire d'inscription, veuillez-vous connecter.</span>";
  $cts->add_paragraph($mess);
}

$cts->add_paragraph("<br /><br />Le FIMU est un évenement co-organisé par la Ville de Belfort, la Fédération Com'Et et l'UTBM");
$cts->add_paragraph("Pour plus d'information : <a href='http://www.fimu.com'>www.fimu.com</a> <br />
      Pôle Musique : 03 84 54 25 81<br />
      Com'Et : 03 84 26 48 01 <br />
      Renseignement auprès de l'AE ");

}

$site->add_contents($cts);

$site->end_page ();

?>
