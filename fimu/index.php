<?php

/* Copyright 2007
 * - Manuel Vonthron < manuel DOT vonthron AT acadis DOT org >
 * - Sarah Amsellem < sarah DOT amsellem AT gmail DOT com >
 * - Ludovic Henry < ludovichenry DOT utbm AT gmail DOT com >
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

require_once __DIR__.'/../vendor/autoload.php';

use Silex\Application;
use Silex\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

require_once __DIR__ . "/forms.php";

$topdir = __DIR__ . "/../";

require_once __DIR__ . "/../include/site.inc.php";
require_once __DIR__ . "/../include/cts/sqltable.inc.php";
require_once __DIR__ . "/../include/cts/user.inc.php";

require_once __DIR__ . "/../include/lib/serviceprovider/PhpRendererServiceProvider.php";

function check_user_is_valid(Request $request) {
  $site = $request->attributes->get('site');

  if (!$site->user->is_valid()) {
    return new RedirectResponse('/connexion.php?redirect_to=' . urlencode($request->getUri()));
  }
}

function check_user_is_gestion_ae_or_gestion_fimu(Request $request) {
  $site = $request->attributes->get('site');

  if (!$site->user->is_in_group("gestion_ae") && !$site->user->is_in_group("gestion_fimu")) {
    return new RedirectResponse('/403.php');
  }
}

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_mysql',
        'dbname'   => mysqlae::$database,
        'host'     => mysqlae::$host,
        'user'     => mysqlae::$login_read_only,
        'password' => mysqlae::$mdp_read_only,
    ),
));

$app->register(new Provider\PhpRendererServiceProvider());

$app->before(function (Request $request) {
  $request->attributes->set('site', new site());
}, Application::EARLY_EVENT);

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    return new Response($code);
});

$app->get('/', function(Request $request) use ($app) {
  $site = $request->attributes->get('site');

  $sqlquery = $app['db']->createQueryBuilder()
      ->select('COUNT(*)')
      ->from('fimu_inscr', 'fi')
      ->where('fi.id_utilisateur = :user_id')
      ->setParameter('user_id', $site->user->id);

  $user_is_inscrit = intval($sqlquery->execute()->fetchColumn(0)) > 0;

  return new Response(
    $app['renderer']->render(
      __DIR__ . '/views/index.php',
      array(
        'request'          => $request,
        'user_is_inscrit'   => $user_is_inscrit,
        'inscription_form' => get_inscription_form($site),
      )
    )
  );
});

$app->post('/inscription', function(Request $request) use ($app) {
  $site = $request->attributes->get('site');

  $sqlquery = $app['db']->createQueryBuilder()
      ->select('COUNT(*)')
      ->from('fimu_inscr', 'fi')
      ->where('fi.id_utilisateur = :user_id')
      ->setParameter('user_id', $site->user->id);

  if (intval($sqlquery->execute()->fetchColumn(0)) > 0) {
    return new RedirectResponse('/fimu/index.php');
  }

  $sqlinsert = new insert(
    $site->dbrw,
    'fimu_inscr',
    array(
      "id_inscr"       => '',
      "id_utilisateur" => $site->user->id,
      "jour1"          => $request->request->get('magicform[boolean][jour1]', '', true),
      "jour2"          => $request->request->get('magicform[boolean][jour2]', '', true),
      "jour3"          => $request->request->get('magicform[boolean][jour3]', '', true),
      "jour4"          => $request->request->get('magicform[boolean][jour4]', '', true),
      "jour5"          => $request->request->get('magicform[boolean][jour5]', '', true),
      "jour6"          => $request->request->get('magicform[boolean][jour6]', '', true),
      "choix1_choix"   => $request->request->get('choix1_choix'),
      "choix1_com"     => $request->request->get('choix1_com'),
      "choix2_choix"   => $request->request->get('choix2_choix'),
      "choix2_com"     => $request->request->get('choix2_com'),
      "lang1_lang"     => $request->request->get('lang1_lang'),
      "lang1_lvl"      => $request->request->get('lang1_lvl'),
      "lang1_com"      => $request->request->get('lang1_com'),
      "lang2_lang"     => $request->request->get('lang2_lang'),
      "lang2_lvl"      => $request->request->get('lang2_lvl'),
      "lang2_com"      => $request->request->get('lang2_com'),
      "lang3_lang"     => $request->request->get('lang3_lang'),
      "lang3_lvl"      => $request->request->get('lang3_lvl'),
      "lang3_com"      => $request->request->get('lang3_com'),
      "permis"         => $request->request->get('permis'),
      "voiture"        => $request->request->get('voiture'),
      "afps"           => $request->request->get('afps'),
      "afps_com"       => $request->request->get('type_afps'),
      "poste_preced"   => $request->request->get('poste_preced'),
      "remarques"      => $request->request->get('remarques'),
    )
  );

  if ($sqlinsert->result) {
    return new RedirectResponse('/fimu/index.php/inscription');
  } else {
    return new Response(
      $app['renderer']->render(
        __DIR__ . '/views/inscription-error.php',
        array(
          'request' => $request,
        )
      )
    );
  }
})->before('check_user_is_valid');

$app->get('/inscription', function(Request $request) use ($app) {
  return new Response(
    $app['renderer']->render(
      __DIR__ . '/views/inscription.php',
      array(
        'request' => $request,
      )
    )
  );
})->before('check_user_is_valid');



$app->get('/liste', function(Request $request) use ($app) {
  $site = $request->attributes->get('site');

  // Have to keep request class (instead of $app['db']), because of sqltable in the view
  $sql = new requete(
    $site->db,
    "SELECT
          fimu_inscr.id_utilisateur
        , utilisateurs.nom_utl
        , utilisateurs.prenom_utl
        , utilisateurs.id_utilisateur
        , utilisateurs.email_utl AS email_utilisateur
        , utilisateurs.tel_portable_utl AS portable_utilisateur
        , fimu_inscr.jour1
        , fimu_inscr.jour2
        , fimu_inscr.jour3
        , fimu_inscr.jour4
        , fimu_inscr.jour5
        , fimu_inscr.jour6
        , fimu_inscr.choix1_choix
        , fimu_inscr.choix1_com
        , fimu_inscr.choix2_choix
        , fimu_inscr.choix2_com
        , fimu_inscr.lang1_lang
        , fimu_inscr.lang2_lang
        , fimu_inscr.lang3_lang
        , fimu_inscr.poste_preced
        , fimu_inscr.remarques
        , CONCAT(utilisateurs.prenom_utl, ' ', utilisateurs.nom_utl) AS `nom_utilisateur`
        , CONCAT(utilisateurs.addresse_utl, ' ', villes.cpostal_ville, ' ', villes.nom_ville) AS adresse_utilisateur
      FROM fimu_inscr LEFT JOIN utilisateurs
                        ON fimu_inscr.id_utilisateur = utilisateurs.id_utilisateur
                      LEFT JOIN villes
                        USING (id_ville)"
  );

  return new Response(
    $app['renderer']->render(
      __DIR__ . '/views/liste.php',
      array(
        'request' => $request,
        'sql'     => $sql,
      )
    )
  );
})->before('check_user_is_valid')
  ->before('check_user_is_gestion_ae_or_gestion_fimu');

$app->run();
