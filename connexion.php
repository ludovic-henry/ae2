<?php

/* Copyright 2005
 * - Julien Etelain < julien at pmad dot net >
 * - Ludovic Henry < ludovichenry DOT utbm AT gmail DOT com >
 *
 * Ce fichier fait partie du site de l'Association des Étudiants de
 * l'UTBM, http://ae.utbm.fr.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License a
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

require_once __DIR__.'/vendor/autoload.php';

use Silex\Application;
use Silex\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

$topdir = __DIR__ . '/';

require_once __DIR__ . '/include/site.inc.php';
require_once __DIR__ . '/include/entities/page.inc.php';

require_once __DIR__ . "/../include/lib/serviceprovider/PhpRendererServiceProvider.php";

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Provider\ServiceControllerServiceProvider());
$app->register(new Provider\TwigServiceProvider());
$app->register(new Provider\UrlGeneratorServiceProvider());

$app->register(new Provider\WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => '/tmp/profiler',
    'profiler.mount_prefix' => '/_profiler', // this is the default
));

$app->register(new Provider\PhpRendererServiceProvider());

$app->before(function (Request $request) {
  $request->attributes->set('site', new site());
}, Application::EARLY_EVENT);

$app->get('/', function (Request $request) use ($app) {
  $site = $request->attributes->get('site');

  if ($site->user->is_valid()) {
    return new RedirectResponse($request->query->get('redirect_to', '/'));
  }

  // TODO : find a way to have a views/ folder instead of having the template here
  ob_start();

  $site->start_page('none', 'Connexion');

  $cts = new contents('Veuillez vous connecter pour accéder à  la page demandée');

  if ($request->query->get('redirect_to', '') !== '') {
    $cts->add_paragraph('Vous serez automatiquement redirigé vers la page que vous avez demandé.');
  }

  $frm = new form('connect2', 'connexion.php', true, 'POST', 'Connexion');
  $frm->add_select_field('domain', 'Connexion', array('utbm' => 'UTBM', 'assidu' => 'Assidu', 'id' => 'ID', 'autre' => 'Autre', 'alias' => 'Alias'));
  $frm->add_text_field('username', 'Utilisateur', 'prenom.nom', '', 27);
  $frm->add_password_field('password', 'Mot de passe', '', '', 27);
  $frm->add_hidden('redirect_to', $request->query->get('redirect_to', ''));
  $frm->add_submit('connectbtn2', 'Se connecter');

  $cts->add($frm, true);

  $site->add_contents($cts);

  $site->end_page();

  return new Response(ob_get_clean());
});

$app->post('/', function (Request $request) use ($app) {
  $site = $request->attributes->get('site');

  switch ($request->request->get('domain')) {
    case 'utbm':
      $site->user->load_by_email($request->request->get('username') . '@utbm.fr');
      break;
    case 'assidu':
      $site->user->load_by_email($request->request->get('username') . '@assidu-utbm.fr');
      break;
    case 'id':
      $site->user->load_by_id($request->request->get('username'));
      break;
    case 'autre':
      $site->user->load_by_email($request->request->get('username'));
      break;
    case 'alias':
      $site->user->load_by_alias($request->request->get('username'));
      break;
    case 'carteae':
      $site->user->load_by_carteae($request->request->get('username'), true, false);
      break;
    default:
      return new RedirectResponse('/connexion.php');
  }

  if (!$site->user->is_valid()) {
    return new RedirectResponse('/article.php?name=site:wrongpassworduser');
  }

  if ($site->user->hash !== 'valid') {
    return new RedirectResponse('/article.php?name=site:activate');
  }

  if (!$site->user->is_password($request->request->get('password', ''))) {
    return new RedirectResponse('/article.php?name=site:wrongpassworduser');
  }

  $site->connect_user($request->request->has('personnal_computer'));

  $redirect_to = $request->request->get('redirect_to', '');

  if ($redirect_to !== ''
        && !strpos($redirect_to, 'connexion.php')
        && !strpos($redirect_to, 'site:wrongpassworduser')
        && !strpos($redirect_to, 'site:activate')) {
    return new RedirectResponse($redirect_to);
  } else {
    return new RedirectResponse('/');
  }
});

$app->run();
