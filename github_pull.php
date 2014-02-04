<?php

require_once __DIR__.'/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

$app = new Silex\Application();
$app['debug'] = false;

$app->register(new Provider\ServiceControllerServiceProvider());
$app->register(new Provider\TwigServiceProvider());
$app->register(new Provider\UrlGeneratorServiceProvider());

$app->register(new Provider\WebProfilerServiceProvider(), array(
    'profiler.cache_dir' => '/tmp/profiler',
    'profiler.mount_prefix' => '/_profiler', // this is the default
));

$app->post('/', function (Request $request) use ($app) {
    $remoteip    = ip2long($_SERVER['REMOTE_ADDR']);
    $allowednet  = ip2long("192.30.252.0");
    $allowedmask = ip2long("255.255.252.0");
    
    if ($remoteip === false || ($remoteip & $allowedmask) != $allowednet) {
      $app->abort(403, "Vous n'êtes pas les serveurs GitHub");
    }

    $commands = array(
      "git --git-dir=" . __DIR__ . "/.git/ checkout -B test",
      "git --git-dir=" . __DIR__ . "/.git/ pull github test",
    );

    $success = true;
    $buffer = "";

    foreach ($commands as $cmd) {
      exec($cmd, $output, $return_value);

      $buffer  .= implode($output) . "\n";
      $success &= ($return_value == 0);

      if (!$success) {
        break;
      }
    }

    return new Response($buffer, $success ? 200 : 500);
});

$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    return new Response($code);
});

$app->run();