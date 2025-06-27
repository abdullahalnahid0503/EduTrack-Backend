<?php
use Slim\Factory\AppFactory;
use DI\Container;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Set up Container
$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

// Load middleware, dependencies, routes
(require __DIR__ . '/../src/dependencies.php')($app);
(require __DIR__ . '/../src/middleware.php')($app);
(require __DIR__ . '/../src/routes.php')($app);

$app->run();
