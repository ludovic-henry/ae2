<?php

require_once __DIR__.'/vendor/silex/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();
$app['debug'] = false;

$app->error(function (\Exception $e, $code) use ($app) {
  if ($app['debug']) {
    return;
  }

  return new Response('', $code);
});

$app->post('/', function (Request $request) use ($app) {
  $request->setTrustedProxies(array('192.168.2.217'));

  $remoteip    = ip2long($request->getClientIp());
  $allowednet  = ip2long("192.30.252.0");
  $allowedmask = ip2long("255.255.252.0");

  if ($remoteip === false || ($remoteip & $allowedmask) != $allowednet) {
    return new Response("Vous n'êtes pas les serveurs GitHub", 403);
  }

  $commands = array(
    "git --git-dir=" . __DIR__ . "/.git/ checkout -B test",
    "git --git-dir=" . __DIR__ . "/.git/ pull github test",
  );

  $success = true;
  $buffer = "";

  foreach ($commands as $cmd) {
    exec($cmd, $output, $return_value);

    $buffer .= implode("\n", $output) . "\n";
    $success = $success && ($return_value == 0);

    if (!$success) {
      break;
    }
  }

  return new Response($buffer, $success ? 200 : 500);
});

$app->run();
