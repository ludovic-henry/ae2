<?php

$topdir = __DIR__ . '/../';

require_once __DIR__ . "/../include/site.inc.php";

function get_inscription_form($site) {
  $frm = new form("fimu_inscr", "/fimu/index.php/inscription", true, "POST", "Inscription");
  $frm->allow_only_one_usage();

  $subfrm = new form("fimu_inscr", "/fimu/index.php/inscription", true, "POST", "Disponibilités");
  //$subfrm->add_info("Il est fortement souhaitable que vous soyez disponible 3 jours consécutifs minimum");
  $subfrm->add_checkbox("jour1", "Jeudi 24 Mai");
  $subfrm->add_checkbox("jour2", "Vendredi 25 Mai");
  $subfrm->add_checkbox("jour3", "Samedi 26 Mai");
  $subfrm->add_checkbox("jour4", "Dimanche 27 Mai");
  $subfrm->add_checkbox("jour5", "Lundi 28 Mai");
  $subfrm->add_checkbox("jour6", "Mardi 29 Mai");
  $frm->add($subfrm);

  $subfrm = new form("fimu_inscr", "/fimu/index.php/inscription", true, "POST", "<a href='http://ae.utbm.fr/article.php?name=fimu_info'>Souhaits de poste <img src='/images/tipp.png' /></a>");

  $prefs = array("pilote" => "Pilote de groupe", "regisseur" => "Régisseur de scène", "accueil" => "Accueil du public", "autres" => "Autres");

  $subfrm2 = new form("fimu_inscr", "/fimu/index.php/inscription");
  $subfrm2->add_select_field("choix1_choix", "Choix 1", $prefs);
  $subfrm2->add_text_field("choix1_com", "Commentaire", "", false, 63);
  $subfrm->add($subfrm2, false, false, false, false, true);

  $subfrm2 = new form("fimu_inscr", "/fimu/index.php/inscription");
  $subfrm2->add_select_field("choix2_choix", "Choix 2", $prefs);
  $subfrm2->add_text_field("choix2_com", "Commentaire", "", false, 63);
  $subfrm->add($subfrm2, false, false, false, false, true);

  $frm->add($subfrm);

  $subfrm = new form("fimu_inscr", "/fimu/index.php/inscription", true, "POST", "Langues parlées");

  $subfrm2 = new form("fimu_inscr", "index.php");
  $subfrm2->add_text_field("lang1_lang", "Langue 1");
  $subfrm2->add_text_field("lang1_lvl", "Niveau", "", false, 10);
  $subfrm2->add_text_field("lang1_com", "Commentaire", "", false, 40);
  $subfrm->add($subfrm2, false, false, false, false, true);

  $subfrm2 = new form("fimu_inscr", "/fimu/index.php/inscription");
  $subfrm2->add_text_field("lang2_lang", "Langue 2");
  $subfrm2->add_text_field("lang2_lvl", "Niveau", "", false, 10);
  $subfrm2->add_text_field("lang2_com", "Commentaire", "", false, 40);
  $subfrm->add($subfrm2, false, false, false, false, true);

  $subfrm2 = new form("fimu_inscr", "/fimu/index.php/inscription");
  $subfrm2->add_text_field("lang3_lang", "Langue 3");
  $subfrm2->add_text_field("lang3_lvl", "Niveau", "", false, 10);
  $subfrm2->add_text_field("lang3_com", "Commentaire", "", false, 40);
  $subfrm->add($subfrm2, false, false, false, false, true);

  $frm->add($subfrm);

  $subfrm = new form("fimu_inscr", "/fimu/index.php/inscription", true, "POST", "Autres renseignements");
  $subfrm->add_radiobox_field("permis", "Possession du permis de conduire", array('O' => "Oui", 'N' => "Non"), "N");
  $subfrm->add_radiobox_field("voiture", "Possession d'une voiture personnelle", array('O' => "Oui", 'N' => "Non"), "N");

  $subfrm2 = new form("fimu_inscr", "/fimu/index.php/inscription");
  $subfrm2->add_radiobox_field("afps", "Titulaire d'un diplôme de premiers secours (PSC1,...)", array('O' => "Oui", 'N' => "Non"), "N");
  $subfrm2->add_text_field("type_afps", "Lequel", "", false, 35);
  $subfrm->add($subfrm2, false, false, false, false, true);

  $subfrm->add_text_field("poste_preced", "Poste(s) aux précédents FIMU", "", false, 43);
  $subfrm->add_text_area("remarques", "Remarques/suggestions");

  $frm->add($subfrm);

  $frm->add_submit("valid","Valider");

  return $frm;
}