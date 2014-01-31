<?php

$site = $request->attributes->get('site');
$site->start_page("services", "FIMU 2011 - Inscriptions des bénévoles");

$cts = new contents("Festival International de Musique Universitaire");

if ($site->user->is_in_group("gestion_fimu")) {
  $cts->add_paragraph("<a href=\"/fimu/index.php/liste\">Liste des inscrits</a>");
}

$intro = <<<INTRO
  <b>26ème FIMU : les 26, 27 et 28 Mai 2012</b>
  <br />
  <br />
  L'AE vous permet de vous inscrire en ligne pour être bénévole au FIMU 2012. Le formulaire suivant est la copie conforme de la feuille que vous pourrez trouver dans les points de distribution.
  <br />
  <br />
  Les informations personnelles (telles que votre nom, prénom, adresse...) seront remplies à partir de vos informations Matmatronch', vous n'avez plus qu'à indiquer vos disponibilités et vos souhaits d'affectation.
  <br />
  <br />
  Pour plus d'informations sur les différents postes disponible pendant le FIMU, <a href="//ae.utbm.fr/article.php?name=fimu_info">rendez vous ici</a>.
  <br />
  <br />
  L'AE, Com'Et, les Belfortains, la Région et certainement une bonne moitié de la planète vous remercient de votre implication dans cet évenement, qui n'existerait pas sans le bénévolat étudiant.
  <br />
  <br />
    <i>Votre inscription implique une diffusion de vos informations personnelles à l'organisation du FIMU.</i>
  <br />
  <hr />
INTRO;

$cts->add_paragraph($intro);

if($site->user->is_valid()) {
  $site->user->load_all_extra();

  $cts->add(new userinfo($site->user, true, false, false, false, true, true), false, true, "Informations personnelles");
  $cts->add_paragraph("<hr />");

  if($user_is_inscrit) {
    $cts->add_paragraph("Nous vous remercions de votre impressionante volonté d'implication dans le FIMU, cependant vous vous êtes déjà inscrit.");
    $cts->add_paragraph("Si vous souhaitez effectuer une modification dans votre inscription, contactez les administrateurs du site");
  } else {
    $cts->add($inscription_form,true);
  }
} else {
  $cts->add_paragraph("<span style='color:red;'>Pour accéder au formulaire d'inscription, veuillez-vous connecter.</span>");
}

$cts->add_paragraph("<br /><br />Le FIMU est un évenement co-organisé par la Ville de Belfort, la Fédération Com'Et et l'UTBM");
$cts->add_paragraph(
  "Pour plus d'information : <a href='http://www.fimu.com'>www.fimu.com</a> <br />
      Pôle Musique : 03 84 54 25 81<br />
      Com'Et : 03 84 26 48 01 <br />
      Renseignement auprès de l'AE ");

$site->add_contents($cts);
$site->end_page ();