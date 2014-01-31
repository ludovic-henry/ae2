<?php

$site = $request->attributes->get('site');
$site->start_page("services", "FIMU 2011 - Inscriptions des bénévoles");

$cts = new contents("Festival International de Musique Universitaire");

$cts->add_paragraph(
  "Une erreur est survenue <br/>
    erreur n°$sql->errno <br/>
    détail : $sql->errmsg <br/><br/>
    Merci de contacter les authorités compétentes");

$site->add_contents($cts);
$site->end_page();
