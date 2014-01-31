<?php

$site = $request->attributes->get('site');
$site->start_page("services", "FIMU 2011 - Inscriptions des bénévoles");

$cts = new contents("Festival International de Musique Universitaire");

$cts->add_paragraph(
  "Votre inscription s'est correctement déroulée, " . $site->user->prenom . " " . $site->user->nom . " <br/>
      Nous vous remercions de votre implication. <br/>
      A présent si vous ne savez pas quoi faire nous vous conseillons cet excellent <a href='http://fr.wikipedia.org/wiki/M%C3%A9sopotamie'>article sur la Mésopotamie</a>");

$site->add_contents($cts);
$site->end_page();
