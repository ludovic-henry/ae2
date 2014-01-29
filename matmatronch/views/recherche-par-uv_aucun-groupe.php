<?php

$topdir = __DIR__ . "/../../";

require_once __DIR__ . "/../../include/site.inc.php";

$cts = new contents("Recherche Mat'Matronch");

$site->add_css('css/mmt.css');
$site->start_page('matmatronch','MatMaTronch');

$cts->add_title(2, "Résultat : Aucun groupe n'a été trouvé");
$cts->add_paragraph("Aucun groupe n'a été trouvé ($semestre).");

$site->add_contents($cts);
$site->end_page();