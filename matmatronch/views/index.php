<?php

$topdir = __DIR__ . "/../../";

require_once __DIR__ . "/../../include/site.inc.php";

$info = new contents("Mat'Matronch");
$info->add_paragraph("Le Mat'Matronch donne enfin un nom a un visage, le numero de portable du binome fantome, l'adresse de ce confrere que l'on recherche tant depuis cette fameuse soiree ...");

$list = new itemlist("Voir aussi");
$list->add("<a href=\"javascript:window.external.AddSearchProvider('http://ae.utbm.fr/matmatronch/static/matmatronch.xml');\">Extension Firefox</a>");
$list->add("La version mobile : <a href=\"http://ae.utbm.fr/m/\">http://ae.utbm.fr/m/</a> <a href=\"/iinfo.php\">Informations</a>");
$info->add($list,true);

$list = new itemlist("Aide");
$list->add("<a href=\"/article.php?name=docs:matmatronch\">Matmatronch</a>");
$list->add("<a href=\"/article.php?name=docs:profil\">Profil personnel</a>");
$info->add($list,true);

$site->add_box("mmtinfo",$info);
$site->set_side_boxes("right",array("mmtinfo"),"mmt2_right");

$site->add_css("css/mmt.css");
$site->start_page("matmatronch","MatMaTronch");

$cts = new contents("Recherche Mat'Matronch");

foreach ($forms as $form) {
  $cts->add($form, true);
}

$site->add_contents($cts);
$site->end_page();