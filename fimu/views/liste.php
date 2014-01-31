<?php

$site = $request->attributes->get('site');
$site->start_page("services", "FIMU 2011 - Inscriptions des bénévoles");

$cts = new contents("Festival International de Musique Universitaire");

$site->set_side_boxes("left",array());

$tbl = new sqltable(
  "fimu_benevoles",
  "Liste des personnes s'étant inscrites pour le FIMU via le site de l'AE",
  $sql,
  "index.php",
  "utilisateurs.id_utilisateur",
  array(
    "=num"                 => "N°",
    "nom_utilisateur"      => "Utilisateur",
    "portable_utilisateur" => "Tel",
    "email_utilisateur"    => "Mail",
    "adresse_utilisateur"  => "Adresse",
    "jour1"                => "Jeudi",
    "jour2"                => "Vendredi",
    "jour3"                => "Samedi",
    "jour4"                => "Dimanche",
    "jour5"                => "Lundi",
    "jour6"                => "Mardi",
    "choix1_choix"         => "Choix 1",
    "choix1_com"           => "Commentaire",
    "choix2_choix"         => "Choix 2",
    "choix2_com"           => "Commentaire",
    "lang1_lang"           => "Langue 1",
    "lang2_lang"           => "Langue 2",
    "lang3_lang"           => "Langue 3",
    "poste_preced"         => "Precedent",
    "remarques"            => "Remarques"),
  array(),
  array(),
  array()
);

$cts->add($tbl,true);

$site->add_contents($cts);
$site->end_page();