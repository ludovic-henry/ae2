<?php
/* Copyright 2005,2006
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
/** @file
 *
 * @brief Page d'erreur HTTP 403
 */

require_once __DIR__.'/vendor/autoload.php';

use Silex\Application;
use Silex\Provider;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

$topdir = __DIR__ . '/';

require_once __DIR__ . '/include/site.inc.php';

require_once __DIR__ . "/../include/lib/serviceprovider/PhpRendererServiceProvider.php";

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Provider\PhpRendererServiceProvider());

$app->before(function (Request $request) {
  $request->attributes->set('site', new site());
}, Application::EARLY_EVENT);

$app->get('/', function (Request $request) use ($app) {
  $site = $request->attributes->get('site');

  if (!$site->user->is_valid()) {
    return new RedirectResponse('/connexion.php?' . http_build_query($request->query->all(), '', '&'));
  }

  ob_start();

  $site->start_page('none','Erreur 403');

  /* TODO à traiter les reasons du 403 */
  if ($request->query->get('reason') !== 'reserved' && $request->query->get('reason') != 'reservedutbm') {
    $site->add_contents(new error('Accés refusé (403)', $request->query->get('reason')));
  } else {
    $site->add_contents(new error('Accés refusé (403)', 'Vous n\'avez pas les droits requis pour accéder à cette page.'));
  }

  $site->end_page();

  return new Response(ob_get_clean());
});

$app->run();
