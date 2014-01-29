<?php

$topdir = __DIR__ . '/../';

require_once __DIR__ . "/../include/site.inc.php";
require_once __DIR__ . "/../include/entities/ville.inc.php";
require_once __DIR__ . "/../include/entities/pays.inc.php";

function get_recherche_form($site) {
  $frm = new form("mmtprofil","/matmatronch/index.php/recherche",true,"GET","Recherche par profil");
  $frm->add_text_field("nom", "Nom");
  $frm->add_text_field("prenom", "Prenom");
  $frm->add_text_field("surnom", "Surnom");
  $frm->add_radiobox_field("sexe", "Sexe", array(1 => "Homme", 2 => "Femme", 0 => "Indifférent"), 0, -1);
  $frm->add_select_field("role", "Role", array_merge($GLOBALS["utbm_roles"], array("" => "Tous")), "etu");
  $frm->add_select_field("departement", "Departement", array_merge($GLOBALS["utbm_departements"], array("" => "Tous")), "");
  $frm->add_text_field("semestre", "Semestre", "");
  $frm->add_select_field("promo", "Promo", $site->user->liste_promos("Toutes"), 0);
  $frm->add_date_field("date_naissance", "Date de naissance");
  $frm->add_checkbox("inclus_ancien", "Inclure les anciens", false);
  $frm->add_checkbox("inclus_nutbm", "Inclure les non-utbm", false);
  $frm->add_submit("go", "Rechercher");

  return $frm;
}

function get_rechercheinversee_form($site) {
  $frm = new form("mmtinv", "/matmatronch/index.php/recherche-inversee", true, "GET", "Recherche inversée");
  $frm->add_text_field("numtel", "Numéro de téléphone");
  $frm->add_submit("go", "Rechercher");

  return $frm;
}

function get_recherchesimple_form($site) {
  $frm = new form("mmtpat", "/matmatronch/index.php/recherche-simple", true, "GET", "Recherche simple");
  $frm->add_text_field("pattern", "Nom/Prenom ou Surnom");
  $frm->add_submit("go", "Rechercher");

  return $frm;
}

function get_rechercheparville_form($site) {
  $ville = new ville($site->db);

  $pays = new pays($site->db);
  $pays->load_by_id(1);

  $frm = new form("mmtville", "/matmatronch/index.php/recherche-par-ville", true, "GET", "Recherche par ville");
  $frm->add_entity_smartselect ("id_pays", "Pays", $pays, true);
  $frm->add_entity_smartselect ("id_ville", "Ville", $ville, true, false, array('id_pays' => 'id_pays_id'), true);
  $frm->add_checkbox ("ville_parents", "Chercher aussi parmis les adresses des parents");
  $frm->add_submit("go", "Rechercher");

  return $frm;
}