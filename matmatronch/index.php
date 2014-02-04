<?php

/* Copyright 2007
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
require_once __DIR__ . "/../include/mysqlae.inc.php";
require_once __DIR__ . "/../include/cts/user.inc.php";
require_once __DIR__ . "/../include/cts/special.inc.php";
require_once __DIR__ . "/../include/genealogie.inc.php";

require_once __DIR__ . "/../include/lib/serviceprovider/PhpRendererServiceProvider.php";

function check_user_is_valid(Request $request) {
  $site = $request->attributes->get('site');

  if (!$site->user->is_valid()) {
    return new RedirectResponse('/connexion.php?redirect_to=' . urlencode($request->getUri()));
  }
}

function check_user_is_ae_or_utbm(Request $request) {
  $site = $request->attributes->get('site');

  if (!$site->user->ae || !$site->user->utbm) {
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

  return new Response(
    $app['renderer']->render(
      __DIR__ . '/views/index.php',
      array(
        'request' => $request,
        'site'    => $site,
        'forms'   => array(
          get_recherche_form($site),
          get_recherchesimple_form($site),
          get_rechercheinversee_form($site),
          get_rechercheparville_form($site))
      )
    ));
})->before('check_user_is_valid')
  ->before('check_user_is_ae_or_utbm');

$app->get('/recherche-{mode}', function(Request $request, $mode) use ($app) {
  if ($request->query->count() === 0) {
    $app->redirect('/matmatronch');
  }

  $site = $request->attributes->get('site');

  $is_admin = $site->user->is_in_group('gestion_ae') || $site->user->is_asso_role (27, 1);

  $sqlquery = $app['db']->createQueryBuilder()
      ->from('utilisateurs', 'u')
      ->leftJoin('u', 'utl_etu', 'ue', 'ue.id_utilisateur = u.id_utilisateur')
      ->leftJoin('u', 'utl_etu_utbm', 'ueu', 'ueu.id_utilisateur = u.id_utilisateur')
      ->orderBy('nom_utl, prenom_utl');

  if (!$is_admin && !$site->user->is_in_group('visu_cotisants') && $site->user->cotisant) {
    $sqlquery->andWhere("u.publique_utl >= '1'");
  } elseif (!$is_admin && !$site->user->is_in_group('visu_cotisants')) {
    $sqlquery->andWhere("u.publique_utl >= '2'");
  }

  switch ($mode) {
    case 'default':
      $form = get_recherche_form($site);

      if ($request->query->get('nom', '') !== '') {
        $sqlquery->andWhere('u.nom_utl LIKE :nom')->setParameter('nom', $request->query->get('nom') . '%');
      }

      if ($request->query->get('prenom', '') !== '') {
        $sqlquery->andWhere('u.prenom_utl LIKE :prenom')->setParameter('prenom', $request->query->get('prenom') . '%');
      }

      if ($request->query->get('surnom', '') !== '') {
        $sqlquery->andWhere('ueu.surnom_utbm LIKE :surnom')->setParameter('surnom', $request->query->get('surnom') . '%')
          ->orderBy('surnom_utbm, nom_utl, prenom_utl');
      }

      if ($request->query->getInt('sexe') > 0) {
        $sqlquery->andWhere('u.sexe_utl = :sexe')->setParameter('sexe', $request->query->getInt('sexe'));
      }

      if ($request->query->get('role', '') !== '') {
        $sqlquery->andWhere('ueu.role_utbm = :role_utbm')->setParameter('role_utbm', $request->query->get('role'));
      }

      if ($request->query->get('departement', '') !== '') {
        $sqlquery->andWhere('ueu.departement_utbm = :departement_utbm')
          ->setParameter('departement_utbm', $request->query->get('departement'));
      }

      if ($request->query->get('semestre', '') !== '' && $request->query->get('role', '') === 'etu') {
        $sqlquery->andWhere('ueu.semestre_utbm = :semestre_utbm')->setParameter('semestre_utbm', $request->query->getInt('semestre'));
      }

      if ($request->query->getInt('promo') > 0) {
        $sqlquery->andWhere('ueu.promo_utbm = :promo_utbm')->setParameter('promo_utbm', $request->query->getInt('promo'));
      }

      if ($request->query->get('magicform[date][date_naissance]', '', true) !== '') {
        $sqlquery->andWhere('u.date_naissance_utl = :date_naissance')
          ->setParameter(
            'date_naissance',
            DateTime::createFromFormat('d/m/Y', $request->query->get('magicform[date][date_naissance]', '', true))->format('Y-m-d'));
      }

      if (!$request->query->get('magicform[date][inclus_ancien]', '', true) === 'true') {
        $sqlquery->andWhere("u.ancien_etudiant_utl = '0'");
      }

      if (!$request->query->get('magicform[date][inclus_nutbm]', '', true) === 'true') {
        $sqlquery->andWhere("u.utbm_utl = '1'");
      }

      break;
    case 'simple':
      $form = get_recherchesimple_form($site);

      if ($request->query->get('pattern', '') !== '') {
        $sqlquery
          ->andWhere(
              $sqlquery->expr()->orX(
                $sqlquery->expr()->like("CONCAT(u.prenom_utl, ' ', u.nom_utl)", ':pattern'),
                $sqlquery->expr()->like("CONCAT(u.nom_utl, ' ', u.prenom_utl)", ':pattern'),
                $sqlquery->expr()->andX(
                  $sqlquery->expr()->neq('ueu.surnom_utbm', "''"),
                  $sqlquery->expr()->like('ueu.surnom_utbm', ':pattern'))
              ))
          ->setParameter('pattern', $request->query->get('pattern') . '%');
      }

      break;
    case 'inversee':
      $form = get_rechercheinversee_form($site);

      if ($request->query->get('numtel', '') !== '') {
        $sqlquery->andWhere('u.tel_maison_utl = :tel OR u.tel_portable_utl = :tel')->setParameter('tel', telephone_userinput($request->query->get('numtel')));
      }

      break;
    case 'par-ville':
      $form = get_rechercheparville_form($site);

      if($request->query->get('id_ville', '') !== '') {
        if ($request->query->get('magicform[boolean][ville_parents]', '', true) !== '') {
          $sqlquery->andWhere('u.id_ville = :id_ville OR ue.id_ville = :id_ville');
        } else {
          $sqlquery->andWhere('u.id_ville = :id_ville');
        }

        $sqlquery->setParameter('id_ville', $request->query->getInt('id_ville'));
      } elseif($request->query->get('id_pays', '') !== '') {
        if ($request->query->get('magicform[boolean][ville_parents]', '', true) !== '') {
          $sqlquery->andWhere('u.id_pays = :id_pays OR ue.id_pays = :id_pays');
        } else {
          $sqlquery->andWhere('u.id_pays = :id_pays');
        }

        $sqlquery->setParameter('id_pays', $request->query->getInt('id_pays'));
      }

      break;
    default:
      return new Response('', 400);
  }

  $utilisateurs_count = $sqlquery
    ->select('COUNT(*)')
    ->execute()
    ->fetchColumn(0);

  $utilisateurs_per_page = 24;

  if ($request->query->has('page')) {
    $offset = $request->query->getInt('page') * $utilisateurs_per_page;
  } else {
    $offset = 0;
  }

  if ($offset > $count) {
    $offset = floor($count / $utilisateurs_per_page) * $utilisateurs_per_page;
  }

  $utilisateurs = $sqlquery
    ->select('u.*, ue.*, ueu.*, u.id_ville AS id_ville, ue.id_ville AS ville_parents, u.id_pays AS id_pays, ue.id_pays AS pays_parents')
    ->setFirstResult($offset)
    ->setMaxResults($utilisateurs_per_page)
    ->execute();

  return new Response(
    $app['renderer']->render(
      __DIR__ . '/views/recherche.php',
      array(
        'request'               => $request,
        'site'                  => $site,
        'recherche_form'        => $form,
        'utilisateurs'          => $utilisateurs,
        'utilisateurs_count'    => $utilisateurs_count,
        'utilisateurs_per_page' => $utilisateurs_per_page,
    ))
  );
})->value('mode', 'default')
  ->before('check_user_is_valid')
  ->before('check_user_is_ae_or_utbm');

$app->get('/famille/{user_id}', function(Request $request, $user_id) use ($app) {
  $site = $request->attributes->get('site');

  if (!$site->user->is_valid()) {
    return $app->redirect('/data/matmatronch/na.gif');
  }

  $genealogie = new genealogie ();

  return new Response(
    $app['renderer']->render(
      __DIR__ . '/views/famille.php',
      array(
        'request'    => $request,
        'site'       => $site,
        'user_id'    => $user_id,
        'genealogie' => $genealogie,
      )
    )
  );
})->convert('user_id', function($user_id) { return intval($user_id); });

$app->get('/image/{user_id}', function(Request $request, $user_id) use ($app) {
  $citation = $app['db']->createQueryBuilder()
    ->select('ue.citation')
    ->from('utl_etu', 'ue')
    ->where('ue.id_utilisateur = :user_id')
    ->setParameter('user_id', $user_id)
    ->execute()
    ->fetchColumn(0);

  return new Response(
    $app['renderer']->render(
      __DIR__ . '/views/image.php',
      array(
        'request'  => $request,
        'site'     => $site,
        'user_id'  => $user_id,
        'citation' => $citation,
      )
    )
  );
})->convert('user_id', function($user_id) { return intval($user_id); });

$app->get('/vcf/{user_id}', function(Request $request, $user_id) use ($app) {
  $site = $request->attributes->get('site');

  $user = new utilisateur($site->db,$site->dbrw);
  $user->load_by_id($user_id);

  if ($user->id < 0) {
    return $site->error_not_found('matmatronch');
  }

  $user->load_all_extra();

  $response = new Response();
  $response->headers->set('Content-Type', 'text/x-vcard');
  $response->headers->set('Content-Disposition', 'attachment; filename="' . addslashes(strtolower(utf8_enleve_accents($user->prenom) . "_" . utf8_enleve_accents($user->nom) . ".vcf")) . '"');

  $response->setContent(
    $app['renderer']->render(
      __DIR__ . '/views/vcf.php',
      array(
        'request' => $request,
        'site'    => $site,
        'user'    => $user,
      )
    )
  );

  return $response;
})->before('check_user_is_valid')
  ->before('check_user_is_ae_or_utbm')
  ->convert('user_id', function($user_id) { return intval($user_id); });

$app->run();
