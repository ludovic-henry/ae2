<?php

namespace Silex\Provider;

require_once __DIR__.'/../../../vendor/autoload.php';

use Silex\Application;
use Silex\ServiceProviderInterface;

class PhpRendererServiceProvider implements ServiceProviderInterface {

    public function register(Application $app) {
      $app['renderer'] = $this;
    }

    public function boot(Application $app) {
    }

    public function render($filename, array $variables) {
      foreach ($variables as $key => $value) {
        $$key = $value;
      }

      ob_start();

      include $filename;

      return ob_get_clean();
    }
}