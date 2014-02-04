<?php
/* Copyright 2005
 * - Julien Etelain < julien at pmad dot net >
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

require_once __DIR__ . '/include/serviceprovider/PhpRendererServiceProvider.php';

$topdir = __DIR__ . '/';

require_once __DIR__ . '/include/site.inc.php';
require_once __DIR__ . '/include/entities/page.inc.php';

$app = new Silex\Application();
$app['debug'] = true;

$app->register(new Provider\PhpRendererServiceProvider());

$app->before(function (Request $request) {
  $request->attributes->set('site', new site());
}, Application::EARLY_EVENT);

$app->get('/', function (Request $request) use ($app) {
  $site = $request->attributes->get('site');

  $sqldelete = new delete($site->dbrw, "site_sessions", array("id_session" => $_COOKIE['AE2_SESS_ID']));

  setcookie ("AE2_SESS_ID", "", time() - 3600, "/", "ae.utbm.fr", 0);
  unset($_COOKIE['AE2_SESS_ID']);
  unset($_SESSION['session_redirect']);

  return new RedirectResponse('/');
});

$app->run();
