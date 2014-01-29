<?php

$topdir = __DIR__ . "/../../";

require_once __DIR__ . "/../../include/site.inc.php";
require_once __DIR__ . "/../../include/cts/gallery.inc.php";

$cts = new contents("Recherche Mat'Matronch");

$site->add_css('css/mmt.css');
$site->start_page('matmatronch','MatMaTronch');

if ($utilisateurs_count == 0) {
  $cts->add_title(2, "Résultat :");
  $cts->add_paragraph('Aucune personne ne correspond aux critères.');
} else if ($utilisateurs_count > 50) {
  $cts->add_title(2, "Résultat :");
  $cts->add_paragraph('Trop de personnes correspondent aux critères, veuillez les affiner.');
} else {
  $cts->add_title(2, "Résultat : $utilisateurs_count personne(s)");

  $user = new utilisateur($site->db);
  $gal = new gallery();

  while ($row = $utilisateurs->fetch()) {
    $user->_load_all($row);
    $same_promo = ($user->promo_utbm == $site->user->promo_utbm);
    $gal->add_item(new userinfov2($user, "small", false, "user.php", $same_promo));
  }

  $cts->add($gal);

  $parameters = http_build_query($request->query->all(), '', '&');

  $tabs = array();
  $i = 0;
  $page = 0;

  while ($i < $utilisateurs_count) {
    $tabs[] = array($page, substr($request->getBaseUrl(), 1) . '?' . $parameters . '&page=' . $page, $page + 1);
    $i    += $utilisateurs_per_page;
    $page += 1;
  }

  $cts->add(new tabshead($tabs, $request->query->getInt('page'), "_bottom"));
}

if (isset($recherche_form) && $recherche_form !== null) {
  $cts->add($recherche_form, true);
}

$site->add_contents($cts);
$site->end_page();